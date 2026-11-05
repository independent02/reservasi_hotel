<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class HotelReviewController
{
    public function show(Request $request, Hotel $hotel): View
    {
        abort_unless($hotel->is_active, 404);

        $filters = $request->validate([
            'rating' => ['sometimes', 'integer', 'between:1,5'],
            'media' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'in:recent,oldest,highest,lowest'],
        ]);
        $sort = $filters['sort'] ?? 'recent';

        $reviewsQuery = $hotel->reviews()->with(['user', 'reservation.room']);
        $reviewsQuery->when(isset($filters['rating']), fn (Builder $query) => $query
            ->where('rating', $filters['rating']));
        $reviewsQuery->when(($filters['media'] ?? false), fn (Builder $query) => $query
            ->whereNotNull('media'));
        $reviewsQuery->when($sort === 'oldest', fn (Builder $query) => $query
            ->orderBy('created_at'));
        $reviewsQuery->when($sort === 'highest', fn (Builder $query) => $query
            ->orderByDesc('rating')->orderByDesc('created_at'));
        $reviewsQuery->when($sort === 'lowest', fn (Builder $query) => $query
            ->orderBy('rating')->orderByDesc('created_at'));
        if ($sort === 'recent') {
            $reviewsQuery->latest();
        }

        $ratingCounts = $hotel->reviews()
            ->select('rating', DB::raw('count(*) as total'))
            ->groupBy('rating')
            ->pluck('total', 'rating');
        $reviewsCount = (int) $ratingCounts->sum();
        $mediaReviewsCount = $hotel->reviews()->whereNotNull('media')->count();
        $summary = $hotel->reviews()
            ->selectRaw('AVG(rating) as rating, AVG(cleanliness) as cleanliness, AVG(comfort) as comfort, AVG(location) as location, AVG(service) as service')
            ->first();
        $gallery = $hotel->reviews()
            ->whereNotNull('media')
            ->latest()
            ->get(['id', 'media'])
            ->flatMap(fn (Review $review) => collect($review->media ?? [])
                ->values()
                ->map(fn (array $item, int $index): array => [
                    'url' => route('reviews.media', ['review' => $review, 'index' => $index]),
                    'type' => $item['type'],
                ]))
            ->values();
        $reviewableReservation = Auth::user()?->reservations()
            ->where('hotel_id', $hotel->id)
            ->where('status', 'confirmed')
            ->where(function (Builder $checkout): void {
                $checkout->whereDate('check_out', '<', today())
                    ->orWhere(function (Builder $sameDay): void {
                        $sameDay->whereDate('check_out', today())
                            ->whereTime('check_out_time', '<=', now()->format('H:i:s'));
                    });
            })
            ->whereDoesntHave('review')
            ->with(['hotel', 'room'])
            ->latest('check_out')
            ->first();
        $fallbackBackUrl = route('hotels.search', [
            'destination' => $hotel->city,
            'checkin' => today()->toDateString(),
            'checkout' => today()->addDay()->toDateString(),
            'guests' => 1,
            'rooms' => 1,
        ]);
        $previousUrl = $request->headers->get('referer');
        $previousUrlParts = $previousUrl ? parse_url($previousUrl) : false;
        $previousPath = is_array($previousUrlParts) ? ($previousUrlParts['path'] ?? '/') : null;
        $currentPath = '/'.ltrim($request->path(), '/');
        $isSameHost = is_array($previousUrlParts)
            && isset($previousUrlParts['host'])
            && strcasecmp($previousUrlParts['host'], $request->getHost()) === 0;
        $hasSafePreviousPage = is_array($previousUrlParts)
            && in_array($previousUrlParts['scheme'] ?? null, ['http', 'https'], true)
            && $isSameHost
            && $previousPath !== $currentPath;
        $reviewBackUrl = $hasSafePreviousPage ? $previousUrl : $fallbackBackUrl;

        return view('hotels.reviews', [
            'hotel' => $hotel,
            'reviewBackUrl' => $reviewBackUrl,
            'reviews' => $reviewsQuery->paginate(10)->withQueryString(),
            'filters' => [
                'rating' => $filters['rating'] ?? null,
                'media' => (bool) ($filters['media'] ?? false),
                'sort' => $sort,
            ],
            'ratingCounts' => collect(range(1, 5))
                ->mapWithKeys(fn (int $rating): array => [$rating => (int) $ratingCounts->get($rating, 0)]),
            'reviewsCount' => $reviewsCount,
            'mediaReviewsCount' => $mediaReviewsCount,
            'summary' => $summary,
            'gallery' => $gallery,
            'reviewableReservation' => $reviewableReservation,
        ]);
    }

    public function media(Review $review, int $index): StreamedResponse
    {
        $item = $review->media[$index] ?? null;
        abort_unless(
            is_array($item)
                && isset($item['path'], $item['type'])
                && is_string($item['path'])
                && $item['path'] !== ''
                && in_array($item['type'], ['image', 'video'], true),
            404,
        );

        $disk = Storage::disk('public');
        abort_unless($disk->exists($item['path']), 404);

        return $disk->response($item['path'], basename($item['path']), [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function store(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless($reservation->user_id === $request->user()->id, 403);
        abort_unless(
            $reservation->status === 'confirmed' && $reservation->hasCheckedOut(),
            403,
            'Ulasan baru bisa dikirim setelah masa menginap selesai.',
        );

        if ($reservation->review()->exists()) {
            return redirect()->route('hotels.reviews.show', $reservation->hotel->slug)
                ->with('warning', 'Ulasan untuk pesanan ini sudah pernah dikirim.');
        }

        $validated = $request->validate([
            'reservation_id' => ['required', 'integer', 'in:'.$reservation->id],
            'rating' => ['required', 'integer', 'between:1,5'],
            'cleanliness' => ['nullable', 'integer', 'between:1,5'],
            'comfort' => ['nullable', 'integer', 'between:1,5'],
            'location' => ['nullable', 'integer', 'between:1,5'],
            'service' => ['nullable', 'integer', 'between:1,5'],
            'tags' => ['nullable', 'array', 'max:6'],
            'tags.*' => ['required', 'string', 'in:Bersih,Nyaman,Staf ramah,Lokasi strategis,Sarapan enak,Sebanding harga'],
            'comment' => ['nullable', 'string', 'max:500'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'videos' => ['nullable', 'array', 'max:1'],
            'videos.*' => ['required', 'file', 'mimetypes:video/mp4,video/webm', 'max:51200'],
        ]);

        if (count($validated['photos'] ?? []) + count($validated['videos'] ?? []) > 6) {
            throw ValidationException::withMessages([
                'photos' => 'Unggah maksimal 6 file: 5 foto dan 1 video.',
            ]);
        }

        $storedPaths = [];

        try {
            $media = [];
            foreach ($validated['photos'] ?? [] as $photo) {
                $path = $photo->store('reviews/'.$reservation->hotel_id, 'public');
                $storedPaths[] = $path;
                $media[] = ['path' => $path, 'type' => 'image'];
            }
            foreach ($validated['videos'] ?? [] as $video) {
                $path = $video->store('reviews/'.$reservation->hotel_id, 'public');
                $storedPaths[] = $path;
                $media[] = ['path' => $path, 'type' => 'video'];
            }

            DB::transaction(function () use ($request, $reservation, $validated, $media): void {
                $lockedReservation = Reservation::query()
                    ->lockForUpdate()
                    ->findOrFail($reservation->id);

                abort_unless(
                    $lockedReservation->user_id === $request->user()->id
                        && $lockedReservation->status === 'confirmed'
                        && $lockedReservation->hasCheckedOut(),
                    403,
                );
                if ($lockedReservation->review()->exists()) {
                    throw ValidationException::withMessages([
                        'rating' => 'Ulasan untuk pesanan ini sudah pernah dikirim.',
                    ]);
                }

                $lockedReservation->review()->create([
                    'user_id' => $request->user()->id,
                    'hotel_id' => $lockedReservation->hotel_id,
                    'rating' => $validated['rating'],
                    'cleanliness' => $validated['cleanliness'] ?? null,
                    'comfort' => $validated['comfort'] ?? null,
                    'location' => $validated['location'] ?? null,
                    'service' => $validated['service'] ?? null,
                    'tags' => $validated['tags'] ?? [],
                    'comment' => filled($validated['comment'] ?? null) ? $validated['comment'] : null,
                    'media' => $media ?: null,
                ]);

                $hotel = Hotel::query()->findOrFail($lockedReservation->hotel_id);
                $hotel->update(['rating' => round((float) $hotel->reviews()->avg('rating'), 1)]);
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }

            throw $exception;
        }

        return redirect()->route('hotels.reviews.show', $reservation->hotel->slug)
            ->with('status', 'Terima kasih. Ulasan Anda berhasil dipublikasikan.');
    }
}

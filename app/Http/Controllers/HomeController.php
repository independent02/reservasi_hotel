<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HomeController
{
    public function index(Request $request): View
    {
        $filters = array_replace([
            'destination' => 'Halmahera',
            'checkin' => now()->toDateString(),
            'checkin_time' => '14:00',
            'checkout' => now()->addDays(3)->toDateString(),
            'checkout_time' => '12:00',
            'guests' => 2,
            'rooms' => 1,
        ], $request->validate([
            'destination' => ['sometimes', 'nullable', 'string', 'max:100'],
            'checkin' => ['sometimes', 'date', 'after_or_equal:today'],
            'checkin_time' => ['sometimes', 'date_format:H:i'],
            'checkout' => ['sometimes', 'date', 'after:checkin'],
            'checkout_time' => ['sometimes', 'date_format:H:i'],
            'guests' => ['sometimes', 'integer', 'min:1', 'max:8'],
            'rooms' => ['sometimes', 'integer', 'min:1', 'max:5'],
        ]));

        return $this->page($filters, Hotel::query()
            ->where('is_active', true)
            ->with(['rooms' => fn ($query) => $query
                ->where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('price_per_night')
                ->limit(1)])
            ->whereHas('rooms', fn (Builder $query) => $query
                ->where('is_active', true)
                ->where('is_featured', true))
            ->orderByDesc('is_featured')
            ->limit(3)
            ->get()
            ->flatMap(fn (Hotel $hotel) => $hotel->rooms));
    }

    public function search(Request $request): View
    {
        $checkin = $request->query('checkin');
        if (is_string($checkin)
            && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $checkin, $dateParts)
            && checkdate((int) $dateParts[2], (int) $dateParts[3], (int) $dateParts[1])
            && $checkin < today()->toDateString()) {
            $checkin = today()->toDateString();
            $request->query->set('checkin', $checkin);
        }

        $checkout = $request->query('checkout');
        if (is_string($checkin)
            && is_string($checkout)
            && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $checkout, $checkoutParts)
            && checkdate((int) $checkoutParts[2], (int) $checkoutParts[3], (int) $checkoutParts[1])
            && $checkout <= $checkin) {
            $request->query->set(
                'checkout',
                \Illuminate\Support\Carbon::parse($checkin)->addDay()->toDateString(),
            );
        }

        $filters = $request->validate([
            'destination' => ['nullable', 'string', 'max:100'],
            'checkin' => ['required', 'date', 'after_or_equal:today'],
            'checkin_time' => ['sometimes', 'date_format:H:i'],
            'checkout' => ['required', 'date', 'after:checkin'],
            'checkout_time' => ['sometimes', 'date_format:H:i'],
            'guests' => ['required', 'integer', 'min:1', 'max:12'],
            'rooms' => ['required', 'integer', 'min:1', 'max:5'],
            'all_hotels' => ['sometimes', 'boolean'],
        ]);
        $filters = array_replace([
            'destination' => '',
            'checkin_time' => '14:00',
            'checkout_time' => '12:00',
        ], $filters);

        $guestCount = (int) $filters['guests'];
        $roomCount = (int) $filters['rooms'];
        if ($guestCount > 8 && $roomCount > 1) {
            throw ValidationException::withMessages([
                'guests' => __('The guests field must not be greater than 8.'),
            ]);
        }
        $showAllHotels = (bool) ($filters['all_hotels'] ?? false);
        $mappedHotelSlug = match (true) {
            $guestCount <= 2 => 'taman-sari-garden-hotel',
            $guestCount <= 4 => 'svara-signature-retreat',
            $guestCount <= 8 => 'aksara-ubud-resort-spa',
            default => null,
        };

        $availableRooms = function ($query) use ($filters, $guestCount, $roomCount, $showAllHotels): void {
            $query
                ->where('is_active', true)
                ->when(
                    !$showAllHotels,
                    fn (Builder $roomQuery) => $roomQuery
                        ->whereRaw('max_guests * ? >= ?', [$roomCount, $guestCount]),
                )
                ->whereRaw(
                    '(quantity - COALESCE((SELECT SUM(CASE
                        WHEN reservations.room_numbers IS NULL THEN rooms.quantity
                        ELSE reservations.room_count
                    END)
                        FROM reservations
                        WHERE reservations.room_id = rooms.id
                            AND reservations.status IN (?, ?, ?)
                            AND reservations.check_in < ?
                            AND reservations.check_out > ?
                            AND (reservations.status != ?
                                OR reservations.expires_at IS NULL
                                OR reservations.expires_at > ?)), 0)) >= ?',
                    [
                        'pending',
                        'confirmed',
                        'checked_in',
                        $filters['checkout'],
                        $filters['checkin'],
                        'pending',
                        now(),
                        $roomCount,
                    ],
                );
        };

        $hotels = Hotel::query()
            ->where('is_active', true)
            ->when(!$showAllHotels, function (Builder $query) use ($mappedHotelSlug): void {
                if ($mappedHotelSlug === null) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where('slug', $mappedHotelSlug);
            })
            ->withReviewSummary()
            ->whereHas('rooms', $availableRooms)
            ->with(['rooms' => $availableRooms])
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return $this->page($filters, collect(), $hotels);
    }

    private function page(
        array $filters,
        Collection $featuredRooms,
        ?LengthAwarePaginator $hotels = null,
    ): View
    {
        return view('welcome', [
            'filters' => $filters,
            'maxGuestsPerRoom' => min(8, max(1, (int) Room::query()
                ->where('is_active', true)
                ->max('max_guests'))),
            'hotels' => $hotels,
            'rooms' => $featuredRooms,
            'destinations' => Hotel::query()
                ->where('is_active', true)
                ->pluck('city')
                ->unique()
                ->sort()
                ->values(),
            'steps' => [
                [
                    'number' => '01',
                    'title' => __('Masuk atau daftar'),
                    'copy' => __('Buat akun atau masuk sebelum menyimpan reservasi.'),
                ],
                [
                    'number' => '02',
                    'title' => __('Cari hotel'),
                    'copy' => __('Tentukan destinasi, tanggal menginap, dan jumlah tamu.'),
                ],
                [
                    'number' => '03',
                    'title' => __('Pilih kamar'),
                    'copy' => __('Pilih nomor kamar yang tersedia dan isi data tamu.'),
                ],
                [
                    'number' => '04',
                    'title' => __('Transfer bank manual'),
                    'copy' => __('Transfer ke rekening hotel, lalu unggah bukti untuk konfirmasi.'),
                ],
                [
                    'number' => '05',
                    'title' => __('Terima e-voucher'),
                    'copy' => __('Setelah pembayaran terkonfirmasi, voucher bisa dicetak atau disimpan sebagai PDF.'),
                ],
                [
                    'number' => '06',
                    'title' => __('Kelola pesanan'),
                    'copy' => __('Buka halaman Pesanan saya untuk melihat status dan detail reservasi.'),
                ],
            ],
            'facilities' => [
                ['number' => '01', 'icon' => 'wifi', 'title' => 'Wi-Fi Cepat', 'copy' => 'Internet stabil di seluruh kamar dan area umum.'],
                ['number' => '02', 'icon' => 'utensils', 'title' => 'Sarapan Pagi', 'copy' => 'Menu hangat untuk memulai hari, tersedia di hotel pilihan.'],
                ['number' => '03', 'icon' => 'parking', 'title' => 'Area Parkir', 'copy' => 'Parkir aman dan mudah dijangkau untuk tamu.'],
                ['number' => '04', 'icon' => 'snowflake', 'title' => 'AC dan Air Panas', 'copy' => 'Suhu kamar sejuk dan kamar mandi yang selalu nyaman.'],
                ['number' => '05', 'icon' => 'clock', 'title' => 'Resepsionis 24 Jam', 'copy' => 'Tim kami siap membantu kapan pun kamu tiba.'],
                ['number' => '06', 'icon' => 'shield', 'title' => 'Keamanan dan CCTV', 'copy' => 'Pengawasan di area umum untuk ketenangan kamu.'],
                ['number' => '07', 'icon' => 'bed', 'title' => 'Linen Premium', 'copy' => 'Tempat tidur bersih dan empuk untuk tidur berkualitas.'],
                ['number' => '08', 'icon' => 'key', 'title' => 'Check-In Mudah', 'copy' => 'Proses cepat dengan konfirmasi pemesanan yang jelas.'],
            ],
        ]);
    }
}

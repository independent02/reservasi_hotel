@extends('layouts.auth')

@section('title', __('Ulasan :hotel', ['hotel' => $hotel->name]))
@section('heading', __('Ulasan :hotel', ['hotel' => $hotel->name]))
@section('intro', $hotel->city.', '.$hotel->province)
@section('mainClass', 'hotel-reviews-main')
@section('cardClass', 'hotel-reviews-card')
@section('bodyClass', 'hotel-reviews-page')

@section('form')
    @php
        $averageRating = (float) ($summary->rating ?? 0);
        $aspects = [
            'cleanliness' => __('Kebersihan'),
            'comfort' => __('Kenyamanan'),
            'location' => __('Lokasi'),
            'service' => __('Pelayanan'),
        ];
        $galleryItems = $gallery->map(fn ($item): array => [
            'url' => $item['url'],
            'type' => $item['type'],
        ])->values();
    @endphp

    <div class="hotel-reviews" x-data="{ galleryOpen: false, activeMedia: 0, media: @js($galleryItems) }">
        <header class="hotel-reviews-toolbar">
            @if ($reviewableReservation)
                <button
                    class="button primary review-write-button"
                    type="button"
                    @click="$dispatch('open-review', {{ $reviewableReservation->id }})"
                >✎ <span>{{ __('Tulis ulasan') }}</span></button>
            @elseif (! auth()->check())
                <a class="button primary review-write-button" href="{{ route('login') }}">{{ __('Masuk untuk menulis ulasan') }}</a>
            @endif
        </header>

        @if (session('status'))
            <div class="auth-success review-flash" role="status">{{ session('status') }}</div>
        @endif
        @if (session('warning'))
            <div class="auth-error review-flash" role="alert">{{ session('warning') }}</div>
        @endif

        <section class="hotel-review-summary" aria-label="{{ __('Ringkasan penilaian') }}">
            <div class="hotel-review-score-card">
                <strong>{{ $reviewsCount ? number_format($averageRating, 1) : '—' }}</strong>
                <span class="review-stars" aria-label="Rata-rata rating {{ number_format($averageRating, 1) }} dari 5">
                    @for ($star = 1; $star <= 5; $star++)
                        <span aria-hidden="true">{{ $averageRating >= $star ? '★' : '☆' }}</span>
                    @endfor
                </span>
                <small>{{ __(':count verified guest reviews', ['count' => number_format($reviewsCount)]) }}</small>
            </div>

            <div class="hotel-review-aspects">
                @foreach ($aspects as $field => $label)
                    @php
                        $score = $summary->{$field} !== null ? (float) $summary->{$field} : 0;
                    @endphp
                    <div class="review-aspect-row">
                        <span>{{ $label }}</span>
                        <span class="review-aspect-track" role="img" aria-label="{{ $score ? __(':score out of 5', ['score' => number_format($score, 1)]) : __('Belum ada penilaian') }}">
                            <span style="width: {{ $score * 20 }}%"></span>
                        </span>
                        <strong>{{ $score ? number_format($score, 1) : '—' }}</strong>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="hotel-review-gallery" aria-labelledby="review-gallery-title">
            <div class="review-section-heading">
                <h2 id="review-gallery-title">{{ __('Foto & video dari tamu') }}</h2>
                @if ($gallery->isNotEmpty())
                    <button type="button" class="review-gallery-all" @click="galleryOpen = true">{{ __('Lihat semua (:count)', ['count' => $gallery->count()]) }}</button>
                @endif
            </div>
            @if ($gallery->isNotEmpty())
                <div class="review-gallery-grid">
                    @foreach ($gallery->take(5) as $index => $item)
                        <button
                            class="review-gallery-item"
                            type="button"
                            aria-label="{{ __('Buka lampiran :number', ['number' => $index + 1]) }}"
                            @click="activeMedia = {{ $index }}; galleryOpen = true"
                        >
                            @if ($item['type'] === 'video')
                                <span class="review-video-tile">▶<small>VIDEO</small></span>
                            @else
                                <img src="{{ $item['url'] }}" alt="Foto ulasan tamu" loading="lazy">
                            @endif
                        </button>
                    @endforeach
                    @if ($gallery->count() > 5)
                        <button class="review-gallery-more" type="button" @click="activeMedia = 5; galleryOpen = true">+{{ $gallery->count() - 5 }}</button>
                    @endif
                </div>
            @else
                <p class="review-gallery-empty">{{ __('Belum ada foto atau video dari tamu.') }}</p>
            @endif
        </section>

        <section class="hotel-review-list" aria-labelledby="review-list-title">
            <div class="review-list-heading">
                <h2 id="review-list-title">{{ __('Ulasan tamu') }}</h2>
                <form class="review-sort-form" method="get" action="{{ route('hotels.reviews.show', $hotel->slug) }}">
                    @if ($filters['rating'])
                        <input type="hidden" name="rating" value="{{ $filters['rating'] }}">
                    @endif
                    @if ($filters['media'])
                        <input type="hidden" name="media" value="1">
                    @endif
                    <label for="review-sort">{{ __('Urutkan') }}</label>
                    <select id="review-sort" name="sort" onchange="this.form.submit()">
                        <option value="recent" @selected($filters['sort'] === 'recent')>{{ __('Terbaru') }}</option>
                        <option value="oldest" @selected($filters['sort'] === 'oldest')>{{ __('Terlama') }}</option>
                        <option value="highest" @selected($filters['sort'] === 'highest')>{{ __('Rating tertinggi') }}</option>
                        <option value="lowest" @selected($filters['sort'] === 'lowest')>{{ __('Rating terendah') }}</option>
                    </select>
                </form>
            </div>
            <nav class="review-filter-tabs" aria-label="{{ __('Filter ulasan') }}">
                <a class="{{ ! $filters['rating'] && ! $filters['media'] ? 'is-active' : '' }}" href="{{ route('hotels.reviews.show', $hotel->slug) }}">{{ __('Semua (:count)', ['count' => $reviewsCount]) }}</a>
                @foreach ([5, 4, 3, 2, 1] as $rating)
                    <a
                        class="{{ $filters['rating'] === $rating && ! $filters['media'] ? 'is-active' : '' }}"
                        href="{{ route('hotels.reviews.show', array_merge(['hotel' => $hotel->slug], ['rating' => $rating, 'sort' => $filters['sort']])) }}"
                    >{{ $rating }} ★ ({{ $ratingCounts[$rating] }})</a>
                @endforeach
                <a
                    class="{{ $filters['media'] ? 'is-active' : '' }}"
                    href="{{ route('hotels.reviews.show', array_merge(['hotel' => $hotel->slug], ['media' => 1, 'sort' => $filters['sort']])) }}"
                >{{ __('Dengan foto & video (:count)', ['count' => $mediaReviewsCount]) }}</a>
            </nav>

            <div class="review-card-list">
                @forelse ($reviews as $review)
                    @php
                        $reviewerParts = preg_split('/\s+/', trim($review->user->name));
                        $reviewerDisplay = count($reviewerParts) > 1
                            ? $reviewerParts[0].' '.mb_substr($reviewerParts[1], 0, 1).'.'
                            : $reviewerParts[0];
                    @endphp
                    <article class="guest-review-card">
                        <header class="guest-review-header">
                            <span class="guest-review-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($reviewerDisplay, 0, 1)) }}</span>
                            <div class="guest-review-author">
                                <strong>{{ $reviewerDisplay }}</strong>
                                <span class="verified-review-badge">✓ {{ __('Tamu terverifikasi') }}</span>
                                <span class="guest-review-stay">{{ $review->reservation->room->name }} · {{ __('Pesanan #:id', ['id' => $review->reservation_id]) }}</span>
                            </div>
                            <time datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->translatedFormat('d M Y') }}</time>
                        </header>
                        <div class="guest-review-rating">
                            <span class="review-stars" aria-label="{{ $review->rating }} dari 5 bintang">
                                @for ($star = 1; $star <= 5; $star++)
                                    <span aria-hidden="true">{{ $star <= $review->rating ? '★' : '☆' }}</span>
                                @endfor
                            </span>
                            @if ($review->tags)
                                <span class="guest-review-tags">{{ implode(' · ', $review->tags) }}</span>
                            @endif
                        </div>
                        @if ($review->comment)
                            <p class="guest-review-comment">{{ $review->comment }}</p>
                        @endif
                        @if ($review->media)
                            <div class="guest-review-media">
                                @foreach ($review->media as $index => $item)
                                    @php
                                        $mediaUrl = route('reviews.media', ['review' => $review, 'index' => $index]);
                                    @endphp
                                    @if ($item['type'] === 'video')
                                        <video controls preload="metadata" aria-label="Video dari ulasan tamu"><source src="{{ $mediaUrl }}" type="video/{{ pathinfo($item['path'], PATHINFO_EXTENSION) }}"></video>
                                    @else
                                        <a href="{{ $mediaUrl }}" target="_blank" rel="noopener">
                                            <img src="{{ $mediaUrl }}" alt="Foto dari ulasan tamu {{ $index + 1 }}" loading="lazy">
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        @if ($review->hotel_response)
                            <aside class="hotel-review-response">
                                <strong>{{ __('Balasan dari :hotel', ['hotel' => $hotel->name]) }}</strong>
                                <p>{{ $review->hotel_response }}</p>
                            </aside>
                        @endif
                    </article>
                @empty
                    <div class="review-list-empty">
                        <strong>{{ __('Belum ada ulasan untuk filter ini') }}</strong>
                        <p>{{ __('Ulasan tamu akan muncul di sini setelah masa menginap selesai.') }}</p>
                    </div>
                @endforelse
            </div>

            @if ($reviews->hasPages())
                <div class="review-pagination">{{ $reviews->links() }}</div>
            @endif
        </section>

        <div class="review-gallery-lightbox" x-cloak x-show="galleryOpen" x-transition @keydown.escape.window="galleryOpen = false" @click.self="galleryOpen = false">
            <button class="review-lightbox-close" type="button" aria-label="Tutup galeri" @click="galleryOpen = false">×</button>
            <button class="review-lightbox-nav review-lightbox-previous" type="button" aria-label="Lampiran sebelumnya" @click="activeMedia = (activeMedia + media.length - 1) % media.length">‹</button>
            <template x-if="media[activeMedia]?.type === 'video'">
                <video controls autoplay :src="media[activeMedia]?.url"></video>
            </template>
            <template x-if="media[activeMedia]?.type === 'image'">
                <img :src="media[activeMedia]?.url" alt="Lampiran ulasan tamu">
            </template>
            <button class="review-lightbox-nav review-lightbox-next" type="button" aria-label="Lampiran berikutnya" @click="activeMedia = (activeMedia + 1) % media.length">›</button>
        </div>
    </div>
    @if ($reviewableReservation)
        @include('hotels.partials.review-modal', ['reservation' => $reviewableReservation])
    @endif
@endsection

@section('switch')
    <a href="{{ route('home') }}">Kembali ke beranda</a>
@endsection

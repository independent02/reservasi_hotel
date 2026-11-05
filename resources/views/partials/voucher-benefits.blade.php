@if ($voucherBenefits)
    <section class="voucher-benefits {{ $voucherBenefitsClass ?? '' }}" aria-label="{{ __('Benefit voucher kamar') }}">
        <div class="voucher-benefits-heading">
            <span>{{ __('VOUCHER KHUSUS KAMAR') }}</span>
            <h2>{{ __('Benefit :room', ['room' => $room->name]) }}</h2>
            <p>{{ __('Benefit :hotel untuk tipe kamar :room.', ['hotel' => $room->hotel->name, 'room' => $room->name]) }}</p>
        </div>
        <div class="voucher-benefits-grid">
            @foreach ($voucherBenefits as $benefit)
                <article class="voucher-benefit">
                    <span class="voucher-benefit-mark" aria-hidden="true">✓</span>
                    <div>
                        <h3>{{ __($benefit['title']) }}</h3>
                        <p>{{ __($benefit['description']) }}</p>
                        @if (filled($benefit['badge'] ?? null))
                            <span class="voucher-benefit-badge">{{ __($benefit['badge']) }}</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif

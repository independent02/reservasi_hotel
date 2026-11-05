@extends('layouts.auth')

@section('title', 'Verifikasi Pembayaran')
@section('eyebrow', 'Portal pemilik hotel')
@section('heading', 'Verifikasi Pembayaran')
@section('intro', 'Periksa bukti transfer sebelum mengonfirmasi reservasi.')
@section('mainClass', 'admin-payment-main')
@section('cardClass', 'admin-payment-card')
@section('bodyClass', 'auth-glass-page admin-payment-reviews-page')

@section('form')
    <a class="admin-payment-back" href="{{ route('admin.dashboard') }}">
        <svg aria-hidden="true" viewBox="0 0 20 20" fill="none">
            <path d="M15.8 10H4.2m0 0 4.5-4.5M4.2 10l4.5 4.5" />
        </svg>
        <span>Kembali ke Dashboard Admin</span>
    </a>

    <section class="admin-payment-summary" aria-label="Ringkasan antrean verifikasi">
        <div class="admin-payment-summary-count">
            <span>Menunggu verifikasi</span>
            <strong>{{ $reservations->total() }}</strong>
        </div>
        <p>Buka bukti transfer dan cocokkan nominal serta rekening tujuan sebelum menyetujui pembayaran.</p>
    </section>

    <section class="admin-payment-list" aria-label="Pembayaran menunggu verifikasi">
        @forelse ($reservations as $reservation)
            <article class="admin-payment-item">
                <header class="admin-payment-item-header">
                    <div class="admin-payment-item-title">
                        <span class="admin-payment-reference">Pesanan #{{ $reservation->id }}</span>
                        <h2>
                            {{ $reservation->room->name }}
                            @if ($reservation->room_numbers)
                                · {{ implode(', ', $reservation->room_numbers) }}
                            @endif
                        </h2>
                    </div>
                    <span class="admin-payment-status">
                        <span aria-hidden="true"></span>
                        Menunggu Verifikasi
                    </span>
                </header>

                <dl class="admin-payment-details">
                    <div>
                        <dt>Nama tamu</dt>
                        <dd>{{ $reservation->guest_name }}</dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd>{{ $reservation->guest_email }}</dd>
                    </div>
                    <div>
                        <dt>Hotel</dt>
                        <dd>{{ $reservation->hotel->name }}</dd>
                    </div>
                    <div>
                        <dt>Metode pembayaran</dt>
                        <dd>{{ config('payments.methods.'.$reservation->payment_method.'.label', $reservation->destination_bank) }}</dd>
                    </div>
                    <div>
                        <dt>Check-in — Check-out</dt>
                        <dd>
                            {{ $reservation->check_in->format('d M Y') }}
                            {{ substr($reservation->check_in_time ?? '14:00', 0, 5) }}
                            — {{ $reservation->check_out->format('d M Y') }}
                            {{ substr($reservation->check_out_time ?? '12:00', 0, 5) }}
                        </dd>
                    </div>
                    <div>
                        <dt>Bukti dikirim</dt>
                        <dd>{{ $reservation->payment_submitted_at?->format('d M Y, H:i') ?? '—' }} WIB</dd>
                    </div>
                    <div>
                        <dt>{{ __('Rekening pengirim') }}</dt>
                        <dd>{{ $reservation->account_name ?: __('Nama tidak tersedia') }} · {{ $reservation->bank_name ?: __('Bank tidak disebutkan') }}</dd>
                    </div>
                </dl>

                <footer class="admin-payment-item-footer">
                    <div class="admin-payment-total">
                        <span>Total pembayaran</span>
                        <strong>Rp{{ number_format((float) $reservation->total_price, 0, ',', '.') }}</strong>
                    </div>
                    <div class="admin-payment-actions">
                        <a
                            class="admin-payment-button admin-payment-button-proof"
                            href="{{ route('admin.payment-reviews.proof', $reservation) }}"
                            target="_blank"
                            rel="noopener"
                        >Lihat Bukti</a>
                        <form action="{{ route('admin.payment-reviews.review', $reservation) }}" method="post">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="decision" value="approve">
                            <button class="admin-payment-button admin-payment-button-approve" type="submit">Setujui Pembayaran</button>
                        </form>
                        <form action="{{ route('admin.payment-reviews.review', $reservation) }}" method="post">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="decision" value="cancel">
                            <button class="admin-payment-button admin-payment-button-cancel" type="submit">Batalkan</button>
                        </form>
                    </div>
                </footer>
            </article>
        @empty
            <div class="admin-payment-empty">
                <h2>Tidak ada bukti transfer untuk diverifikasi</h2>
                <p>Reservasi baru akan muncul di sini setelah tamu mengirimkan bukti pembayaran.</p>
            </div>
        @endforelse
    </section>

    @if ($reservations->hasPages())
        <nav class="admin-payment-pagination" aria-label="Navigasi halaman antrean">
            {{ $reservations->links() }}
        </nav>
    @endif
@endsection

@section('switch')
    <a href="{{ route('admin.dashboard') }}">Kembali ke Dashboard Admin</a>
@endsection

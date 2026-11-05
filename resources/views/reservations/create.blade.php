@extends('layouts.auth')

@section('title', __('Pilih nomor kamar'))
@section('eyebrow', __('Langkah 1 · Pilih nomor kamar'))
@section('heading', __('Pilih nomor kamar'))
@section('intro')
    {{ $room->name }} · {{ \Illuminate\Support\Carbon::parse($filters['checkin'])->format('d M Y') }} {{ $filters['checkin_time'] }} - {{ \Illuminate\Support\Carbon::parse($filters['checkout'])->format('d M Y') }} {{ $filters['checkout_time'] }} · {{ $filters['guests'] }} Tamu
@endsection
@section('mainClass', 'room-selection-main')
@section('cardClass', 'room-selection-card')
@section('bodyClass', 'room-selection-page')

@section('form')
    <div
        class="room-selection"
        data-room-selector
        data-lock-url="{{ route('reservations.lock-selection', $room) }}"
        data-availability-url="{{ route('reservations.availability', $room) }}"
        data-checkin="{{ $filters['checkin'] }}"
        data-checkout="{{ $filters['checkout'] }}"
        data-room-count="{{ $filters['rooms'] }}"
        data-room-price="{{ (float) $room->price_per_night }}"
        data-nights="{{ (int) \Illuminate\Support\Carbon::parse($filters['checkin'])->diffInDays($filters['checkout']) }}"
        data-selection-expiry="{{ $selectionExpiry ?? '' }}"
        data-initial-rooms="{{ json_encode(collect($roomStates)->where('status', 'selected')->pluck('number')->values()) }}"
    >
        @include('reservations.partials.steps', ['activeStep' => 1])

        @if ($errors->has('room_numbers'))
            <div class="auth-error" role="alert">{{ $errors->first('room_numbers') }}</div>
        @endif

        <form action="{{ route('reservations.continue-to-guest', $room) }}" method="post" data-room-form>
            @csrf
            <input type="hidden" name="checkin" value="{{ $filters['checkin'] }}">
            <input type="hidden" name="checkin_time" value="{{ $filters['checkin_time'] }}">
            <input type="hidden" name="checkout" value="{{ $filters['checkout'] }}">
            <input type="hidden" name="checkout_time" value="{{ $filters['checkout_time'] }}">
            <input type="hidden" name="guests" value="{{ $filters['guests'] }}">
            <input type="hidden" name="rooms" value="{{ $filters['rooms'] }}">
            <div data-selected-inputs></div>

            <div class="selection-heading">
                <div>
                    <span class="kicker">{{ $room->hotel->name }}</span>
                    <p class="selection-date">{{ \Illuminate\Support\Carbon::parse($filters['checkin'])->format('d M Y') }} {{ $filters['checkin_time'] }} - {{ \Illuminate\Support\Carbon::parse($filters['checkout'])->format('d M Y') }} {{ $filters['checkout_time'] }} · {{ __(':count tamu', ['count' => $filters['guests']]) }} · {{ __(':count kamar', ['count' => $filters['rooms']]) }}</p>
                </div>
                <div class="selection-countdown" data-selection-countdown aria-live="polite">
                    <span aria-hidden="true">◷</span>
                    <span data-countdown-label>{{ $selectionExpiry ? __('Kamar dikunci sementara') : __('Pilih kamar untuk mengunci 10 menit') }}</span>
                    <strong data-countdown-time></strong>
                    <i data-countdown-progress></i>
                </div>
            </div>

            <div class="floor-tabs" role="tablist" aria-label="{{ __('Pilih lantai') }}">
                @foreach (range(1, 3) as $floor)
                    <button
                        type="button"
                        role="tab"
                        class="floor-tab{{ $floor === 1 ? ' is-active' : '' }}"
                        aria-selected="{{ $floor === 1 ? 'true' : 'false' }}"
                        aria-controls="floor-panel-{{ $floor }}"
                        data-floor-tab="{{ $floor }}"
                    >{{ __('Lantai :floor', ['floor' => $floor]) }}</button>
                @endforeach
            </div>

            <div class="selection-layout">
                <section class="floor-plan" aria-label="{{ __('Denah kamar') }}">
                    @foreach (range(1, 3) as $floor)
                        @php($floorRooms = collect($roomStates)->filter(fn ($state) => intdiv($state['number'] - 1, 10) + 1 === $floor)->values())
                        <div
                            id="floor-panel-{{ $floor }}"
                            class="floor-panel{{ $floor === 1 ? ' is-active' : '' }}"
                            role="tabpanel"
                            data-floor-panel="{{ $floor }}"
                            @if ($floor !== 1) hidden @endif
                        >
                            <h2>{{ __('Lantai :floor', ['floor' => $floor]) }} <span>· {{ __('Sayap Barat') }}</span></h2>
                            @if ($floorRooms->isEmpty())
                                <p class="floor-empty">{{ __('Belum ada kamar pada lantai ini.') }}</p>
                            @else
                                <div class="room-grid">
                                    @foreach ($floorRooms as $state)
                                        @php($roomIndex = (($state['number'] - 1) % 10) + 1)
                                        @php($label = $floor * 100 + $roomIndex)
                                        <button
                                            type="button"
                                            class="room-tile is-{{ $state['status'] }}"
                                            data-room-option
                                            data-number="{{ $state['number'] }}"
                                            data-floor="{{ $floor }}"
                                            data-label="{{ $label }}"
                                            data-status="{{ $state['status'] }}"
                                            data-base-status="{{ $state['status'] === 'selected' ? 'available' : $state['status'] }}"
                                            @disabled(in_array($state['status'], ['occupied', 'locked'], true))
                                            aria-pressed="{{ $state['status'] === 'selected' ? 'true' : 'false' }}"
                                            aria-label="{{ __('Kamar :number, :status', ['number' => $label, 'status' => match ($state['status']) {
                                                'occupied' => __('Terisi'),
                                                'locked' => __('Dikunci sementara'),
                                                'selected' => __('Dipilih'),
                                                default => __('Tersedia'),
                                            }]) }}"
                                        >
                                            <span class="room-tile-number">{{ $label }}</span>
                                            <span class="room-tile-type">{{ strtoupper(substr($room->name, 0, 3)) }}</span>
                                            @if ($state['status'] === 'locked')
                                                <span class="room-tile-state" aria-hidden="true">⌑</span>
                                            @elseif ($state['status'] === 'occupied')
                                                <span class="room-tile-state" aria-hidden="true">×</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                                <div class="corridor">
                                    <span>{{ __('Koridor') }}</span>
                                    <span aria-hidden="true">Lift</span>
                                    <span aria-hidden="true">Tangga</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                    <p class="floor-note">{{ __('Setiap lantai menampilkan hingga 10 kamar. Kamar yang dipilih dikunci selama 10 menit agar tidak dipilih tamu lain.') }}</p>
                    <p class="selection-feedback" data-selection-feedback role="status" aria-live="polite"></p>
                </section>

                <aside class="selection-summary" aria-label="{{ __('Ringkasan pilihan kamar') }}">
                    <h2>{{ __('Ringkasan Pilihan') }}</h2>
                    <dl>
                        <div><dt>{{ __('Kamar') }}</dt><dd data-summary-rooms>-</dd></div>
                        <div><dt>{{ __('Tipe') }}</dt><dd>{{ $room->name }}</dd></div>
                        <div><dt>{{ __('Lantai') }}</dt><dd data-summary-floor>-</dd></div>
                        <div><dt>{{ __('Durasi') }}</dt><dd>{{ __(':count malam', ['count' => (int) \Illuminate\Support\Carbon::parse($filters['checkin'])->diffInDays($filters['checkout'])]) }}</dd></div>
                        <div><dt>{{ __('Harga') }}</dt><dd>Rp{{ number_format((float) $room->price_per_night, 0, ',', '.') }} / {{ __('per malam') }}</dd></div>
                    </dl>
                    <div class="selection-total">
                        <span>{{ __('Total') }}</span>
                        <strong data-summary-total>Rp0</strong>
                    </div>
                    <p class="selection-warning">{{ __('Kunci pilihan berlaku 10 menit dan diperbarui otomatis. Jika waktu habis, kamar tersedia kembali untuk tamu lain.') }}</p>
                    <button class="button primary full-button selection-continue" type="submit" data-selection-submit disabled>
                        {{ __('Lanjutkan') }}
                    </button>
                </aside>
            </div>

            <div class="room-legend" aria-label="{{ __('Keterangan status kamar') }}">
                <span><i class="legend-available"></i>{{ __('Tersedia') }}</span>
                <span><i class="legend-selected"></i>{{ __('Dipilih') }}</span>
                <span><i class="legend-occupied"></i>{{ __('Terisi') }}</span>
                <span><i class="legend-locked"></i>{{ __('Dikunci sementara') }}</span>
            </div>
        </form>
    </div>
@endsection

@section('switch')
    <a href="{{ route('home') }}#koleksi">{{ __('Kembali ke pilihan kamar') }}</a>
@endsection

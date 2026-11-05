@php
    $steps = [
        ['label' => __('Pilih kamar'), 'number' => 1],
        ['label' => __('Data tamu'), 'number' => 2],
        ['label' => __('Pembayaran'), 'number' => 3],
        ['label' => __('Konfirmasi'), 'number' => 4],
    ];
@endphp

<nav class="booking-steps flow-card" aria-label="{{ __('Progres pemesanan') }}">
    <ol style="--steps-progress: {{ (($activeStep - 1) / (count($steps) - 1)) * 75 }}%;">
        @foreach ($steps as $step)
            @php($isComplete = $step['number'] < $activeStep)
            @php($isActive = $step['number'] === $activeStep)
            <li class="booking-step{{ $isComplete ? ' is-complete' : '' }}{{ $isActive ? ' is-active' : '' }}">
                @if ($isComplete && $step['number'] === 1 && ($allowRoomEdit ?? true))
                    <a href="{{ route('reservations.create', array_merge(['room' => $room], collect($filters)->except('room_numbers')->all())) }}">
                        <span class="booking-step-marker" aria-hidden="true">✓</span>
                        <span>{{ $step['label'] }}</span>
                    </a>
                @else
                    <span class="booking-step-marker" aria-hidden="true">{{ $isComplete ? '✓' : $step['number'] }}</span>
                    <span>{{ $step['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

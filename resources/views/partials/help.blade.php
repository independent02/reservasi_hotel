@php
    $whatsapp = trim((string) config('hotel.whatsapp'));
    $supportEmail = trim((string) config('hotel.support_email'));
    $locale = app()->getLocale();
    $whatsappMessage = (string) config(
        'hotel.whatsapp_message.'.$locale,
        config('hotel.whatsapp_message.id', ''),
    );
    $emailSubject = __('help.email_subject');
    $emailHref = 'mailto:'.$supportEmail
        .'?subject='.rawurlencode($emailSubject)
        .'&body='.rawurlencode($whatsappMessage);
    $gmailHref = 'https://mail.google.com/mail/?view=cm&fs=1&to='.rawurlencode($supportEmail)
        .'&su='.rawurlencode($emailSubject)
        .'&body='.rawurlencode($whatsappMessage);
@endphp

<link rel="stylesheet" href="{{ asset('css/help.css') }}">
<div class="help-widget" data-help-widget>
    <div
        class="help-panel"
        data-help-panel
        id="help-panel"
        role="dialog"
        aria-labelledby="help-title"
        aria-hidden="true"
    >
        <header class="help-header">
            <div>
                <h2 id="help-title">{{ __('help.title') }}</h2>
                <p>{{ __('help.greeting') }}</p>
            </div>
            <button
                class="help-close"
                type="button"
                data-help-close
                aria-label="{{ __('help.close') }}"
            >✕</button>
        </header>

        @if (isset($helpFaqs) && $helpFaqs->isNotEmpty())
            <section class="help-faqs" aria-label="{{ __('help.faq') }}">
                @foreach ($helpFaqs as $faq)
                    <details class="help-faq">
                        <summary>
                            <span>{{ $faq->question }}</span>
                            <span class="help-faq-icon" aria-hidden="true">+</span>
                        </summary>
                        <p>{{ $faq->answer }}</p>
                    </details>
                @endforeach
            </section>
        @endif

        @if ($whatsapp !== '' || $supportEmail !== '')
            <footer class="help-contact">
                <p>{{ __('help.still_need_help') }}</p>
                <div class="help-contact-actions">
                    @if ($whatsapp !== '')
                        <a
                            class="help-contact-link help-whatsapp"
                            href="https://wa.me/6289667875415?text={{ urlencode(__('help.whatsapp_message_default', [], app()->getLocale()) ?: 'Halo Admin Stayvia, saya ingin bertanya seputar reservasi kamar.') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                        >{{ __('help.chat_whatsapp') }}</a>
                    @endif

                    @if ($supportEmail !== '')
                        <a 
        class="help-contact-link help-email"
        href="https://mail.google.com/mail/?view=cm&fs=1&to=vibecode812@gmail.com&su=Bantuan%20Reservasi%20Stayvia"
        target="_blank"
    >{{ __('help.send_email') }}</a>
                    @endif
                </div>
            </footer>
        @endif
    </div>

    <button
        class="help-toggle"
        type="button"
        data-help-toggle
        data-open-label="{{ __('help.open') }}"
        data-close-label="{{ __('help.close') }}"
        aria-controls="help-panel"
        aria-expanded="false"
        aria-label="{{ __('help.open') }}"
    >
        <span class="help-pulse" aria-hidden="true"></span>
        <span data-help-icon aria-hidden="true">?</span>
    </button>
</div>
<script src="{{ asset('js/help.js') }}" defer></script>

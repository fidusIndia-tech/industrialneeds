{{-- Floating WhatsApp button — included from the front-end layout, so it appears on every
     page rather than the homepage only (where it used to live inline).

     The chat launcher in _chatbot sits bottom-right at bottom:20px, so this stacks just
     above it at bottom:82px. Icon-only so both fit in the corner together; _chatbot hides
     this button while the chat window is open.

     The number comes from the company_phone business setting rather than a hardcoded one,
     matching how the product-page button builds its link. wa.me wants a country code plus
     the number with no "+", spaces or punctuation, hence the digit strip. --}}
@php($waNumber = preg_replace('/\D/', '', \App\CPU\Helpers::get_business_settings('company_phone') ?? ''))

@if($waNumber !== '')
    @php($waMessage = rawurlencode(\App\CPU\translate('Hello, I am interested in your industrial products. Please assist me with pricing and availability.')))

    <style>
        .ind-whatsapp-float {
            position: fixed;
            {{ Session::get('direction') === "rtl" ? 'left' : 'right' }}: 20px;
            bottom: 82px; /* sits just above the chat "Chat with us" launcher */
            z-index: 1030; /* above page content, below Bootstrap modals (1050) */
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            padding: 0;
            background-color: #25D366;
            color: #ffffff;
            line-height: 1;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .ind-whatsapp-float:hover,
        .ind-whatsapp-float:focus {
            color: #ffffff;
            background-color: #1ebe57;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
            transform: translateY(-2px);
            text-decoration: none;
        }

        .ind-whatsapp-float__icon {
            flex: 0 0 auto;
        }

        @media (max-width: 767.98px) {
            .ind-whatsapp-float {
                {{ Session::get('direction') === "rtl" ? 'left' : 'right' }}: 16px;
                bottom: 78px;
                width: 48px;
                height: 48px;
            }
        }
    </style>

    <a href="https://wa.me/{{ $waNumber }}?text={{ $waMessage }}"
       class="ind-whatsapp-float"
       target="_blank"
       rel="noopener noreferrer"
       aria-label="{{ \App\CPU\translate('Chat on WhatsApp') }}">
        <svg class="ind-whatsapp-float__icon" viewBox="0 0 32 32" width="28" height="28" aria-hidden="true" focusable="false">
            <path fill="currentColor" d="M16.001 3.2c-7.06 0-12.8 5.74-12.8 12.8 0 2.257.59 4.46 1.71 6.4L3.2 28.8l6.56-1.72a12.74 12.74 0 0 0 6.24 1.62h.005c7.06 0 12.8-5.74 12.8-12.8s-5.74-12.7-12.8-12.7zm0 23.04h-.004a10.62 10.62 0 0 1-5.41-1.48l-.388-.23-4.02 1.054 1.072-3.92-.253-.402a10.6 10.6 0 0 1-1.626-5.662c0-5.867 4.776-10.64 10.65-10.64 2.842 0 5.513 1.108 7.524 3.12a10.56 10.56 0 0 1 3.116 7.526c0 5.867-4.776 10.638-10.64 10.638zm5.835-7.97c-.32-.16-1.892-.933-2.185-1.04-.293-.107-.507-.16-.72.16-.213.32-.826 1.04-1.013 1.253-.187.213-.373.24-.693.08-.32-.16-1.35-.498-2.572-1.587-.95-.848-1.592-1.895-1.779-2.215-.187-.32-.02-.493.14-.652.144-.144.32-.373.48-.56.16-.187.213-.32.32-.533.107-.213.053-.4-.027-.56-.08-.16-.72-1.735-.987-2.375-.26-.624-.524-.54-.72-.55l-.613-.01c-.213 0-.56.08-.853.4-.293.32-1.12 1.094-1.12 2.668 0 1.574 1.146 3.094 1.306 3.307.16.213 2.256 3.444 5.466 4.83.764.33 1.36.527 1.825.674.767.244 1.464.21 2.016.127.615-.092 1.892-.773 2.158-1.52.267-.747.267-1.387.187-1.52-.08-.133-.293-.213-.613-.373z"/>
        </svg>
    </a>
@endif

<span {{ $attributes->merge(['class' => 'prisma-auth-brand']) }} aria-label="Análises Prisma">
    <svg
        class="prisma-auth-brand__symbol"
        viewBox="0 0 160 128"
        role="img"
        aria-label="Prisma irradiando dados por diferentes ângulos"
    >
        <defs>
            <linearGradient id="prisma-face-left" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#1515a6" />
                <stop offset="1" stop-color="#304dff" />
            </linearGradient>
            <linearGradient id="prisma-face-right" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#2f6aff" />
                <stop offset="1" stop-color="#21d8e7" />
            </linearGradient>
            <linearGradient id="prisma-face-bottom" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#111285" />
                <stop offset=".55" stop-color="#2547ef" />
                <stop offset="1" stop-color="#20cde5" />
            </linearGradient>
            <linearGradient id="prisma-ray-top" x1="0" y1="1" x2="1" y2="0">
                <stop offset="0" stop-color="#20d8e8" />
                <stop offset="1" stop-color="#14e4b3" />
            </linearGradient>
            <linearGradient id="prisma-ray-bottom" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#315cff" />
                <stop offset="1" stop-color="#7115f2" />
            </linearGradient>
            <filter id="prisma-glow" x="-60%" y="-60%" width="220%" height="220%">
                <feGaussianBlur stdDeviation="5" />
            </filter>
        </defs>

        <circle cx="75" cy="65" r="13" fill="#42e7f2" opacity=".42" filter="url(#prisma-glow)" />
        <path d="M74 8 30 67 74 58Z" fill="url(#prisma-face-left)" />
        <path d="M74 8 116 69 74 58Z" fill="url(#prisma-face-right)" />
        <path d="M30 67 74 58 115 70 74 118 18 99Z" fill="url(#prisma-face-bottom)" />
        <path d="M74 58 74 118" stroke="#4fe9f2" stroke-width="1.5" opacity=".9" />
        <path d="M30 67 74 58 115 70" fill="none" stroke="#fff" stroke-width="4" opacity=".92" />
        <path d="M74 58 18 99" fill="none" stroke="#77eafa" stroke-width="1.4" opacity=".85" />
        <path d="M74 58 115 70" fill="none" stroke="#fff" stroke-width="3.5" />

        <path d="M76 59 145 21" stroke="url(#prisma-ray-top)" stroke-width="4" stroke-linecap="round" />
        <path d="M77 61 151 48" stroke="#21cbe8" stroke-width="4" stroke-linecap="round" />
        <path d="M78 63 154 70" stroke="#247bf3" stroke-width="4" stroke-linecap="round" />
        <path d="M77 65 148 101" stroke="url(#prisma-ray-bottom)" stroke-width="4" stroke-linecap="round" />
        <circle cx="145" cy="21" r="5" fill="#14e4b3" />
        <circle cx="151" cy="48" r="5" fill="#21cbe8" />
        <circle cx="154" cy="70" r="5" fill="#247bf3" />
        <circle cx="148" cy="101" r="5" fill="#7115f2" />
    </svg>

    <span class="prisma-auth-brand__name">
        <span>Análises</span>
        <strong>PRISMA</strong>
    </span>

    <span class="prisma-auth-brand__tagline">Dados por todos os ângulos.</span>
</span>

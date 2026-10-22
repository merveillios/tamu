{{--
    Ilustrasi animasi SVG bertema Disperindag: gedung dinas, roda gigi industri (bergerak),
    dan grafik perdagangan (batang tumbuh + garis naik). Murni CSS animation, tanpa JS.
--}}
<svg class="disdag-illustration" viewBox="0 0 320 200" xmlns="http://www.w3.org/2000/svg" role="img"
     aria-label="Ilustrasi Dinas Perindustrian dan Perdagangan">
    {{-- Awan lembut bergerak pelan --}}
    <g class="disdag-clouds" fill="#ffffff" opacity="0.55">
        <ellipse class="disdag-cloud disdag-cloud-1" cx="60" cy="30" rx="26" ry="10"/>
        <ellipse class="disdag-cloud disdag-cloud-2" cx="255" cy="22" rx="20" ry="8"/>
    </g>

    {{-- Matahari --}}
    <circle class="disdag-sun" cx="290" cy="46" r="16" fill="#fbbf24"/>

    {{-- Tanah --}}
    <rect x="0" y="168" width="320" height="32" fill="#0a2f5e" opacity="0.25"/>
    <rect x="0" y="166" width="320" height="4" fill="#ffffff" opacity="0.35"/>

    {{-- Gedung dinas (tengah) --}}
    <g class="disdag-building">
        <rect x="120" y="70" width="80" height="98" rx="3" fill="#ffffff" opacity="0.95"/>
        <rect x="112" y="62" width="96" height="12" rx="3" fill="#fbbf24"/>
        <rect x="120" y="70" width="80" height="98" rx="3" fill="none" stroke="#0b3d7b" stroke-opacity="0.25"/>
        {{-- Jendela gedung --}}
        <g fill="#0b3d7b" opacity="0.75">
            <rect x="130" y="84" width="14" height="12" rx="1.5"/>
            <rect x="153" y="84" width="14" height="12" rx="1.5"/>
            <rect x="176" y="84" width="14" height="12" rx="1.5"/>
            <rect x="130" y="106" width="14" height="12" rx="1.5"/>
            <rect x="153" y="106" width="14" height="12" rx="1.5"/>
            <rect x="176" y="106" width="14" height="12" rx="1.5"/>
        </g>
        {{-- Pintu --}}
        <rect x="148" y="136" width="24" height="32" rx="2" fill="#0b3d7b"/>
        {{-- Bendera di atap --}}
        <line x1="160" y1="62" x2="160" y2="40" stroke="#ffffff" stroke-width="2"/>
        <path class="disdag-flag" d="M160 40 h20 l-5 6 5 6 h-20 z" fill="#e11d48"/>
    </g>

    {{-- Roda gigi industri (kiri) — berputar --}}
    <g class="disdag-gear" transform="translate(72 120)">
        <g class="disdag-gear-spin">
            <circle r="26" fill="#fbbf24"/>
            <circle r="10" fill="#0b3d7b"/>
            <g fill="#fbbf24">
                <rect x="-5" y="-38" width="10" height="14" rx="2"/>
                <rect x="-5" y="24" width="10" height="14" rx="2"/>
                <rect x="-38" y="-5" width="14" height="10" rx="2"/>
                <rect x="24" y="-5" width="14" height="10" rx="2"/>
                <rect x="-30" y="-30" width="12" height="9" rx="2" transform="rotate(45 -24 -25.5)"/>
                <rect x="18" y="-30" width="12" height="9" rx="2" transform="rotate(-45 24 -25.5)"/>
                <rect x="-30" y="21" width="12" height="9" rx="2" transform="rotate(-45 -24 25.5)"/>
                <rect x="18" y="21" width="12" height="9" rx="2" transform="rotate(45 24 25.5)"/>
            </g>
        </g>
    </g>
    {{-- Roda gigi kecil — berputar berlawanan --}}
    <g class="disdag-gear" transform="translate(36 152)">
        <g class="disdag-gear-spin disdag-gear-reverse">
            <circle r="14" fill="#ffffff" opacity="0.9"/>
            <circle r="5" fill="#0b3d7b"/>
            <g fill="#ffffff" opacity="0.9">
                <rect x="-3" y="-22" width="6" height="9" rx="1.5"/>
                <rect x="-3" y="13" width="6" height="9" rx="1.5"/>
                <rect x="-22" y="-3" width="9" height="6" rx="1.5"/>
                <rect x="13" y="-3" width="9" height="6" rx="1.5"/>
            </g>
        </g>
    </g>

    {{-- Grafik perdagangan (kanan): batang tumbuh + garis tren naik --}}
    <g class="disdag-chart">
        <rect class="disdag-bar disdag-bar-1" x="228" y="132" width="14" height="36" rx="2" fill="#ffffff" opacity="0.9"/>
        <rect class="disdag-bar disdag-bar-2" x="250" y="118" width="14" height="50" rx="2" fill="#fbbf24"/>
        <rect class="disdag-bar disdag-bar-3" x="272" y="100" width="14" height="68" rx="2" fill="#ffffff" opacity="0.9"/>
        <polyline class="disdag-trend" points="230,126 256,106 288,84" fill="none" stroke="#22c55e"
                  stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <path class="disdag-arrow" d="M288 84 l-10 1 6 9 z" fill="#22c55e"/>
    </g>
</svg>

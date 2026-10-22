{{-- Dekorasi panel kiri: garis bergelombang topografi, titik-titik, lingkaran, plus --}}
<svg class="hero-deco hero-deco-wave-tl" viewBox="0 0 200 160" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <g stroke="#ffffff" stroke-width="1.2">
        <path d="M-10 20 C 40 5, 70 45, 120 30 S 190 10, 215 35"/>
        <path d="M-10 40 C 40 25, 70 65, 120 50 S 190 30, 215 55"/>
        <path d="M-10 60 C 40 45, 70 85, 120 70 S 190 50, 215 75"/>
        <path d="M-10 80 C 40 65, 70 105, 120 90 S 190 70, 215 95"/>
        <path d="M-10 100 C 40 85, 70 125, 120 110 S 190 90, 215 115"/>
    </g>
</svg>

<svg class="hero-deco hero-deco-wave-br" viewBox="0 0 220 170" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <g stroke="#ffffff" stroke-width="1.2">
        <path d="M-10 25 C 50 5, 90 50, 140 32 S 200 15, 235 40"/>
        <path d="M-10 50 C 50 30, 90 75, 140 57 S 200 40, 235 65"/>
        <path d="M-10 75 C 50 55, 90 100, 140 82 S 200 65, 235 90"/>
        <path d="M-10 100 C 50 80, 90 125, 140 107 S 200 90, 235 115"/>
        <path d="M-10 125 C 50 105, 90 150, 140 132 S 200 115, 235 140"/>
        <path d="M-10 150 C 50 130, 90 175, 140 157 S 200 140, 235 165"/>
    </g>
</svg>

<svg class="hero-deco hero-deco-dots" viewBox="0 0 72 72" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <g fill="#ffffff">
        @for ($row = 0; $row < 6; $row++)
            @for ($col = 0; $col < 6; $col++)
                <circle cx="{{ 6 + $col * 12 }}" cy="{{ 6 + $row * 12 }}" r="1.8"/>
            @endfor
        @endfor
    </g>
</svg>

{{-- Lingkaran & plus melayang --}}
<span class="hero-deco-float" style="top: 18%; right: 22%;">
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><circle cx="9" cy="9" r="7" stroke="currentColor" stroke-width="1.6"/></svg>
</span>
<span class="hero-deco-float" style="top: 12%; left: 38%; animation-delay: 1.2s;">
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 1v14M1 8h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
</span>
<span class="hero-deco-float" style="bottom: 14%; left: 18%; animation-delay: 2.1s;">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.6"/></svg>
</span>
<span class="hero-deco-float" style="bottom: 22%; right: 12%; animation-delay: 0.7s;">
    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M7 1v12M1 7h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
</span>

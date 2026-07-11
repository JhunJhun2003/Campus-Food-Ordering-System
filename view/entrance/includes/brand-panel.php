<?php
/**
 * Brand Panel - Left side of login/register pages
 * 
 * @var string $brandTitle - Title text
 * @var string $brandSubtitle - Subtitle text
 */

$brandTitle = $brandTitle ?? 'FOODIE';
$brandSubtitle = $brandSubtitle ?? 'Delicious Food, Delivered Fast';
?>
<div class="brand-panel">
    <div class="brand-logo">
        <!-- <svg viewBox="0 0 100 100" class="w-16 h-16 fill-current text-slate-950">
            <path d="M42,28 C26,28 22,35 22,41 C22,43 23,45 25,45 L59,45 C61,45 62,43 62,41 C62,35 58,28 42,28 Z M22,49 C21,49 20,50 20,51 C20,53 23,55 25,55 L59,55 C61,55 64,53 64,51 C64,50 63,49 62,49 L22,49 Z M25,59 C21,59 21,63 21,65 C21,72 29,76 42,76 C55,76 63,72 63,65 C63,63 63,59 59,59 L25,59 Z" />
            <path d="M68,20 L80,20 C81,20 82,21 82,22 L76,72 C76,73 75,74 74,74 L64,74 C63,74 62,73 62,72 L65,48 L68,20 Z" />
            <line x1="74" y1="20" x2="63" y2="8" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
        </svg> -->
                       <script
  src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.9.14/dist/dotlottie-wc.js"
  type="module"
></script>

<dotlottie-wc
  src="https://lottie.host/ea75b4fe-1d6d-4e5e-97eb-df01f2e490df/FTXFOlVlea.lottie"
  style="width: 130px;height: 130px"
  autoplay
  loop
></dotlottie-wc>
        <span class="brand-name"><?php echo htmlspecialchars($brandTitle); ?></span>
    </div>
    <p class="brand-tagline"><?php echo htmlspecialchars($brandSubtitle); ?></p>

    <!-- Illustrated Pots -->
    <div class="relative w-full max-w-sm aspect-[4/3] mt-6 select-none">
        <svg viewBox="0 0 400 300" class="w-full h-full drop-shadow-md">
            <defs>
                <path id="steam-wave" d="M 0 0 C 10 -10, -10 -30, 0 -40 C 10 -50, -10 -70, 0 -80" fill="none" stroke="#CBD5E1" stroke-width="3" stroke-linecap="round" />
            </defs>
            <!-- Pot 1 -->
            <g transform="translate(40, 95) scale(0.75)">
                <use href="#steam-wave" x="120" y="50" class="steam-line steam-delay-1" />
                <use href="#steam-wave" x="140" y="45" class="steam-line steam-delay-3" />
                <use href="#steam-wave" x="100" y="55" class="steam-line steam-delay-2" />
                <path d="M 30 70 C 10 70, 10 110, 30 110" fill="none" stroke="#334155" stroke-width="14" stroke-linecap="round" />
                <path d="M 210 70 C 230 70, 230 110, 210 110" fill="none" stroke="#334155" stroke-width="14" stroke-linecap="round" />
                <path d="M 35 70 C 35 150, 205 150, 205 70 Z" fill="#334155" />
                <ellipse cx="120" cy="70" rx="80" ry="22" fill="#F59E0B" />
                <polygon points="90,65 105,62 100,74" fill="#F97316" />
                <polygon points="140,72 155,75 148,64" fill="#F97316" />
                <path d="M 110,75 Q 115,68 122,76" fill="none" stroke="#22C55E" stroke-width="3" stroke-linecap="round" />
                <path d="M 70,72 Q 74,66 79,73" fill="none" stroke="#22C55E" stroke-width="2.5" stroke-linecap="round" />
                <circle cx="125" cy="65" r="7" fill="#78350F" />
                <circle cx="85" cy="73" r="6" fill="#78350F" />
                <circle cx="155" cy="68" r="8" fill="#78350F" />
            </g>
            <!-- Pot 2 -->
            <g transform="translate(180, 95) scale(0.75)">
                <use href="#steam-wave" x="120" y="50" class="steam-line steam-delay-2" />
                <use href="#steam-wave" x="140" y="45" class="steam-line steam-delay-4" />
                <use href="#steam-wave" x="100" y="55" class="steam-line steam-delay-1" />
                <path d="M 30 70 C 10 70, 10 110, 30 110" fill="none" stroke="#334155" stroke-width="14" stroke-linecap="round" />
                <path d="M 210 70 C 230 70, 230 110, 210 110" fill="none" stroke="#334155" stroke-width="14" stroke-linecap="round" />
                <path d="M 35 70 C 35 150, 205 150, 205 70 Z" fill="#334155" />
                <ellipse cx="120" cy="70" rx="80" ry="22" fill="#F59E0B" />
                <polygon points="85,68 95,61 97,71" fill="#F97316" />
                <polygon points="130,71 145,74 138,63" fill="#F97316" />
                <path d="M 105,73 Q 110,66 117,74" fill="none" stroke="#22C55E" stroke-width="3" stroke-linecap="round" />
                <path d="M 150,70 Q 155,64 162,71" fill="none" stroke="#22C55E" stroke-width="2.5" stroke-linecap="round" />
                <circle cx="115" cy="64" r="7" fill="#78350F" />
                <circle cx="75" cy="72" r="6" fill="#78350F" />
                <circle cx="145" cy="67" r="8" fill="#78350F" />
            </g>
            <!-- Pot 3 -->
            <g transform="translate(90, 130) scale(0.9)">
                <use href="#steam-wave" x="120" y="50" class="steam-line steam-delay-3" />
                <use href="#steam-wave" x="145" y="45" class="steam-line steam-delay-1" />
                <use href="#steam-wave" x="95" y="55" class="steam-line steam-delay-4" />
                <path d="M 30 70 C 10 70, 10 110, 30 110" fill="none" stroke="#3F4E5F" stroke-width="16" stroke-linecap="round" />
                <path d="M 210 70 C 230 70, 230 110, 210 110" fill="none" stroke="#3F4E5F" stroke-width="16" stroke-linecap="round" />
                <path d="M 35 70 C 35 155, 205 155, 205 70 Z" fill="#3F4E5F" />
                <ellipse cx="120" cy="70" rx="80" ry="22" fill="#F59E0B" />
                <polygon points="75,70 90,66 84,78" fill="#F97316" />
                <polygon points="120,74 135,76 128,66" fill="#F97316" />
                <polygon points="160,67 172,74 165,62" fill="#F97316" />
                <path d="M 95,74 Q 102,66 111,75" fill="none" stroke="#22C55E" stroke-width="3" stroke-linecap="round" />
                <path d="M 140,73 Q 146,65 155,72" fill="none" stroke="#22C55E" stroke-width="3" stroke-linecap="round" />
                <circle cx="105" cy="65" r="8" fill="#78350F" />
                <circle cx="150" cy="68" r="7" fill="#78350F" />
            </g>
        </svg>
    </div>
</div>
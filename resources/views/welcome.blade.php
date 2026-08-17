<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dot.Pulse — the Dot Ecosystem's community feed</title>
        <meta name="description" content="Dot.Pulse is the shared feed for every team building on the Dot platforms — post updates, join communities, and follow what's trending across the ecosystem.">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        <script defer src="https://unpkg.com/alpinejs@3.10.2/dist/cdn.min.js"></script>

        <style>
            :root {
                --ink: #09090b;
                --panel: #141416;
                --accent: #f1c62e;
                --accent-soft: #f6d670;
                --accent-ink: #08354f;
                --navy: #08354f;
                --paper: #f4f4f5;
                --mist: #a1a1aa;
                --line: rgba(244, 244, 245, 0.10);
                --font-display: 'Syne', system-ui, sans-serif;
                --font-body: 'Inter', system-ui, sans-serif;
                --font-mono: 'JetBrains Mono', ui-monospace, monospace;
                --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
            }
            html { background: var(--ink); }
            body { font-family: var(--font-body); background: var(--ink); color: var(--paper); }
            .font-display { font-family: var(--font-display); }
            .font-mono { font-family: var(--font-mono); }
            /* The logo's wordmark/chevron are navy, ~1.5:1 contrast against every dark
               surface here -- a rim + glow lifts the shape without recoloring the asset. */
            .dot-logo { filter: drop-shadow(0 0 1px rgba(255,255,255,0.7)) drop-shadow(0 0 2px rgba(255,255,255,0.4)) drop-shadow(0 0 8px rgba(241,198,46,0.35)); }

            .press { transition: transform 160ms var(--ease-out); }
            .press:active { transform: scale(0.97); }

            @media (prefers-reduced-motion: no-preference) {
                .reveal {
                    opacity: 0;
                    transform: translateY(14px);
                    transition: opacity 600ms var(--ease-out), transform 600ms var(--ease-out);
                }
                .reveal.is-visible { opacity: 1; transform: translateY(0); }
            }
            @media (prefers-reduced-motion: reduce) {
                .reveal { opacity: 1; transform: none; }
            }

            @media (hover: hover) and (pointer: fine) {
                .row-hover:hover { background: rgba(244, 244, 245, 0.03); }
                .link-underline { background-size: 0% 1px; }
                .link-underline:hover { background-size: 100% 1px; }
            }
            .link-underline {
                background-image: linear-gradient(currentColor, currentColor);
                background-position: 0 100%;
                background-repeat: no-repeat;
                transition: background-size 220ms var(--ease-out);
            }
        </style>
    </head>
    <body class="antialiased">

        <!-- Nav -->
        <header
            x-data="{ scrolled: false, mobileMenuOpen: false }"
            @scroll.window="scrolled = window.pageYOffset > 24"
            :class="scrolled ? 'bg-[#09090b]/95 backdrop-blur-md border-b border-[var(--line)]' : 'border-b border-transparent'"
            class="fixed top-0 left-0 right-0 z-50 transition-colors duration-300"
        >
            <nav class="max-w-[1400px] mx-auto px-5 sm:px-8 py-3.5 flex items-center justify-between">
                <a href="/" class="flex items-center press">
                    <img src="{{ asset('images/logo.png') }}" alt="Dot.Pulse" class="dot-logo h-11 sm:h-12 w-auto">
                </a>

                <div class="hidden md:flex items-center gap-8 font-mono text-[13px] tracking-wide uppercase text-[var(--mist)]">
                    <a href="#feed" class="link-underline hover:text-[var(--paper)] pb-0.5">Feed</a>
                    <a href="#communities" class="link-underline hover:text-[var(--paper)] pb-0.5">Communities</a>
                </div>

                @if (Route::has('login'))
                    <div class="flex items-center gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="press flex items-center gap-2 px-5 py-2.5 bg-[var(--accent)] hover:bg-[var(--accent-soft)] text-[#08354f] text-sm font-display font-bold rounded-lg transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="hidden sm:block text-sm font-medium text-[var(--mist)] hover:text-[var(--paper)] transition-colors">
                                Sign in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="press px-5 py-2.5 bg-[var(--accent)] hover:bg-[var(--accent-soft)] text-[#08354f] text-sm font-display font-bold rounded-lg transition-colors">
                                    Get started
                                </a>
                            @endif
                        @endauth

                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden press p-2 -mr-2 text-[var(--paper)]" aria-label="Toggle menu">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 7h16M4 12h16M4 17h16"></path>
                                <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                @endif
            </nav>

            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="md:hidden border-t border-[var(--line)] bg-[#09090b]"
                 style="display: none;">
                <div class="flex flex-col px-5 py-4 gap-1 font-mono text-sm uppercase tracking-wide">
                    <a href="#feed" class="px-3 py-2.5 text-[var(--mist)] hover:text-[var(--paper)]">Feed</a>
                    <a href="#communities" class="px-3 py-2.5 text-[var(--mist)] hover:text-[var(--paper)]">Communities</a>
                    @guest
                        <a href="{{ route('login') }}" class="px-3 py-2.5 text-[var(--mist)] hover:text-[var(--paper)]">Sign in</a>
                    @endguest
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative min-h-[100dvh] flex items-center overflow-hidden">
            <!-- Photo: Keysight oscilloscope tracing a live square-wave signal, by Doug Baney,
                 unsplash.com/photos/black-and-white-audio-mixer-daADC54moaU — a literal pulse
                 trace, matching what this platform is named for and what it surfaces. -->
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1621638363255-9c092fa8d4ab?q=80&w=2400&auto=format&fit=crop');"></div>
            <div class="absolute inset-0" style="background: linear-gradient(100deg, var(--ink) 0%, var(--ink) 32%, rgba(9,9,11,0.55) 54%, rgba(9,9,11,0.22) 76%, rgba(9,9,11,0.06) 100%);"></div>
            <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(9,9,11,0.65) 0%, transparent 16%);"></div>
            <div class="absolute inset-0" style="background: radial-gradient(ellipse 80% 60% at 6% 0%, rgba(241,198,46,0.14) 0%, transparent 55%), radial-gradient(ellipse 60% 45% at 100% 100%, rgba(241,198,46,0.08) 0%, transparent 55%);"></div>

            <!-- Signature: the real logo's own pulse-line device (the white EKG trace inside
                 the gold circle in the official mark), echoed here as an expanding ping —
                 the same idea, given room to actually pulse at hero scale. -->
            <svg class="hidden lg:block absolute right-[10%] top-1/2 -translate-y-1/2 h-[42%] w-auto opacity-[0.65] pointer-events-none" viewBox="0 0 240 240" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <circle cx="120" cy="120" r="30" stroke="var(--accent)" stroke-width="1.5">
                    <animate attributeName="r" values="14;100" dur="3.2s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.8;0" dur="3.2s" repeatCount="indefinite"/>
                </circle>
                <circle cx="120" cy="120" r="30" stroke="var(--accent-soft)" stroke-width="1.5">
                    <animate attributeName="r" values="14;100" dur="3.2s" begin="1.6s" repeatCount="indefinite"/>
                    <animate attributeName="opacity" values="0.8;0" dur="3.2s" begin="1.6s" repeatCount="indefinite"/>
                </circle>
                <circle cx="120" cy="120" r="6" fill="var(--accent)"/>
            </svg>

            <div class="relative z-10 max-w-[1400px] mx-auto px-5 sm:px-8 pt-28 pb-16 w-full">
                <div class="max-w-2xl reveal" data-reveal>
                    <p class="font-mono text-xs tracking-[0.18em] uppercase text-[var(--accent)] mb-6">
                        Community intelligence — Dot Ecosystem
                    </p>

                    <h1 class="font-display font-extrabold text-4xl sm:text-5xl lg:text-6xl leading-[1.05] tracking-tight text-[var(--paper)] mb-6">
                        What's happening<br>across the ecosystem.
                    </h1>

                    <p class="text-lg text-[var(--mist)] leading-relaxed max-w-xl mb-10">
                        Dot.Pulse is the shared feed for every team building on the Dot platforms — post updates, join communities, and follow what's trending across the ecosystem.
                    </p>

                    @guest
                        <div class="flex flex-wrap items-center gap-4">
                            <a href="{{ route('register') }}" class="press px-7 py-3.5 bg-[var(--accent)] hover:bg-[var(--accent-soft)] text-[#08354f] font-display font-bold rounded-lg transition-colors">
                                Get started
                            </a>
                            <a href="#feed" class="press flex items-center gap-2 px-7 py-3.5 text-[var(--paper)] font-medium rounded-lg border border-[var(--line)] hover:border-[var(--mist)] transition-colors">
                                See what's inside
                            </a>
                        </div>
                    @endguest
                </div>
            </div>
        </section>

        <script>
            if (window.matchMedia('(prefers-reduced-motion: no-preference)').matches && 'IntersectionObserver' in window) {
                const io = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
                document.querySelectorAll('[data-reveal]').forEach((el) => io.observe(el));
            } else {
                document.querySelectorAll('[data-reveal]').forEach((el) => el.classList.add('is-visible'));
            }
        </script>
    </body>
</html>

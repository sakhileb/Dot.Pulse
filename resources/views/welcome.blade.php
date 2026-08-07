<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dot.Pulse — Community intelligence for the people who solve things</title>
        <meta name="description" content="Join communities, post across seventeen content types, and get credited when your answer solves it. Dot.Pulse is where technical communities discuss, moderate, and build reputation together.">

        <!-- Favicon -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">

        {{-- Alpine: this standalone guest page never loads @livewireScripts (the usual trigger for
             Livewire's bundled Alpine to call Alpine.start()), so it needs its own instance — the
             same CDN build and version already used for guest/auth pages in layouts/app.blade.php. --}}
        <script defer src="https://unpkg.com/alpinejs@3.10.2/dist/cdn.min.js"></script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --ink: #0a2836;
                --ink-soft: #0f3448;
                --navy: #08354f;
                --gold: #f1c62e;
                --gold-soft: #f6d666;
                --paper: #eef4f3;
                --mist: #86a0ac;
                --line: rgba(238, 244, 243, 0.14);
                --font-display: 'Instrument Serif', Georgia, serif;
                --font-body: 'IBM Plex Sans', system-ui, sans-serif;
                --font-mono: 'IBM Plex Mono', ui-monospace, monospace;
                --ease-out: cubic-bezier(0.23, 1, 0.32, 1);
            }
            html { background: var(--ink); }
            body { font-family: var(--font-body); background: var(--ink); color: var(--paper); }
            .font-display { font-family: var(--font-display); }
            .font-mono { font-family: var(--font-mono); }

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
                .row-hover:hover { background: rgba(238, 244, 243, 0.03); }
                .link-underline { background-size: 0% 1px; }
                .link-underline:hover { background-size: 100% 1px; }
            }
            .link-underline {
                background-image: linear-gradient(currentColor, currentColor);
                background-position: 0 100%;
                background-repeat: no-repeat;
                transition: background-size 220ms var(--ease-out);
            }

            /* The real mark's chevron and "pulse" wordmark are drawn in the brand navy,
               which has almost no contrast against this page's navy background (~1.2:1).
               A thin light rim (stacked drop-shadows, not a recolor) keeps every part of
               the authentic logo legible without inventing a new asset. */
            .brand-mark {
                filter: drop-shadow(0 0 1px rgba(238, 244, 243, 0.9)) drop-shadow(0 0 1px rgba(238, 244, 243, 0.9)) drop-shadow(0 0 2px rgba(238, 244, 243, 0.55));
            }
        </style>
    </head>
    <body class="antialiased">

        <!-- Nav -->
        <header
            x-data="{ scrolled: false, mobileMenuOpen: false }"
            @scroll.window="scrolled = window.pageYOffset > 24"
            :class="scrolled ? 'bg-[#0a2836]/95 backdrop-blur-md border-b border-[var(--line)]' : 'border-b border-transparent'"
            class="fixed top-0 left-0 right-0 z-50 transition-colors duration-300"
        >
            <nav class="max-w-[1400px] mx-auto px-5 sm:px-8 py-3 flex items-center justify-between">
                <a href="/" class="flex items-center gap-2.5 press">
                    <img src="{{ asset('images/logo-light.png') }}" alt="Dot.Pulse" class="brand-mark h-16 sm:h-20 w-auto">
                </a>

                <div class="hidden md:flex items-center gap-8 font-mono text-[13px] tracking-wide uppercase text-[var(--mist)]">
                    <a href="#features" class="link-underline hover:text-[var(--paper)] pb-0.5">Features</a>
                    <a href="#capabilities" class="link-underline hover:text-[var(--paper)] pb-0.5">Platform</a>
                </div>

                @if (Route::has('login'))
                    <div class="flex items-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="press flex items-center gap-2 px-5 py-2.5 bg-[var(--gold)] hover:bg-[var(--gold-soft)] text-[#0a2836] text-sm font-semibold rounded-lg transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="hidden sm:block text-sm font-medium text-[var(--mist)] hover:text-[var(--paper)] transition-colors">
                                Sign in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="press px-5 py-2.5 bg-[var(--gold)] hover:bg-[var(--gold-soft)] text-[#0a2836] text-sm font-semibold rounded-lg transition-colors">
                                    Create free account
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
                 class="md:hidden border-t border-[var(--line)] bg-[#0a2836]"
                 style="display: none;">
                <div class="flex flex-col px-5 py-4 gap-1 font-mono text-sm uppercase tracking-wide">
                    <a href="#features" class="px-3 py-2.5 text-[var(--mist)] hover:text-[var(--paper)]">Features</a>
                    <a href="#capabilities" class="px-3 py-2.5 text-[var(--mist)] hover:text-[var(--paper)]">Platform</a>
                    @guest
                        <a href="{{ route('login') }}" class="px-3 py-2.5 text-[var(--mist)] hover:text-[var(--paper)]">Sign in</a>
                    @endguest
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative min-h-[100dvh] flex items-end overflow-hidden">
            <!-- Photo: group of people using laptop computers, by Annie Spratt (@anniespratt), unsplash.com/photos/group-of-people-using-laptop-computer-QckxruozjRg -->
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=2400&auto=format&fit=crop');"></div>
            <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(10,40,54,0.60) 0%, rgba(10,40,54,0.78) 45%, #0a2836 92%);"></div>
            <div class="absolute inset-0" style="background: linear-gradient(90deg, #0a2836 0%, rgba(10,40,54,0.6) 38%, transparent 68%);"></div>

            <!-- Pulse trace — line-art nod to the heartbeat waveform in the real Dot.Pulse mark.
                 Confined to the right side, clear of the text column (max-w-2xl), so it never
                 crosses the headline or body copy. -->
            <svg class="hidden lg:block absolute left-[46%] right-0 bottom-[30%] h-[26%] w-[54%] opacity-[0.16] pointer-events-none" viewBox="0 0 900 200" preserveAspectRatio="none" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M0,120 L60,120 L85,70 L100,170 L115,30 L130,150 L150,120 L260,120 L285,90 L300,140 L315,120 L430,120 L455,60 L470,180 L485,20 L500,150 L520,120 L630,120 L655,90 L670,140 L685,120 L800,120 L825,60 L840,180 L855,20 L870,150 L890,120 L900,120" stroke="#eef4f3" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
            </svg>

            <div class="relative z-10 max-w-[1400px] mx-auto px-5 sm:px-8 pt-32 pb-16 sm:pb-20 w-full">
                <div class="max-w-2xl reveal" data-reveal>
                    <p class="font-mono text-xs tracking-[0.18em] uppercase text-[var(--gold)] mb-6">
                        Community intelligence platform
                    </p>

                    <h1 class="font-display font-normal text-5xl sm:text-6xl lg:text-7xl leading-[1.05] tracking-tight text-[var(--paper)] mb-6">
                        Ask the question.<br>Get the accepted answer.
                    </h1>

                    <p class="text-lg text-[var(--mist)] leading-relaxed max-w-xl mb-10">
                        Post across seventeen content types — from questions to release notes — comment in threads, react, follow people whose answers you trust, and message them directly. Solve someone's problem and your reputation says so.
                    </p>

                    @guest
                        <div class="flex flex-wrap items-center gap-4">
                            <a href="{{ route('register') }}" class="press px-7 py-3.5 bg-[var(--gold)] hover:bg-[var(--gold-soft)] text-[#0a2836] font-semibold rounded-lg transition-colors">
                                Create free account
                            </a>
                            <a href="#features" class="press flex items-center gap-2 px-7 py-3.5 text-[var(--paper)] font-medium rounded-lg border border-[var(--line)] hover:border-[var(--mist)] transition-colors">
                                See what it does
                            </a>
                        </div>
                    @endguest
                </div>
            </div>

            <!-- Live capabilities strip — instrumentation-styled, not a fabricated metric -->
            <div class="relative z-10 w-full border-t border-[var(--line)] bg-[#0a2836]/60 backdrop-blur-sm">
                <div class="max-w-[1400px] mx-auto px-5 sm:px-8 py-4 flex flex-wrap gap-x-6 gap-y-2 font-mono text-[11px] tracking-[0.14em] uppercase text-[var(--mist)]">
                    <span>17 content types</span>
                    <span><span class="text-[var(--gold)]">&middot;</span> Real-time via Reverb</span>
                    <span><span class="text-[var(--gold)]">&middot;</span> AI-moderated posts</span>
                    <span><span class="text-[var(--gold)]">&middot;</span> Reputation for solutions</span>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="py-24 sm:py-28 px-5 sm:px-8">
            <div class="max-w-[1400px] mx-auto">
                <div class="max-w-xl mb-16 reveal" data-reveal>
                    <p class="font-mono text-xs tracking-[0.18em] uppercase text-[var(--gold)] mb-4">What it does</p>
                    <h2 class="font-display font-normal text-4xl sm:text-5xl text-[var(--paper)] leading-tight">
                        Everything a working community runs on
                    </h2>
                </div>

                <div class="grid md:grid-cols-2 border-t border-[var(--line)]">
                    @php
                        $features = [
                            ['tag' => 'Discussions', 'title' => 'Seventeen ways to post', 'body' => 'Question, Idea, Bug Report, Release, Tutorial, Showcase, Event, Poll, Job, and more — each typed, each searchable in one feed.'],
                            ['tag' => 'Reputation', 'title' => 'Points for solving, not scrolling', 'body' => 'Posts earn 5 points, comments 2, and a marked solution 10 — badges like Problem Solver and Verified Expert follow real outcomes, not raw activity.'],
                            ['tag' => 'Communities', 'title' => 'Public, private, or enterprise', 'body' => 'Join or create communities scoped to your team, each with its own rules, membership, and moderation.'],
                            ['tag' => 'Real-time', 'title' => "Threads that move without a refresh", 'body' => 'Comments, reactions, and messages broadcast live over Laravel Reverb — the feed updates as it happens.'],
                            ['tag' => 'Moderation', 'title' => "Every post reviewed before it's public", 'body' => 'An AI moderation pipeline scores spam, safety, and sentiment on submission, with a deterministic fallback so posting is never blocked.'],
                            ['tag' => 'Marketplace', 'title' => 'Agents, not just answers', 'body' => 'Discover and install community-submitted AI agents, automations, and integrations alongside the feed.'],
                        ];
                    @endphp
                    @foreach ($features as $i => $f)
                        <div class="row-hover border-b border-[var(--line)] {{ $i % 2 === 0 ? 'md:border-r' : '' }} px-1 py-8 sm:py-10 transition-colors reveal" data-reveal>
                            <p class="font-mono text-[11px] tracking-[0.14em] uppercase text-[var(--gold)] mb-3">{{ $f['tag'] }}</p>
                            <h3 class="font-display font-normal text-2xl text-[var(--paper)] mb-2.5">{{ $f['title'] }}</h3>
                            <p class="text-[var(--mist)] leading-relaxed max-w-md">{{ $f['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Capabilities -->
        <section id="capabilities" class="py-24 sm:py-28 px-5 sm:px-8 bg-[var(--ink-soft)] border-y border-[var(--line)]">
            <div class="max-w-[1400px] mx-auto">
                <div class="grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)] gap-12 lg:gap-20">
                    <div class="reveal" data-reveal>
                        <p class="font-mono text-xs tracking-[0.18em] uppercase text-[var(--gold)] mb-4">Built for the ecosystem</p>
                        <h2 class="font-display font-normal text-4xl sm:text-5xl text-[var(--paper)] leading-tight mb-5">
                            One account across the whole Dot platform
                        </h2>
                        <p class="text-[var(--mist)] leading-relaxed max-w-sm">
                            Sign in once through the ecosystem hub and Dot.Pulse is ready — no separate account, no separate password to manage.
                        </p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-x-10">
                        @php
                            $capabilities = [
                                ['title' => 'Ecosystem SSO', 'body' => 'A Sanctum token from the Dot hub logs you in directly — the one confirmed piece of cross-platform integration shipped today.'],
                                ['title' => 'Direct messaging', 'body' => 'One-to-one and group conversations, scoped to participants — not a shared inbox.'],
                                ['title' => 'Knowledge graph', 'body' => 'Solved discussions become structured problem, product, and expert nodes you can search long after the thread goes quiet.'],
                                ['title' => 'Live events', 'body' => 'Host and RSVP to webinars, AMAs, and meetups without leaving the feed.'],
                                ['title' => 'Polls', 'body' => 'Ask the community directly and watch results update as votes come in.'],
                                ['title' => 'Team-scoped posting', 'body' => 'Content is attributed to your team at creation; communities and posts stay open for the whole ecosystem to read.'],
                            ];
                        @endphp
                        @foreach ($capabilities as $c)
                            <div class="py-6 border-t border-[var(--line)] reveal" data-reveal>
                                <h3 class="font-display font-normal text-lg text-[var(--paper)] mb-1.5">{{ $c['title'] }}</h3>
                                <p class="text-sm text-[var(--mist)] leading-relaxed">{{ $c['body'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="relative py-28 sm:py-36 px-5 sm:px-8 overflow-hidden">
            <!-- Photo: speaker presenting on stage to an audience, by Carlos Gil (@carlosgil83), unsplash.com/photos/speaker-presenting-on-stage-to-an-audience-AsxOJcsaR4g -->
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1762968274962-20c12e6e8ecd?q=80&w=2400&auto=format&fit=crop');"></div>
            <div class="absolute inset-0" style="background: linear-gradient(180deg, #0a2836 0%, rgba(10,40,54,0.82) 50%, #0a2836 100%);"></div>

            <div class="relative z-10 max-w-2xl mx-auto text-center reveal" data-reveal>
                <h2 class="font-display font-normal text-4xl sm:text-5xl text-[var(--paper)] leading-tight mb-5">
                    Bring the question that's actually stuck
                </h2>
                <p class="text-[var(--mist)] leading-relaxed mb-10 max-w-lg mx-auto">
                    Free to join. Post it, and let the community — not a support queue — take a crack at it.
                </p>

                @guest
                    <div class="flex flex-wrap justify-center gap-4">
                        <a href="{{ route('register') }}" class="press px-8 py-3.5 bg-[var(--gold)] hover:bg-[var(--gold-soft)] text-[#0a2836] font-semibold rounded-lg transition-colors">
                            Create free account
                        </a>
                        <a href="{{ route('login') }}" class="press px-8 py-3.5 text-[var(--paper)] font-medium rounded-lg border border-[var(--line)] hover:border-[var(--mist)] transition-colors">
                            Sign in
                        </a>
                    </div>
                @endguest
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-14 px-5 sm:px-8 border-t border-[var(--line)]">
            <div class="max-w-[1400px] mx-auto flex flex-col sm:flex-row items-center justify-between gap-6">
                <a href="/" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo-light.png') }}" alt="Dot.Pulse" class="brand-mark h-11 w-auto opacity-90">
                </a>
                <div class="flex items-center gap-6 font-mono text-xs tracking-wide uppercase text-[var(--mist)]">
                    <a href="{{ route('policy.show') }}" class="hover:text-[var(--paper)] transition-colors">Privacy</a>
                    <a href="{{ route('cookies') }}" class="hover:text-[var(--paper)] transition-colors">Cookies</a>
                    <a href="{{ route('terms.show') }}" class="hover:text-[var(--paper)] transition-colors">Terms</a>
                </div>
                <p class="font-mono text-xs tracking-wide text-[var(--mist)]">
                    &copy; {{ date('Y') }} Dot.Pulse. The community intelligence layer of the Dot ecosystem.
                </p>
            </div>
        </footer>

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

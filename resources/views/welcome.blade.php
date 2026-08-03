<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dot.Pulse — Community Intelligence</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{background:#09090b;color:#f4f4f5;font-family:'Inter',system-ui,sans-serif;font-size:15px;line-height:1.6;overflow-x:hidden}
        :root{--accent:#c084fc}
        .material-symbols-rounded{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;line-height:1;user-select:none}
        .btn-primary{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;border-radius:10px;background:#c084fc;color:#09090b;font-family:'Inter',sans-serif;font-size:15px;font-weight:700;text-decoration:none;transition:filter .15s}
        .btn-primary:hover{filter:brightness(1.1)}
        .btn-ghost{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;border-radius:10px;background:transparent;border:1px solid rgba(255,255,255,0.12);color:#a1a1aa;font-family:'Inter',sans-serif;font-size:15px;font-weight:600;text-decoration:none;transition:all .15s}
        .btn-ghost:hover{border-color:rgba(192,132,252,0.4);color:#f4f4f5}
        .feature-card{background:#141416;border:1px solid rgba(255,255,255,0.07);border-radius:14px;padding:1.75rem;transition:border-color .2s}
        .feature-card:hover{border-color:rgba(192,132,252,0.2)}
        .feature-icon{width:44px;height:44px;border-radius:12px;background:rgba(192,132,252,0.1);border:1px solid rgba(192,132,252,0.2);display:flex;align-items:center;justify-content:center;margin-bottom:1.25rem}
        .feature-icon .material-symbols-rounded{font-size:22px;color:#c084fc}
    </style>
</head>
<body>
    {{-- Nav --}}
    <nav style="position:sticky;top:0;z-index:50;background:rgba(9,9,11,0.85);backdrop-filter:blur(14px);border-bottom:1px solid rgba(255,255,255,0.06);padding:0 max(2rem,5vw);">
        <div style="max-width:1200px;margin:0 auto;height:60px;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:10px;">
                <img src="{{ asset('images/logo.png') }}" alt="Dot.Pulse" style="height:32px;width:auto;">
                <span style="font-family:'Syne',sans-serif;font-size:16px;font-weight:700;letter-spacing:-0.01em;">Dot.Pulse</span>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                @if(Route::has('login'))
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary" style="padding:8px 20px;font-size:14px;">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-ghost" style="padding:8px 20px;font-size:14px;">Sign in</a>
                        @if(Route::has('register'))
                            <a href="{{ route('register') }}" class="btn-primary" style="padding:8px 20px;font-size:14px;">Get started</a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section style="padding:7rem max(2rem,5vw) 5rem;text-align:center;position:relative;overflow:hidden;">
        <!-- Photographic Background: real diverse-team-collaborating-around-a-table photo by Vitaly Gariev (@silverkblack), unsplash.com/photos/diverse-team-collaborates-around-a-table-in-office-fm4B1xWEIsU -->
        <div style="position:absolute;inset:0;background-image:url('https://images.unsplash.com/photo-1758873269276-9518d0cb4a0b?q=80&amp;w=2400&amp;auto=format&amp;fit=crop');background-size:cover;background-position:center;"></div>
        <div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(9,9,11,0.90) 0%,rgba(9,9,11,0.94) 60%,#09090b 100%);"></div>
        <div style="position:absolute;top:-100px;left:50%;transform:translateX(-50%);width:800px;height:600px;background:radial-gradient(ellipse,rgba(192,132,252,0.08) 0%,transparent 65%);pointer-events:none;"></div>
        <div style="max-width:760px;margin:0 auto;position:relative;">
            <div style="display:inline-flex;align-items:center;gap:7px;padding:6px 14px;background:rgba(192,132,252,0.08);border:1px solid rgba(192,132,252,0.2);border-radius:100px;font-size:12px;font-weight:600;color:#c084fc;margin-bottom:2rem;">
                <span class="material-symbols-rounded" style="font-size:14px;">electric_bolt</span>
                Community Intelligence Platform
            </div>
            <h1 style="font-family:'Syne',sans-serif;font-size:clamp(2.5rem,6vw,4rem);font-weight:800;color:#f4f4f5;line-height:1.1;letter-spacing:-0.03em;margin-bottom:1.5rem;">
                The operating system<br>for business communities
            </h1>
            <p style="font-size:1.1rem;color:#71717a;max-width:580px;margin:0 auto 2.5rem;line-height:1.7;">
                Share ideas, ask questions, discover AI agents, and build your reputation — all inside one platform deeply integrated with the Dot ecosystem.
            </p>
            <div style="display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap;">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary">Go to Dashboard <span class="material-symbols-rounded" style="font-size:17px;">arrow_forward</span></a>
                @else
                    <a href="{{ route('register') }}" class="btn-primary">Get started — it's free <span class="material-symbols-rounded" style="font-size:17px;">arrow_forward</span></a>
                    <a href="{{ route('login') }}" class="btn-ghost">Sign in</a>
                @endauth
            </div>
        </div>
    </section>

    {{-- Features grid --}}
    <section style="padding:3rem max(2rem,5vw) 6rem;">
        <div style="max-width:1200px;margin:0 auto;">
            <div style="text-align:center;margin-bottom:3.5rem;">
                <h2 style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:700;color:#f4f4f5;letter-spacing:-0.02em;margin-bottom:0.75rem;">Everything your ecosystem needs</h2>
                <p style="font-size:15px;color:#71717a;">From LinkedIn-style profiles to Reddit-style communities to AI-powered moderation.</p>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem;">
                @foreach([
                    ['groups','Communities','Create or join industry communities. Public, private, or enterprise-scoped with rules and roles.'],
                    ['forum','Smart Feed','An AI-ranked feed showing posts most relevant to your role, industry, and current projects.'],
                    ['smart_toy','Marketplace','Discover and install AI agents, automations, templates, and integrations with one click.'],
                    ['event','Live Events','Host webinars, AMAs, product launches and town halls. RSVP and attend with your community.'],
                    ['auto_awesome','AI Moderation','Every post runs through Claude AI for spam detection, sentiment analysis, and content safety before publishing.'],
                    ['hub','Knowledge Graph','Every solved discussion becomes structured knowledge — topics, experts, solutions, and root causes — forever searchable.'],
                    ['workspace_premium','Reputation System','Earn points, badges, and verified expert status through helpful contributions.'],
                    ['chat_bubble_outline','Real-time Messaging','Direct messages, group chats, and in-conversation AI assistance.'],
                ] as [$icon, $title, $desc])
                    <div class="feature-card">
                        <div class="feature-icon">
                            <span class="material-symbols-rounded">{{ $icon }}</span>
                        </div>
                        <h3 style="font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;color:#f4f4f5;margin-bottom:0.5rem;">{{ $title }}</h3>
                        <p style="font-size:13px;color:#71717a;line-height:1.6;">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section style="padding:4rem max(2rem,5vw) 7rem;text-align:center;">
        <div style="max-width:600px;margin:0 auto;padding:3rem 2.5rem;background:#141416;border:1px solid rgba(192,132,252,0.15);border-radius:20px;">
            <h2 style="font-family:'Syne',sans-serif;font-size:1.75rem;font-weight:700;color:#f4f4f5;letter-spacing:-0.02em;margin-bottom:0.75rem;">See what's happening on Dot.Pulse</h2>
            <p style="font-size:14px;color:#71717a;margin-bottom:2rem;">Join thousands of businesses already sharing knowledge, discovering automation, and building the future together.</p>
            @guest
                <a href="{{ route('register') }}" class="btn-primary">Create your free account</a>
            @else
                <a href="{{ route('dashboard') }}" class="btn-primary">Go to your feed</a>
            @endguest
        </div>
    </section>

    <footer style="border-top:1px solid rgba(255,255,255,0.06);padding:2rem max(2rem,5vw);text-align:center;">
        <img src="{{ asset('images/logo.png') }}" alt="Dot.Pulse" style="height:26px;width:auto;opacity:0.85;margin-bottom:0.85rem;">
        <p style="font-size:12px;color:#3f3f46;">© {{ date('Y') }} Dot.Pulse · Community Intelligence Platform</p>
    </footer>
</body>
</html>

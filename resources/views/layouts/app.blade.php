<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dot.Pulse</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { corePlugins: { preflight: false } }</script>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; background: #0f0e17; color: #dae2fd; font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; line-height: 1; }
        [x-cloak] { display: none !important; }
        .sl { display:flex;align-items:center;gap:0.75rem;padding:0.65rem 1rem;border-radius:0.5rem;font-family:'Manrope',sans-serif;font-size:0.875rem;font-weight:600;text-decoration:none;color:#b7c8e1;opacity:0.75;transition:all 0.2s; }
        .sl:hover { background:rgba(26,36,56,0.9);opacity:1;color:#c4b5fd; }
        .sl.active { border-left:4px solid #7c3aed;background:rgba(124,58,237,0.1);color:#c4b5fd;opacity:1; }
        @keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:.4} }
    </style>
    @livewireStyles
    <script defer src="https://unpkg.com/alpinejs@3.10.2/dist/cdn.min.js"></script>
</head>
<body>
    <x-banner />

    <aside style="position:fixed;left:0;top:0;height:100vh;width:272px;background:#1a1528;border-right:1px solid rgba(124,58,237,0.15);z-index:50;overflow-y:auto;padding:1.75rem 1rem;display:flex;flex-direction:column;">
        <div style="margin-bottom:1.75rem;padding:0 0.5rem;">
            <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;gap:0.75rem;text-decoration:none;">
                <div style="width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg,#7c3aed,#4f46e5);display:flex;align-items:center;justify-content:center;">
                    <span class="material-symbols-outlined" style="font-size:18px;color:#fff;">hub</span>
                </div>
                <div>
                    <div style="font-family:'Manrope',sans-serif;font-size:1rem;font-weight:800;color:#c4b5fd;">Dot.Pulse</div>
                    <div style="font-size:0.58rem;font-weight:600;color:#8d90a2;letter-spacing:0.15em;text-transform:uppercase;">Community Intelligence</div>
                </div>
            </a>
        </div>

        <nav style="flex:1;display:flex;flex-direction:column;gap:0.15rem;">
            <a href="{{ route('dashboard') }}" class="sl {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="material-symbols-outlined" style="font-size:20px;">dynamic_feed</span>
                <span>Community Feed</span>
            </a>
        </nav>

        @auth
        <div style="margin-top:auto;padding-top:1.25rem;border-top:1px solid rgba(124,58,237,0.15);">
            <div style="display:flex;align-items:center;gap:0.75rem;padding:0 0.5rem;">
                <div style="width:36px;height:36px;border-radius:9999px;background:#7c3aed;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:#fff;flex-shrink:0;">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div style="min-width:0;">
                    <div style="font-size:0.75rem;font-weight:700;color:#dae2fd;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ Auth::user()->name }}</div>
                    <div style="font-size:0.6rem;color:#8d90a2;text-transform:uppercase;letter-spacing:0.08em;">Member</div>
                </div>
            </div>
        </div>
        @endauth
    </aside>

    @livewire('navigation-menu')

    <div style="margin-left:272px;padding-top:72px;min-height:100vh;background:#0f0e17;">
        @if(isset($header))
        <div style="padding:1.75rem 2.5rem 0;">{{ $header }}</div>
        @endif
        <main>{{ $slot }}</main>
    </div>

    <div style="position:fixed;bottom:1.5rem;right:1.5rem;display:flex;align-items:center;gap:0.5rem;padding:0.4rem 0.85rem;background:rgba(26,21,40,0.8);backdrop-filter:blur(16px);border-radius:9999px;border:1px solid rgba(124,58,237,0.2);z-index:50;">
        <div style="width:6px;height:6px;border-radius:9999px;background:#22c55e;animation:pulse-dot 2s infinite;"></div>
        <span style="font-size:0.58rem;font-weight:700;color:rgba(196,181,253,0.5);text-transform:uppercase;letter-spacing:0.18em;">Dot.Pulse Online</span>
    </div>

    @stack('modals')
    @livewireScripts
</body>
</html>

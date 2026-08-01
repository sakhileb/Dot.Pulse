<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dot.Pulse</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { corePlugins: { preflight: false } }</script>
    <style>
        :root { --accent: #c084fc; --accent-rgb: 192,132,252; }
        *, *::before, *::after { box-sizing: border-box; }
        body { margin:0; background:#09090b; color:#f4f4f5; font-family:'Inter',system-ui,sans-serif; font-size:14px; line-height:1.5; }
        .material-symbols-rounded { font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24; line-height:1; user-select:none; }
        [x-cloak] { display:none!important; }

        /* Sidebar */
        .sidebar { position:fixed; left:0; top:0; width:260px; height:100vh; background:#0d0d10; border-right:1px solid rgba(255,255,255,0.06); display:flex; flex-direction:column; z-index:40; overflow:hidden; }
        .sidebar::before { content:''; position:absolute; top:-80px; left:-80px; width:320px; height:320px; background:radial-gradient(circle, rgba(192,132,252,0.1) 0%, transparent 65%); pointer-events:none; }

        .sidebar-brand { padding:20px 18px 14px; display:flex; align-items:center; gap:11px; flex-shrink:0; }
        .brand-icon { width:36px; height:36px; border-radius:10px; background:rgba(192,132,252,0.12); border:1px solid rgba(192,132,252,0.22); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .brand-icon .material-symbols-rounded { font-size:18px; color:#c084fc; }
        .brand-name { font-family:'Syne',sans-serif; font-size:14.5px; font-weight:700; color:#f4f4f5; letter-spacing:-0.01em; line-height:1.2; }
        .brand-status { display:flex; align-items:center; gap:5px; margin-top:3px; }
        .live-dot { width:6px; height:6px; border-radius:50%; background:#c084fc; flex-shrink:0; animation:live-pulse 2.8s ease-in-out infinite; }
        @keyframes live-pulse { 0%,100% { opacity:1; box-shadow:0 0 0 0 rgba(192,132,252,0.45); } 60% { opacity:.6; box-shadow:0 0 0 5px rgba(192,132,252,0); } }
        .brand-subtitle { font-size:10px; font-weight:500; color:#3f3f46; text-transform:uppercase; letter-spacing:0.09em; }

        .sidebar-divider { height:1px; background:rgba(255,255,255,0.06); margin:4px 14px 8px; }
        .sidebar-nav { padding:0 10px; flex:1; overflow-y:auto; scrollbar-width:none; }
        .sidebar-nav::-webkit-scrollbar { display:none; }
        .nav-section-label { font-size:10px; font-weight:600; color:#3f3f46; text-transform:uppercase; letter-spacing:0.1em; padding:14px 8px 5px; }
        .nav-item { display:flex; align-items:center; gap:9px; padding:7.5px 10px; border-radius:8px; font-size:13px; font-weight:500; color:#71717a; text-decoration:none; transition:background .13s,color .13s,transform .13s; margin-bottom:1px; }
        .nav-item:hover { background:rgba(255,255,255,0.05); color:#d4d4d8; transform:translateX(1px); }
        .nav-item.active { background:rgba(192,132,252,0.1); color:#c084fc; font-weight:600; }
        .nav-icon { font-size:17px; width:20px; text-align:center; flex-shrink:0; }

        .sidebar-footer { padding:10px 14px 14px; border-top:1px solid rgba(255,255,255,0.06); flex-shrink:0; }
        .user-row { display:flex; align-items:center; gap:9px; padding:8px 6px; border-radius:8px; }
        .user-avatar { width:28px; height:28px; border-radius:50%; background:rgba(192,132,252,0.18); border:1px solid rgba(192,132,252,0.28); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#c084fc; flex-shrink:0; font-family:'Syne',sans-serif; }
        .user-name { font-size:12px; font-weight:600; color:#d4d4d8; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .user-team { font-size:10px; color:#52525b; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

        /* Topbar */
        .topbar { position:fixed; top:0; left:260px; right:0; height:54px; background:rgba(9,9,11,0.85); backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px); border-bottom:1px solid rgba(255,255,255,0.06); display:flex; align-items:center; padding:0 22px; z-index:30; gap:12px; }
        .topbar-title { font-family:'Syne',sans-serif; font-size:14px; font-weight:700; color:#f4f4f5; flex:1; }
        .topbar-team { font-size:11px; color:#52525b; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.07); border-radius:6px; padding:3px 8px; font-weight:500; white-space:nowrap; }
        .topbar-btn { width:30px; height:30px; border-radius:7px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); display:flex; align-items:center; justify-content:center; color:#71717a; cursor:pointer; transition:background .13s,color .13s; text-decoration:none; flex-shrink:0; }
        .topbar-btn:hover { background:rgba(255,255,255,0.09); color:#d4d4d8; }
        .topbar-btn .material-symbols-rounded { font-size:17px; }

        /* Content */
        .content-wrap { margin-left:260px; padding-top:54px; min-height:100vh; }

        /* Shared UI tokens */
        .dot-card { background:#141416; border:1px solid rgba(255,255,255,0.07); border-radius:12px; }
        .dot-card:hover { border-color:rgba(255,255,255,0.11); }
        .metric-val { font-family:'JetBrains Mono',monospace; font-weight:500; letter-spacing:-0.02em; }
        .dot-input { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:8px; color:#f4f4f5; font-family:'Inter',sans-serif; font-size:13px; padding:8px 12px; width:100%; transition:border-color .15s,box-shadow .15s; outline:none; }
        .dot-input:focus { border-color:rgba(192,132,252,0.45); box-shadow:0 0 0 3px rgba(192,132,252,0.07); }
        .dot-input::placeholder { color:#3f3f46; }
        .dot-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; transition:all .14s; border:none; text-decoration:none; font-family:'Inter',sans-serif; }
        .dot-btn-primary { background:#c084fc; color:#09090b; }
        .dot-btn-primary:hover { filter:brightness(1.1); }
        .dot-btn-ghost { background:rgba(255,255,255,0.06); color:#a1a1aa; border:1px solid rgba(255,255,255,0.08); }
        .dot-btn-ghost:hover { background:rgba(255,255,255,0.1); color:#f4f4f5; }
        .dot-badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:100px; font-size:11px; font-weight:600; }
        .dot-badge-accent { background:rgba(192,132,252,0.12); color:#c084fc; }
        /* Mobile sidebar */
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); transition: transform .22s ease; }
            .sidebar.open { transform: translateX(0); }
            .topbar { left: 0; }
            .content-wrap { margin-left: 0; }
            .mobile-overlay { display: block !important; }
        }
        .mobile-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 39; }
    </style>
    @livewireStyles
    <script defer src="https://unpkg.com/alpinejs@3.10.2/dist/cdn.min.js"></script>
</head>
<body x-data="{ sidebarOpen: false }">
    <x-banner />

    <div class="mobile-overlay" @click="sidebarOpen = false" :class="{ 'hidden': !sidebarOpen }"></div>

    <aside class="sidebar" :class="{ 'open': sidebarOpen }">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <span class="material-symbols-rounded">groups</span>
            </div>
            <div>
                <div class="brand-name">Dot.Pulse</div>
                <div class="brand-status">
                    <div class="live-dot"></div>
                    <span class="brand-subtitle">Community Intelligence</span>
                </div>
            </div>
        </div>

        <div class="sidebar-divider"></div>

        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="material-symbols-rounded nav-icon">dashboard</span>
                Dashboard
            </a>

            <div class="nav-section-label">Discover</div>
            <a href="{{ route('communities.index') }}" class="nav-item {{ request()->routeIs('communities.*') ? 'active' : '' }}">
                <span class="material-symbols-rounded nav-icon">groups</span>
                Communities
            </a>
            <a href="{{ route('events.index') }}" class="nav-item {{ request()->routeIs('events.*') ? 'active' : '' }}">
                <span class="material-symbols-rounded nav-icon">event</span>
                Events
            </a>
            <a href="{{ route('marketplace.index') }}" class="nav-item {{ request()->routeIs('marketplace.*') ? 'active' : '' }}">
                <span class="material-symbols-rounded nav-icon">storefront</span>
                Marketplace
            </a>
            <a href="{{ route('search') }}" class="nav-item {{ request()->routeIs('search') ? 'active' : '' }}">
                <span class="material-symbols-rounded nav-icon">search</span>
                Search
            </a>

            <div class="nav-section-label">You</div>
            <a href="{{ auth()->check() ? route('profile.public', auth()->user()->name) : '#' }}" class="nav-item {{ request()->routeIs('profile.public') && request()->route('username') === auth()->user()?->name ? 'active' : '' }}">
                <span class="material-symbols-rounded nav-icon">account_circle</span>
                My Profile
            </a>
            <a href="{{ route('messages.index') }}" class="nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}">
                <span class="material-symbols-rounded nav-icon">chat_bubble_outline</span>
                Messages
            </a>
            <a href="{{ route('notifications.index') }}" class="nav-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                <span class="material-symbols-rounded nav-icon">notifications</span>
                Notifications
            </a>
            <a href="{{ route('pulse-profile.edit') }}" class="nav-item {{ request()->routeIs('pulse-profile.edit') ? 'active' : '' }}">
                <span class="material-symbols-rounded nav-icon">tune</span>
                Pulse Settings
            </a>
            <a href="{{ route('profile.show') }}" class="nav-item {{ request()->routeIs('profile.show') ? 'active' : '' }}">
                <span class="material-symbols-rounded nav-icon">manage_accounts</span>
                Account
            </a>

            @auth
            @php
                $currentProfile = \App\Models\PulseProfile::where('user_id', auth()->id())->first();
                $isMod = $currentProfile && in_array($currentProfile->role, ['moderator', 'admin']);
            @endphp
            @if($isMod)
            <div class="nav-section-label">Admin</div>
            <a href="{{ route('moderation.index') }}" class="nav-item {{ request()->routeIs('moderation.*') ? 'active' : '' }}">
                <span class="material-symbols-rounded nav-icon" style="color:#f87171;">shield</span>
                Moderation
            </a>
            @endif
            @endauth
        </nav>

        @auth
        <div class="sidebar-footer">
            <div class="user-row">
                <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                <div style="min-width:0;flex:1;">
                    <div class="user-name">{{ Auth::user()->name }}</div>
                    <div class="user-team">{{ Auth::user()->currentTeam->name ?? 'Personal' }}</div>
                </div>
            </div>
        </div>
        @endauth
    </aside>

    <header class="topbar">
        <button @click="sidebarOpen = !sidebarOpen" class="topbar-btn" style="display:none;" x-show="true" x-cloak>
            <span class="material-symbols-rounded">menu</span>
        </button>
        <style>@media(max-width:900px){.topbar-hamburger{display:flex!important}}</style>
        <button class="topbar-btn topbar-hamburger" style="display:none;" @click="sidebarOpen = !sidebarOpen">
            <span class="material-symbols-rounded">menu</span>
        </button>
        <div class="topbar-title">
            @isset($header){{ $header }}@else Dot.Pulse
            @endisset
        </div>
        <a href="{{ route('communities.create') }}" class="dot-btn dot-btn-primary" style="font-size:12px;padding:5px 12px;text-decoration:none;">
            <span class="material-symbols-rounded" style="font-size:14px;">add</span>
            New Post
        </a>
        <a href="{{ route('search') }}" class="topbar-btn" title="Search">
            <span class="material-symbols-rounded">search</span>
        </a>
        @auth
        <span class="topbar-team">{{ Auth::user()->currentTeam->name ?? 'Personal' }}</span>
        <livewire:pulse.notification-bell />
        @endauth
        <a href="{{ route('profile.show') }}" class="topbar-btn" title="Profile">
            <span class="material-symbols-rounded">account_circle</span>
        </a>
    </header>

    @livewire('navigation-menu')

    <div class="content-wrap">
        <main>{{ $slot }}</main>
    </div>

    @stack('modals')
    @livewireScripts
</body>
</html>

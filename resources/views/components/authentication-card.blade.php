<div class="relative min-h-screen flex flex-col sm:justify-center items-center pt-10 sm:pt-0 px-5 overflow-hidden">
    {{-- Same hero photo as welcome.blade.php (group of people using laptop computers, by Annie
    Spratt), with the same dark-ink scrim. --}}
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=2400&auto=format&fit=crop');"></div>
    <div class="absolute inset-0" style="background: radial-gradient(ellipse 68% 62% at 50% 40%, rgba(10,40,54,0.9) 0%, rgba(10,40,54,0.68) 45%, rgba(10,40,54,0.35) 74%, rgba(10,40,54,0.12) 100%);"></div>
    <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(10,40,54,0.6) 0%, transparent 18%, transparent 74%, rgba(10,40,54,0.5) 100%);"></div>

    <div class="relative z-10">
        {{ $logo }}
    </div>

    <div class="relative z-10 w-full sm:max-w-md mt-8 px-6 sm:px-8 py-8 bg-[var(--ink-soft)] border border-[var(--line)] shadow-2xl overflow-hidden sm:rounded-xl">
        {{ $slot }}
    </div>
</div>

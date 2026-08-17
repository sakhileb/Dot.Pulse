<div class="relative min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4 overflow-hidden">
    {{-- Same hero photo as welcome.blade.php (Keysight oscilloscope tracing a live square-wave
    signal, by Doug Baney), reused as-is so the auth pages carry the same photographic identity
    as the welcome hero. --}}
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1621638363255-9c092fa8d4ab?q=80&w=2400&auto=format&fit=crop');"></div>
    <div class="absolute inset-0" style="background: radial-gradient(ellipse 68% 62% at 50% 40%, rgba(9,9,11,0.92) 0%, rgba(9,9,11,0.72) 45%, rgba(9,9,11,0.38) 74%, rgba(9,9,11,0.14) 100%);"></div>
    <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(9,9,11,0.6) 0%, transparent 18%, transparent 74%, rgba(9,9,11,0.55) 100%);"></div>

    <div class="relative z-10">
        {{ $logo }}
    </div>

    <div {{ $attributes->merge(['class' => 'relative z-10 w-full sm:max-w-md mt-6 px-6 py-6 sm:py-8 rounded-xl border shadow-2xl overflow-hidden']) }} style="background: var(--panel); border-color: var(--line);">
        {{ $slot }}
    </div>
</div>

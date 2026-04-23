<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Client Portal' }} — ProjexFlow</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600|syne:600,700,800|dm-mono:400,500&display=swap" rel="stylesheet"/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#080c14] text-[#dde6f0] antialiased" style="font-family:'Inter',sans-serif">

    {{-- Minimal portal top bar --}}
    <header class="sticky top-0 z-30 flex items-center justify-between px-5 h-14 bg-[#080c14]/95 backdrop-blur border-b border-[#1c2e45]">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 flex items-center justify-center rounded-lg bg-[#7EE8A2]/10 border border-[#7EE8A2]/20">
                <svg width="14" height="14" viewBox="0 0 32 32" fill="none">
                    <path d="M6 8h10M6 14h14M6 20h7" stroke="#7EE8A2" stroke-width="2.5" stroke-linecap="round"/>
                    <circle cx="24" cy="22" r="5" stroke="#7EE8A2" stroke-width="2"/>
                    <path d="M24 20v2l1.5 1.5" stroke="#7EE8A2" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </div>
            <span style="font-family:'Syne',sans-serif;font-weight:800;color:#fff;font-size:15px;letter-spacing:-.2px">
                ProjexFlow
            </span>
        </div>
        <span class="text-xs text-[#506070] font-mono uppercase tracking-widest">Client Portal</span>
    </header>

    {{ $slot }}

    {{-- Toast --}}
    <div
        x-data="{ toasts: [] }"
        @toast.window="toasts.push({ id: Date.now(), ...$event.detail[0] }); setTimeout(() => toasts.shift(), 3500)"
        class="fixed bottom-5 right-5 z-50 flex flex-col gap-2"
    >
        <template x-for="t in toasts" :key="t.id">
            <div x-transition
                class="px-4 py-3 rounded-xl text-sm font-medium shadow-xl max-w-sm"
                :class="{
                    'bg-emerald-950/90 border border-emerald-700/40 text-emerald-300': t.type === 'success',
                    'bg-red-950/90 border border-red-700/40 text-red-300': t.type === 'error',
                    'bg-blue-950/90 border border-blue-700/40 text-blue-300': !t.type || t.type === 'info',
                }"
                x-text="t.message"
            ></div>
        </template>
    </div>

    @livewireScripts
    @fluxScripts
</body>
</html>

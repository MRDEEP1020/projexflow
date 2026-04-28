<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Admin' }} — ProjexFlow Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600|syne:600,700,800|dm-mono:400,500&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#05080f] text-[#dde6f0] antialiased" style="font-family:'Inter',sans-serif">

<div class="flex h-screen overflow-hidden">

    {{-- Admin sidebar --}}
    <aside class="w-56 flex-shrink-0 flex flex-col bg-[#080c14] border-r border-[#1c2e45] overflow-y-auto">

        {{-- Brand --}}
        <div class="flex items-center gap-2.5 px-4 h-14 border-b border-[#1c2e45]">
            <div class="w-6 h-6 rounded-md bg-red-500/15 border border-red-500/30 flex items-center justify-center">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2.5" stroke-linecap="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <div>
                <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:13px;color:#fff">ProjexFlow</span>
                <span class="block text-[9px] font-mono text-red-400 uppercase tracking-widest">Admin</span>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 p-3 space-y-1">
            @php
                $adminLinks = [
                    ['route' => 'admin.dashboard',   'icon' => 'chart-bar',        'label' => 'Dashboard'],
                    ['route' => 'admin.users',        'icon' => 'users',            'label' => 'Users'],
                    ['route' => 'admin.disputes',     'icon' => 'exclamation-triangle', 'label' => 'Disputes'],
                    ['route' => 'admin.withdrawals',  'icon' => 'banknotes',        'label' => 'Withdrawals'],
                    ['route' => 'admin.moderation',   'icon' => 'shield-check',     'label' => 'Moderation'],
                ];
            @endphp

            @foreach($adminLinks as $link)
                <a href="{{ route($link['route']) }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-all
                       {{ request()->routeIs($link['route'])
                           ? 'bg-red-500/10 text-red-400 border border-red-500/20'
                           : 'text-[#8da0b8] hover:text-[#dde6f0] hover:bg-[#131d2e]' }}">
                    <flux:icon :name="$link['icon']" class="size-4 flex-shrink-0"/>
                    {{ $link['label'] }}
                </a>
            @endforeach

            <div class="pt-3 mt-3 border-t border-[#1c2e45]">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-[#506070] hover:text-[#dde6f0] transition-all">
                    <flux:icon.arrow-left class="size-4"/>
                    Back to app
                </a>
            </div>
        </nav>

        {{-- Admin badge --}}
        <div class="p-3 border-t border-[#1c2e45]">
            <div class="flex items-center gap-2 px-2 py-1.5">
                <flux:avatar name="{{ Auth::user()->name }}" size="xs"/>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-[9px] font-mono text-red-400 uppercase">Super Admin</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- Main content --}}
    <main class="flex-1 overflow-y-auto">
        {{-- Top bar --}}
        <div class="sticky top-0 z-20 h-14 flex items-center justify-between px-6 bg-[#05080f]/95 backdrop-blur border-b border-[#1c2e45]">
            <h1 style="font-family:'Syne',sans-serif;font-weight:700;font-size:16px;color:#fff">
                {{ $title ?? 'Admin' }}
            </h1>
            <div class="flex items-center gap-2">
                <span class="text-xs text-[#506070]">{{ now()->format('M d, Y H:i') }}</span>
                <div class="w-1.5 h-1.5 rounded-full bg-[#7EE8A2] animate-pulse"></div>
            </div>
        </div>

        <div class="p-6">
            {{ $slot }}
        </div>
    </main>
</div>

{{-- Toast --}}
<div x-data="{ toasts: [] }"
     @toast.window="toasts.push({ id: Date.now(), ...$event.detail[0] }); setTimeout(() => toasts.shift(), 3500)"
     class="fixed bottom-5 right-5 z-50 flex flex-col gap-2">
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
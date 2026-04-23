<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'ProjexFlow') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Mono:wght@300;400;500&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <!-- Tailwind CSS CDN + base layer overrides for dark theme -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom Tailwind overrides to match original dark design tokens */
        @layer base {
            :root {
                --bg: #080c14;
                --surface: #0e1420;
                --surface2: #131d2e;
                --surface3: #1a2438;
                --border: #1c2e45;
                --border2: #254060;
                --accent: #7EE8A2;
                --accent2: #00ff94;
                --text: #dde6f0;
                --dim: #8da0b8;
                --muted: #506070;
                --danger: #ef4444;
                --warning: #f59e0b;
                --success: #10b981;
                --sidebar-w: 240px;
            }
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }
            body {
                background-color: var(--bg);
                color: var(--text);
                font-family: 'Inter', sans-serif;
                -webkit-font-smoothing: antialiased;
            }
            /* Custom scrollbar - Tailwind doesn't cover this */
            ::-webkit-scrollbar { width: 5px; height: 5px; }
            ::-webkit-scrollbar-track { background: var(--bg); }
            ::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 3px; }
        }
        /* Additional utilities for backdrop blur and transitions not fully covered */
        .backdrop-blur-custom {
            backdrop-filter: blur(12px);
        }
        .transition-sidebar {
            transition: transform 0.25s ease;
        }
        .sidebar-overlay-transition {
            transition-property: opacity;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 200ms;
        }
    </style>
</head>
<body class="antialiased">

{{-- ── App shell with Tailwind ────────────────── --}}
<div class="flex min-h-screen bg-[#080c14]" x-data="{ sidebarOpen: false }">

    {{-- ── Sidebar (Tailwind + responsive transforms) ── --}}
    <aside class="fixed lg:sticky top-0 left-0 bottom-0 z-50 w-[var(--sidebar-w)] flex-shrink-0 bg-[#0e1420] border-r border-[#1c2e45] flex flex-col transition-sidebar transform -translate-x-full lg:translate-x-0"
           :class="{ 'translate-x-0': sidebarOpen }">

        {{-- Logo --}}
        <div class="px-[18px] pt-5 pb-4 border-b border-[#1c2e45]">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5 no-underline">
                <svg width="28" height="28" viewBox="0 0 32 32" fill="none">
                    <rect width="32" height="32" rx="7" fill="#7EE8A2" fill-opacity="0.12"/>
                    <path d="M8 10h10M8 16h14M8 22h7" stroke="#7EE8A2" stroke-width="2.5" stroke-linecap="round"/>
                    <circle cx="24" cy="22" r="4" stroke="#7EE8A2" stroke-width="2"/>
                    <path d="M24 20v2l1.5 1.5" stroke="#7EE8A2" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <span class="font-['Syne',sans-serif] text-base font-extrabold text-white tracking-tight">ProjexFlow</span>
            </a>
        </div>

        {{-- Org switcher --}}
        <div class="px-3 py-3 pb-2 border-b border-[#1c2e45]">
            @livewire('backend.org-switcher')
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto py-3">
            <div class="mb-5">
                <span class="block font-['DM_Mono',monospace] text-[10px] uppercase tracking-wider text-[#506070] px-4 mb-1">Workspace</span>

                <a href="{{ route('dashboard') }}" wire:navigate
                   class="flex items-center gap-2.5 px-4 py-2 font-['Inter',sans-serif] text-[13.5px] text-[#8da0b8] no-underline transition-all duration-150 relative hover:text-[#dde6f0] hover:bg-white/5 {{ request()->routeIs('dashboard') ? 'text-[#7EE8A2] bg-[#7EE8A2]/10' : '' }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 opacity-70 group-hover:opacity-100"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Dashboard
                    @if(request()->routeIs('dashboard'))<span class="absolute left-0 top-1 bottom-1 w-0.5 bg-[#7EE8A2] rounded-r"></span>@endif
                </a>

                <a href="{{ route('backend.projectList') }}" wire:navigate
                   class="flex items-center gap-2.5 px-4 py-2 font-['Inter',sans-serif] text-[13.5px] text-[#8da0b8] no-underline transition-all duration-150 relative hover:text-[#dde6f0] hover:bg-white/5 {{ request()->routeIs('backend.project*') ? 'text-[#7EE8A2] bg-[#7EE8A2]/10' : '' }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 opacity-70"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                    Projects
                    @if(request()->routeIs('backend.project*'))<span class="absolute left-0 top-1 bottom-1 w-0.5 bg-[#7EE8A2] rounded-r"></span>@endif
                </a>

                <a href="{{ route('my-tasks') }}" wire:navigate
                   class="flex items-center gap-2.5 px-4 py-2 font-['Inter',sans-serif] text-[13.5px] text-[#8da0b8] no-underline transition-all duration-150 relative hover:text-[#dde6f0] hover:bg-white/5 {{ request()->routeIs('my-tasks') ? 'text-[#7EE8A2] bg-[#7EE8A2]/10' : '' }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                    My Tasks
                    @if(request()->routeIs('my-tasks'))<span class="absolute left-0 top-1 bottom-1 w-0.5 bg-[#7EE8A2] rounded-r"></span>@endif
                </a>

                <a href="{{ route('backend.calendar') }}" wire:navigate
                   class="flex items-center gap-2.5 px-4 py-2 font-['Inter',sans-serif] text-[13.5px] text-[#8da0b8] no-underline transition-all duration-150 relative hover:text-[#dde6f0] hover:bg-white/5 {{ request()->routeIs('backend.calendar') ? 'text-[#7EE8A2] bg-[#7EE8A2]/10' : '' }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Calendar
                    @if(request()->routeIs('backend.calendar'))<span class="absolute left-0 top-1 bottom-1 w-0.5 bg-[#7EE8A2] rounded-r"></span>@endif
                </a>

                <a href="{{ route('backend.projectArchived') }}" wire:navigate
                   class="flex items-center gap-2.5 px-4 py-2 font-['Inter',sans-serif] text-[13.5px] text-[#8da0b8] no-underline transition-all duration-150 relative hover:text-[#dde6f0] hover:bg-white/5 {{ request()->routeIs('archive') ? 'text-[#7EE8A2] bg-[#7EE8A2]/10' : '' }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Archived
                    @if(request()->routeIs('backend.projectArchived'))<span class="absolute left-0 top-1 bottom-1 w-0.5 bg-[#7EE8A2] rounded-r"></span>@endif
                </a>
            </div>

            <div class="mb-5">
                <span class="block font-['DM_Mono',monospace] text-[10px] uppercase tracking-wider text-[#506070] px-4 mb-1">Marketplace</span>

                <a href="{{ route('backend.marketplace') }}" wire:navigate
                   class="flex items-center gap-2.5 px-4 py-2 font-['Inter',sans-serif] text-[13.5px] text-[#8da0b8] no-underline transition-all duration-150 relative hover:text-[#dde6f0] hover:bg-white/5 {{ request()->routeIs('marketplace*') ? 'text-[#7EE8A2] bg-[#7EE8A2]/10' : '' }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                    Marketplace
                    @if(request()->routeIs('backend.marketplace'))<span class="absolute left-0 top-1 bottom-1 w-0.5 bg-[#7EE8A2] rounded-r"></span>@endif
                </a>

                <a href="{{ route('backend.bookingInbox') }}" wire:navigate
                   class="flex items-center gap-2.5 px-4 py-2 font-['Inter',sans-serif] text-[13.5px] text-[#8da0b8] no-underline transition-all duration-150 relative hover:text-[#dde6f0] hover:bg-white/5 {{ request()->routeIs('backend.bookingInbox') ? 'text-[#7EE8A2] bg-[#7EE8A2]/10' : '' }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                    Bookings
                    @if(request()->routeIs('backend.bookingInbox'))<span class="absolute left-0 top-1 bottom-1 w-0.5 bg-[#7EE8A2] rounded-r"></span>@endif
                </a>

                <a href="{{ route('backend.wallet') }}" wire:navigate
                   class="flex items-center gap-2.5 px-4 py-2 font-['Inter',sans-serif] text-[13.5px] text-[#8da0b8] no-underline transition-all duration-150 relative hover:text-[#dde6f0] hover:bg-white/5 {{ request()->routeIs('backend.wallet') ? 'text-[#7EE8A2] bg-[#7EE8A2]/10' : '' }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 010-4h14v4"/><path d="M3 5v14a2 2 0 002 2h16v-5"/><path d="M18 12a2 2 0 000 4h4v-4z"/></svg>
                    Wallet
                    @if(request()->routeIs('backend.wallet'))<span class="absolute left-0 top-1 bottom-1 w-0.5 bg-[#7EE8A2] rounded-r"></span>@endif
                </a>
            </div>
        </nav>

        {{-- Sidebar footer user --}}
        <div class="p-3 border-t border-[#1c2e45]">
            <a href="{{ route('settings.profile') }}" wire:navigate class="flex items-center gap-2.5 p-2 rounded-lg no-underline transition-colors duration-150 hover:bg-[#131d2e] cursor-pointer w-full">
                <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-8 h-8 rounded-full object-cover border border-[#254060] shrink-0">
                <div class="flex-1 min-w-0">
                    <span class="block font-['Inter',sans-serif] text-[12.5px] font-medium text-[#dde6f0] truncate">{{ Auth::user()->name }}</span>
                    <span class="block font-['Inter',sans-serif] text-[11px] text-[#506070] truncate">{{ Auth::user()->email }}</span>
                </div>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="text-[#506070] shrink-0"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
            </a>
        </div>
    </aside>

    {{-- ── Main area with proper margin-left on lg ── --}}
    <div class="flex-1 min-w-0 flex flex-col">

        {{-- Top navbar --}}
        <header class="sticky top-0 z-40 flex items-center gap-4 px-6 h-14 bg-[#080c14]/95 backdrop-blur-custom border-b border-[#1c2e45]">
            {{-- Mobile menu toggle --}}
            <button class="lg:hidden flex items-center bg-transparent border-none text-[#8da0b8] cursor-pointer p-1" @click="sidebarOpen = !sidebarOpen" aria-label="Toggle menu">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

            <div class="flex-1 font-['Syne',sans-serif] text-[15px] font-semibold text-[#dde6f0]">
                {{ $header ?? '' }}
            </div>

            <div class="flex items-center gap-2">
                {{-- Notification bell component --}}
                @livewire('backend.notification-bell')

                {{-- User menu dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center gap-1.5 bg-transparent border-none cursor-pointer text-[#8da0b8] p-1 rounded-md hover:bg-[#131d2e] transition-colors" :aria-expanded="open">
                        <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-7 h-7 rounded-full object-cover border border-[#254060]">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="absolute right-0 top-full mt-2 w-56 bg-[#0e1420] border border-[#1c2e45] rounded-xl overflow-hidden shadow-xl z-50"
                         x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" style="display: none;">
                        <div class="px-3.5 py-3">
                            <span class="block font-['Inter',sans-serif] text-[13px] font-medium text-[#dde6f0]">{{ Auth::user()->name }}</span>
                            <span class="block font-['Inter',sans-serif] text-[11.5px] text-[#506070] mt-0.5">{{ Auth::user()->email }}</span>
                        </div>
                        <div class="h-px bg-[#1c2e45]"></div>
                        <a href="{{ route('settings.profile') }}" wire:navigate class="flex items-center gap-2.5 px-3.5 py-2.5 font-['Inter',sans-serif] text-[13px] text-[#8da0b8] no-underline transition-all hover:text-[#dde6f0] hover:bg-[#131d2e]">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Profile Settings
                        </a>
                        @if(Auth::user()->is_marketplace_enabled)
                        <a href="{{ route('backend.profilePage', Auth::user()) }}" wire:navigate class="flex items-center gap-2.5 px-3.5 py-2.5 font-['Inter',sans-serif] text-[13px] text-[#8da0b8] no-underline transition-all hover:text-[#dde6f0] hover:bg-[#131d2e]">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                            My Public Profile
                        </a>
                        @endif
                        <div class="h-px bg-[#1c2e45]"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2.5 px-3.5 py-2.5 font-['Inter',sans-serif] text-[13px] text-[#8da0b8] no-underline transition-all hover:text-[#ef4444] hover:bg-[#131d2e] text-left">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 p-7 lg:p-9">
            {{ $slot }}
        </main>
    </div>

    {{-- Mobile sidebar overlay --}}
    <div class="fixed inset-0 bg-black/60 z-40 lg:hidden sidebar-overlay-transition"
         x-show="sidebarOpen" @click="sidebarOpen = false"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;"></div>
</div>

{{-- Toast notifications (Tailwind styled) --}}
<div id="toast-container" class="fixed bottom-6 right-6 z-[200] flex flex-col gap-2"
     x-data="{ toasts: [] }"
     @toast.window="toasts.push({ id: Date.now(), ...$event.detail[0] }); setTimeout(() => toasts.shift(), 3500)">
    <template x-for="toast in toasts" :key="toast.id">
        <div class="px-4 py-3 rounded-lg font-['Inter',sans-serif] text-[13.5px] font-medium max-w-xs shadow-lg"
             :class="{
                'bg-emerald-500/15 border border-emerald-500/30 text-emerald-300': toast.type === 'success',
                'bg-red-500/12 border border-red-500/25 text-red-300': toast.type === 'error',
                'bg-blue-500/12 border border-blue-500/25 text-blue-300': toast.type === 'info',
                'bg-amber-500/12 border border-amber-500/25 text-amber-300': toast.type === 'warning'
             }"
             x-transition>
            <span x-text="toast.message"></span>
        </div>
    </template>
</div>

@livewireScripts
@fluxScripts
</body>
</html>
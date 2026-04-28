<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600|syne:600,700,800|dm-mono:400,500&display=swap"
        rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{--
    ─────────────────────────────────────────────────────────────────
    Dynamic accent color based on active_mode.

    DetectUserMode middleware shares $userMode with all views.
    Client mode  → blue  (#60a5fa) — communicates trust, stability
    Freelancer   → green (#7EE8A2) — communicates growth, earnings
    Admin (admin routes) → always defaults to app.blade.php override

    We output a <style> tag with CSS custom properties so Flux's
    --color-accent is overridden at runtime without any build step.
    ─────────────────────────────────────────────────────────────────
    --}}
    @php
        $mode = session('active_mode', 'client');
        $accent = $mode === 'freelancer' ? '126 232 162' : '96 165 250';
    @endphp
    <style>
        :root,
        .dark {
            --color-accent: {{ $accent }};
        }

        :root,
        .dark {
            --header-bg: rgba(8, 12, 20, 0.85);
        }

        .backdrop-blur-custom {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
    </style>
</head>

<body class="min-h-screen bg-[#080c14] text-[#dde6f0] antialiased flex" style="font-family:'Inter',sans-serif">
    <flux:sidebar sticky clamp>
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        {{-- ── Brand (changes label based on mode) ─────────────────── --}}
        <a href="{{ $mode === 'freelancer' ? route('freelancer.dashboard') : route('client.dashboard') }}" wire:navigate
            class="flex items-center gap-2.5 px-2 mb-2">
            <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                style="background:rgba(var(--color-accent)/.1);border:1px solid rgba(var(--color-accent)/.2)">
                <svg width="14" height="14" viewBox="0 0 32 32" fill="none">
                    <path d="M6 8h10M6 14h14M6 20h7" stroke="rgb(var(--color-accent))" stroke-width="2.5"
                        stroke-linecap="round" />
                    <circle cx="24" cy="22" r="5" stroke="rgb(var(--color-accent))" stroke-width="2" />
                    <path d="M24 20v2l1.5 1.5" stroke="rgb(var(--color-accent))" stroke-width="1.5"
                        stroke-linecap="round" />
                </svg>
            </div>
            <div>
                <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:15px;color:#fff">
                    ProjexFlow
                </span>
                <span class="block text-[9px] font-mono uppercase tracking-widest"
                    style="color:rgb(var(--color-accent))">
                    {{ ucfirst($mode) }}
                </span>
            </div>
        </a>

        {{-- ── Mode switcher ──────────────────────────────────────────── --}}
        <div class="px-2 mb-3">
            @livewire('backend.mode-switcher')
        </div>

        {{-- ═══════════════════════════════════════════════════════════
         CLIENT NAV — shown when active_mode = 'client'
    ═══════════════════════════════════════════════════════════════ --}}
        @if ($mode === 'client')
            <flux:navlist variant="outline">

                <flux:navlist.group heading="Overview" class="mt-1">
                    <flux:navlist.item icon="home" href="{{ route('client.dashboard') }}" wire:navigate
                        :current="request()->routeIs('client.dashboard')">
                        Dashboard
                    </flux:navlist.item>
                    <flux:navlist.item icon="calendar-days" href="{{ route('backend.calendar') }}" wire:navigate
                        :current="request()->routeIs('backend.calendar')">
                        Calendar
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group heading="My projects" class="mt-2">
                    <flux:navlist.item icon="rectangle-group" href="{{ route('backend.projectList') }}" wire:navigate
                        :current="request()->routeIs('backend.project*')">
                        Projects
                    </flux:navlist.item>
                    <flux:navlist.item icon="check-circle" href="{{ route('my-tasks') }}" wire:navigate
                        :current="request()->routeIs('my-tasks')">
                        Tasks
                    </flux:navlist.item>
                    <flux:navlist.item icon="archive-box" href="{{ route('backend.projectArchived') }}" wire:navigate
                        :current="request()->routeIs('backend.projectArchived')">
                        Archive
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group heading="Hire talent" class="mt-2">
                    <flux:navlist.item icon="magnifying-glass" href="{{ route('client.marketplace') }}" wire:navigate
                        :current="request()->routeIs('client.marketplace')">
                        Find freelancers
                    </flux:navlist.item>
                    <flux:navlist.item icon="briefcase" href="{{ route('backend.jobBoard') }}" wire:navigate
                        :current="request()->routeIs('backend.jobBoard')">
                        Job board
                    </flux:navlist.item>
                    <flux:navlist.item icon="clipboard-document-list" href="{{ route('backend.myJobs') }}"
                        wire:navigate :current="request()->routeIs('backend.myJobs')">
                        My job posts
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group heading="Payments" class="mt-2">
                    <flux:navlist.item icon="document-text" href="{{ route('backend.contracts') }}" wire:navigate
                        :current="request()->routeIs('backend.contracts')">
                        Contracts
                    </flux:navlist.item>
                    <flux:navlist.item icon="banknotes" href="{{ route('backend.wallet') }}" wire:navigate
                        :current="request()->routeIs('backend.wallet')">
                        Wallet
                    </flux:navlist.item>
                    <flux:navlist.item icon="calendar" href="{{ route('backend.bookingInbox') }}" wire:navigate
                        :current="request()->routeIs('backend.bookingInbox')">
                        Bookings
                    </flux:navlist.item>
                </flux:navlist.group>

            </flux:navlist>
        @endif

        {{-- ═══════════════════════════════════════════════════════════
         FREELANCER NAV — shown when active_mode = 'freelancer'
    ═══════════════════════════════════════════════════════════════ --}}
        @if ($mode === 'freelancer')
            <flux:navlist variant="outline">

                <flux:navlist.group heading="Overview" class="mt-1">
                    <flux:navlist.item icon="home" href="{{ route('freelancer.dashboard') }}" wire:navigate
                        :current="request()->routeIs('freelancer.dashboard')">
                        Dashboard
                    </flux:navlist.item>
                    <flux:navlist.item icon="calendar-days" href="{{ route('backend.calendar') }}" wire:navigate
                        :current="request()->routeIs('backend.calendar')">
                        Calendar
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group heading="My work" class="mt-2">
                    <flux:navlist.item icon="check-circle" href="{{ route('my-tasks') }}" wire:navigate
                        :current="request()->routeIs('my-tasks')">
                        My tasks
                    </flux:navlist.item>
                    <flux:navlist.item icon="rectangle-group" href="{{ route('backend.projectList') }}" wire:navigate
                        :current="request()->routeIs('backend.project*')">
                        Projects I'm on
                    </flux:navlist.item>
                    <flux:navlist.item icon="paper-airplane" href="{{ route('backend.myApplications') }}" wire:navigate
                        :current="request()->routeIs('backend.myApplications')">
                        My applications
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group heading="Marketplace" class="mt-2">
                    <flux:navlist.item icon="user-circle" href="{{ route('backend.editProfile') }}" wire:navigate
                        :current="request()->routeIs('backend.editProfile')">
                        My profile
                    </flux:navlist.item>
                    <flux:navlist.item icon="magnifying-glass" href="{{ route('backend.jobBoard') }}" wire:navigate
                        :current="request()->routeIs('backend.jobBoard')">
                        Browse jobs
                    </flux:navlist.item>
                    <flux:navlist.item icon="calendar" href="{{ route('backend.availabilitySettings') }}" wire:navigate
                        :current="request()->routeIs('backend.availabilitySettings')">
                        Availability
                    </flux:navlist.item>
                    <flux:navlist.item icon="star" href="{{ route('backend.profilePage', Auth::user()->name) }}"
                        wire:navigate>
                        Public profile ↗
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group heading="Earnings" class="mt-2">
                    <flux:navlist.item icon="document-text" href="{{ route('backend.contracts') }}" wire:navigate
                        :current="request()->routeIs('backend.contracts')">
                        Contracts
                    </flux:navlist.item>
                    <flux:navlist.item icon="banknotes" href="{{ route('backend.wallet') }}" wire:navigate
                        :current="request()->routeIs('backend.wallet')">
                        Wallet & payouts
                    </flux:navlist.item>
                    <flux:navlist.item icon="video-camera" href="{{ route('backend.bookingInbox') }}" wire:navigate
                        :current="request()->routeIs('backend.bookingInbox')">
                        Booking inbox
                    </flux:navlist.item>
                </flux:navlist.group>

            </flux:navlist>
        @endif

        <flux:spacer />

        {{-- ── User footer ─────────────────────────────────────────────── --}}
        <div class="border-t border-[#1c2e45] pt-3 pb-1 px-1">
            <flux:dropdown position="top" align="start" class="w-full">
                <flux:button variant="ghost" class="w-full !justify-start gap-2.5 px-2">
                    <flux:avatar name="{{ Auth::user()->name }}" src="{{ Auth::user()->avatar_url }}"
                        size="xs" />
                    <div class="flex-1 min-w-0 text-left">
                        <p class="text-xs font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                        <p class="text-[10px] truncate" style="color:rgb(var(--color-accent))">
                            {{ ucfirst($mode) }} mode
                        </p>
                    </div>
                    <flux:icon.chevron-up-down class="size-3.5 text-[#506070] flex-shrink-0" />
                </flux:button>

                <flux:menu class="w-52">
                    <flux:menu.item icon="user-circle" href="{{ route('settings.profile') }}" wire:navigate>
                        Profile settings
                    </flux:menu.item>
                    @if ($mode === 'freelancer')
                        <flux:menu.item icon="star" href="{{ route('backend.editProfile') }}" wire:navigate>
                            Marketplace profile
                        </flux:menu.item>
                    @endif
                    @if ($mode === 'client')
                        <flux:menu.item icon="building-office" href="{{ route('backend.create') }}" wire:navigate>
                            New organization
                        </flux:menu.item>
                    @endif
                    @if (Auth::user()->isAdmin())
                        <flux:menu.separator />
                        <flux:menu.item icon="shield-check" href="{{ route('admin.dashboard') }}"
                            class="text-red-400">
                            Admin panel
                        </flux:menu.item>
                    @endif
                    <flux:menu.separator />
                    <flux:menu.item icon="square-arrow-right-enter" href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit()">
                        Sign out
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
                @csrf
            </form>
        </div>
    </flux:sidebar>

    {{-- ── Main content area ────────────────────────────────────────── --}}
    {{-- MAIN CONTENT WITH CUSTOM NAVBAR --}}
    <div class="flex-1 flex flex-col min-h-screen">
        {{-- Top navbar with theme switcher --}}
        <header
            class="sticky top-0 z-40 h-[52px] flex items-center gap-3 px-5
               bg-[rgba(8,12,20,0.88)] backdrop-blur-md
               border-b border-[#1c2e45] shrink-0">

            {{-- Mobile toggle --}}
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" />

            {{-- Page title --}}
            <div class="flex-1 font-['Syne',sans-serif] text-[14px] font-bold text-[#dde6f0]">
                {{ $header ?? '' }}
            </div>

            <div class="flex items-center gap-1.5">

                {{-- Theme picker --}}
                <div class="relative" x-data="{
                    open: false,
                    themes: [
                        { id: 'dark', label: 'Dark', icon: '🌑' },
                        { id: 'light', label: 'Light', icon: '☀️' },
                        { id: 'ocean', label: 'Ocean', icon: '🌊' },
                        { id: 'midnight', label: 'Midnight', icon: '🌌' },
                        { id: 'forest', label: 'Forest', icon: '🌿' },
                    ],
                    current: localStorage.getItem('theme') || 'dark',
                    set(id) {
                        this.current = id;
                        this.open = false;
                        document.documentElement.setAttribute('data-theme', id);
                        localStorage.setItem('theme', id);
                    }
                }">
                    <button @click="open = !open"
                        class="flex items-center gap-2 h-[34px] px-2.5
                       bg-[#0f1c2e] border border-[#1c2e45] rounded-lg
                       text-[#8da0b8] text-[12px] font-medium
                       hover:bg-[#14243a] hover:border-[#2a4060] transition-all">
                        <span x-text="themes.find(t=>t.id===current)?.icon ?? '🌑'"></span>
                        <span class="hidden sm:inline"
                            x-text="themes.find(t=>t.id===current)?.label ?? 'Dark'"></span>
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    </button>

                    <div x-show="open" @click.away="open = false"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute right-0 top-full mt-2 w-44
                        bg-[#0d1928] border border-[#1c2e45] rounded-xl
                        shadow-2xl overflow-hidden z-50"
                        style="display:none">
                        <div class="px-3 py-2 border-b border-[#1c2e45]">
                            <span class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Theme</span>
                        </div>
                        <template x-for="t in themes" :key="t.id">
                            <button @click="set(t.id)"
                                class="w-full flex items-center gap-3 px-3 py-2.5 text-[13px] text-left
                               hover:bg-[#14243a] transition-colors"
                                :class="current === t.id ? 'text-[#60a5fa]' : 'text-[#8da0b8]'">
                                <span x-text="t.icon" style="font-size:14px"></span>
                                <span class="flex-1" x-text="t.label"></span>
                                <svg x-show="current === t.id" width="12" height="12" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Notification bell --}}
                @livewire('backend.notification-bell')

                {{-- User chip --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                        class="flex items-center gap-2 h-[34px] pl-1.5 pr-2.5
                       bg-[#0f1c2e] border border-[#1c2e45] rounded-lg
                       hover:bg-[#14243a] hover:border-[#2a4060] transition-all">
                        <img src="{{ Auth::user()->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name) . '&background=1d4ed8&color=fff' }}"
                            alt="{{ Auth::user()->name }}"
                            class="w-6 h-6 rounded-full object-cover border border-[rgba(96,165,250,.3)]">
                        <span class="hidden sm:inline text-[12px] font-medium text-[#dde6f0] max-w-[100px] truncate">
                            {{ Str::before(Auth::user()->name, ' ') }}
                        </span>
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#506070"
                            stroke-width="2.5">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    </button>

                    <div x-show="open" @click.away="open = false"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute right-0 top-full mt-2 w-56
            bg-[#0d1928] border border-[#1c2e45] rounded-xl
            shadow-2xl overflow-hidden z-50"
                        style="display:none">

                        {{-- User info header --}}
                        <div class="px-3.5 py-3 border-b border-[#1c2e45]">
                            <p class="text-[13px] font-semibold text-[#dde6f0]">{{ Auth::user()->name }}</p>
                            <p class="text-[11px] text-[#506070] mt-0.5">{{ Auth::user()->email }}</p>
                        </div>

                        {{-- Profile --}}
                        <a href="{{ route('settings.profile') }}" wire:navigate
                            class="flex items-center gap-2.5 px-3.5 py-2.5 text-[13px] text-[#8da0b8] no-underline hover:bg-[#14243a] hover:text-[#dde6f0] transition-colors">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            Profile settings
                        </a>

                        {{-- Notifications --}}
                        @php
                            $unread = \App\Models\Notification::where('user_id', Auth::id())
                                ->whereNull('read_at')
                                ->count();
                        @endphp
                        <a href="{{ route('backend.notifications') }}" wire:navigate
                            class="flex items-center gap-2.5 px-3.5 py-2.5 text-[13px] text-[#8da0b8] no-underline hover:bg-[#14243a] hover:text-[#dde6f0] transition-colors">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8">
                                <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                <path d="M13.73 21a2 2 0 01-3.46 0" />
                            </svg>
                            Notifications
                            @if ($unread > 0)
                                <span
                                    class="ml-auto text-[10px] font-bold bg-red-500/20 text-red-400 border border-red-500/30 rounded-full px-1.5 py-0.5 leading-none">
                                    {{ $unread > 9 ? '9+' : $unread }}
                                </span>
                            @endif
                        </a>

                        {{-- Settings --}}
                        <a href="{{ route('settings.profile') }}" wire:navigate
                            class="flex items-center gap-2.5 px-3.5 py-2.5 text-[13px] text-[#8da0b8] no-underline hover:bg-[#14243a] hover:text-[#dde6f0] transition-colors">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8">
                                <path d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                                <path
                                    d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z" />
                            </svg>
                            Settings
                        </a>

                        @if ($mode === 'freelancer')
                            <div class="h-px bg-[#1c2e45] my-1"></div>
                            <a href="{{ route('backend.editProfile') }}" wire:navigate
                                class="flex items-center gap-2.5 px-3.5 py-2.5 text-[13px] text-[#8da0b8] no-underline hover:bg-[#14243a] hover:text-[#dde6f0] transition-colors">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8">
                                    <polygon
                                        points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                </svg>
                                Marketplace profile
                            </a>
                        @endif

                        @if ($mode === 'client')
                            <div class="h-px bg-[#1c2e45] my-1"></div>
                            <a href="{{ route('backend.create') }}" wire:navigate
                                class="flex items-center gap-2.5 px-3.5 py-2.5 text-[13px] text-[#8da0b8] no-underline hover:bg-[#14243a] hover:text-[#dde6f0] transition-colors">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                    <polyline points="9 22 9 12 15 12 15 22" />
                                </svg>
                                New organization
                            </a>
                        @endif

                        @if (Auth::user()->isAdmin())
                            <div class="h-px bg-[#1c2e45] my-1"></div>
                            <a href="{{ route('admin.dashboard') }}"
                                class="flex items-center gap-2.5 px-3.5 py-2.5 text-[13px] text-red-400 no-underline hover:bg-[#14243a] transition-colors">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                </svg>
                                Admin panel
                            </a>
                        @endif

                        <div class="h-px bg-[#1c2e45] my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center gap-2.5 px-3.5 py-2.5 text-[13px] text-[#8da0b8] hover:text-red-400 hover:bg-[#14243a] transition-colors text-left">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4" />
                                    <polyline points="16 17 21 12 16 7" />
                                    <line x1="21" y1="12" x2="9" y2="12" />
                                </svg>
                                Sign out
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </header>

        {{-- Page content - Remove extra padding if content already has it --}}
        <main class="flex-1 overflow-y-auto">
            {{ $slot }}
        </main>
    </div>

    {{-- ── Toast system ─────────────────────────────────────────────── --}}
    <div x-data="{ toasts: [] }"
        @toast.window="toasts.push({ id: Date.now(), ...$event.detail[0] }); setTimeout(() => toasts.shift(), 3500)"
        class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none">
        <template x-for="t in toasts" :key="t.id">
            <div x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="px-4 py-3 rounded-xl text-sm font-medium shadow-2xl max-w-sm pointer-events-auto"
                :class="{
                    'bg-emerald-950/95 border border-emerald-700/50 text-emerald-300': t.type === 'success',
                    'bg-red-950/95 border border-red-700/50 text-red-300': t.type === 'error',
                    'bg-amber-950/95 border border-amber-700/50 text-amber-300': t.type === 'warning',
                    'bg-[#0d1520]/95 border border-[#1c2e45] text-[#8da0b8]': !t.type || t.type === 'info',
                }"
                x-text="t.message"></div>
        </template>
    </div>

    @livewireScripts
    @fluxScripts
</body>

</html>

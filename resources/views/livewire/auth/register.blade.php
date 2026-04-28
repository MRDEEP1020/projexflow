<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Organization;
use App\Models\OrganizationMember;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

new #[Layout('components.layouts.auth')] #[Title('Create Account')] class extends Component
{
    public string $name                  = '';
    public string $email                 = '';
    public string $password              = '';
    public string $password_confirmation = '';
    public bool   $terms                 = false;

    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

    public function register(): void
    {
        $validated = $this->validate([
            'name'                  => ['required', 'string', 'max:100'],
            'email'                 => ['required', 'string', 'email', 'max:191', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
            'terms'                 => ['accepted'],
        ], [
            'terms.accepted' => 'You must accept the Terms of Service to continue.',
        ]);

        DB::transaction(function () use ($validated) {
            $user = \App\Models\User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'slug'     => $this->uniqueSlug(Str::slug($validated['name'])),
                'password' => $validated['password'],
                'timezone' => 'UTC',
                'is_marketplace_enabled' => false,
            ]);

            $slug = $this->uniqueSlug(Str::slug($validated['name']));
            $org  = Organization::create([
                'name'     => $validated['name'] . "'s Workspace",
                'slug'     => $slug,
                'owner_id' => $user->id,
                'type'     => 'personal',
                'plan'     => 'free',
            ]);

            OrganizationMember::create([
                'org_id'    => $org->id,
                'user_id'   => $user->id,
                'role'      => 'owner',
                'joined_at' => now(),
            ]);

            event(new Registered($user));
            Auth::login($user);
        });

        $this->redirect(route('verification.notice'), navigate: true);
    }

    protected function uniqueSlug(string $base): string
    {
        $slug = $base ?: 'workspace';
        $n    = 2;
        while (Organization::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
            if ($n > 100) { $slug = $base . '-' . Str::random(6); break; }
        }
        return $slug;
    }
}; ?>

<div class="flex min-h-screen bg-[var(--bg)]">
    
    {{-- Left Panel (Hidden on mobile, shown on lg screens) --}}
    <div class="hidden lg:flex flex-1 relative bg-gradient-to-br from-[#080c14] via-[#0d1825] to-[#080c14] border-r border-[var(--border)] overflow-hidden">
        {{-- Background gradient overlay --}}
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_50%_at_30%_60%,rgba(126,232,162,0.06),transparent_70%)]"></div>
        
        <div class="relative z-10 flex flex-col p-12 w-full animate-in fade-in duration-600">
            {{-- Brand --}}
            <div class="flex items-center gap-2.5 mb-18">
                <div class="w-8 h-8 rounded-lg bg-[rgba(126,232,162,0.12)] flex items-center justify-center">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <path d="M8 10h10M8 16h14M8 22h7" stroke="#7EE8A2" stroke-width="2.5" stroke-linecap="round"/>
                        <circle cx="24" cy="22" r="4" stroke="#7EE8A2" stroke-width="2"/>
                        <path d="M24 20v2l1.5 1.5" stroke="#7EE8A2" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <span class="font-['Syne',sans-serif] text-[20px] font-extrabold text-white tracking-tight">ProjexFlow</span>
            </div>

            {{-- Main content --}}
            <div class="mb-10">
                <div class="font-['Syne',sans-serif] text-[clamp(30px,3.5vw,44px)] font-extrabold text-white leading-tight tracking-[-1px] mb-4">
                    Your workspace.<br>
                    <span class="text-[var(--accent)]">Your rules.</span>
                </div>
                <div class="font-['Inter',sans-serif] text-[15px] text-[var(--dim)] leading-relaxed max-w-[380px]">
                    Join thousands of professionals managing projects, delivering work, and getting paid — on one platform built for how you actually work.
                </div>
            </div>

            {{-- Feature list --}}
            <ul class="flex flex-col gap-3">
                @foreach([
                    'Free forever for personal projects',
                    'Invite your team in seconds',
                    'Client portal included — no extra charge',
                    'GitHub, video, payments — all built in',
                ] as $feature)
                    <li class="flex items-center gap-2.5 font-['Inter',sans-serif] text-[14px] text-[var(--dim)]">
                        <span class="w-5.5 h-5.5 shrink-0 flex items-center justify-center bg-[rgba(126,232,162,0.1)] border border-[rgba(126,232,162,0.2)] rounded-full text-[var(--accent)]">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        {{ $feature }}
                    </li>
                @endforeach
            </ul>

            {{-- Decorative grid --}}
            <div class="absolute bottom-0 left-0 right-0 h-50 flex flex-col justify-end opacity-8 pointer-events-none">
                @for($i = 0; $i < 6; $i++)
                    <div class="w-full h-px bg-gradient-to-r from-transparent via-[var(--accent)] to-transparent mb-7"></div>
                @endfor
            </div>
        </div>
    </div>

    {{-- Right Panel (Form) --}}
    <div class="flex items-center justify-center flex-1 w-full lg:flex-[0_0_480px] min-h-screen p-8 lg:p-12">
        <div class="w-full max-w-[380px] animate-in fade-up duration-400">
            
            {{-- Header --}}
            <div class="mb-6">
                <div class="flex items-center gap-1.5 mb-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-[var(--accent)] animate-pulse"></div>
                    <div class="font-['DM_Mono',monospace] text-[11px] uppercase tracking-[1.5px] text-[var(--accent)]">
                        Get started free
                    </div>
                </div>
                <div class="font-['Syne',sans-serif] text-[24px] font-bold text-white mb-1.5 tracking-[-0.4px]">
                    Create your account
                </div>
                <div class="font-['Inter',sans-serif] text-[13.5px] text-[var(--dim)]">
                    Already have one? 
                    <a href="{{ route('login') }}" wire:navigate class="text-[var(--accent)] hover:underline no-underline">
                        Sign in
                    </a>
                </div>
            </div>

            <form wire:submit="register" class="flex flex-col gap-4">
                {{-- Full Name --}}
                <div class="flex flex-col gap-1.5">
                    <label for="name" class="text-[12.5px] font-medium text-[var(--dim)]">
                        Full name
                    </label>
                    <div class="relative">
                        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--muted)]">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            id="name" 
                            wire:model="name" 
                            class="w-full bg-[var(--surface)] border border-[var(--border)] rounded-lg px-3 py-3 pl-9 text-[13.5px] text-[var(--text)] placeholder:text-[var(--muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-3 focus:ring-[rgba(126,232,162,0.1)] transition-all"
                            :class="{'border-red-500': $wire.{{ $errors->has('name') ? 'true' : 'false' }}}"
                            placeholder="Alex Johnson" 
                            autocomplete="name" 
                            autofocus
                        >
                    </div>
                    @error('name')
                        <div class="text-[11.5px] text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-[12.5px] font-medium text-[var(--dim)]">
                        Work email
                    </label>
                    <div class="relative">
                        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--muted)]">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <input 
                            type="email" 
                            id="email" 
                            wire:model="email" 
                            class="w-full bg-[var(--surface)] border border-[var(--border)] rounded-lg px-3 py-3 pl-9 text-[13.5px] text-[var(--text)] placeholder:text-[var(--muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-3 focus:ring-[rgba(126,232,162,0.1)] transition-all"
                            :class="{'border-red-500': $wire.{{ $errors->has('email') ? 'true' : 'false' }}}"
                            placeholder="you@company.com" 
                            autocomplete="email"
                        >
                    </div>
                    @error('email')
                        <div class="text-[11.5px] text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center gap-1.5">
                        <label for="password" class="text-[12.5px] font-medium text-[var(--dim)]">
                            Password
                        </label>
                        <span class="text-[11px] font-normal text-[var(--muted)]">
                            min. 8 characters
                        </span>
                    </div>
                    <div x-data="{ show: false }" class="relative">
                        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--muted)]">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                <rect x="3" y="11" width="18" height="11" rx="2"/>
                                <path d="M7 11V7a5 5 0 0110 0v4"/>
                            </svg>
                        </div>
                        <input 
                            :type="show ? 'text' : 'password'" 
                            id="password" 
                            wire:model="password" 
                            class="w-full bg-[var(--surface)] border border-[var(--border)] rounded-lg px-3 py-3 pl-9 pr-11 text-[13.5px] text-[var(--text)] placeholder:text-[var(--muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-3 focus:ring-[rgba(126,232,162,0.1)] transition-all"
                            :class="{'border-red-500': $wire.{{ $errors->has('password') ? 'true' : 'false' }}}"
                            placeholder="••••••••" 
                            autocomplete="new-password"
                        >
                        <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--muted)] hover:text-[var(--text)] transition-colors">
                            <svg x-show="!show" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg x-show="show" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <div class="text-[11.5px] text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div class="flex flex-col gap-1.5">
                    <label for="password_confirmation" class="text-[12.5px] font-medium text-[var(--dim)]">
                        Confirm password
                    </label>
                    <div class="relative">
                        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--muted)]">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <input 
                            type="password" 
                            id="password_confirmation" 
                            wire:model="password_confirmation" 
                            class="w-full bg-[var(--surface)] border border-[var(--border)] rounded-lg px-3 py-3 pl-9 text-[13.5px] text-[var(--text)] placeholder:text-[var(--muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-3 focus:ring-[rgba(126,232,162,0.1)] transition-all"
                            :class="{'border-red-500': $wire.{{ $errors->has('password_confirmation') ? 'true' : 'false' }}}"
                            placeholder="••••••••" 
                            autocomplete="new-password"
                        >
                    </div>
                    @error('password_confirmation')
                        <div class="text-[11.5px] text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Terms Checkbox --}}
                <div class="flex flex-col">
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="terms" class="hidden">
                        <span class="w-[17px] h-[17px] min-w-[17px] border border-[var(--border2)] rounded bg-[var(--surface)] transition-all duration-150 mt-0.5 flex items-center justify-center"
                              :class="{'bg-[var(--accent)] border-[var(--accent)]': $wire.terms}">
                            <svg x-show="$wire.terms" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#080c14" stroke-width="3" stroke-linecap="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="font-['Inter',sans-serif] text-[12.5px] text-[var(--dim)] leading-relaxed">
                            I agree to the <a href="#" class="text-[var(--accent)] hover:underline">Terms</a> and <a href="#" class="text-[var(--accent)] hover:underline">Privacy Policy</a>
                        </span>
                    </label>
                    @error('terms')
                        <div class="text-[11.5px] text-red-400 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="flex items-center justify-center gap-1.5 w-full py-3 mt-1 bg-[var(--accent)] text-[#080c14] rounded-lg font-['Syne',sans-serif] text-[14.5px] font-bold transition-all duration-200 hover:bg-[var(--accent2)] hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(126,232,162,0.25)] disabled:opacity-70 disabled:cursor-not-allowed relative overflow-hidden group" wire:loading.attr="disabled">
                    <span class="absolute inset-0 bg-gradient-to-br from-white/15 to-transparent pointer-events-none"></span>
                    <span wire:loading.remove class="flex items-center gap-1.5">
                        Create free account
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </span>
                    <span wire:loading class="flex items-center gap-1.5">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="animate-spin">
                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                        </svg>
                        Creating your workspace…
                    </span>
                </button>
            </form>

            {{-- Footer Note --}}
            <div class="mt-4.5 text-center font-['Inter',sans-serif] text-[12px] text-[var(--muted)]">
                No credit card required. Free forever on personal projects.
            </div>
        </div>
    </div>
</div>
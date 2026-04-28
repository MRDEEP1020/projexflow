<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Flux\Flux;

new #[Layout('components.layouts.auth')] #[Title('Sign In')] class extends Component {
    public string $email = '';
    public string $password = '';
    public bool $remember = false;
    public ?string $status = null;

    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirect(route('dashboard'), navigate: true);
        }
        $this->status = session('status');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited();

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        // Restore last active org context
        $lastOrgId = Auth::user()->orgMemberships()->latest('joined_at')->value('org_id');
        Session::put('active_org_id', $lastOrgId);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('Too many login attempts. Please try again in :seconds seconds.', [
                'seconds' => $seconds,
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email) . '|' . request()->ip());
    }
}; ?>

<div class="min-h-screen bg-white dark:bg-zinc-950 flex">
    
    {{-- Left Panel: Branding --}}
    <div class="hidden lg:flex lg:flex-1 relative bg-gradient-to-br from-zinc-50 via-white to-zinc-50 dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950 border-r border-zinc-200 dark:border-zinc-800 overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_50%_at_30%_60%,rgba(34,197,94,0.03)_0%,transparent_70%),radial-gradient(ellipse_40%_30%_at_80%_20%,rgba(34,197,94,0.02)_0%,transparent_60%)] pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col p-12 xl:p-16 w-full">
            {{-- Logo --}}
            <div class="flex items-center gap-3 mb-20">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10">
                    <svg width="22" height="22" viewBox="0 0 32 32" fill="none">
                        <path d="M8 10h10M8 16h14M8 22h7" stroke="#10b981" stroke-width="2.5" stroke-linecap="round"/>
                        <circle cx="24" cy="22" r="4" stroke="#10b981" stroke-width="2"/>
                        <path d="M24 20v2l1.5 1.5" stroke="#10b981" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <span class="font-bold text-xl tracking-tight text-zinc-900 dark:text-white">ProjexFlow</span>
            </div>

            {{-- Headline --}}
            <div class="max-w-md">
                <h1 class="font-bold text-4xl xl:text-5xl tracking-tight text-zinc-900 dark:text-white leading-[1.1] mb-5">
                    Ship projects.<br>
                    <span class="text-emerald-600 dark:text-emerald-400">Get paid.</span><br>
                    Grow your craft.
                </h1>
                <p class="text-zinc-600 dark:text-zinc-400 leading-relaxed">
                    The platform for professionals who deliver.
                    Manage teams, show clients real progress,
                    and close contracts — without switching tabs.
                </p>
            </div>

            {{-- Feature list --}}
            <div class="mt-12 space-y-4">
                @foreach([
                    ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'label' => 'Multi-team project management'],
                    ['icon' => 'M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z', 'label' => 'Built-in video meetings + AI transcripts'],
                    ['icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Escrow payments with auto-release'],
                    ['icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'label' => 'Verified reviews from real projects'],
                ] as $feature)
                    <div class="flex items-center gap-3.5">
                        <div class="w-8 h-8 flex items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-600 dark:text-emerald-400">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="{{ $feature['icon'] }}"/>
                            </svg>
                        </div>
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $feature['label'] }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Decorative grid --}}
            <div class="absolute bottom-0 left-0 right-0 h-48 flex flex-col justify-end gap-0 opacity-[0.04] pointer-events-none">
                @for($i = 0; $i < 5; $i++)
                    <div class="w-full h-px bg-gradient-to-r from-transparent via-emerald-500 to-transparent mb-6"></div>
                @endfor
            </div>
        </div>
    </div>

    {{-- Right Panel: Form --}}
    <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
        <div class="w-full max-w-md">
            
            {{-- Status message --}}
            @if (session('status'))
                <flux:callout variant="success" icon="check-circle" class="mb-6">
                    {{ session('status') }}
                </flux:callout>
            @endif

            {{-- Form header --}}
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                    <span class="font-mono text-[11px] uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Welcome back</span>
                </div>
                <h2 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white mb-2">Sign in to ProjexFlow</h2>
                <p class="text-zinc-600 dark:text-zinc-400">
                    No account?
                    <a href="{{ route('register') }}" wire:navigate class="font-medium text-emerald-600 dark:text-emerald-400 hover:underline">Create one free</a>
                </p>
            </div>

            {{-- Login form --}}
            <form wire:submit="login" class="space-y-5">
                {{-- Email --}}
                <div class="space-y-1.5">
                    <flux:label for="email">Email address</flux:label>
                    <flux:input 
                        id="email" 
                        type="email" 
                        wire:model="email" 
                        placeholder="you@company.com"
                        icon="envelope"
                        autocomplete="email"
                        autofocus
                        required 
                    />
                    @error('email')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <flux:label for="password">Password</flux:label>
                        @if (Route::has('password.request'))
                            <flux:link href="{{ route('password.request') }}" wire:navigate size="sm">Forgot password?</flux:link>
                        @endif
                    </div>
                    <flux:input 
                        id="password" 
                        type="password" 
                        wire:model="password" 
                        placeholder="••••••••••"
                        icon="lock-closed"
                        autocomplete="current-password"
                        required 
                    />
                    @error('password')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div class="flex items-center justify-between">
                    <flux:checkbox label="Keep me signed in for 30 days" wire:model="remember" />
                </div>

                {{-- Submit button --}}
                <flux:button 
                    type="submit" 
                    variant="primary" 
                    class="w-full justify-center gap-2"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove class="flex items-center gap-2">
                        Sign in
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </span>
                    <span wire:loading class="flex items-center gap-2">
                        <flux:icon.loading class="w-4 h-4 animate-spin" />
                        Signing in...
                    </span>
                </flux:button>
            </form>

            {{-- Divider --}}
            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-zinc-200 dark:border-zinc-800"></div>
                </div>
                <div class="relative flex justify-center text-xs">
                    <span class="px-3 bg-white dark:bg-zinc-950 text-zinc-500 dark:text-zinc-400">or continue with</span>
                </div>
            </div>

            {{-- OAuth buttons --}}
            <div class="grid grid-cols-2 gap-2.5">
                <flux:button variant="outline" class="gap-2 justify-center" disabled title="Coming soon">
                    {{-- <flux:icon.github class="w-4 h-4" /> --}}
                    GitHub
                </flux:button>
                <flux:button variant="outline" class="gap-2 justify-center" disabled title="Coming soon">
                    {{-- <flux:icon.google class="w-4 h-4" /> --}}
                    Google
                </flux:button>
            </div>

            {{-- Footer --}}
            <p class="mt-6 text-center text-xs text-zinc-500 dark:text-zinc-500">
                By signing in you agree to our
                <a href="#" class="text-emerald-600 dark:text-emerald-400 hover:underline">Terms</a> and
                <a href="#" class="text-emerald-600 dark:text-emerald-400 hover:underline">Privacy Policy</a>
            </p>

        </div>
    </div>
</div>
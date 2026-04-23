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

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
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
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
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
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div x-data="{ showPassword: false }" >
    {{-- Left Panel --}}
    <div class="hidden lg:flex lg:w-1/2 relative bg-gradient-to-br from-emerald-600 to-teal-700 overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 1px); background-size: 32px 32px;"></div>
        </div>
        
        <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-black/20"></div>
        
        <div class="relative z-10 flex flex-col justify-between p-12 w-full">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/10 backdrop-blur-sm rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-6 9 6-9 6-9-6z"/>
                            <path d="M3 15l9 6 9-6"/>
                            <path d="M3 12l9 6 9-6"/>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-white tracking-tight">ProjexFlow</span>
                </div>
            </div>

            <div class="my-auto py-12">
                <div class="max-w-md">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 backdrop-blur-sm rounded-full mb-8">
                        <div class="w-1.5 h-1.5 bg-emerald-300 rounded-full animate-pulse"></div>
                        <span class="text-xs font-medium text-white/90 uppercase tracking-wider">Trusted by 10,000+ teams</span>
                    </div>

                    <h1 class="text-6xl font-bold text-white mb-6 leading-tight">
                        Ship projects.<br>
                        <span class="text-emerald-300">Get paid.</span><br>
                        Grow your craft.
                    </h1>
                    
                    <p class="text-white/80 text-lg leading-relaxed mb-12">
                        The platform for professionals who deliver. Manage teams, show clients real progress, and close contracts — all in one place.
                    </p>

                    <div class="space-y-4">
                        <div class="flex items-start gap-3 text-white/90">
                            <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium mb-1">Multi-team project management</div>
                                <div class="text-sm text-white/60">Collaborate seamlessly across departments</div>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3 text-white/90">
                            <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium mb-1">Built-in video meetings + AI transcripts</div>
                                <div class="text-sm text-white/60">Record, transcribe, and search every conversation</div>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3 text-white/90">
                            <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium mb-1">Escrow payments with auto-release</div>
                                <div class="text-sm text-white/60">Secure payments released upon milestones</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-white/40 text-sm">
                © 2024 ProjexFlow. All rights reserved.
            </div>
        </div>
    </div>

    {{-- Right Panel --}}
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 lg:p-12 bg-white dark:bg-gray-900">
        <div class="w-full max-w-md">
            <div class="lg:hidden flex justify-center mb-8">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-emerald-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-6 9 6-9 6-9-6z"/>
                            <path d="M3 15l9 6 9-6"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900 dark:text-white">ProjexFlow</span>
                </div>
            </div>

            @if ($status)
                <div class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl">
                    <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-400">
                        <flux:icon.check-circle class="w-4 h-4" />
                        <span class="text-sm">{{ $status }}</span>
                    </div>
                </div>
            @endif

            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 dark:bg-emerald-950/30 rounded-full mb-4">
                    <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></div>
                    <span class="text-xs font-medium text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Welcome back</span>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Sign in to ProjexFlow</h2>
                <p class="text-gray-600 dark:text-gray-400">
                    Don't have an account? 
                    <a href="{{ route('register') }}" wire:navigate class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 font-medium transition">
                        Create one free
                    </a>
                </p>
            </div>

            <form wire:submit="login" class="space-y-6">
                <div>
                    <flux:input 
                        wire:model="email" 
                        label="Email address" 
                        type="email" 
                        name="email" 
                        required 
                        autofocus 
                        autocomplete="email" 
                        placeholder="you@company.com"
                    />
                    @error('email')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex justify-between items-end mb-1.5">
                        <flux:label>Password</flux:label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" wire:navigate class="text-xs text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 transition">
                                Forgot password?
                            </a>
                        @endif
                    </div>
                    <div class="relative">
                        <flux:input 
                            wire:model="password" 
                            x-bind:type="showPassword ? 'text' : 'password'"
                            name="password" 
                            required 
                            autocomplete="current-password" 
                            placeholder="••••••••"
                        />
                        <button 
                            type="button" 
                            x-on:click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition z-10"
                        >
                            <svg x-show="!showPassword" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg x-show="showPassword" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <flux:checkbox 
                        wire:model="remember" 
                        label="Keep me signed in" 
                    />
                    
                    <div class="text-xs text-gray-500 dark:text-gray-500 flex items-center gap-1">
                        <flux:icon.shield-check class="w-3 h-3" />
                        <span>Secure login</span>
                    </div>
                </div>

                <flux:button 
                    variant="primary" 
                    type="submit" 
                    class="w-full justify-center bg-emerald-600 hover:bg-emerald-700"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove class="flex items-center gap-2">
                        Sign in
                        <flux:icon.arrow-right class="w-4 h-4" />
                    </span>
                    <span wire:loading class="flex items-center gap-2">
                        <flux:icon.loader class="w-4 h-4 animate-spin" />
                        Signing in...
                    </span>
                </flux:button>

                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200 dark:border-gray-800"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white dark:bg-gray-900 text-gray-500 dark:text-gray-500">Or continue with</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <flux:button variant="outline" class="flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        <span>Google</span>
                    </flux:button>
                    
                    <flux:button variant="outline" class="flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/>
                        </svg>
                        <span>GitHub</span>
                    </flux:button>
                </div>
            </form>

            <p class="mt-8 text-center text-xs text-gray-500 dark:text-gray-500">
                By signing in you agree to our 
                <a href="#" class="text-emerald-600 dark:text-emerald-400 hover:underline">Terms of Service</a> 
                and 
                <a href="#" class="text-emerald-600 dark:text-emerald-400 hover:underline">Privacy Policy</a>
            </p>
        </div>
    </div>
</div>
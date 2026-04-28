<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

new #[Layout('components.layouts.auth')] #[Title('Verify Email')] class extends Component
{
    public ?string $status = null;
    public string $statusType = 'success';

    public function mount(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

    public function resend(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirect(route('dashboard'), navigate: true);
            return;
        }

        $key = 'email-verify-resend:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $this->status = "Please wait {$seconds} seconds before requesting another email.";
            $this->statusType = 'warning';
            return;
        }

        RateLimiter::hit($key, 600);
        $user->sendEmailVerificationNotification();

        $this->status = 'A new verification link has been sent to your email.';
        $this->statusType = 'success';
    }
}; ?>

<div class="min-h-screen flex items-center justify-center bg-[var(--bg)] p-6 relative overflow-hidden">
    {{-- Background gradient --}}
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_40%_at_50%_0%,rgba(126,232,162,0.04),transparent_70%)]"></div>
    
    <flux:card class="w-full max-w-[400px] !p-10 text-center relative z-10">
        
        {{-- Brand --}}
        <div class="flex items-center justify-center gap-2 mb-7">
            <div class="w-[26px] h-[26px] rounded-lg bg-[rgba(126,232,162,0.12)] flex items-center justify-center">
                <svg width="26" height="26" viewBox="0 0 32 32" fill="none">
                    <path d="M8 10h10M8 16h14M8 22h7" stroke="#7EE8A2" stroke-width="2.5" stroke-linecap="round"/>
                    <circle cx="24" cy="22" r="4" stroke="#7EE8A2" stroke-width="2"/>
                    <path d="M24 20v2l1.5 1.5" stroke="#7EE8A2" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </div>
            <span class="font-['Syne',sans-serif] text-[17px] font-extrabold text-white">ProjexFlow</span>
        </div>

        {{-- Icon --}}
        <div class="w-[70px] h-[70px] rounded-[20px] flex items-center justify-center mx-auto mb-5.5 bg-[rgba(126,232,162,0.07)] border border-[rgba(126,232,162,0.15)] transition-transform duration-300 hover:scale-110">
            <flux:icon.mail class="!text-[var(--accent)]" style="width: 38px; height: 38px;" />
        </div>

        {{-- Content --}}
        <div class="text-[21px] font-bold font-['Syne',sans-serif] mb-2.5 text-white tracking-tight">
            Check your inbox
        </div>
        
        <div class="text-sm text-[var(--dim)] leading-relaxed mb-5.5">
            We sent a verification link to
        </div>
        
        <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-[rgba(126,232,162,0.1)] text-[var(--accent)] border border-[rgba(126,232,162,0.2)] mb-5.5">
            {{ Auth::user()->email }}
        </div>

        {{-- Status Message --}}
        @if ($status)
            <div class="flex items-center justify-center gap-2 px-3.5 py-2.5 rounded-lg mb-4.5 text-[13px] {{ $statusType === 'warning' ? 'bg-amber-500/10 border border-amber-500/20 text-amber-400' : 'bg-[rgba(126,232,162,0.07)] border border-[rgba(126,232,162,0.2)] text-[var(--accent)]' }}">
                @if($statusType === 'success')
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @else
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                @endif
                {{ $status }}
            </div>
        @endif

        {{-- Resend Button --}}
        <button 
            wire:click="resend"
            :disabled="$statusType === 'warning'"
            wire:loading.attr="disabled"
            class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-lg bg-[var(--surface2)] border border-[var(--border2)] text-[var(--text)] font-medium text-[13.5px] transition-all duration-200 hover:border-[var(--accent)] hover:text-[var(--accent)] hover:bg-[rgba(126,232,162,0.04)] disabled:opacity-60 disabled:cursor-not-allowed"
        >
            <span wire:loading.remove class="flex items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="transition-transform group-hover:rotate-180">
                    <polyline points="1 4 1 10 7 10"/>
                    <path d="M3.51 15a9 9 0 102.13-9.36L1 10"/>
                </svg>
                Resend verification email
            </span>
            <span wire:loading class="flex items-center gap-2">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="animate-spin">
                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                </svg>
                Sending…
            </span>
        </button>

        {{-- Divider --}}
        <div class="relative my-5.5">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-[var(--border)]"></div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="text-[13px] text-[var(--muted)]">
            Wrong email?
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('lf').submit();"
               class="text-[var(--accent)] hover:underline no-underline">
                Sign out
            </a>
            and register again.
        </div>
        
        <form id="lf" action="{{ route('logout') }}" method="POST" style="display: none">@csrf</form>

    </flux:card>
</div>
<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Locked;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

new #[Layout('components.layouts.auth')] #[Title('Set New Password')] class extends Component
{
    #[Locked]
    public string $token = '';

    public string $email                 = '';
    public string $password              = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->string('email');
    }

    public function reset(): void
    {
        $this->validate([
            'token'                 => ['required', 'string'],
            'email'                 => ['required', 'string', 'email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $status = Password::reset(
            [
                'token'                 => $this->token,
                'email'                 => $this->email,
                'password'              => $this->password,
                'password_confirmation' => $this->password_confirmation,
            ],
            function ($user) {
                $user->forceFill([
                    'password'       => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                Auth::logoutOtherDevices($this->password);
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            Session::flash('status', __($status));
            $this->redirect(route('login'), navigate: true);
            return;
        }

        $this->addError('email', __($status));
    }
}; ?>

<div class="min-h-screen flex items-center justify-center bg-[var(--bg)] p-6 relative overflow-hidden">
    {{-- Background gradient --}}
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_60%_40%_at_50%_0%,rgba(126,232,162,0.04),transparent_70%)]"></div>
    
    <flux:card class="w-full max-w-[400px] !p-10 relative z-10">
        
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

        {{-- Header --}}
        <div class="mb-6">
            <div class="flex items-center gap-1.5 mb-2">
                <div class="w-1.5 h-1.5 rounded-full bg-[var(--accent)] animate-pulse"></div>
                <div class="font-['DM_Mono',monospace] text-[11px] uppercase tracking-[1.5px] text-[var(--accent)]">
                    Account security
                </div>
            </div>
            <div class="text-[21px] font-bold font-['Syne',sans-serif] text-white mb-1.5 tracking-tight">
                Set new password
            </div>
            <div class="text-[13.5px] text-[var(--dim)]">
                Choose a strong password you haven't used before.
            </div>
        </div>

        <form wire:submit="reset" class="flex flex-col gap-4">
            {{-- Hidden email input --}}
            <input type="hidden" wire:model="email">

            {{-- Email error alert --}}
            @error('email')
                <div class="flex items-center gap-2 px-3.5 py-2.5 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-[13px]">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    {{ $message }}
                </div>
            @enderror

            {{-- New Password Field --}}
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-1.5">
                    <label for="password" class="text-[12.5px] font-medium text-[var(--dim)]">
                        New password
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
                        class="w-full bg-[var(--bg)] border border-[var(--border)] rounded-lg px-3 py-3 pl-9 pr-11 text-[13.5px] text-[var(--text)] placeholder:text-[var(--muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-3 focus:ring-[rgba(126,232,162,0.1)] transition-all"
                        :class="{'border-red-500': $wire.{{ $errors->has('password') ? 'true' : 'false' }}}"
                        placeholder="••••••••" 
                        autocomplete="new-password" 
                        autofocus
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

            {{-- Confirm Password Field --}}
            <div class="flex flex-col gap-1.5">
                <label for="password_confirmation" class="text-[12.5px] font-medium text-[var(--dim)]">
                    Confirm new password
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
                        class="w-full bg-[var(--bg)] border border-[var(--border)] rounded-lg px-3 py-3 pl-9 text-[13.5px] text-[var(--text)] placeholder:text-[var(--muted)] focus:outline-none focus:border-[var(--accent)] focus:ring-3 focus:ring-[rgba(126,232,162,0.1)] transition-all"
                        :class="{'border-red-500': $wire.{{ $errors->has('password_confirmation') ? 'true' : 'false' }}}"
                        placeholder="••••••••" 
                        autocomplete="new-password"
                    >
                </div>
                @error('password_confirmation')
                    <div class="text-[11.5px] text-red-400">{{ $message }}</div>
                @enderror
            </div>

            {{-- Submit Button --}}
            <button type="submit" class="flex items-center justify-center gap-1.5 w-full py-3 mt-1 bg-[var(--accent)] text-[#080c14] rounded-lg font-['Syne',sans-serif] text-[14.5px] font-bold transition-all duration-200 hover:bg-[var(--accent2)] hover:-translate-y-0.5 disabled:opacity-70 disabled:cursor-not-allowed" wire:loading.attr="disabled">
                <span wire:loading.remove class="flex items-center gap-1.5">
                    Reset password
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </span>
                <span wire:loading class="flex items-center gap-1.5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#080c14" stroke-width="2" class="animate-spin">
                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                    </svg>
                    Updating…
                </span>
            </button>
        </form>

        {{-- Divider --}}
        <div class="relative my-5.5">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-[var(--border)]"></div>
            </div>
        </div>

        {{-- Back Link --}}
        <a href="{{ route('login') }}" wire:navigate class="inline-flex items-center gap-1.5 text-[13px] text-[var(--muted)] no-underline transition-colors duration-150 hover:text-[var(--text)]">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <path d="M19 12H5M12 5l-7 7 7 7"/>
            </svg>
            Back to sign in
        </a>

    </flux:card>
</div>
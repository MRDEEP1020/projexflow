<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Password;

new #[Layout('components.layouts.auth')] #[Title('Reset Password')] class extends Component
{
    public string $email = '';
    public bool   $sent  = false;

    public function send(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink(['email' => $this->email]);
        $this->sent = true;
    }
}; ?>

<div class="min-h-screen flex items-center justify-center bg-[var(--bg)] p-6" 
     style="background-image: radial-gradient(ellipse 60% 40% at 50% 0%, rgba(126,232,162,0.04), transparent 70%)">
    
    <flux:card class="w-full max-w-[400px] !p-10 text-center">
        
        {{-- Brand Logo --}}
        <div class="flex items-center justify-center gap-2 mb-7">
            <div class="w-6.5 h-6.5 rounded-lg bg-[rgba(126,232,162,0.12)] flex items-center justify-center">
                <svg width="26" height="26" viewBox="0 0 32 32" fill="none">
                    <path d="M8 10h10M8 16h14M8 22h7" stroke="#7EE8A2" stroke-width="2.5" stroke-linecap="round"/>
                    <circle cx="24" cy="22" r="4" stroke="#7EE8A2" stroke-width="2"/>
                    <path d="M24 20v2l1.5 1.5" stroke="#7EE8A2" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </div>
            <span class="font-['Syne',sans-serif] text-[17px] font-extrabold text-white">ProjexFlow</span>
        </div>

        @if ($sent)
            {{-- Success State --}}
            <div class="w-[70px] h-[70px] rounded-[20px] flex items-center justify-center mx-auto mb-5.5 bg-[rgba(126,232,162,0.07)] border border-[rgba(126,232,162,0.15)]">
                <flux:icon.check-circle class="!text-[var(--accent)]" style="width: 38px; height: 38px;" />
            </div>
            
            <flux:heading size="xl" class="!text-[21px] mb-2.5">
                Check your inbox
            </flux:heading>
            
            <flux:text class="block text-sm mb-6">
                If an account exists for <strong class="text-[var(--text)] font-semibold">{{ $email }}</strong>,
                you'll receive a reset link shortly. The link expires in 60 minutes.
            </flux:text>
            
            <div class="flex justify-center">
                <flux:button
                    :href="route('login')"
                    wire:navigate
                    variant="ghost"
                    class="text-[var(--muted)] hover:text-[var(--text)]"
                >
                    <flux:icon.arrow-left class="mr-1.5" style="width: 13px; height: 13px;" />
                    Back to sign in
                </flux:button>
            </div>

        @else
            {{-- Request Form --}}
            <div class="w-[70px] h-[70px] rounded-[20px] flex items-center justify-center mx-auto mb-5.5 bg-[rgba(91,58,158,0.1)] border border-[rgba(91,58,158,0.2)]">
                <flux:icon.lock-closed class="!text-[#a78bfa]" style="width: 38px; height: 38px;" />
            </div>
            
            <flux:heading size="xl" class="!text-[21px] mb-2.5">
                Reset your password
            </flux:heading>
            
            <flux:text class="block text-sm mb-6">
                Enter your email and we'll send you a reset link.
            </flux:text>

            <form wire:submit="send" class="space-y-4 text-left">
                {{-- Email Field with custom icon wrapper --}}
                <div>
                    <flux:label for="email" class="text-[12.5px] font-medium text-[var(--dim)] mb-1.5 block">
                        Email address
                    </flux:label>
                    
                    <div class="relative">
                        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--muted)]">
                            <flux:icon.mail style="width: 14px; height: 14px;" />
                        </div>
                        <flux:input 
                            id="email"
                            type="email"
                            wire:model="email"
                            placeholder="you@company.com"
                            autocomplete="email"
                            autofocus
                            :invalid="$errors->has('email')"
                            class="pl-9"
                        />
                    </div>
                    
                    @error('email')
                        <flux:text class="text-[11.5px] !text-red-400 mt-1 block">{{ $message }}</flux:text>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <flux:button 
                    type="submit"
                    class="w-full mt-2"
                    :disabled="$sent"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Send reset link</span>
                    <span wire:loading class="flex items-center justify-center gap-1.5">
                        <flux:icon.loader class="animate-spin" style="width: 15px; height: 15px;" />
                        Sending…
                    </span>
                </flux:button>
            </form>

            {{-- Divider --}}
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-[var(--border)]"></div>
                </div>
            </div>

            {{-- Back Link --}}
            <div class="flex justify-center">
                <flux:button
                    :href="route('login')"
                    wire:navigate
                    variant="ghost"
                    class="text-[var(--muted)] hover:text-[var(--text)]"
                >
                    <flux:icon.arrow-left class="mr-1.5" style="width: 13px; height: 13px;" />
                    Back to sign in
                </flux:button>
            </div>
        @endif

    </flux:card>
</div>

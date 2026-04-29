<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

/**
 * The mode-switcher toggle that appears in both app layouts.
 * Switches active_mode between 'client' and 'freelancer'
 * and redirects to the correct dashboard.
 */
class ModeSwitcher extends Component
{
    public string $currentMode = 'client';

    public function mount(): void
    {
        $this->currentMode = session('active_mode', Auth::user()->active_mode ?? 'client');
    }

    public function switchTo(string $mode): void
    {
        if (! in_array($mode, ['client', 'freelancer'])) return;

        session(['active_mode' => $mode]);
        Auth::user()->update(['active_mode' => $mode]);

        $this->currentMode = $mode;

        // Redirect to the correct dashboard
        $this->redirect(
            $mode === 'freelancer'
                ? route('freelancer.dashboard')
                : route('client.dashboard'),
            navigate: true
        );
    }

    public function render()
    {
        return <<<'BLADE'
        <div class="flex items-center gap-1 p-1 bg-[#080c14] border border-[#1c2e45] rounded-xl">
            <button
                wire:click="switchTo('client')"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all
                    {{ $currentMode === 'client'
                        ? 'bg-[#7EE8A2] text-[#080c14] font-bold'
                        : 'text-[#506070] hover:text-[#dde6f0]' }}"
            >
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <path d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/>
                    <circle cx="12" cy="12" r="2"/><path d="M6 12H4M20 12h-2"/>
                </svg>
                Client
            </button>
            <button
                wire:click="switchTo('freelancer')"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all
                    {{ $currentMode === 'freelancer'
                        ? 'bg-[#7EE8A2] text-[#080c14] font-bold'
                        : 'text-[#506070] hover:text-[#dde6f0]' }}"
            >
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                </svg>
                Freelancer
            </button>
        </div>
        BLADE;
    }
}
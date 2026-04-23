<x-slot name="header">Create Organization</x-slot>

<div class="max-w-xl">
    <flux:card class="bg-[#0e1420] border-[#1c2e45]">

        {{-- Card header --}}
        <div class="mb-6">
            <div class="flex items-center gap-2 text-[#7EE8A2] mb-2">
                <div class="w-1.5 h-1.5 rounded-full bg-[#7EE8A2] animate-pulse"></div>
                <span class="font-mono text-[10px] uppercase tracking-[1.5px]">New workspace</span>
            </div>
            <flux:heading size="xl">Create an organization</flux:heading>
            <flux:text class="mt-1">Set up a shared workspace for your team or company.</flux:text>
        </div>

        <form wire:submit="create" class="space-y-5">

            {{-- Name --}}
            <flux:field>
                <flux:label>Organization name <span class="text-red-400">*</span></flux:label>
                <flux:input
                    wire:model.live.debounce.300ms="name"
                    placeholder="Acme Corporation"
                    autofocus
                    icon="building-office-2"
                />
                <flux:error name="name"/>
            </flux:field>

            {{-- Slug --}}
            <flux:field>
                <flux:label>
                    URL slug <span class="text-red-400">*</span>
                    <flux:description>Letters, numbers, hyphens only</flux:description>
                </flux:label>
                <flux:input
                    wire:model.live="slug"
                    placeholder="acme-corporation"
                    icon="link"
                    prefix="{{ config('app.url') }}/"
                />
                @if($slug && !$errors->has('slug'))
                    <p class="text-[11px] text-[#506070] flex items-center gap-1 mt-1">
                        <flux:icon.information-circle class="size-3"/>
                        Your org URL: <span class="text-[#7EE8A2] font-mono">{{ $this->getPreviewUrl() }}</span>
                    </p>
                @endif
                <flux:error name="slug"/>
            </flux:field>

            {{-- Type --}}
            <flux:field>
                <flux:label>Organization type <span class="text-red-400">*</span></flux:label>
                <div class="grid grid-cols-2 gap-3 mt-1">
                    @foreach([
                        ['value' => 'company',  'icon' => 'building-office-2', 'name' => 'Company / Team',   'desc' => 'Multiple members'],
                        ['value' => 'personal', 'icon' => 'user',              'name' => 'Personal',         'desc' => 'Solo freelancer'],
                    ] as $opt)
                        <label
                            class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition-all
                                {{ $type === $opt['value']
                                    ? 'border-[#7EE8A2] bg-[#7EE8A2]/[0.04]'
                                    : 'border-[#1c2e45] bg-[#080c14] hover:border-[#254060]' }}"
                        >
                            <input type="radio" wire:model="type" value="{{ $opt['value'] }}" class="sr-only">
                            <div class="w-9 h-9 flex-shrink-0 flex items-center justify-center rounded-lg
                                {{ $type === $opt['value'] ? 'bg-[#7EE8A2]/10 text-[#7EE8A2]' : 'bg-[#131d2e] text-[#8da0b8]' }}">
                                <flux:icon :name="$opt['icon']" class="size-5"/>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-[#dde6f0]">{{ $opt['name'] }}</p>
                                <p class="text-[11px] text-[#506070] mt-0.5">{{ $opt['desc'] }}</p>
                            </div>
                            <div class="w-4.5 h-4.5 mt-0.5 rounded-full border flex items-center justify-center flex-shrink-0
                                {{ $type === $opt['value'] ? 'border-[#7EE8A2] bg-[#7EE8A2]' : 'border-[#254060]' }}">
                                @if($type === $opt['value'])
                                    <flux:icon.check class="size-2.5 text-[#080c14]"/>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
                <flux:error name="type"/>
            </flux:field>

            {{-- Logo --}}
            <flux:field>
                <flux:label>Logo <span class="text-[#506070] font-normal">(optional · max 2MB)</span></flux:label>
                @if($logo)
                    <div class="flex items-center gap-3">
                        <img src="{{ $logo->temporaryUrl() }}" class="w-16 h-16 rounded-xl object-cover border border-[#1c2e45]">
                        <flux:button type="button" variant="ghost" size="sm" wire:click="$set('logo', null)" icon="x-mark">
                            Remove
                        </flux:button>
                    </div>
                @else
                    <label class="flex flex-col items-center gap-2 p-6 rounded-xl border border-dashed border-[#254060] bg-[#080c14] cursor-pointer hover:border-[#7EE8A2]/40 transition-colors">
                        <input type="file" wire:model="logo" accept="image/*" class="sr-only">
                        <flux:icon.photo class="size-7 text-[#506070]"/>
                        <span class="text-sm text-[#8da0b8]">Click to upload logo</span>
                        <span class="text-xs text-[#506070]">PNG, JPG, SVG</span>
                    </label>
                @endif
                <flux:error name="logo"/>
            </flux:field>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-[#1c2e45]">
                <flux:button variant="ghost" href="{{ route('dashboard') }}" wire:navigate>
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>Create organization</span>
                    <span wire:loading>Creating…</span>
                </flux:button>
            </div>

        </form>
    </flux:card>
</div>

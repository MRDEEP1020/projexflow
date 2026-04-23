<div class="min-h-screen">

    {{-- Provider hero --}}
    <div class="bg-gradient-to-b from-[#0d1825] to-[#080c14] border-b border-[#1c2e45]">
        <div class="max-w-2xl mx-auto px-5 py-8">
            <div class="flex items-center gap-4">
                <flux:avatar
                    src="{{ $provider->avatar_url }}"
                    name="{{ $provider->name }}"
                    size="xl"
                    class="ring-2 ring-[#7EE8A2]/20"
                />
                <div>
                    <h1 style="font-family:'Syne',sans-serif;font-weight:800;font-size:22px;color:#fff">
                        Book a session with {{ $provider->name }}
                    </h1>
                    @if($profile?->headline)
                        <p class="text-sm text-[#8da0b8] mt-1">{{ $profile->headline }}</p>
                    @endif
                    <div class="flex items-center gap-3 mt-2">
                        @if($profile?->session_duration)
                            <span class="flex items-center gap-1 text-xs text-[#506070]">
                                <flux:icon.clock class="size-3.5"/>
                                {{ $profile->session_duration }} min sessions
                            </span>
                        @endif
                        @if($profile?->hourly_rate)
                            <span class="flex items-center gap-1 text-xs text-[#7EE8A2]">
                                <flux:icon.currency-dollar class="size-3.5"/>
                                {{ number_format($profile->hourly_rate) }} {{ $profile->currency ?? 'USD' }}/hr
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Step progress --}}
            @if(!$booked)
                <div class="flex items-center gap-0 mt-6">
                    @foreach(['Choose date','Choose time','Your details','Confirm'] as $i => $label)
                        <div class="flex items-center {{ $i < 3 ? 'flex-1' : '' }}">
                            <div class="flex flex-col items-center">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all
                                    {{ $step > $i + 1
                                        ? 'bg-[#7EE8A2] text-[#080c14]'
                                        : ($step === $i + 1
                                            ? 'bg-[#7EE8A2]/20 border-2 border-[#7EE8A2] text-[#7EE8A2]'
                                            : 'bg-[#1c2e45] text-[#506070]') }}">
                                    @if($step > $i + 1)
                                        <flux:icon.check class="size-3.5"/>
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </div>
                                <span class="text-[10px] mt-1 whitespace-nowrap
                                    {{ $step === $i + 1 ? 'text-[#7EE8A2]' : 'text-[#506070]' }}">
                                    {{ $label }}
                                </span>
                            </div>
                            @if($i < 3)
                                <div class="flex-1 h-px mx-2 {{ $step > $i + 1 ? 'bg-[#7EE8A2]/40' : 'bg-[#1c2e45]' }}"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="max-w-2xl mx-auto px-5 py-8">

        {{-- ── Step 1: Date picker ────────────────────────────── --}}
        @if($step === 1)
            <flux:card class="bg-[#0e1420] border-[#1c2e45]">
                <flux:heading size="lg" class="mb-4">Select a date</flux:heading>

                @if(empty($this->availableDates))
                    <div class="flex flex-col items-center gap-2 py-8 text-[#506070]">
                        <flux:icon.calendar-x-2 class="size-8"/>
                        <p class="text-sm">No availability in the next 60 days.</p>
                    </div>
                @else
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                        @foreach($this->availableDates as $date)
                            @php $d = \Carbon\Carbon::parse($date); @endphp
                            <button
                                wire:click="selectDate('{{ $date }}')"
                                class="flex flex-col items-center p-3 rounded-xl border transition-all hover:-translate-y-0.5
                                    {{ $selectedDate === $date
                                        ? 'border-[#7EE8A2] bg-[#7EE8A2]/08 text-[#7EE8A2]'
                                        : 'border-[#1c2e45] text-[#8da0b8] hover:border-[#254060]' }}"
                            >
                                <span class="text-[10px] font-mono uppercase">{{ $d->format('D') }}</span>
                                <span class="text-lg font-bold" style="font-family:'Syne',sans-serif">{{ $d->format('j') }}</span>
                                <span class="text-[10px]">{{ $d->format('M') }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </flux:card>
        @endif

        {{-- ── Step 2: Time slot ──────────────────────────────── --}}
        @if($step === 2)
            <flux:card class="bg-[#0e1420] border-[#1c2e45] space-y-4">
                <div class="flex items-center gap-3">
                    <flux:button variant="ghost" size="sm" icon="arrow-left" wire:click="goBack"/>
                    <flux:heading size="lg">
                        Available times — {{ \Carbon\Carbon::parse($selectedDate)->format('l, M j') }}
                    </flux:heading>
                </div>

                @if(empty($this->availableSlots))
                    <div class="flex flex-col items-center gap-2 py-8 text-[#506070]">
                        <flux:icon.clock class="size-8"/>
                        <p class="text-sm">No slots available for this date. Please go back and pick another.</p>
                        <flux:button variant="ghost" size="sm" wire:click="goBack">Choose different date</flux:button>
                    </div>
                @else
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                        @foreach($this->availableSlots as $slot)
                            <button
                                wire:click="selectSlot('{{ $slot }}')"
                                class="py-2.5 px-3 rounded-xl border text-sm font-mono font-medium transition-all hover:-translate-y-0.5
                                    {{ $selectedSlot === $slot
                                        ? 'border-[#7EE8A2] bg-[#7EE8A2]/08 text-[#7EE8A2]'
                                        : 'border-[#1c2e45] text-[#8da0b8] hover:border-[#254060]' }}"
                            >{{ $slot }}</button>
                        @endforeach
                    </div>
                @endif
            </flux:card>
        @endif

        {{-- ── Step 3: Client details ─────────────────────────── --}}
        @if($step === 3)
            <flux:card class="bg-[#0e1420] border-[#1c2e45] space-y-5">
                <div class="flex items-center gap-3">
                    <flux:button variant="ghost" size="sm" icon="arrow-left" wire:click="goBack"/>
                    <flux:heading size="lg">Your details</flux:heading>
                </div>

                {{-- Booking summary --}}
                <div class="flex items-center gap-3 p-3 rounded-xl bg-[#080c14] border border-[#1c2e45]">
                    <div class="w-9 h-9 rounded-xl bg-[#7EE8A2]/10 border border-[#7EE8A2]/15 flex items-center justify-center">
                        <flux:icon.calendar-check-2 class="size-5 text-[#7EE8A2]"/>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">
                            {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j Y') }}
                        </p>
                        <p class="text-xs text-[#8da0b8]">
                            at {{ $selectedSlot }}
                            · {{ $profile?->session_duration ?? 60 }} min
                            · with {{ $provider->name }}
                        </p>
                    </div>
                </div>

                <form wire:submit="book" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Full name <<span class="text-red-400">*</span>/></flux:label>
                            <flux:input wire:model="clientName" icon="user" placeholder="Your name" autofocus/>
                            <flux:error name="clientName"/>
                        </flux:field>
                        <flux:field>
                            <flux:label>Email <<span class="text-red-400">*</span>/></flux:label>
                            <flux:input wire:model="clientEmail" type="email" icon="envelope" placeholder="you@email.com"/>
                            <flux:error name="clientEmail"/>
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>What would you like to discuss? <span class="text-[#506070] font-normal text-xs">(optional)</span></flux:label>
                        <flux:textarea
                            wire:model="clientMessage"
                            placeholder="Brief description of what you'd like to cover…"
                            rows="3"
                        />
                    </flux:field>

                    <div class="flex justify-end gap-2 pt-1 border-t border-[#1c2e45]">
                        <flux:button variant="ghost" wire:click="goBack">Back</flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                            <span wire:loading.remove>Request booking</span>
                            <span wire:loading>Sending request…</span>
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        @endif

        {{-- ── Step 4: Confirmation ───────────────────────────── --}}
        @if($step === 4)
            <div class="flex flex-col items-center gap-5 py-10 text-center">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center bg-[#7EE8A2]/10 border border-[#7EE8A2]/20"
                     style="animation:popIn .4s ease both">
                    <flux:icon.check-circle class="size-8 text-[#7EE8A2]"/>
                </div>
                <div>
                    <h2 style="font-family:'Syne',sans-serif;font-weight:800;font-size:22px;color:#fff">
                        Booking request sent!
                    </h2>
                    <p class="text-sm text-[#8da0b8] mt-2 max-w-sm">
                        {{ $provider->name }} will confirm your session shortly.
                        A confirmation will be sent to <strong class="text-white">{{ $clientEmail }}</strong>.
                    </p>
                </div>

                <div class="flex items-center gap-3 px-5 py-4 bg-[#0e1420] border border-[#1c2e45] rounded-2xl">
                    <div class="w-9 h-9 rounded-xl bg-[#7EE8A2]/10 border border-[#7EE8A2]/15 flex items-center justify-center">
                        <flux:icon.calendar class="size-5 text-[#7EE8A2]"/>
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-semibold text-white">
                            {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j Y') }}
                        </p>
                        <p class="text-xs text-[#8da0b8]">
                            {{ $selectedSlot }} · {{ $profile?->session_duration ?? 60 }} min · {{ $provider->name }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>

<style>
    @keyframes popIn { from{opacity:0;transform:scale(.7)} to{opacity:1;transform:scale(1)} }
</style>

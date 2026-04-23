<div class="max-w-lg mx-auto py-8 px-4">

    @if($submitted)
        {{-- Already submitted / success state --}}
        <div class="flex flex-col items-center gap-5 py-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-[#7EE8A2]/10 border border-[#7EE8A2]/20 flex items-center justify-center"
                 style="animation:popIn .4s ease">
                <flux:icon.check-circle class="size-8 text-[#7EE8A2]"/>
            </div>
            <div>
                <h2 class="font-['Syne'] text-xl font-bold text-white">Review submitted!</h2>
                <p class="text-sm text-[#8da0b8] mt-2">Thank you for your feedback.</p>
            </div>
            <flux:button variant="ghost" href="{{ route('backend.bookingInbox') }}" wire:navigate>
                Back to bookings
            </flux:button>
        </div>
    @else
        <flux:card class="bg-[#0e1420] border-[#1c2e45] space-y-6">

            {{-- Reviewee info --}}
            @if($reviewee)
                <div class="flex items-center gap-3 pb-4 border-b border-[#1c2e45]">
                    <flux:avatar src="{{ $reviewee->avatar_url }}" name="{{ $reviewee->name }}" size="md"/>
                    <div>
                        <h2 class="font-['Syne'] font-bold text-white text-lg">{{ $reviewee->name }}</h2>
                        @if($reviewee->serviceProfile?->headline)
                            <p class="text-sm text-[#8da0b8]">{{ $reviewee->serviceProfile->headline }}</p>
                        @endif
                    </div>
                </div>
            @endif

            <div>
                <flux:heading size="lg">Leave a review</flux:heading>
                <flux:text class="mt-1">
                    Your review is
                    @if($bookingId || $projectId)
                        <flux:badge size="sm" color="green" class="mx-1">verified</flux:badge>
                        — backed by a real engagement on ProjexFlow.
                    @else
                        unverified.
                    @endif
                </flux:text>
            </div>

            <form wire:submit="submit" class="space-y-5">

                {{-- Star rating --}}
                <div class="space-y-2">
                    <p class="text-sm font-medium text-[#dim]">Rating <span class="text-[#7EE8A2]">*</span></p>
                    <div class="flex gap-2"
                         x-data="{ hover: 0 }"
                         @mouseleave="hover = 0">
                        @for ($i = 1; $i <= 5; $i++)
                            <button
                                type="button"
                                wire:click="setRating({{ $i }})"
                                @mouseenter="hover = {{ $i }}"
                                class="transition-transform hover:scale-110 focus:outline-none"
                            >
                                <svg width="36" height="36" viewBox="0 0 24 24"
                                     :fill="(hover || {{ $rating }}) >= {{ $i }} ? '#f59e0b' : '#1c2e45'"
                                     class="transition-all duration-100"
                                >
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            </button>
                        @endfor
                        @if($rating > 0)
                            <span class="self-center text-sm font-medium ml-2
                                {{ match($rating) {
                                    5 => 'text-[#7EE8A2]',
                                    4 => 'text-blue-400',
                                    3 => 'text-amber-400',
                                    2 => 'text-orange-400',
                                    1 => 'text-red-400',
                                    default => 'text-[#506070]'
                                } }}">
                                {{ ['','Poor','Fair','Good','Very good','Excellent!'][$rating] }}
                            </span>
                        @endif
                    </div>
                    @error('rating')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                </div>

                {{-- Written review --}}
                <flux:field>
                    <flux:label>
                        Your review
                        <span class="text-[#506070] font-normal text-xs">(optional)</span>
                    </flux:label>
                    <flux:textarea
                        wire:model="body"
                        rows="4"
                        placeholder="Share your experience working with {{ $reviewee?->name }}. What went well? What could be improved?"
                    />
                    <flux:description>Max 2000 characters. {{ strlen($body) }}/2000</flux:description>
                    <flux:error name="body"/>
                </flux:field>

                <div class="flex justify-end gap-2 pt-2 border-t border-[#1c2e45]">
                    <flux:button variant="ghost" href="{{ route('backend.bookingInbox') }}" wire:navigate>
                        Skip
                    </flux:button>
                    <flux:button
                        type="submit"
                        variant="primary"
                        icon="star"
                        wire:loading.attr="disabled"
                        :disabled="$rating === 0"
                    >
                        <span wire:loading.remove>Submit review</span>
                        <span wire:loading>Submitting…</span>
                    </flux:button>
                </div>
            </form>
        </flux:card>
    @endif
</div>

<style>
    @keyframes popIn { from{opacity:0;transform:scale(.7)} to{opacity:1;transform:scale(1)} }
</style>

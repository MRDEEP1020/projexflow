<div class="relative" x-data="{ open: false }">

    {{-- Current org button --}}
    <button @click="open = !open" class="flex items-center gap-2 w-full p-2 rounded-lg transition-all duration-150 theme-transition" :aria-expanded="open"
            style="background-color: var(--surface2); border: 1px solid var(--border);"
            onmouseover="this.style.borderColor='var(--border2)'" onmouseout="this.style.borderColor='var(--border)'">
        
        <div class="w-7 h-7 shrink-0 rounded-md flex items-center justify-center overflow-hidden"
             style="background-color: rgba(126,232,162,0.1); border: 1px solid rgba(126,232,162,0.15);">
            @if($activeOrgId && $memberships->firstWhere('org.id', $activeOrgId))
                @php $active = $memberships->firstWhere('org.id', $activeOrgId)['org'] @endphp
                @if($active->logo)
                    <img src="{{ \Storage::url($active->logo) }}" alt="{{ $active->name }}" class="w-full h-full object-cover">
                @else
                    <span class="font-['Syne',sans-serif] text-xs font-bold" style="color: var(--accent)">{{ strtoupper(substr($active->name, 0, 1)) }}</span>
                @endif
            @else
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" style="color: var(--accent)"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            @endif
        </div>

        <div class="flex-1 min-w-0 text-left">
            <span class="block font-['Inter',sans-serif] text-[12.5px] font-medium truncate" style="color: var(--text)">
                @if($activeOrgId && $memberships->firstWhere('org.id', $activeOrgId))
                    {{ $memberships->firstWhere('org.id', $activeOrgId)['org']->name }}
                @else
                    Personal Workspace
                @endif
            </span>
            <span class="block font-['Inter',sans-serif] text-[10.5px] mt-0.5" style="color: var(--muted)">
                @if($activeOrgId && $memberships->firstWhere('org.id', $activeOrgId))
                    {{ ucfirst(str_replace('_', ' ', $memberships->firstWhere('org.id', $activeOrgId)['role'])) }}
                @else
                    Owner
                @endif
            </span>
        </div>

        <svg class="shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="color: var(--muted)"><polyline points="6 9 12 15 18 9"/></svg>
    </button>

    {{-- Dropdown --}}
    <div class="absolute top-full left-0 right-0 mt-1.5 rounded-xl overflow-hidden shadow-xl z-[100] theme-transition"
         style="background-color: var(--surface); border: 1px solid var(--border);"
         x-show="open" @click.away="open=false" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1" style="display: none;">

        <div class="font-['DM_Mono',monospace] text-[9.5px] uppercase tracking-wider px-3 pt-3 pb-1.5" style="color: var(--muted)">Switch workspace</div>

        @foreach($memberships as $m)
            <button
                wire:click="switchOrg({{ $m['org']->id }})"
                @click="open=false"
                class="flex items-center gap-2.5 w-full px-3 py-2 transition-colors duration-100 text-left"
                :class="$activeOrgId == {{ $m['org']->id }} ? 'bg-[var(--accent)]/5' : ''"
                style="background: none; border: none; cursor: pointer;"
                onmouseover="this.style.backgroundColor='var(--surface2)'" 
                onmouseout="if(!this.classList.contains('is-active')) this.style.backgroundColor='transparent'">
                
                <div class="w-6.5 h-6.5 shrink-0 rounded-md flex items-center justify-center overflow-hidden"
                     style="background-color: rgba(126,232,162,0.08); border: 1px solid rgba(126,232,162,0.12);">
                    @if($m['org']->logo)
                        <img src="{{ \Storage::url($m['org']->logo) }}" alt="{{ $m['org']->name }}" class="w-full h-full object-cover">
                    @else
                        <span class="font-['Syne',sans-serif] text-[11px] font-bold" style="color: var(--accent)">{{ strtoupper(substr($m['org']->name, 0, 1)) }}</span>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <span class="block font-['Inter',sans-serif] text-[12.5px] font-medium truncate" style="color: var(--text)">{{ $m['org']->name }}</span>
                    <span class="block font-['Inter',sans-serif] text-[10.5px]" style="color: var(--muted)">{{ ucfirst(str_replace('_', ' ', $m['role'])) }}</span>
                </div>

                @if($activeOrgId == $m['org']->id)
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="color: var(--accent); flex-shrink: 0;"><polyline points="20 6 9 17 4 12"/></svg>
                @endif
            </button>
        @endforeach

        <div class="h-px my-1" style="background-color: var(--border)"></div>

        <a href="{{ route('backend.projectCreate') }}" wire:navigate class="flex items-center gap-2.5 px-3 py-2.5 font-['Inter',sans-serif] text-[12.5px] no-underline transition-all duration-100"
           style="color: var(--dim)"
           onmouseover="this.style.color='var(--accent)'; this.style.backgroundColor='rgba(126,232,162,0.04)'" 
           onmouseout="this.style.color='var(--dim)'; this.style.backgroundColor='transparent'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Create organization
        </a>

    </div>

</div>

<style>
    /* Additional utility classes for theme transitions */
    .theme-transition {
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }
    .rotate-180 {
        transform: rotate(180deg);
    }
    /* Custom width utilities (6.5 = 26px) */
    .w-6\.5 {
        width: 26px;
    }
    .h-6\.5 {
        height: 26px;
    }
</style>
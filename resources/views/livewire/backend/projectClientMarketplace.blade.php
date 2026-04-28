<div class="max-w-7xl space-y-6">

    {{-- ── Client-mode hero ────────────────────────────────────── --}}
    <div class="relative overflow-hidden rounded-2xl border border-[#1c2e45] bg-gradient-to-br from-[#0d1520] to-[#080c14] p-8">
        <div class="absolute right-0 top-0 w-72 h-72 bg-[#7EE8A2]/04 rounded-full blur-3xl pointer-events-none"></div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
            <div>
                <h1 style="font-family:'Syne',sans-serif;font-weight:800;font-size:26px;color:#fff;letter-spacing:-.4px">
                    Find the right freelancer
                </h1>
                <p class="text-sm text-[#8da0b8] mt-1.5 max-w-lg">
                    Browse {{ $this->freelancers->total() }} verified professionals. Shortlist who you like, then book a session or send a contract directly.
                </p>
                {{-- Category quick links --}}
                <div class="flex flex-wrap gap-2 mt-4">
                    @foreach([
                        'software_dev'      => '💻 Dev',
                        'ui_ux'             => '🎨 Design',
                        'digital_marketing' => '📣 Marketing',
                        'data_analytics'    => '📊 Data',
                        'content_writing'   => '✍️ Content',
                        'ai_ml'             => '🤖 AI/ML',
                    ] as $cat => $label)
                        <button wire:click="$set('category','{{ $cat }}')"
                            class="px-3 py-1.5 rounded-xl text-xs font-medium transition-all
                                {{ $category === $cat
                                    ? 'bg-[#7EE8A2] text-[#080c14] font-bold'
                                    : 'bg-[#131d2e] border border-[#1c2e45] text-[#8da0b8] hover:border-[#7EE8A2]/30 hover:text-[#dde6f0]' }}">
                            {{ $label }}
                            @if(isset($this->categoryStats[$cat]) && $this->categoryStats[$cat] > 0)
                                <span class="opacity-60 ml-1">{{ $this->categoryStats[$cat] }}</span>
                            @endif
                        </button>
                    @endforeach
                    @if($category !== 'all')
                        <button wire:click="$set('category','all')"
                            class="px-3 py-1.5 rounded-xl text-xs text-[#506070] hover:text-[#dde6f0] transition-colors">
                            Clear ×
                        </button>
                    @endif
                </div>
            </div>
            {{-- Shortlist badge --}}
            @if(count($shortlisted) > 0)
                <div class="flex-shrink-0">
                    <div class="flex items-center gap-3 px-4 py-3 bg-[#7EE8A2]/08 border border-[#7EE8A2]/25 rounded-xl">
                        <div>
                            <p class="text-xs text-[#506070] font-mono uppercase tracking-wider">Shortlisted</p>
                            <p style="font-family:'Syne',sans-serif;font-weight:800;font-size:24px;color:#7EE8A2;line-height:1">
                                {{ count($shortlisted) }}
                            </p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <flux:button variant="primary" size="sm" wire:navigate
                                href="{{ route('backend.jobPostCreate') }}" icon="plus">
                                Post a job
                            </flux:button>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex-shrink-0">
                    <flux:button variant="primary" icon="plus"
                        href="{{ route('backend.jobPostCreate') }}" wire:navigate>
                        Post a job instead
                    </flux:button>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Search + filter row ─────────────────────────────────── --}}
    <div class="flex flex-wrap gap-2">
        <div class="flex-1 min-w-64">
            <flux:input wire:model.live.debounce.350ms="search"
                placeholder="Search by skill, role, name…"
                icon="magnifying-glass" clearable/>
        </div>
        <flux:select wire:model.live="availability" class="w-36">
            <flux:select.option value="all">Any status</flux:select.option>
            <flux:select.option value="open_to_work">Available now</flux:select.option>
            <flux:select.option value="busy">Busy (limited)</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="experience" class="w-32">
            <flux:select.option value="all">Any level</flux:select.option>
            <flux:select.option value="entry">Entry</flux:select.option>
            <flux:select.option value="mid">Mid-level</flux:select.option>
            <flux:select.option value="senior">Senior</flux:select.option>
            <flux:select.option value="expert">Expert</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="language" class="w-32">
            <flux:select.option value="all">Any language</flux:select.option>
            @foreach(['English','French','Arabic','Swahili','Hausa','Portuguese'] as $lang)
                <flux:select.option value="{{ $lang }}">{{ $lang }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="sortBy" class="w-36">
            <flux:select.option value="rank">Best match</flux:select.option>
            <flux:select.option value="rating">Top rated</flux:select.option>
            <flux:select.option value="rate_asc">Lowest rate</flux:select.option>
            <flux:select.option value="rate_desc">Highest rate</flux:select.option>
            <flux:select.option value="newest">Newest</flux:select.option>
        </flux:select>
        @if($search || $category !== 'all' || $availability !== 'all' || $experience !== 'all' || $maxRate > 0)
            <flux:button variant="ghost" size="sm" wire:click="clearFilters">Clear all</flux:button>
        @endif
    </div>

    <div class="flex gap-6">

        {{-- ── Freelancer grid ─────────────────────────────────── --}}
        <div class="flex-1 min-w-0">
            @if($this->freelancers->isEmpty())
                <div class="flex flex-col items-center gap-3 py-14 bg-[#0e1420] border border-dashed border-[#1c2e45] rounded-2xl text-center">
                    <flux:icon.user-group class="size-8 text-[#506070]"/>
                    <flux:heading>No freelancers match your search</flux:heading>
                    <flux:button variant="ghost" size="sm" wire:click="clearFilters">Reset filters</flux:button>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($this->freelancers as $sp)
                        @php $isShortlisted = in_array($sp->user_id, $shortlisted); @endphp
                        <div class="group flex flex-col bg-[#0e1420] border rounded-2xl overflow-hidden transition-all hover:shadow-lg hover:-translate-y-0.5
                            {{ $isShortlisted ? 'border-[#7EE8A2]/40' : 'border-[#1c2e45] hover:border-[#254060]' }}"
                             wire:key="frl-{{ $sp->id }}">

                            {{-- Card header --}}
                            <div class="p-4 flex-1">
                                <div class="flex items-start justify-between gap-2 mb-3">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <flux:avatar
                                            src="{{ $sp->user->avatar_url }}"
                                            name="{{ $sp->user->name }}"
                                            size="md"
                                            class="flex-shrink-0 ring-2 {{ $isShortlisted ? 'ring-[#7EE8A2]/30' : 'ring-transparent' }}"/>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5">
                                                <p class="text-sm font-bold text-white truncate font-['Syne']">
                                                    {{ $sp->user->name }}
                                                </p>
                                                @if($sp->is_verified)
                                                    <flux:icon.check-badge class="size-3.5 text-[#7EE8A2] flex-shrink-0"/>
                                                @endif
                                            </div>
                                            <p class="text-xs text-[#506070] truncate">{{ $sp->profession_category ? ucfirst(str_replace('_',' ',$sp->profession_category)) : 'Freelancer' }}</p>
                                        </div>
                                    </div>

                                    {{-- Availability pill --}}
                                    <flux:badge size="sm" class="flex-shrink-0"
                                        :color="match($sp->availability_status) {
                                            'open_to_work'  => 'green',
                                            'busy'          => 'yellow',
                                            'not_available' => 'red',
                                            default         => 'zinc',
                                        }"
                                    >{{ $sp->availability_status === 'open_to_work' ? 'Available' : ucfirst(str_replace('_',' ',$sp->availability_status)) }}</flux:badge>
                                </div>

                                {{-- Headline --}}
                                <p class="text-xs text-[#8da0b8] leading-relaxed line-clamp-2 mb-3">
                                    {{ $sp->headline ?: 'No headline set.' }}
                                </p>

                                {{-- Rating + rate --}}
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-1.5">
                                        @if($sp->avg_rating > 0)
                                            <div class="flex gap-0.5">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <svg width="11" height="11" viewBox="0 0 24 24"
                                                         fill="{{ $i <= round($sp->avg_rating) ? '#f59e0b' : '#1c2e45' }}">
                                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                    </svg>
                                                @endfor
                                            </div>
                                            <span class="text-xs font-mono text-[#fbbf24]">{{ number_format($sp->avg_rating, 1) }}</span>
                                            <span class="text-[10px] text-[#506070]">({{ $sp->total_reviews }})</span>
                                        @else
                                            <span class="text-[10px] text-[#506070]">No reviews yet</span>
                                        @endif
                                    </div>
                                    @if($sp->hourly_rate)
                                        <span class="text-sm font-mono font-bold text-[#7EE8A2]">
                                            ${{ number_format($sp->hourly_rate) }}/hr
                                        </span>
                                    @endif
                                </div>

                                {{-- Skills --}}
                                @if(!empty($sp->skills))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach(array_slice((array)$sp->skills, 0, 4) as $skill)
                                            <span class="text-[10px] px-2 py-0.5 rounded-md bg-[#131d2e] border border-[#1c2e45] text-[#8da0b8]">
                                                {{ $skill }}
                                            </span>
                                        @endforeach
                                        @if(count((array)$sp->skills) > 4)
                                            <span class="text-[10px] text-[#506070]">+{{ count((array)$sp->skills) - 4 }}</span>
                                        @endif
                                    </div>
                                @endif

                                {{-- Location + response time --}}
                                <div class="flex items-center gap-3 mt-3 text-[10px] text-[#506070]">
                                    @if($sp->location)
                                        <span class="flex items-center gap-1">
                                            <flux:icon.map-pin class="size-3"/>
                                            {{ $sp->location }}
                                        </span>
                                    @endif
                                    @if($sp->response_time_hours)
                                        <span class="flex items-center gap-1">
                                            <flux:icon.clock class="size-3"/>
                                            ~{{ $sp->response_time_hours }}h response
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Card actions --}}
                            <div class="px-4 py-3 border-t border-[#1c2e45] bg-[#080c14]/50 flex gap-2">
                                <flux:button variant="ghost" size="sm" class="flex-1 justify-center text-xs"
                                    wire:click="openQuickView({{ $sp->user_id }})">
                                    Quick view
                                </flux:button>
                                <a href="{{ route('backend.profilePage', $sp->user->name) }}" wire:navigate
                                   class="flex-1 flex items-center justify-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-[#131d2e] border border-[#1c2e45] text-[#8da0b8] hover:text-[#dde6f0] hover:border-[#254060] transition-all">
                                    Full profile
                                </a>
                                <button wire:click="shortlist({{ $sp->user_id }})"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center transition-all flex-shrink-0
                                        {{ $isShortlisted
                                            ? 'bg-[#7EE8A2]/15 text-[#7EE8A2] border border-[#7EE8A2]/30'
                                            : 'bg-[#131d2e] border border-[#1c2e45] text-[#506070] hover:text-[#7EE8A2] hover:border-[#7EE8A2]/30' }}"
                                    title="{{ $isShortlisted ? 'Remove from shortlist' : 'Add to shortlist' }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24"
                                         fill="{{ $isShortlisted ? 'currentColor' : 'none' }}"
                                         stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">{{ $this->freelancers->links() }}</div>
            @endif
        </div>

        {{-- ── Shortlist sidebar ───────────────────────────────── --}}
        @if(count($shortlisted) > 0)
            <div class="w-64 flex-shrink-0 hidden xl:block">
                <div class="sticky top-20 space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">
                            Your shortlist ({{ count($shortlisted) }})
                        </p>
                        <button wire:click="$set('shortlisted', [])"
                            class="text-[10px] text-[#506070] hover:text-red-400 transition-colors">
                            Clear all
                        </button>
                    </div>

                    @foreach($this->shortlistedProfiles as $sp)
                        <div class="flex items-center gap-2.5 bg-[#0e1420] border border-[#1c2e45] rounded-xl p-3"
                             wire:key="sl-{{ $sp->user_id }}">
                            <flux:avatar src="{{ $sp->user->avatar_url }}" name="{{ $sp->user->name }}" size="xs"/>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-white truncate">{{ $sp->user->name }}</p>
                                @if($sp->hourly_rate)
                                    <p class="text-[10px] font-mono text-[#7EE8A2]">${{ number_format($sp->hourly_rate) }}/hr</p>
                                @endif
                            </div>
                            <button wire:click="shortlist({{ $sp->user_id }})"
                                class="text-[#506070] hover:text-red-400 transition-colors flex-shrink-0">
                                <flux:icon.x-mark class="size-3.5"/>
                            </button>
                        </div>
                    @endforeach

                    <div class="space-y-2 pt-1">
                        <flux:button variant="primary" size="sm" class="w-full" icon="paper-airplane"
                            href="{{ route('backend.jobPostCreate') }}" wire:navigate>
                            Post a job to them
                        </flux:button>
                        <a href="{{ route('backend.contracts') }}" wire:navigate
                           class="flex items-center justify-center gap-1.5 w-full py-2 rounded-xl border border-[#1c2e45] text-xs text-[#8da0b8] hover:border-[#254060] hover:text-[#dde6f0] transition-all">
                            <flux:icon.document-text class="size-3.5"/>
                            Create contract
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- ── Quick view drawer ────────────────────────────────────────── --}}
@if($quickViewId && $this->quickViewProfile)
    @php $sp = $this->quickViewProfile; @endphp
    <div class="fixed inset-0 z-40 flex justify-end">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"
             wire:click="closeQuickView"></div>
        <div class="relative z-50 w-full max-w-lg bg-[#0e1420] border-l border-[#1c2e45] h-full overflow-y-auto shadow-2xl"
             style="animation:slideIn .25s ease both">

            <div class="flex items-center justify-between px-5 py-4 border-b border-[#1c2e45] sticky top-0 bg-[#0e1420] z-10">
                <flux:heading>Freelancer profile</flux:heading>
                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="closeQuickView"/>
            </div>

            <div class="p-5 space-y-5">

                {{-- Header --}}
                <div class="flex items-start gap-3">
                    <flux:avatar src="{{ $sp->user->avatar_url }}" name="{{ $sp->user->name }}" size="xl"
                        class="ring-2 ring-[#7EE8A2]/20 flex-shrink-0"/>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="font-['Syne'] font-bold text-lg text-white">{{ $sp->user->name }}</h2>
                            @if($sp->is_verified)
                                <flux:icon.check-badge class="size-4.5 text-[#7EE8A2]"/>
                            @endif
                        </div>
                        <p class="text-sm text-[#8da0b8] mt-0.5">{{ $sp->headline }}</p>
                        <div class="flex items-center gap-3 mt-2 text-xs text-[#506070]">
                            @if($sp->location)
                                <span class="flex items-center gap-1"><flux:icon.map-pin class="size-3"/>{{ $sp->location }}</span>
                            @endif
                            @if($sp->years_experience)
                                <span>{{ $sp->years_experience }}y exp</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Key stats --}}
                <div class="grid grid-cols-3 gap-3">
                    <div class="text-center p-3 bg-[#131d2e] rounded-xl border border-[#1c2e45]">
                        <p class="font-['Syne'] text-lg font-bold text-[#7EE8A2]">
                            @if($sp->hourly_rate) ${{ number_format($sp->hourly_rate) }} @else — @endif
                        </p>
                        <p class="text-[10px] text-[#506070]">per hour</p>
                    </div>
                    <div class="text-center p-3 bg-[#131d2e] rounded-xl border border-[#1c2e45]">
                        <p class="font-['Syne'] text-lg font-bold text-[#fbbf24]">
                            {{ $sp->avg_rating > 0 ? number_format($sp->avg_rating, 1) : '—' }}
                        </p>
                        <p class="text-[10px] text-[#506070]">{{ $sp->total_reviews }} reviews</p>
                    </div>
                    <div class="text-center p-3 bg-[#131d2e] rounded-xl border border-[#1c2e45]">
                        <p class="font-['Syne'] text-lg font-bold text-white">
                            {{ $sp->response_time_hours ? '~'.$sp->response_time_hours.'h' : '—' }}
                        </p>
                        <p class="text-[10px] text-[#506070]">response</p>
                    </div>
                </div>

                {{-- Bio --}}
                @if($sp->bio)
                    <div>
                        <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070] mb-2">About</p>
                        <p class="text-sm text-[#8da0b8] leading-relaxed line-clamp-4">{{ $sp->bio }}</p>
                    </div>
                @endif

                {{-- Skills --}}
                @if($sp->skills)
                    <div>
                        <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070] mb-2">Skills</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach((array)$sp->skills as $skill)
                                <flux:badge size="sm" color="zinc">{{ $skill }}</flux:badge>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Recent portfolio items --}}
                @if($sp->user->portfolioItems->isNotEmpty())
                    <div>
                        <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070] mb-2">Portfolio</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($sp->user->portfolioItems as $pt)
                                <div class="bg-[#131d2e] rounded-xl border border-[#1c2e45] p-3">
                                    <p class="text-xs font-semibold text-white truncate">{{ $pt->title }}</p>
                                    @if($pt->tech_stack)
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach(array_slice((array)$pt->tech_stack, 0, 2) as $t)
                                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-[#0e1420] text-[#506070] border border-[#1c2e45]">{{ $t }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($pt->project_url)
                                        <a href="{{ $pt->project_url }}" target="_blank"
                                           class="text-[10px] text-[#7EE8A2] hover:underline flex items-center gap-0.5 mt-1.5">
                                            <flux:icon.arrow-top-right-on-square class="size-2.5"/>View
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Recent reviews --}}
                @if($sp->user->reviews->isNotEmpty())
                    <div>
                        <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070] mb-2">Recent reviews</p>
                        @foreach($sp->user->reviews as $r)
                            <div class="mb-3 pb-3 border-b border-[#1c2e45] last:border-b-0 last:mb-0 last:pb-0">
                                <div class="flex items-center gap-1 mb-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg width="10" height="10" viewBox="0 0 24 24"
                                             fill="{{ $i <= $r->rating ? '#f59e0b' : '#1c2e45' }}">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                    @endfor
                                </div>
                                <p class="text-xs text-[#8da0b8] line-clamp-2">{{ $r->body }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- CTAs --}}
                <div class="flex flex-col gap-2 pt-2 border-t border-[#1c2e45]">
                    <a href="{{ route('backend.bookingPage', $sp->user->name) }}" wire:navigate
                       class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl text-sm font-semibold bg-[#7EE8A2] text-[#080c14] hover:bg-[#9ef7b8] transition-all">
                        <flux:icon.calendar class="size-4"/>
                        Book a session
                    </a>
                    <a href="{{ route('backend.contracts') }}" wire:navigate
                       class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl text-sm font-medium bg-[#131d2e] border border-[#1c2e45] text-[#dde6f0] hover:border-[#254060] transition-all">
                        <flux:icon.document-text class="size-4"/>
                        Create a contract
                    </a>
                    <a href="{{ route('backend.profilePage', $sp->user->name) }}" wire:navigate
                       class="text-center text-xs text-[#506070] hover:text-[#dde6f0] transition-colors py-1">
                        View full profile & portfolio →
                    </a>
                    <button wire:click="shortlist({{ $sp->user_id }}); closeQuickView()"
                        class="text-xs transition-colors py-1
                            {{ in_array($sp->user_id, $shortlisted) ? 'text-red-400 hover:text-red-300' : 'text-[#506070] hover:text-[#7EE8A2]' }}">
                        {{ in_array($sp->user_id, $shortlisted) ? '★ Remove from shortlist' : '☆ Add to shortlist' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <style>@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}</style>
@endif
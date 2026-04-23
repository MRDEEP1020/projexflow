<div class="space-y-6 max-w-7xl">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Marketplace</flux:heading>
            <flux:text class="mt-0.5">Find verified professionals across Africa and beyond.</flux:text>
        </div>
        <flux:button variant="primary" size="sm" icon="star"
            href="{{ route('backend.editProfile') }}" wire:navigate>
            My marketplace profile
        </flux:button>
    </div>

    {{-- Featured --}}
    @if($this->featured->isNotEmpty() && !$search)
        <div>
            <p class="text-[10px] font-mono uppercase tracking-widest text-[#506070] mb-3">
                ⚡ Top verified professionals
            </p>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach($this->featured as $sp)
                    <a href="{{ route('backend.profilePage', $sp->user->name) }}" wire:navigate
                       class="group flex flex-col gap-3 bg-gradient-to-br from-[#0e1420] to-[#131d2e] border border-[#7EE8A2]/20 rounded-2xl p-4 hover:border-[#7EE8A2]/40 transition-all hover:-translate-y-0.5">
                        <div class="flex items-center gap-2.5">
                            <flux:avatar src="{{ $sp->user->avatar_url }}" name="{{ $sp->user->name }}" size="sm"/>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-white truncate font-['Syne']">{{ $sp->user->name }}</p>
                                <p class="text-[10px] text-[#506070] truncate">{{ $sp->profession_category }}</p>
                            </div>
                            <flux:icon.check-badge class="size-4 text-[#7EE8A2] flex-shrink-0 ml-auto" title="Verified"/>
                        </div>
                        <p class="text-xs text-[#8da0b8] line-clamp-2 leading-relaxed">{{ $sp->headline }}</p>
                        <div class="flex items-center justify-between mt-auto">
                            <div class="flex items-center gap-1">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="#f59e0b"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                <span class="text-xs font-mono text-[#fbbf24]">{{ number_format($sp->avg_rating, 1) }}</span>
                                <span class="text-[10px] text-[#506070]">({{ $sp->total_reviews }})</span>
                            </div>
                            @if($sp->hourly_rate)
                                <span class="text-xs text-[#7EE8A2] font-mono">${{ number_format($sp->hourly_rate) }}/hr</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Search + filters --}}
    <div class="flex flex-wrap gap-3">
        <div class="flex-1 min-w-48">
            <flux:input wire:model.live.debounce.300ms="search"
                placeholder="Search by skill, name, or expertise…"
                icon="magnifying-glass" clearable/>
        </div>

        <flux:select wire:model.live="category" class="w-44">
            <flux:select.option value="all">All categories</flux:select.option>
            @foreach([
                'software_dev'     => 'Software Dev',
                'ui_ux'            => 'UI/UX Design',
                'digital_marketing'=> 'Digital Marketing',
                'data_analytics'   => 'Data Analytics',
                'content_writing'  => 'Content Writing',
                'project_management'=> 'Project Management',
                'cybersecurity'    => 'Cybersecurity',
                'video_editing'    => 'Video Editing',
                'virtual_assistant'=> 'Virtual Assistant',
                'ai_ml'            => 'AI / ML',
                'other'            => 'Other',
            ] as $val => $lbl)
                <flux:select.option value="{{ $val }}">{{ $lbl }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="availability" class="w-36">
            <flux:select.option value="all">Any availability</flux:select.option>
            <flux:select.option value="open_to_work">Open to work</flux:select.option>
            <flux:select.option value="busy">Busy</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="minRating" class="w-36">
            <flux:select.option value="0">Any rating</flux:select.option>
            <flux:select.option value="3">3★ and up</flux:select.option>
            <flux:select.option value="4">4★ and up</flux:select.option>
            <flux:select.option value="5">5★ only</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="sortBy" class="w-36">
            <flux:select.option value="rank">Best match</flux:select.option>
            <flux:select.option value="rating">Highest rated</flux:select.option>
            <flux:select.option value="reviews">Most reviewed</flux:select.option>
            <flux:select.option value="rate_asc">Lowest rate</flux:select.option>
            <flux:select.option value="rate_desc">Highest rate</flux:select.option>
        </flux:select>

        @if($search || $category !== 'all' || $availability !== 'all' || $minRating > 0)
            <flux:button variant="ghost" size="sm" wire:click="clearFilters">
                Clear filters
            </flux:button>
        @endif
    </div>

    {{-- Results --}}
    @if($this->profiles->isEmpty())
        <div class="flex flex-col items-center gap-3 py-16 bg-[#0e1420] border border-dashed border-[#1c2e45] rounded-2xl text-center">
            <flux:icon.user-group class="size-8 text-[#506070]"/>
            <flux:heading>No professionals found</flux:heading>
            <flux:text class="text-sm">Try adjusting your filters or search term.</flux:text>
            <flux:button variant="ghost" size="sm" wire:click="clearFilters">Reset filters</flux:button>
        </div>
    @else
        <flux:table :paginate="$this->profiles">
            <flux:table.columns>
                <flux:table.column>Professional</flux:table.column>
                <flux:table.column>Skills</flux:table.column>
                <flux:table.column>Rating</flux:table.column>
                <flux:table.column>Rate</flux:table.column>
                <flux:table.column>Availability</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->profiles as $sp)
                    <flux:table.row :key="$sp->id" wire:key="sp-{{ $sp->id }}">

                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <flux:avatar src="{{ $sp->user->avatar_url }}" name="{{ $sp->user->name }}" size="sm"/>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-sm font-semibold text-white">{{ $sp->user->name }}</p>
                                        @if($sp->is_verified)
                                            <flux:icon.check-badge class="size-3.5 text-[#7EE8A2]" title="Verified"/>
                                        @endif
                                    </div>
                                    <p class="text-xs text-[#506070]">{{ $sp->headline }}</p>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1 max-w-xs">
                                @foreach(array_slice((array)$sp->skills, 0, 3) as $skill)
                                    <flux:badge size="sm" color="zinc">{{ $skill }}</flux:badge>
                                @endforeach
                                @if(count((array)$sp->skills) > 3)
                                    <span class="text-[10px] text-[#506070]">+{{ count((array)$sp->skills) - 3 }}</span>
                                @endif
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-1">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="#f59e0b"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                <span class="text-sm font-mono text-[#fbbf24]">{{ number_format($sp->avg_rating, 1) }}</span>
                                <span class="text-xs text-[#506070]">({{ $sp->total_reviews }})</span>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            @if($sp->hourly_rate)
                                <span class="text-sm font-mono text-[#7EE8A2]">
                                    ${{ number_format($sp->hourly_rate) }}<span class="text-[10px] text-[#506070]">/hr</span>
                                </span>
                            @else
                                <span class="text-xs text-[#506070]">—</span>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm"
                                :color="match($sp->availability_status) {
                                    'open_to_work'  => 'green',
                                    'busy'          => 'yellow',
                                    'not_available' => 'red',
                                    default         => 'zinc',
                                }"
                            >{{ ucfirst(str_replace('_',' ',$sp->availability_status)) }}</flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex gap-1">
                                <flux:button variant="primary" size="sm"
                                    href="{{ route('backend.profilePage', $sp->user->name) }}" wire:navigate>
                                    View
                                </flux:button>
                                <flux:button variant="ghost" size="sm" icon="calendar"
                                    href="{{ route('backend.bookingPage', $sp->user->name) }}" wire:navigate
                                    title="Book a session"/>
                            </div>
                        </flux:table.cell>

                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</div>

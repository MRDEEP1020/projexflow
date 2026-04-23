<div class="max-w-4xl space-y-6">

    {{-- ── Profile hero ──────────────────────────────────────── --}}
    <flux:card class="bg-[#0e1420] border-[#1c2e45] !p-0 overflow-hidden">

        {{-- Cover gradient --}}
        <div class="h-24 bg-gradient-to-br from-[#0d1825] via-[#1a2438] to-[#080c14]"></div>

        <div class="px-6 pb-6">
            <div class="flex items-end justify-between -mt-10 mb-4">
                <flux:avatar
                    src="{{ $profileUser->avatar_url }}"
                    name="{{ $profileUser->name }}"
                    size="xl"
                    class="ring-4 ring-[#0e1420]"
                />
                <div class="flex gap-2">
                    @if($isOwn)
                        <flux:button variant="ghost" size="sm" icon="pencil-square"
                            href="{{ route('backend.editProfile') }}" wire:navigate>
                            Edit profile
                        </flux:button>
                    @else
                        <flux:button variant="ghost" size="sm" icon="calendar"
                            href="{{ route('backend.bookingPage', $profileUser->name) }}" wire:navigate>
                            Book session
                        </flux:button>
                        <flux:button variant="primary" size="sm" icon="chat-bubble-left">
                            Message
                        </flux:button>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-start gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="font-['Syne'] text-xl font-bold text-white">{{ $profileUser->name }}</h1>
                        @if($this->profile?->is_verified)
                            <flux:icon.check-badge class="size-5 text-[#7EE8A2]" title="Verified professional"/>
                        @endif
                        <flux:badge size="sm"
                            :color="match($this->profile?->availability_status) {
                                'open_to_work'  => 'green',
                                'busy'          => 'yellow',
                                'not_available' => 'red',
                                default         => 'zinc',
                            }"
                        >{{ ucfirst(str_replace('_',' ', $this->profile?->availability_status ?? 'unknown')) }}</flux:badge>
                    </div>
                    <p class="text-sm text-[#8da0b8] mt-1">{{ $this->profile?->headline }}</p>

                    <div class="flex flex-wrap items-center gap-4 mt-3 text-xs text-[#506070]">
                        @if($this->profile?->location)
                            <span class="flex items-center gap-1">
                                <flux:icon.map-pin class="size-3.5"/>
                                {{ $this->profile->location }}
                            </span>
                        @endif
                        @if($this->profile?->response_time_hours)
                            <span class="flex items-center gap-1">
                                <flux:icon.clock class="size-3.5"/>
                                Responds in ~{{ $this->profile->response_time_hours }}h
                            </span>
                        @endif
                        @if($this->profile?->years_experience)
                            <span class="flex items-center gap-1">
                                <flux:icon.briefcase class="size-3.5"/>
                                {{ $this->profile->years_experience }}y experience
                            </span>
                        @endif
                        @if($this->profile?->total_reviews > 0)
                            <span class="flex items-center gap-1 text-[#fbbf24]">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                {{ number_format($this->profile->avg_rating, 1) }}
                                <span class="text-[#506070]">({{ $this->profile->total_reviews }} reviews)</span>
                            </span>
                        @endif
                    </div>
                </div>

                @if($this->profile?->hourly_rate)
                    <div class="text-right flex-shrink-0">
                        <p class="font-['Syne'] text-2xl font-bold text-[#7EE8A2]">
                            ${{ number_format($this->profile->hourly_rate) }}
                        </p>
                        <p class="text-xs text-[#506070]">per hour · {{ $this->profile->currency ?? 'USD' }}</p>
                    </div>
                @endif
            </div>

            {{-- Skills --}}
            @if($this->profile?->skills)
                <div class="flex flex-wrap gap-1.5 mt-4">
                    @foreach((array)$this->profile->skills as $skill)
                        <flux:badge size="sm" color="zinc">{{ $skill }}</flux:badge>
                    @endforeach
                </div>
            @endif
        </div>
    </flux:card>

    {{-- ── About ──────────────────────────────────────────────── --}}
    @if($this->profile?->bio)
        <flux:card class="bg-[#0e1420] border-[#1c2e45]">
            <flux:heading size="sm" class="mb-3">About</flux:heading>
            <p class="text-sm text-[#8da0b8] leading-relaxed whitespace-pre-wrap">{{ $this->profile->bio }}</p>
        </flux:card>
    @endif

    {{-- ── Portfolio ─────────────────────────────────────────── --}}
    @if($this->portfolio->isNotEmpty())
        <div>
            <flux:heading size="lg" class="mb-4">Portfolio</flux:heading>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($this->portfolio as $item)
                    <div class="group bg-[#0e1420] border border-[#1c2e45] rounded-xl overflow-hidden hover:border-[#254060] transition-all"
                         wire:key="pi-{{ $item->id }}">
                        @if($item->cover_image)
                            <img src="{{ \Storage::url($item->cover_image) }}"
                                 class="w-full h-36 object-cover" alt="{{ $item->title }}">
                        @else
                            <div class="w-full h-36 bg-gradient-to-br from-[#131d2e] to-[#0e1420] flex items-center justify-center">
                                <flux:icon.folder-open class="size-8 text-[#254060]"/>
                            </div>
                        @endif
                        <div class="p-3.5">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-semibold text-white font-['Syne'] line-clamp-1">{{ $item->title }}</p>
                                @if($item->is_featured)
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="#f59e0b" class="flex-shrink-0 mt-0.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                @endif
                            </div>
                            @if($item->description)
                                <p class="text-xs text-[#506070] mt-1 line-clamp-2">{{ $item->description }}</p>
                            @endif
                            @if($item->tech_stack)
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @foreach(array_slice((array)$item->tech_stack, 0, 3) as $t)
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-[#131d2e] text-[#506070] border border-[#1c2e45]">{{ $t }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <div class="flex gap-2 mt-3">
                                @if($item->project_url)
                                    <a href="{{ $item->project_url }}" target="_blank"
                                       class="text-[11px] text-[#7EE8A2] hover:underline flex items-center gap-1">
                                        <flux:icon.arrow-top-right-on-square class="size-3"/>View
                                    </a>
                                @endif
                                @if($item->github_url)
                                    <a href="{{ $item->github_url }}" target="_blank"
                                       class="text-[11px] text-[#506070] hover:text-[#dde6f0] flex items-center gap-1">
                                        <flux:icon.code-bracket class="size-3"/>Code
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── Reviews ─────────────────────────────────────────────── --}}
    @if($this->reviews->isNotEmpty())
        <div>
            <flux:heading size="lg" class="mb-4">Reviews</flux:heading>
            <flux:card class="bg-[#0e1420] border-[#1c2e45]">

                {{-- Rating distribution --}}
                <div class="flex items-start gap-6 mb-5 pb-5 border-b border-[#1c2e45]">
                    <div class="text-center flex-shrink-0">
                        <p class="font-['Syne'] text-4xl font-extrabold text-white">
                            {{ number_format($this->profile?->avg_rating ?? 0, 1) }}
                        </p>
                        <div class="flex gap-0.5 justify-center mt-1">
                            @for($i = 1; $i <= 5; $i++)
                                <svg width="13" height="13" viewBox="0 0 24 24"
                                     fill="{{ $i <= round($this->profile?->avg_rating ?? 0) ? '#f59e0b' : '#1c2e45' }}">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            @endfor
                        </div>
                        <p class="text-[11px] text-[#506070] mt-1">{{ $this->profile?->total_reviews ?? 0 }} reviews</p>
                    </div>
                    <div class="flex-1 space-y-1.5">
                        @foreach($this->ratingBreakdown as $stars => $data)
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] text-[#506070] w-3">{{ $stars }}</span>
                                <div class="flex-1 h-1.5 bg-[#1c2e45] rounded-full overflow-hidden">
                                    <div class="h-full bg-[#fbbf24] rounded-full transition-all"
                                         style="width:{{ $data['pct'] }}%"></div>
                                </div>
                                <span class="text-[11px] text-[#506070] w-5 text-right">{{ $data['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Review list --}}
                <div class="space-y-4">
                    @foreach($this->reviews as $r)
                        <div class="pb-4 border-b border-[#1c2e45] last:border-b-0 last:pb-0" wire:key="rv-{{ $r->id }}">
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div class="flex items-center gap-2.5">
                                    <flux:avatar src="{{ $r->reviewer?->avatar_url }}" name="{{ $r->reviewer?->name ?? 'Anonymous' }}" size="xs"/>
                                    <div>
                                        <p class="text-sm font-medium text-[#dde6f0]">{{ $r->reviewer?->name ?? 'Client' }}</p>
                                        <div class="flex items-center gap-1 mt-0.5">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg width="10" height="10" viewBox="0 0 24 24"
                                                     fill="{{ $i <= $r->rating ? '#f59e0b' : '#1c2e45' }}">
                                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                </svg>
                                            @endfor
                                            @if($r->is_verified)
                                                <flux:badge size="sm" color="green" class="ml-1">Verified</flux:badge>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <span class="text-[11px] text-[#506070]">{{ $r->created_at->diffForHumans() }}</span>
                            </div>
                            @if($r->body)
                                <p class="text-sm text-[#8da0b8] leading-relaxed">{{ $r->body }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </flux:card>
        </div>
    @endif

</div>

<div class="max-w-3xl space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">My Marketplace Profile</flux:heading>
            <flux:text class="mt-0.5">How clients see you on the marketplace.</flux:text>
        </div>
        <flux:button variant="ghost" size="sm" icon="eye"
            href="{{ route('backend.profilePage', Auth::user()->name) }}" wire:navigate>
            View public profile
        </flux:button>
    </div>

    {{-- Completeness bar --}}
    <div class="flex items-center gap-3 p-3 bg-[#0e1420] border border-[#1c2e45] rounded-xl">
        <div class="flex-1">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs text-[#8da0b8]">Profile completeness</span>
                <span class="text-xs font-mono text-[#7EE8A2]">{{ $this->completeness }}%</span>
            </div>
            <div class="h-1.5 bg-[#1c2e45] rounded-full overflow-hidden">
                <div class="h-full bg-[#7EE8A2] rounded-full transition-all" style="width:{{ $this->completeness }}%">
                </div>
            </div>
        </div>
        @if ($this->completeness < 100)
            <flux:text class="text-xs">Complete your profile to appear higher in search results.</flux:text>
        @else
            <flux:badge size="sm" color="green">Complete!</flux:badge>
        @endif
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-[#1c2e45]">
        @foreach (['profile' => 'Profile', 'services' => 'Services', 'portfolio' => 'Portfolio'] as $key => $label)
            <button wire:click="$set('tab','{{ $key }}')"
                class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-all"
                style="{{ $tab === $key ? 'border-color:#7EE8A2;color:#7EE8A2' : 'border-color:transparent;color:#8da0b8' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ── PROFILE TAB ────────────────────────────────────────── --}}
    @if ($tab === 'profile')
        <flux:card class="bg-[#0e1420] border-[#1c2e45] space-y-5" wire:key="profile-tab">
            <form wire:submit="saveProfile" class="space-y-5">

                <flux:field>
                    <flux:label>Headline <<span class="text-red-400">*</span>/></flux:label>
                    <flux:input wire:model="headline"
                        placeholder="e.g. Full-Stack Developer · Laravel & Vue · 5 years exp" />
                    <flux:error name="headline" />
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Category</flux:label>
                        <flux:select wire:model="category">
                            <flux:select.option value="">Select category</flux:select.option>
                            @foreach (['software_dev' => 'Software Dev', 'ui_ux' => 'UI/UX Design', 'digital_marketing' => 'Digital Marketing', 'data_analytics' => 'Data Analytics', 'content_writing' => 'Content Writing', 'project_management' => 'Project Management', 'cybersecurity' => 'Cybersecurity', 'video_editing' => 'Video Editing', 'virtual_assistant' => 'Virtual Assistant', 'ai_ml' => 'AI / ML', 'other' => 'Other'] as $v => $l)
                                <flux:select.option value="{{ $v }}">{{ $l }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Availability</flux:label>
                        <flux:select wire:model="availability">
                            <flux:select.option value="open_to_work">Open to work</flux:select.option>
                            <flux:select.option value="busy">Busy — taking limited work</flux:select.option>
                            <flux:select.option value="not_available">Not available</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <flux:field>
                        <flux:label>Hourly rate</flux:label>
                        <flux:input wire:model="hourlyRate" type="number" min="1" placeholder="25" />
                        <flux:error name="hourlyRate" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Currency</flux:label>
                        <flux:select wire:model="currency">
                            <flux:select.option value="USD">USD $</flux:select.option>
                            <flux:select.option value="EUR">EUR €</flux:select.option>
                            <flux:select.option value="XAF">XAF (FCFA)</flux:select.option>
                            <flux:select.option value="GBP">GBP £</flux:select.option>
                            <flux:select.option value="NGN">NGN ₦</flux:select.option>
                            <flux:select.option value="KES">KES</flux:select.option>
                            <flux:select.option value="GHS">GHS ₵</flux:select.option>
                        </flux:select>
                    </flux:field>
                    <flux:field>
                        <flux:label>Session (min)</flux:label>
                        <flux:select wire:model="sessionDuration">
                            <flux:select.option value="30">30 min</flux:select.option>
                            <flux:select.option value="45">45 min</flux:select.option>
                            <flux:select.option value="60">60 min</flux:select.option>
                            <flux:select.option value="90">90 min</flux:select.option>
                            <flux:select.option value="120">2 hours</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Years of experience</flux:label>
                        <flux:input wire:model="yearsExp" type="number" min="0" max="50"
                            placeholder="5" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Typical response time</flux:label>
                        <flux:select wire:model="responseTime">
                            <flux:select.option value="1">Within 1 hour</flux:select.option>
                            <flux:select.option value="2">Within 2 hours</flux:select.option>
                            <flux:select.option value="12">Within 12 hours</flux:select.option>
                            <flux:select.option value="24">Within 24 hours</flux:select.option>
                            <flux:select.option value="48">Within 48 hours</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Location</flux:label>
                    <flux:input wire:model="location" placeholder="e.g. Douala, Cameroon" icon="map-pin" />
                </flux:field>

                {{-- Skills --}}
                <flux:field>
                    <flux:label>Skills</flux:label>
                    <div class="flex gap-2">
                        <flux:input wire:model="newSkill" placeholder="Add a skill…" class="flex-1"
                            wire:keydown.enter.prevent="addSkill" />
                        <flux:button type="button" variant="ghost" size="sm" icon="plus" wire:click="addSkill">
                            Add</flux:button>
                    </div>
                    @if (count($skills) > 0)
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            @foreach ($skills as $i => $skill)
                                <div
                                    class="flex items-center gap-1 px-2.5 py-1 rounded-lg border border-[#1c2e45] bg-[#131d2e]">
                                    <span class="text-xs text-[#dde6f0]">{{ $skill }}</span>
                                    <button type="button" wire:click="removeSkill({{ $i }})"
                                        class="text-[#506070] hover:text-red-400 transition-colors">
                                        <flux:icon.x-mark class="size-3" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </flux:field>

                {{-- Languages --}}
                <flux:field>
                    <flux:label>Languages</flux:label>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['English', 'French', 'Arabic', 'Swahili', 'Hausa', 'Yoruba', 'Igbo', 'Portuguese', 'Spanish'] as $lang)
                            <button type="button" wire:click="toggleLanguage('{{ $lang }}')"
                                class="px-3 py-1.5 rounded-lg border text-xs font-medium transition-all
                                    {{ in_array($lang, $languages)
                                        ? 'border-[#7EE8A2] bg-[#7EE8A2]/08 text-[#7EE8A2]'
                                        : 'border-[#1c2e45] text-[#506070] hover:border-[#254060]' }}">{{ $lang }}</button>
                        @endforeach
                    </div>
                </flux:field>

                <flux:field>
                    <flux:label>Bio</flux:label>
                    <flux:textarea wire:model="bio" rows="5"
                        placeholder="Tell clients about your background, expertise, and what makes you great to work with…" />
                    <flux:error name="bio" />
                </flux:field>

                <div class="flex justify-end pt-2 border-t border-[#1c2e45]">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>Save profile</span>
                        <span wire:loading>Saving…</span>
                    </flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    {{-- ── SERVICES TAB ───────────────────────────────────────── --}}
    @if ($tab === 'services')
        <div class="space-y-4" wire:key="services-tab">
            <div class="flex justify-end">
                <flux:button variant="primary" size="sm" icon="plus" wire:click="openServiceForm()">Add
                    service</flux:button>
            </div>

            @if ($this->services->isEmpty())
                <div
                    class="flex flex-col items-center gap-2 py-10 bg-[#0e1420] border border-dashed border-[#1c2e45] rounded-2xl text-center">
                    <flux:icon.shopping-bag class="size-8 text-[#506070]" />
                    <flux:text class="text-sm">No services yet. Add what you offer to clients.</flux:text>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($this->services as $svc)
                        <div class="flex items-start gap-3 p-4 bg-[#0e1420] border border-[#1c2e45] rounded-xl"
                            wire:key="svc-{{ $svc->id }}">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-white">{{ $svc->title }}</p>
                                    <flux:badge size="sm" :color="$svc->is_active ? 'green' : 'zinc'">
                                        {{ $svc->is_active ? 'Active' : 'Inactive' }}
                                    </flux:badge>
                                </div>
                                @if ($svc->description)
                                    <p class="text-xs text-[#8da0b8] mt-1 line-clamp-2">{{ $svc->description }}</p>
                                @endif
                                <div class="flex items-center gap-3 mt-2 text-xs text-[#506070]">
                                    @if ($svc->price_from)
                                        <span class="text-[#7EE8A2]">
                                            From ${{ number_format($svc->price_from) }}
                                            @if ($svc->price_to)
                                                – ${{ number_format($svc->price_to) }}
                                            @endif
                                        </span>
                                    @endif
                                    @if ($svc->delivery_days)
                                        <span>{{ $svc->delivery_days }}d delivery</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <flux:button variant="ghost" size="sm" icon="pencil"
                                    wire:click="openServiceForm({{ $svc->id }})" />
                                <flux:button variant="ghost" size="sm"
                                    :icon="$svc->is_active ? 'eye-slash' : 'eye'"
                                    wire:click="toggleService({{ $svc->id }})" class="text-[#506070]" />
                                <flux:button variant="ghost" size="sm" icon="trash"
                                    wire:click="deleteService({{ $svc->id }})"
                                    class="text-[#506070] hover:text-red-400" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- ── PORTFOLIO TAB ──────────────────────────────────────── --}}
    @if ($tab === 'portfolio')
        <div class="space-y-4" wire:key="portfolio-tab">
            <div class="flex justify-end">
                <flux:button variant="primary" size="sm" icon="plus" wire:click="openPortfolioForm()">Add
                    project</flux:button>
            </div>

            @if ($this->portfolioItems->isEmpty())
                <div
                    class="flex flex-col items-center gap-2 py-10 bg-[#0e1420] border border-dashed border-[#1c2e45] rounded-2xl text-center">
                    <flux:icon.folder-open class="size-8 text-[#506070]" />
                    <flux:text class="text-sm">No portfolio items yet. Showcase your best work.</flux:text>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($this->portfolioItems as $pt)
                        <div class="flex items-start gap-3 p-4 bg-[#0e1420] border border-[#1c2e45] rounded-xl"
                            wire:key="pt-{{ $pt->id }}">
                            @if ($pt->cover_image)
                                <img src="{{ Storage::url($pt->cover_image) }}"
                                    class="w-16 h-16 rounded-xl object-cover border border-[#1c2e45] flex-shrink-0" />
                            @else
                                <div
                                    class="w-16 h-16 rounded-xl bg-[#131d2e] border border-[#1c2e45] flex-shrink-0 flex items-center justify-center">
                                    <flux:icon.folder class="size-6 text-[#506070]" />
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-white">{{ $pt->title }}</p>
                                    @if ($pt->is_featured)
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="#f59e0b">
                                            <path
                                                d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                        </svg>
                                    @endif
                                    @if (!$pt->is_public)
                                        <flux:badge size="sm" color="zinc">Private</flux:badge>
                                    @endif
                                </div>
                                @if ($pt->description)
                                    <p class="text-xs text-[#8da0b8] mt-0.5 line-clamp-1">{{ $pt->description }}</p>
                                @endif
                                @if ($pt->tech_stack)
                                    <div class="flex flex-wrap gap-1 mt-1.5">
                                        @foreach (array_slice((array) $pt->tech_stack, 0, 4) as $t)
                                            <span
                                                class="text-[10px] px-1.5 py-0.5 rounded bg-[#131d2e] text-[#506070] border border-[#1c2e45]">{{ $t }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="flex gap-1">
                                <flux:button variant="ghost" size="sm" icon="pencil"
                                    wire:click="openPortfolioForm({{ $pt->id }})" />
                                <flux:button variant="ghost" size="sm" icon="trash"
                                    wire:click="deletePortfolio({{ $pt->id }})"
                                    class="text-[#506070] hover:text-red-400" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Service form modal --}}
    <flux:modal wire:model="showServiceForm" class="max-w-lg">
        <div class="space-y-4">
            <flux:heading>{{ $editServiceId ? 'Edit service' : 'Add service' }}</flux:heading>
            <form wire:submit="saveService" class="space-y-4">
                <flux:field>
                    <flux:label>Title <<span class="text-red-400">*</span>/></flux:label>
                    <flux:input wire:model="svcTitle" placeholder="e.g. Full-Stack Web Application" />
                    <flux:error name="svcTitle" />
                </flux:field>
                <flux:field>
                    <flux:label>Category <<span class="text-red-400">*</span>/></flux:label>
                    <flux:select wire:model="svcCategory">
                        <flux:select.option value="">Select…</flux:select.option>
                        @foreach (['software_dev' => 'Software Dev', 'ui_ux' => 'UI/UX', 'digital_marketing' => 'Digital Marketing', 'content_writing' => 'Content Writing', 'data_analytics' => 'Data Analytics', 'other' => 'Other'] as $v => $l)
                            <flux:select.option value="{{ $v }}">{{ $l }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="svcCategory" />
                </flux:field>
                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="svcDescription" rows="3" placeholder="What do you deliver?" />
                </flux:field>
                <div class="grid grid-cols-3 gap-3">
                    <flux:field>
                        <flux:label>Price from</flux:label>
                        <flux:input wire:model="svcPriceFrom" type="number" min="1" placeholder="100" />
                        <flux:error name="svcPriceFrom" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Price to</flux:label>
                        <flux:input wire:model="svcPriceTo" type="number" min="1" placeholder="500" />
                        <flux:error name="svcPriceTo" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Delivery days</flux:label>
                        <flux:input wire:model="svcDeliveryDays" type="number" min="1" placeholder="7" />
                    </flux:field>
                </div>
                <flux:field>
                    <flux:label>Currency</flux:label>
                    <flux:select wire:model="svcCurrency">
                        <flux:select.option value="USD">USD</flux:select.option>
                        <flux:select.option value="EUR">EUR</flux:select.option>
                        <flux:select.option value="XAF">XAF (FCFA)</flux:select.option>
                    </flux:select>
                </flux:field>
                <div class="flex justify-end gap-2 pt-1">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary">Save service</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Portfolio form modal --}}
    <flux:modal wire:model="showPortfolioForm" class="max-w-lg">
        <div class="space-y-4">
            <flux:heading>{{ $editPortfolioId ? 'Edit project' : 'Add project' }}</flux:heading>
            <form wire:submit="savePortfolio" class="space-y-4">
                <flux:field>
                    <flux:label>Title <<span class="text-red-400">*</span>/></flux:label>
                    <flux:input wire:model="ptTitle" placeholder="Project name" />
                    <flux:error name="ptTitle" />
                </flux:field>
                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="ptDescription" rows="3"
                        placeholder="What did you build and what problem did it solve?" />
                </flux:field>
                <div class="grid grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Project URL</flux:label>
                        <flux:input wire:model="ptProjectUrl" type="url" placeholder="https://…" />
                        <flux:error name="ptProjectUrl" />
                    </flux:field>
                    <flux:field>
                        <flux:label>GitHub URL</flux:label>
                        <flux:input wire:model="ptGithubUrl" type="url" placeholder="https://github.com/…" />
                    </flux:field>
                </div>
                <flux:field>
                    <flux:label>Tech stack</flux:label>
                    <div class="flex gap-2">
                        <flux:input wire:model="ptNewTech" placeholder="e.g. Laravel" class="flex-1"
                            wire:keydown.enter.prevent="$set('ptTechStack', [...ptTechStack, ptNewTech]); $set('ptNewTech', '')" />
                        <flux:button type="button" variant="ghost" size="sm" icon="plus"
                            wire:click="$set('ptTechStack', array_merge(ptTechStack, [ptNewTech])); $set('ptNewTech','')">
                            Add</flux:button>
                    </div>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @foreach ($ptTechStack as $ti => $t)
                            <div
                                class="flex items-center gap-1 px-2 py-1 rounded-lg border border-[#1c2e45] bg-[#131d2e]">
                                <span class="text-xs text-[#dde6f0]">{{ $t }}</span>
                                <button type="button"
                                    wire:click="$set('ptTechStack', array_values(array_filter(ptTechStack, fn($v,$k) => $k !== {{ $ti }}, ARRAY_FILTER_USE_BOTH)))">
                                    <flux:icon.x-mark class="size-3 text-[#506070]" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                </flux:field>
                <flux:field>
                    <flux:label>Cover image</flux:label>
                    <input type="file" wire:model="ptCover" accept="image/*" class="text-sm text-[#8da0b8]">
                    <flux:error name="ptCover" />
                </flux:field>
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <flux:switch wire:model="ptIsPublic" />
                        <span class="text-sm text-[#8da0b8]">Public</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <flux:switch wire:model="ptIsFeatured" />
                        <span class="text-sm text-[#8da0b8]">Featured</span>
                    </label>
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>Save project</span>
                        <span wire:loading>Saving…</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>

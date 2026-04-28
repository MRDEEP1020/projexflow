<div class="max-w-3xl space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Post a Job</flux:heading>
            <flux:text class="mt-0.5">Describe what you need — freelancers will apply directly.</flux:text>
        </div>
        <flux:button variant="ghost" size="sm" wire:click="saveDraft" icon="document">
            Save draft
        </flux:button>
    </div>

    {{-- Writing tips --}}
    <flux:callout icon="light-bulb" color="blue">
        <flux:callout.heading>Tips for better applicants</flux:callout.heading>
        <flux:callout.text>
            Jobs with a clear description, defined budget, and required skills get 3× more qualified applicants.
            Be specific about deliverables and timeline.
        </flux:callout.text>
    </flux:callout>

    <flux:card class="bg-[#0e1420] border-[#1c2e45] space-y-6">

        {{-- Title --}}
        <flux:field>
            <flux:label>Job title <<span class="text-red-400">*</span>/></flux:label>
            <flux:input wire:model="title"
                placeholder="e.g. Laravel Developer for SaaS Dashboard — 3 week project"
                icon="briefcase"/>
            <flux:description>Be specific. Include the tech, deliverable, or timeframe.</flux:description>
            <flux:error name="title"/>
        </flux:field>

        {{-- Category + Type --}}
        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Category <<span class="text-red-400">*</span>/></flux:label>
                <flux:select wire:model="category">
                    <flux:select.option value="">Select category…</flux:select.option>
                    @foreach([
                        'software_dev'      => 'Software Development',
                        'ui_ux'             => 'UI/UX Design',
                        'digital_marketing' => 'Digital Marketing',
                        'data_analytics'    => 'Data Analytics',
                        'content_writing'   => 'Content Writing',
                        'video_editing'     => 'Video Editing',
                        'virtual_assistant' => 'Virtual Assistant',
                        'cybersecurity'     => 'Cybersecurity',
                        'ai_ml'             => 'AI / ML',
                        'project_management'=> 'Project Management',
                        'other'             => 'Other',
                    ] as $val => $lbl)
                        <flux:select.option value="{{ $val }}">{{ $lbl }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="category"/>
            </flux:field>

            <flux:field>
                <flux:label>Contract type</flux:label>
                <div class="flex gap-2 mt-1">
                    @foreach(['fixed' => '🎯 Fixed price', 'hourly' => '⏱ Hourly rate'] as $val => $lbl)
                        <label class="flex-1 flex items-center justify-center gap-1.5 p-2.5 rounded-xl border cursor-pointer text-xs font-medium transition-all
                            {{ $type === $val
                                ? 'border-[#7EE8A2] bg-[#7EE8A2]/06 text-[#7EE8A2]'
                                : 'border-[#1c2e45] text-[#506070] hover:border-[#254060]' }}">
                            <input type="radio" wire:model.live="type" value="{{ $val }}" class="sr-only">
                            {{ $lbl }}
                        </label>
                    @endforeach
                </div>
            </flux:field>
        </div>

        {{-- Description --}}
        <flux:field>
            <flux:label>Job description <<span class="text-red-400">*</span>/></flux:label>
            <flux:textarea wire:model="description" rows="8"
                placeholder="Describe the project in detail:

• What needs to be built or done?
• What are the key deliverables?
• What tech stack or tools are required?
• What does success look like?
• Any existing code or assets to work with?"/>
            <flux:description>Minimum 50 characters. The more detail, the better your applicants.</flux:description>
            <flux:error name="description"/>
        </flux:field>

        {{-- Budget --}}
        <div class="space-y-2">
            <flux:label>Budget</flux:label>
            <div class="grid grid-cols-3 gap-3">
                <flux:field>
                    <flux:label class="text-xs text-[#506070]">
                        {{ $type === 'hourly' ? 'Min $/hr' : 'Min budget' }}
                    </flux:label>
                    <flux:input wire:model="budgetMin" type="number" min="1" placeholder="100"/>
                    <flux:error name="budgetMin"/>
                </flux:field>
                <flux:field>
                    <flux:label class="text-xs text-[#506070]">
                        {{ $type === 'hourly' ? 'Max $/hr' : 'Max budget' }}
                    </flux:label>
                    <flux:input wire:model="budgetMax" type="number" min="1" placeholder="500"/>
                    <flux:error name="budgetMax"/>
                </flux:field>
                <flux:field>
                    <flux:label class="text-xs text-[#506070]">Currency</flux:label>
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
            </div>
            @if($budgetMin && $budgetMax)
                <p class="text-xs text-[#7EE8A2]">
                    Budget range: {{ $currency }} {{ number_format($budgetMin) }} – {{ number_format($budgetMax) }}
                    {{ $type === 'hourly' ? '/hr' : 'fixed' }}
                </p>
            @endif
        </div>

        {{-- Experience + Duration --}}
        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Experience level</flux:label>
                <div class="grid grid-cols-2 gap-1.5 mt-1">
                    @foreach(['entry' => 'Entry', 'mid' => 'Mid', 'senior' => 'Senior', 'expert' => 'Expert'] as $val => $lbl)
                        <label class="flex items-center justify-center p-2 rounded-lg border cursor-pointer text-xs font-medium transition-all
                            {{ $experienceLevel === $val
                                ? 'border-[#7EE8A2] bg-[#7EE8A2]/06 text-[#7EE8A2]'
                                : 'border-[#1c2e45] text-[#506070] hover:border-[#254060]' }}">
                            <input type="radio" wire:model="experienceLevel" value="{{ $val }}" class="sr-only">
                            {{ $lbl }}
                        </label>
                    @endforeach
                </div>
            </flux:field>
            <div class="space-y-3">
                <flux:field>
                    <flux:label>Estimated duration</flux:label>
                    <flux:select wire:model="duration">
                        <flux:select.option value="">Not sure yet</flux:select.option>
                        <flux:select.option value="less_1_week">Less than 1 week</flux:select.option>
                        <flux:select.option value="1_2_weeks">1–2 weeks</flux:select.option>
                        <flux:select.option value="1_month">About 1 month</flux:select.option>
                        <flux:select.option value="1_3_months">1–3 months</flux:select.option>
                        <flux:select.option value="3_6_months">3–6 months</flux:select.option>
                        <flux:select.option value="ongoing">Ongoing / long-term</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:field>
                    <flux:label>Application deadline</flux:label>
                    <flux:input wire:model="deadline" type="date" min="{{ today()->addDay()->toDateString() }}"/>
                    <flux:error name="deadline"/>
                </flux:field>
            </div>
        </div>

        {{-- Skills --}}
        <flux:field>
            <flux:label>Required skills</flux:label>
            <div class="flex gap-2">
                <flux:input wire:model="newSkill"
                    placeholder="e.g. Laravel, Vue.js, MySQL…"
                    class="flex-1"
                    wire:keydown.enter.prevent="addSkill"/>
                <flux:button type="button" variant="ghost" size="sm" icon="plus" wire:click="addSkill">
                    Add
                </flux:button>
            </div>
            @if(count($skills) > 0)
                <div class="flex flex-wrap gap-1.5 mt-2">
                    @foreach($skills as $i => $skill)
                        <div class="flex items-center gap-1 px-2.5 py-1 rounded-lg border border-[#7EE8A2]/20 bg-[#7EE8A2]/05">
                            <span class="text-xs text-[#7EE8A2]">{{ $skill }}</span>
                            <button type="button" wire:click="removeSkill({{ $i }})"
                                class="text-[#506070] hover:text-red-400 transition-colors">
                                <flux:icon.x-mark class="size-3"/>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:field>

        {{-- Visibility + Applicants --}}
        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Visibility</flux:label>
                <flux:select wire:model="visibility">
                    <flux:select.option value="public">🌍 Public — all freelancers can see and apply</flux:select.option>
                    <flux:select.option value="invite_only">🔒 Invite only — you share the link</flux:select.option>
                </flux:select>
            </flux:field>
            <flux:field>
                <flux:label>Max applicants</flux:label>
                <flux:input wire:model="maxApplicants" type="number" min="1" max="200"/>
                <flux:description>Close applications after this many.</flux:description>
            </flux:field>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3 pt-3 border-t border-[#1c2e45]">
            <flux:button variant="ghost" wire:click="saveDraft" wire:loading.attr="disabled">
                Save draft
            </flux:button>
            <flux:button variant="primary" icon="paper-airplane" wire:click="publish" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="publish">Publish job</span>
                <span wire:loading wire:target="publish">Publishing…</span>
            </flux:button>
        </div>

    </flux:card>
</div>

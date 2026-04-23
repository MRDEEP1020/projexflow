<?php

namespace App\Livewire\Backend;

use App\Models\Organization;
use App\Models\OrganizationMember;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Create Organization')]
class CreateOrganization extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:150')]
    public string $name = '';

    #[Validate('required|string|max:150|regex:/^[a-z0-9-]+$/')]
    public string $slug = '';

    #[Validate('required|in:company,personal')]
    public string $type = 'company';

    #[Validate('nullable|image|max:2048')]
    public $logo = null;

    public $slug_preview = '';
    public bool $slugManuallyEdited = false;

    public function updatedName(string $value): void
    {
        // Auto-generate slug from name unless user has manually edited it
        if (! $this->slugManuallyEdited) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugManuallyEdited = true;
        $this->slug = Str::slug($this->slug);
    }

    public function create(): void
    {
        $this->validate();

        // Ensure slug uniqueness
        $slug    = $this->ensureUniqueSlug($this->slug);
        $logoPath = null;

        DB::transaction(function () use ($slug, &$logoPath) {
            // Handle logo upload
            if ($this->logo) {
                $logoPath = $this->logo->store('organizations/logos', 's3');
            }

            // ALG-ORG-01 Step 4: INSERT organization
            $org = Organization::create([
                'name'     => $this->name,
                'slug'     => $slug,
                'owner_id' => Auth::id(),
                'type'     => $this->type,
                'plan'     => 'free',
                'logo'     => $logoPath,
            ]);

            // ALG-ORG-01 Step 5: INSERT owner membership
            OrganizationMember::create([
                'org_id'    => $org->id,
                'user_id'   => Auth::id(),
                'role'      => 'owner',
                'joined_at' => now(),
            ]);

            // ALG-ORG-01 Step 7: Update session context
            Session::put('active_org_id', $org->id);
        });

        $this->dispatch('toast', ['message' => "Organization \"{$this->name}\" created!", 'type' => 'success']);
        $this->redirect(route('dashboard'), navigate: true);
    }

    protected function ensureUniqueSlug(string $base): string
    {
        $slug    = $base ?: 'org';
        $counter = 2;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
            if ($counter > 100) {
                $slug = $base . '-' . Str::random(6);
                break;
            }
        }

        return $slug;
    }

    public function getSlugPreviewAttribute(): string
    {
        return config('app.url') . '/' . ($this->slug ?: 'your-org');
    }

    public function render()
    {
        return view('livewire.backend.create');
    }
}

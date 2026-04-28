<?php

namespace App\Livewire\Backend;

use App\Models\Organization;
use App\Policies\OrganizationPolicy;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Organization Settings')]
class OrgSettings extends Component
{
    use WithFileUploads, AuthorizesRequests;

    public Organization $org;

    public string $activeTab = 'general';

    // General tab fields
    #[Validate('required|string|max:150')]
    public string $name = '';

    #[Validate('nullable|image|max:2048')]
    public $logo = null;

    // Danger zone
    public string $deleteConfirm = '';
    public bool $showDeleteModal = false;

    public function mount(Organization $org): void
    {
        $this->authorize('update', $org);

        $this->org  = $org;
        $this->name = $org->name;
    }

    public function saveGeneral(): void
    {
        $this->authorize('update', $this->org);

        $this->validateOnly('name');
        $this->validateOnly('logo');

        $logoPath = $this->org->logo;

        if ($this->logo) {
            // Delete old logo
            if ($logoPath) {
                Storage::disk('s3')->delete($logoPath);
            }
            $logoPath = $this->logo->store('organizations/logos', 's3');
        }

        $this->org->update([
            'name' => $this->name,
            'logo' => $logoPath,
        ]);

        $this->logo = null;
        $this->dispatch('toast', ['message' => 'Settings saved.', 'type' => 'success']);
    }

    public function removeLogo(): void
    {
        $this->authorize('update', $this->org);

        if ($this->org->logo) {
            Storage::disk('s3')->delete($this->org->logo);
        }

        $this->org->update(['logo' => null]);
        $this->dispatch('toast', ['message' => 'Logo removed.', 'type' => 'info']);
    }

    public function deleteOrg(): void
    {
        $this->authorize('delete', $this->org);

        // Must type exact org name to confirm
        if ($this->deleteConfirm !== $this->org->name) {
            $this->addError('deleteConfirm', 'Organization name does not match. Please type it exactly.');
            return;
        }

        // Cannot delete if there are active projects
        $activeCount = $this->org->activeProjects()->count();
        if ($activeCount > 0) {
            $this->addError('deleteConfirm', "Cannot delete: organization has {$activeCount} active project(s). Archive them first.");
            return;
        }

        $orgName = $this->org->name;
        $this->org->delete();

        // Reset session to personal workspace
        \Session::forget('active_org_id');

        $this->dispatch('toast', ['message' => "\"{$orgName}\" has been deleted.", 'type' => 'info']);
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.backend.settings');
    }
}

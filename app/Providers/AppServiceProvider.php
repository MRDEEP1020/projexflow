<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ── Register all Livewire components ──────────────────────────
        // Since you are using classic Livewire (not Volt), components are
        // auto-discovered from app/Livewire/ by default in Livewire v3.
        //
        // If auto-discovery is not picking them up, register manually:

        Livewire::component('backend.dashboard',         \App\Livewire\Backend\Dashboard::class);
        Livewire::component('backend.createOrganization', \App\Livewire\Backend\CreateOrganization::class);
        Livewire::component('backend.orgSettings',      \App\Livewire\Backend\OrgSettings::class);
        Livewire::component('backend.members',           \App\Livewire\Backend\Members::class);
        Livewire::component('backend.projectList',      \App\Livewire\Backend\ProjectList::class);
        Livewire::component('backend.projectCreate',    \App\Livewire\Backend\ProjectCreate::class);
        Livewire::component('backend.projectBoard',     \App\Livewire\Backend\ProjectBoard::class);
        Livewire::component('backend.projectSettings',  \App\Livewire\Backend\ProjectSettings::class);
        Livewire::component('backend.projectArchived',  \App\Livewire\Backend\ProjectArchived::class);
        Livewire::component('backend.projectPortal',    \App\Livewire\Backend\ProjectPortal::class);
        Livewire::component('backend.myTask',           \App\Livewire\Backend\MyTask::class);
        Livewire::component('backend.orgSwitcher',      \App\Livewire\Backend\OrgSwitcher::class);
        Livewire::component('backend.taskDetail',         \App\Livewire\Backend\TaskDetail::class);
        Livewire::component('backend.marketplace-browse', \App\Livewire\Backend\MarketplaceBrowse::class);
        Livewire::component('backend.profile-page',       \App\Livewire\Backend\ProfilePage::class);
        Livewire::component('backend.edit-profile',       \App\Livewire\Backend\EditProfile::class);
        Livewire::component('backend.contract-manager',   \App\Livewire\Backend\ContractManager::class);
        Livewire::component('backend.wallet-page',        \App\Livewire\Backend\WalletPage::class);
        Livewire::component('backend.meeting-room',       \App\Livewire\Backend\projectMeetingRoom::class);
        Livewire::component('backend.submit-review',      \App\Livewire\Backend\SubmitReview::class);
        Livewire::component('backend.notifications-center', \App\Livewire\Backend\NotificationsCenter::class);

    }
}

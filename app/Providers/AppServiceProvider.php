<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // ── Register middleware aliases ─────────────────────────────
        // In app/Http/Kernel.php, add these to $middlewareAliases:
        //
        // 'detect.mode' => \App\Http\Middleware\DetectUserMode::class,
        // 'require.admin' => \App\Http\Middleware\RequireAdmin::class,

        // ── Register ALL Livewire components ───────────────────────

        // Mode switcher (used in both layouts)
        Livewire::component('backend.mode-switcher',            \App\Livewire\Backend\ModeSwitcher::class);

        // ── CLIENT-ONLY components ─────────────────────────────────
        Livewire::component('backend.client.client-dashboard',  \App\Livewire\Backend\ClientDashboard::class);
        Livewire::component('backend.client-marketplace',       \App\Livewire\Backend\ClientMarketplace::class);

        // ── FREELANCER-ONLY components ─────────────────────────────
        Livewire::component('backend.freelancer.freelancer-dashboard', \App\Livewire\Backend\FreelancerDashboard::class);

        // ── SHARED backend components ──────────────────────────────
        Livewire::component('backend.dashboard',                \App\Livewire\Backend\Dashboard::class);
        Livewire::component('backend.create-organization',      \App\Livewire\Backend\CreateOrganization::class);
        Livewire::component('backend.org-settings',             \App\Livewire\Backend\OrgSettings::class);
        Livewire::component('backend.members',                  \App\Livewire\Backend\Members::class);
        Livewire::component('backend.org-switcher',             \App\Livewire\Backend\OrgSwitcher::class);
        Livewire::component('backend.notification-bell',        \App\Livewire\Backend\NotificationBell::class);
        Livewire::component('backend.project-list',             \App\Livewire\Backend\ProjectList::class);
        Livewire::component('backend.project-create',           \App\Livewire\Backend\ProjectCreate::class);
        Livewire::component('backend.project-board',            \App\Livewire\Backend\ProjectBoard::class);
        Livewire::component('backend.project-settings',         \App\Livewire\Backend\ProjectSettings::class);
        Livewire::component('backend.project-archived',         \App\Livewire\Backend\ProjectArchived::class);
        Livewire::component('backend.project-portal',           \App\Livewire\Backend\ProjectPortal::class);
        Livewire::component('backend.my-task',                  \App\Livewire\Backend\MyTask::class);
        Livewire::component('tasks.task-detail',                \App\Livewire\Backend\TaskDetail::class);
        Livewire::component('backend.personal-calendar',        \App\Livewire\Backend\PersonalCalendar::class);
        Livewire::component('backend.availability-settings',    \App\Livewire\Backend\AvailabilitySettings::class);
        Livewire::component('backend.public-booking-page',      \App\Livewire\Backend\PublicBookingPage::class);
        Livewire::component('backend.booking-inbox',            \App\Livewire\Backend\BookingInbox::class);
        Livewire::component('backend.marketplace-browse',       \App\Livewire\Backend\MarketplaceBrowse::class);
        Livewire::component('backend.profile-page',             \App\Livewire\Backend\ProfilePage::class);
        Livewire::component('backend.edit-profile',             \App\Livewire\Backend\EditProfile::class);
        Livewire::component('backend.job-board',                \App\Livewire\Backend\JobBoard::class);
        Livewire::component('backend.job-post-create',          \App\Livewire\Backend\JobPostCreate::class);
        Livewire::component('backend.job-post-detail',          \App\Livewire\Backend\JobPostDetail::class);
        Livewire::component('backend.my-jobs',                  \App\Livewire\Backend\MyJobs::class);
        Livewire::component('backend.contract-manager',         \App\Livewire\Backend\ContractManager::class);
        Livewire::component('backend.wallet-page',              \App\Livewire\Backend\WalletPage::class);
        Livewire::component('backend.meeting-room',             \App\Livewire\Backend\MeetingRoom::class);
        Livewire::component('backend.submit-review',            \App\Livewire\Backend\SubmitReview::class);
        Livewire::component('backend.notifications-center',     \App\Livewire\Backend\NotificationsCenter::class);

        // ── ADMIN components ───────────────────────────────────────
        Livewire::component('backend.adminDashboard',            \App\Livewire\AdminDashboard::class);
        Livewire::component('backend.adminUsers',                \App\Livewire\AdminUsers::class);
        Livewire::component('backend.adminDisputes',             \App\Livewire\AdminDisputes::class);
        Livewire::component('backend.adminWithdrawals',          \App\Livewire\AdminWithdrawals::class);
        Livewire::component('backend.adminModeration',           \App\Livewire\AdminModeration::class);
    }
}

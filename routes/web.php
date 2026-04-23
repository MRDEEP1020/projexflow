<?php

use App\Livewire\Backend\CreateOrganization;
use App\Livewire\Backend\Dashboard;
use App\Livewire\Backend\Members;
use App\Livewire\Backend\OrgSettings;
use App\Livewire\Backend\ProjectArchived;
use App\Livewire\Backend\ProjectBoard;
use App\Livewire\Backend\ProjectCreate;
use App\Livewire\Backend\ProjectList;
use App\Livewire\Backend\ProjectSettings;
use App\Livewire\Backend\ProjectPortal;
use App\Livewire\Backend\PersonalCalendar;
use App\Livewire\Backend\AvailabilitySettings;
use App\Livewire\Backend\PublicBookingPage;
use App\Livewire\Backend\BookingInbox;
use App\Livewire\Backend\MyTask;
use App\Livewire\Backend\MarketplaceBrowse;
use App\Livewire\Backend\ProfilePage;
use App\Livewire\Backend\EditProfile;
use App\Livewire\Backend\ContractManager;
use App\Livewire\Backend\WalletPage;
use App\Livewire\Backend\ProjectMeetingRoom;
use App\Livewire\Backend\SubmitReview;
use App\Livewire\Backend\NotificationsCenter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// ── Auth routes ────────────────────────────────────────────────
require __DIR__ . '/auth.php';

// ── Welcome ────────────────────────────────────────────────────
Route::get('welcome', fn() => view('welcome'))->name('welcome');

// ── Public: invitation accept ──────────────────────────────────
Route::get('/invitations/accept/{token}', function (string $token) {
    $invitation = \App\Models\Invitation::where('token', $token)->firstOrFail();

    if ($invitation->isExpired()) {
        return view('invitations.expired', compact('invitation'));
    }

    if ($invitation->isAccepted()) {
        return redirect()->route('dashboard')
            ->with('status', 'This invitation has already been accepted.');
    }

    session(['invitation_token' => $token]);

    // ✅ FIXED: was Auth::heck() — typo caused fatal error on invitation flow
    if (! Auth::check()) {
        return redirect()->route('register')
            ->with('invitation_email', $invitation->email);
    }

    if (Auth::user()->email !== $invitation->email) {
        return view('invitations.wrong-account', compact('invitation'));
    }

    DB::transaction(function () use ($invitation) {
        \App\Models\OrganizationMember::create([
            'org_id'    => $invitation->org_id,
            'user_id'   => Auth::id(),
            'role'      => $invitation->role,
            'joined_at' => now(),
        ]);
        $invitation->update(['accepted_at' => now()]);
    });

    session(['active_org_id' => $invitation->org_id]);
    session()->forget('invitation_token');

    return redirect()->route('dashboard')
        ->with('status', 'You have joined ' . $invitation->organization->name . '!');
})->name('invitations.accept');

// ── Public: Client Portal ──────────────────────────────────────
// MUST be outside auth middleware — token IS the authentication
Route::get('/portal/{token}', ProjectPortal::class)->name('backend.projectPortal');

Route::get('/backend/meetings/{token}', ProjectMeetingRoom::class)->name('backend.meetingRoom');

// ── Authenticated routes ───────────────────────────────────────
Route::middleware(['auth', 'verified', \App\Http\Middleware\SetActiveOrg::class])
    ->group(function () {

        // ── Root redirect ──────────────────────────────────────
        Route::get('/', fn() => redirect()->route('dashboard'));
        Route::get('/backend/dashboard', Dashboard::class)->name('dashboard');

        // ── Org switcher ───────────────────────────────────────
        Route::post('/switch-org/{org}', function (\App\Models\Organization $org) {
            abort_unless(Auth::user()->isMemberOfOrg($org->id), 403);
            session(['active_org_id' => $org->id]);
            return redirect()->route('dashboard');
        })->name('orgs.switch');

        // ── Organizations ──────────────────────────────────────
        // ✅ FIXED: was '/backend/create' — conflicted with projectCreate below
        Route::get('/backend/organizations/create', CreateOrganization::class)
            ->name('backend.create');

        Route::get('/backend/organizations/{org}/settings', OrgSettings::class)
            ->name('backend.settings');

        Route::get('/backend/organizations/{org}/members', Members::class)
            ->name('backend.members');

        // ── Projects ───────────────────────────────────────────
        // ✅ FIXED: specific routes MUST come before wildcard {project} routes
        //    Laravel matches routes top-to-bottom; /backend/archived would have
        //    been swallowed by /backend/{project} without this ordering.

        Route::get('/backend/projects', ProjectList::class)
            ->name('backend.projectList');

        Route::get('/backend/projects/create', ProjectCreate::class)
            ->name('backend.projectCreate');

        Route::get('/backend/projects/archived', ProjectArchived::class)
            ->name('backend.projectArchived');

        // ✅ Wildcard project routes AFTER all static /projects/* routes
        Route::get('/backend/projects/{project}', ProjectBoard::class)
            ->name('backend.projectBoard');

        Route::get('/backend/projects/{project}/settings', ProjectSettings::class)
            ->name('backend.projectSettings');

        // ── Tasks ──────────────────────────────────────────────
        Route::get('/my-tasks', MyTask::class)->name('my-tasks');

        // Phase 7 — Marketplace
        Route::get('/backend/marketplace',              MarketplaceBrowse::class)->name('backend.marketplace');
        Route::get('/backend/marketplace/{username}',   ProfilePage::class)->name('backend.profilePage');
        Route::get('/backend/profile/edit',             EditProfile::class)->name('backend.editProfile');

        // Phase 8 — Payments & Video
        Route::get('/backend/contracts',                ContractManager::class)->name('backend.contracts');
        Route::get('/backend/wallet',                   WalletPage::class)->name('backend.wallet');
        Route::get('/backend/review',                   SubmitReview::class)->name('backend.submitReview');


        Route::get('/backend/calendar',               PersonalCalendar::class)->name('backend.calendar');
        Route::get('/settings/availability',          AvailabilitySettings::class)->name('backend.availabilitySettings');
        Route::get('/backend/bookings',               BookingInbox::class)->name('backend.bookingInbox');
        Route::get('/book/{username}',                PublicBookingPage::class)->name('backend.bookingPage');

Route::get('/backend/notifications', NotificationsCenter::class)->name('backend.notifications');

        // ── Placeholders (future phases) ───────────────────────
        Route::get('/settings/profile',     fn() => 'Coming soon')->name('settings.profile');
    });

<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Volt;

// ── Auth routes ────────────────────────────────────────────────
require __DIR__ . '/auth.php';

// ── Public ─────────────────────────────────────────────────────
Route::get('/', fn () => view('welcome'));

// ── Public: client portal ──────────────────────────────────────
Route::get('/portal/{token}', \App\Livewire\Backend\ProjectPortal::class)
    ->name('backend.projectPortal');

// ── Public: freelancer booking page ───────────────────────────
Route::get('/book/{username}', \App\Livewire\Backend\PublicBookingPage::class)
    ->name('backend.bookingPage');

// ── Public: invitation accept ──────────────────────────────────
Route::get('/invitations/accept/{token}', function (string $token) {
    $invitation = \App\Models\Invitation::where('token', $token)->firstOrFail();
    if ($invitation->isExpired())   return view('invitations.expired', compact('invitation'));
    if ($invitation->isAccepted())  return redirect()->route('client.dashboard')->with('status','Already accepted.');
    session(['invitation_token' => $token]);
    if (! Auth::check()) {
        return redirect()->route('register')->with('invitation_email', $invitation->email);
    }
    if (Auth::user()->email !== $invitation->email) return view('invitations.wrong-account', compact('invitation'));
    \Illuminate\Support\Facades\DB::transaction(function () use ($invitation) {
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
    return redirect()->route('client.dashboard')->with('status','Joined '.$invitation->organization->name.'!');
})->name('invitations.accept');

// ═══════════════════════════════════════════════════════════════
// AUTHENTICATED ROUTES
// ═══════════════════════════════════════════════════════════════
Route::middleware([
    'auth',
    'verified',
    \App\Http\Middleware\SetActiveOrg::class,
    \App\Http\Middleware\DetectUserMode::class,
])->group(function () {

    // ── Root redirect based on active mode ────────────────────
    Route::get('/backend/dashboard', function () {
        $mode = session('active_mode', Auth::user()->active_mode ?? 'client');
        return redirect()->route($mode === 'freelancer' ? 'freelancer.dashboard' : 'client.dashboard');
    })->name('dashboard');

    Route::get('/', fn () => redirect()->route('dashboard'));

    // ── Org switch ─────────────────────────────────────────────
    Route::post('/switch-org/{org}', function (\App\Models\Organization $org) {
        abort_unless(Auth::user()->isMemberOfOrg($org->id), 403);
        session(['active_org_id' => $org->id]);
        return redirect()->route('dashboard');
    })->name('orgs.switch');

    // ══════════════════════════════════════════════════════════
    // CLIENT ROUTES — blue layout, client nav
    // ══════════════════════════════════════════════════════════
    Route::get('/client/dashboard', \App\Livewire\Backend\ClientDashboard::class)
        ->name('client.dashboard');

    // Client marketplace (different UX from freelancer profile editor)
    Route::get('/client/marketplace', \App\Livewire\Backend\ProjectClientMarketplace::class)
        ->name('client.marketplace');

    // ══════════════════════════════════════════════════════════
    // FREELANCER ROUTES — green layout, freelancer nav
    // ══════════════════════════════════════════════════════════
    Route::get('/freelancer/dashboard', \App\Livewire\Backend\FreelancerDashboard::class)
        ->name('freelancer.dashboard');

    // ══════════════════════════════════════════════════════════
    // SHARED ROUTES — work in both modes (use app.blade.php or
    // detect mode inside the component for layout)
    // ══════════════════════════════════════════════════════════

    // Organizations (shared — both modes can manage orgs)
    Route::get('/backend/organizations/create',              \App\Livewire\Backend\CreateOrganization::class)->name('backend.create');
    Route::get('/backend/organizations/{org}/settings',      \App\Livewire\Backend\OrgSettings::class)->name('backend.settings');
    Route::get('/backend/organizations/{org}/members',       \App\Livewire\Backend\Members::class)->name('backend.members');

    // Projects (shared)
    Route::get('/backend/projects',                          \App\Livewire\Backend\ProjectList::class)->name('backend.projectList');
    Route::get('/backend/projects/create',                   \App\Livewire\Backend\ProjectCreate::class)->name('backend.projectCreate');
    Route::get('/backend/projects/archived',                 \App\Livewire\Backend\ProjectArchived::class)->name('backend.projectArchived');
    Route::get('/backend/projects/{project}',                \App\Livewire\Backend\ProjectBoard::class)->name('backend.projectBoard');
    Route::get('/backend/projects/{project}/settings',       \App\Livewire\Backend\ProjectSettings::class)->name('backend.projectSettings');

    // Tasks (shared)
    Route::get('/my-tasks',                                  \App\Livewire\Backend\MyTask::class)->name('my-tasks');

    // Calendar (shared)
    Route::get('/backend/calendar',                          \App\Livewire\Backend\PersonalCalendar::class)->name('backend.calendar');

    // Marketplace (freelancer profile side — shared but freelancer-centric)
    Route::get('/backend/marketplace',                       \App\Livewire\Backend\MarketplaceBrowse::class)->name('backend.marketplace');
    Route::get('/backend/marketplace/{username}',            \App\Livewire\Backend\ProfilePage::class)->name('backend.profilePage');
    Route::get('/backend/profile/edit',                      \App\Livewire\Backend\EditProfile::class)->name('backend.editProfile');
    Route::get('/settings/availability',                     \App\Livewire\Backend\AvailabilitySettings::class)->name('backend.availabilitySettings');

    // Job board (shared — clients post, freelancers apply)
    Route::get('/backend/jobs',                              \App\Livewire\Backend\JobBoard::class)->name('backend.jobBoard');
    Route::get('/backend/jobs/create',                       \App\Livewire\Backend\JobPostCreate::class)->name('backend.jobPostCreate');
   Route::get('/backend/jobs/mine', \App\Livewire\Backend\MyJobs::class)->name('backend.myJobs');
Route::get('/backend/applications/mine', \App\Livewire\Backend\MyJobs::class)->name('backend.myApplications');
    Route::get('/backend/jobs/{id}',                         \App\Livewire\Backend\JobPostDetail::class)->name('backend.jobPostDetail');

    // Bookings (shared)
    Route::get('/backend/bookings',                          \App\Livewire\Backend\BookingInbox::class)->name('backend.bookingInbox');

    // Payments (shared)
    Route::get('/backend/contracts',                         \App\Livewire\Backend\ContractManager::class)->name('backend.contracts');
    Route::get('/backend/wallet',                            \App\Livewire\Backend\WalletPage::class)->name('backend.wallet');
    Route::get('/backend/review',                            \App\Livewire\Backend\SubmitReview::class)->name('backend.submitReview');

    // Video
    Route::get('/backend/meetings/{token}',                  \App\Livewire\Backend\ProjectMeetingRoom::class)->name('backend.meetingRoom');

    // Notifications + Settings
    Route::get('/backend/notifications',                     \App\Livewire\Backend\NotificationsCenter::class)->name('backend.notifications');
    Route::get('/settings/profile',                          fn() => 'Coming soon')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')
        ->name('settings.password');
});

// ═══════════════════════════════════════════════════════════════
// ADMIN ROUTES — completely separate middleware stack
// ════════════════════════════════════
Route::middleware(['auth', 'verified', \App\Http\Middleware\RequireAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard',    \App\Livewire\Backend\AdminDashboard::class)->name('dashboard');
        Route::get('/users',        \App\Livewire\Backend\AdminUsers::class)->name('users');
        Route::get('/disputes',     \App\Livewire\Backend\AdminDisputes::class)->name('disputes');
        Route::get('/withdrawals',  \App\Livewire\Backend\AdminWithdrawals::class)->name('withdrawals');
        Route::get('/moderation',   \App\Livewire\Backend\AdminModeration::class)->name('moderation');
    });

// ═══════════════════════════════════════════════════════════════
// WEBHOOK ROUTES (no auth, signature-verified inside controller)
// ═══════════════════════════════════════════════════════════════
// These go in routes/api.php:
//
// Route::post('/webhook/github',  [\App\Http\Controllers\GitHubWebhookController::class, 'handle'])->name('webhook.github');
// Route::post('/webhook/livekit', [\App\Http\Controllers\LiveKitWebhookController::class, 'handle'])->name('webhook.livekit');

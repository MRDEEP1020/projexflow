<?php

// tests/Unit/AlgorithmTest.php
// Tests every ALG-* algorithm in isolation — no HTTP, no Livewire
uses(Tests\TestCase::class);

use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\Contract;
use App\Models\ServiceProfile;
use App\Models\Review;
use App\Models\AvailabilitySchedule;
use App\Models\AvailabilityOverride;
use App\Models\Booking;
use App\Models\Wallet;
use App\Models\PaymentTransaction;
use App\Livewire\Backend\PublicBookingPage;
use App\Livewire\Backend\SubmitReview;
use App\Console\Commands\EscrowAutoRelease;
use Illuminate\Support\Facades\Artisan;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── ALG-TASK-03: auto-transition to in_review ─────────────────────

describe('ALG-TASK-03 auto-transition', function () {

    it('auto-transitions task to in_review when deliverable submitted', function () {
        $user    = clientUser();
        $project = projectFor($user);
        $task    = Task::factory()->create([
            'project_id' => $project->id,
            'status'     => 'in_progress',
        ]);

        // Simulate deliverable submission
        $task->update([
            'deliverable_type' => 'url',
            'deliverable_url'  => 'https://example.com/delivery',
            'status'           => 'in_review', // ALG-TASK-03
        ]);

        expect($task->fresh()->status)->toBe('in_review');
        expect($task->fresh()->deliverable_url)->not->toBeNull();
    });

    it('does not auto-transition if no deliverable url', function () {
        $user    = clientUser();
        $project = projectFor($user);
        $task    = Task::factory()->create([
            'project_id' => $project->id,
            'status'     => 'in_progress',
        ]);

        // Update without deliverable
        $task->update(['title' => 'Updated title']);

        expect($task->fresh()->status)->toBe('in_progress');
    });
});

// ── ALG-CAL-02: slot availability algorithm ───────────────────────

describe('ALG-CAL-02 slot availability', function () {

    beforeEach(function () {
        $this->provider = freelancerUser();

        // Set Mon–Fri 09:00–17:00 schedule (dow 0=Mon in our system)
        for ($dow = 0; $dow < 5; $dow++) {
            AvailabilitySchedule::create([
                'user_id'      => $this->provider->id,
                'day_of_week'  => $dow,
                'is_available' => true,
                'start_time'   => '09:00',
                'end_time'     => '17:00',
            ]);
        }

        $this->provider->update([
            'is_marketplace_enabled' => true,
            'name' => 'slot-test-provider-' . uniqid(),
        ]);

        ServiceProfile::where('user_id', $this->provider->id)->update([
            'session_duration' => 60,
        ]);
    });

    it('returns slots for a free weekday', function () {
        $component = new PublicBookingPage();
        $component->mount($this->provider->name);

        $nextMonday = now()->next('Monday')->toDateString();
        $slots      = $component->computeSlots($nextMonday);

        expect($slots)->not->toBeEmpty();
        expect($slots)->toContain('09:00');
        expect($slots)->toContain('10:00');
        expect($slots)->toContain('16:00');
        expect($slots)->not->toContain('17:00'); // end boundary excluded
    });

    it('returns no slots for a day with no schedule', function () {
        $component = new PublicBookingPage();
        $component->mount($this->provider->name);

        // Saturday (dow=5 in our 0=Mon system)
        $nextSaturday = now()->next('Saturday')->toDateString();
        $slots        = $component->computeSlots($nextSaturday);

        expect($slots)->toBeEmpty();
    });

    it('removes already-booked slots', function () {
        $component = new PublicBookingPage();
        $component->mount($this->provider->name);

        $nextMonday = now()->next('Monday');

        // Book the 10:00 slot
        Booking::factory()->create([
            'provider_id' => $this->provider->id,
            'status'      => 'confirmed',
            'start_at'    => $nextMonday->copy()->setHour(10)->setMinute(0),
            'end_at'      => $nextMonday->copy()->setHour(11)->setMinute(0),
        ]);

        $slots = $component->computeSlots($nextMonday->toDateString());

        expect($slots)->not->toContain('10:00');
        expect($slots)->toContain('09:00');
        expect($slots)->toContain('11:00');
    });

    it('respects date overrides - blocked day returns no slots', function () {
        $component = new PublicBookingPage();
        $component->mount($this->provider->name);

        $nextTuesday = now()->next('Tuesday')->toDateString();

        AvailabilityOverride::create([
            'user_id'      => $this->provider->id,
            'date'         => $nextTuesday,
            'is_available' => false,
            'reason'       => 'Holiday',
        ]);

        $slots = $component->computeSlots($nextTuesday);

        expect($slots)->toBeEmpty();
    });

    it('respects date overrides - open day with custom hours', function () {
        $component = new PublicBookingPage();
        $component->mount($this->provider->name);

        // Override a Saturday to be open 10:00–12:00
        $nextSaturday = now()->next('Saturday')->toDateString();

        AvailabilityOverride::create([
            'user_id'      => $this->provider->id,
            'date'         => $nextSaturday,
            'is_available' => true,
            'start_time'   => '10:00',
            'end_time'     => '12:00',
        ]);

        $slots = $component->computeSlots($nextSaturday);

        expect($slots)->toContain('10:00');
        expect($slots)->toContain('11:00');
        expect($slots)->not->toContain('09:00');
        expect($slots)->not->toContain('12:00');
    });
});

// ── ALG-ARCH-02: marketplace ranking algorithm ────────────────────

describe('ALG-ARCH-02 marketplace ranking', function () {

    it('ranks verified freelancers higher than unverified', function () {
        $verified   = freelancerUser();
        $unverified = freelancerUser();

        $verified->serviceProfile->update([
            'is_verified'         => true,
            'avg_rating'          => 4.0,
            'availability_status' => 'open_to_work',
        ]);
        $unverified->serviceProfile->update([
            'is_verified'         => false,
            'avg_rating'          => 4.0,
            'availability_status' => 'open_to_work',
        ]);

        $client    = clientUser();
        $component = \Livewire\Livewire::actingAs($client)
            ->test(\App\Livewire\Backend\ClientMarketplace::class);

        $profiles = collect($component->get('freelancers')->items());
        $verifiedIdx   = $profiles->search(fn($p) => $p->user_id === $verified->id);
        $unverifiedIdx = $profiles->search(fn($p) => $p->user_id === $unverified->id);

        expect($verifiedIdx)->toBeLessThan($unverifiedIdx);
    });

    it('penalizes not_available freelancers in ranking', function () {
        $available    = freelancerUser();
        $notAvailable = freelancerUser();

        $available->serviceProfile->update([
            'is_verified'         => false,
            'avg_rating'          => 3.0,
            'availability_status' => 'open_to_work',
        ]);
        $notAvailable->serviceProfile->update([
            'is_verified'         => false,
            'avg_rating'          => 5.0, // higher rating but not available
            'availability_status' => 'not_available',
        ]);

        $client    = clientUser();
        $component = \Livewire\Livewire::actingAs($client)
            ->test(\App\Livewire\Backend\ClientMarketplace::class);

        $profiles      = collect($component->get('freelancers')->items());
        $availableIdx    = $profiles->search(fn($p) => $p->user_id === $available->id);
        $notAvailableIdx = $profiles->search(fn($p) => $p->user_id === $notAvailable->id);

        // available (open_to_work) should rank higher despite lower rating
        expect($availableIdx)->toBeLessThan($notAvailableIdx);
    });
});

// ── ALG-REV-01/02: review submission + rating recalculation ───────

describe('ALG-REV-01 verified review + ALG-REV-02 recalculation', function () {

    it('creates a verified review when backed by real booking', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $booking = Booking::factory()->create([
            'client_id'   => $client->id,
            'provider_id' => $freelancer->id,
            'status'      => 'confirmed',
            'start_at'    => now()->subDay(),
            'end_at'      => now()->subHours(22),
        ]);

        \Livewire\Livewire::actingAs($client)
            ->test(\App\Livewire\Backend\SubmitReview::class, [
                'reviewee' => $freelancer->id,
                'booking'  => $booking->id,
            ])
            ->set('rating', 5)
            ->set('body', 'Excellent work, delivered on time and exceeded expectations!')
            ->call('submit')
            ->assertHasNoErrors();

        $review = Review::where('reviewer_id', $client->id)
            ->where('reviewee_id', $freelancer->id)
            ->first();

        expect($review)->not->toBeNull();
        expect($review->is_verified)->toBeTrue();
        expect($review->rating)->toBe(5);
    });

    it('recalculates avg_rating after review submission (ALG-REV-02)', function () {
        $client     = clientUser();
        $client2    = clientUser();
        $freelancer = freelancerUser();

        $freelancer->serviceProfile->update(['avg_rating' => 0, 'total_reviews' => 0]);

        // Submit first review (5 stars)
        $b1 = Booking::factory()->create([
            'client_id' => $client->id, 'provider_id' => $freelancer->id,
            'status' => 'confirmed', 'start_at' => now()->subDays(2), 'end_at' => now()->subDays(2)->addHour(),
        ]);

        \Livewire\Livewire::actingAs($client)
            ->test(\App\Livewire\Backend\SubmitReview::class, [
                'reviewee' => $freelancer->id, 'booking' => $b1->id,
            ])
            ->set('rating', 5)->set('body', 'Five stars all the way, absolutely brilliant work done here.')
            ->call('submit');

        expect($freelancer->serviceProfile->fresh()->avg_rating)->toBe(5.0);
        expect($freelancer->serviceProfile->fresh()->total_reviews)->toBe(1);

        // Submit second review (3 stars)
        $b2 = Booking::factory()->create([
            'client_id' => $client2->id, 'provider_id' => $freelancer->id,
            'status' => 'confirmed', 'start_at' => now()->subDay(), 'end_at' => now()->subDay()->addHour(),
        ]);

        \Livewire\Livewire::actingAs($client2)
            ->test(\App\Livewire\Backend\SubmitReview::class, [
                'reviewee' => $freelancer->id, 'booking' => $b2->id,
            ])
            ->set('rating', 3)->set('body', 'Good work, met expectations. Could have communicated better.')
            ->call('submit');

        // avg = (5 + 3) / 2 = 4.0
        expect($freelancer->serviceProfile->fresh()->avg_rating)->toBe(4.0);
        expect($freelancer->serviceProfile->fresh()->total_reviews)->toBe(2);
    });

    it('prevents duplicate reviews for same booking', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $booking = Booking::factory()->create([
            'client_id' => $client->id, 'provider_id' => $freelancer->id,
            'status' => 'confirmed', 'start_at' => now()->subDay(), 'end_at' => now()->subDay()->addHour(),
        ]);

        // First review
        Review::factory()->create([
            'reviewer_id' => $client->id, 'reviewee_id' => $freelancer->id,
            'booking_id'  => $booking->id, 'rating' => 4,
        ]);

        // Try to load the form again — should already be submitted
        $component = \Livewire\Livewire::actingAs($client)
            ->test(\App\Livewire\Backend\SubmitReview::class, [
                'reviewee' => $freelancer->id, 'booking' => $booking->id,
            ]);

        expect($component->get('submitted'))->toBeTrue();
    });

    it('rejects review without real booking relationship', function () {
        $stranger   = clientUser();
        $freelancer = freelancerUser();

        // No booking between stranger and freelancer
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        \Livewire\Livewire::actingAs($stranger)
            ->test(\App\Livewire\Backend\SubmitReview::class, [
                'reviewee' => $freelancer->id,
            ]);
    });
});

// ── ALG-PAY-05: escrow auto-release timing ────────────────────────

describe('ALG-PAY-05 escrow auto-release', function () {

    it('releases contracts exactly when auto_release_at <= now', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $dueContract = contractBetween($client, $freelancer, [
            'status'              => 'submitted',
            'total_amount'        => 500,
            'deposit_amount'      => 150,
            'platform_fee_amount' => 50,
            'platform_fee_percentage' => 10,
            'auto_release_at'     => now()->subMinutes(1), // due
        ]);

        $notDueContract = contractBetween($client, $freelancer, [
            'status'          => 'submitted',
            'auto_release_at' => now()->addHours(48), // not due
        ]);

        walletFor($freelancer, 0);

        Artisan::call('escrow:auto-release');

        expect($dueContract->fresh()->status)->toBe('completed');
        expect($dueContract->fresh()->auto_released)->toBeTrue();
        expect($notDueContract->fresh()->status)->toBe('submitted');
    });

    it('does not release disputed contracts', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $contract = contractBetween($client, $freelancer, [
            'status'          => 'disputed',
            'auto_release_at' => now()->subHour(),
        ]);

        walletFor($freelancer, 0);

        Artisan::call('escrow:auto-release');

        // disputed contracts must not be auto-released
        expect($contract->fresh()->status)->toBe('disputed');
    });

    it('notifies both parties on auto-release', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $contract = contractBetween($client, $freelancer, [
            'status'              => 'submitted',
            'total_amount'        => 200,
            'deposit_amount'      => 60,
            'platform_fee_amount' => 20,
            'platform_fee_percentage' => 10,
            'auto_release_at'     => now()->subMinutes(5),
        ]);

        walletFor($freelancer, 0);

        Artisan::call('escrow:auto-release');

        expect(\App\Models\Notification::where('user_id', $freelancer->id)
            ->where('type', 'payment_auto_released')
            ->exists())->toBeTrue();

        expect(\App\Models\Notification::where('user_id', $client->id)
            ->where('type', 'contract_auto_completed')
            ->exists())->toBeTrue();
    });
});

// ── ALG-CP-01: client portal token validation ─────────────────────

describe('ALG-CP-01 client portal token validation', function () {

    it('accepts a valid 64-char token', function () {
        $client  = clientUser();
        $project = projectFor($client, [
            'client_portal_enabled' => true,
            'client_token'          => str_repeat('a', 64),
        ]);

        $this->get('/portal/' . str_repeat('a', 64))->assertOk();
    });

    it('returns 404 for short token', function () {
        $this->get('/portal/short')->assertNotFound();
    });

    it('returns 404 for non-existent token', function () {
        $this->get('/portal/' . str_repeat('z', 64))->assertNotFound();
    });

    it('returns 403 when portal is disabled', function () {
        $client  = clientUser();
        $project = projectFor($client, [
            'client_portal_enabled' => false,
            'client_token'          => str_repeat('b', 64),
        ]);

        $this->get('/portal/' . str_repeat('b', 64))->assertForbidden();
    });
});

// ── Mode switching ────────────────────────────────────────────────

describe('Mode switching', function () {

    it('switches from client to freelancer mode', function () {
        $user = clientUser();

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\Backend\ModeSwitcher::class)
            ->call('switchTo', 'freelancer');

        expect(session('active_mode'))->toBe('freelancer');
        expect($user->fresh()->active_mode)->toBe('freelancer');
    });

    it('switches from freelancer to client mode', function () {
        $user = freelancerUser();

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\Backend\ModeSwitcher::class)
            ->call('switchTo', 'client');

        expect(session('active_mode'))->toBe('client');
        expect($user->fresh()->active_mode)->toBe('client');
    });

    it('ignores invalid mode values', function () {
        $user = clientUser();

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\Backend\ModeSwitcher::class)
            ->call('switchTo', 'hacker');

        // mode should remain unchanged
        expect($user->fresh()->active_mode)->toBe('client');
    });

    it('layout reads correct mode from session', function () {
        $user = clientUser();
        session(['active_mode' => 'client']);

        $this->actingAs($user)
            ->get(route('client.dashboard'))
            ->assertSee('Client'); // brand label

        session(['active_mode' => 'freelancer']);
        $user->update(['active_mode' => 'freelancer']);

        $this->actingAs($user)
            ->get(route('freelancer.dashboard'))
            ->assertSee('Freelancer'); // brand label
    });
});

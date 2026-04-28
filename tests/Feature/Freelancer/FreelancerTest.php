<?php

// tests/Feature/Freelancer/FreelancerTest.php
uses(Tests\TestCase::class);

use App\Models\ServiceProfile;
use App\Models\AvailabilitySchedule;
use App\Models\AvailabilityOverride;
use App\Models\Booking;
use App\Livewire\Backend\FFreelancerDashboard;
use App\Livewire\Backend\EditProfile;
use App\Livewire\Backend\AvailabilitySettings;
use App\Livewire\Backend\PublicBookingPage;
use App\Livewire\Backend\BookingInbox;
use Livewire\Livewire;

// ── FREELANCER DASHBOARD ──────────────────────────────────────────

describe('Freelancer Dashboard', function () {

    it('renders for a freelancer user', function () {
        $user = freelancerUser();

        Livewire::actingAs($user)
            ->test(FreelancerDashboard::class)
            ->assertOk()
            ->assertSee('Available balance')
            ->assertSee('Earned this month')
            ->assertSee('Profile health');
    });

    it('shows profile health checklist', function () {
        $user = freelancerUser();

        // Ensure profile has all fields
        $user->serviceProfile->update([
            'headline' => 'Senior Laravel Developer',
            'bio'      => 'I build SaaS products',
            'skills'   => ['Laravel', 'Vue'],
        ]);

        Livewire::actingAs($user)
            ->test(FreelancerDashboard::class)
            ->assertSee('Headline')
            ->assertSee('Bio')
            ->assertSee('Skills');
    });

    it('shows today meeting banner when booking exists today', function () {
        $user     = freelancerUser();
        $client   = clientUser();

        $booking = Booking::factory()->create([
            'provider_id' => $user->id,
            'client_id'   => $client->id,
            'status'      => 'confirmed',
            'start_at'    => now()->setHour(14)->setMinute(0),
            'end_at'      => now()->setHour(15)->setMinute(0),
        ]);

        Livewire::actingAs($user)
            ->test(FreelancerDashboard::class)
            ->assertSee('meeting')
            ->assertSee('today');
    });

    it('shows earnings KPIs', function () {
        $user   = freelancerUser();
        walletFor($user, 1250.50);

        Livewire::actingAs($user)
            ->test(FreelancerDashboard::class)
            ->assertSee('1,250.50');
    });
});

// ── EDIT PROFILE ──────────────────────────────────────────────────

describe('Edit Profile', function () {

    it('saves profile with valid data', function () {
        $user = freelancerUser();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('headline', 'Full-Stack Developer · Laravel Expert')
            ->set('bio', 'I have 5 years of experience building SaaS products for African startups.')
            ->set('hourlyRate', 45)
            ->set('currency', 'USD')
            ->set('category', 'software_dev')
            ->set('availability', 'open_to_work')
            ->call('saveProfile')
            ->assertHasNoErrors();

        expect($user->serviceProfile->fresh()->headline)
            ->toBe('Full-Stack Developer · Laravel Expert');
        expect($user->serviceProfile->fresh()->hourly_rate)->toBe(45.0);
    });

    it('fails with missing headline', function () {
        $user = freelancerUser();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('headline', '')
            ->call('saveProfile')
            ->assertHasErrors(['headline' => 'required']);
    });

    it('adds and removes skills', function () {
        $user = freelancerUser();

        $component = Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('newSkill', 'Livewire')
            ->call('addSkill');

        expect($component->get('skills'))->toContain('Livewire');

        $component->call('removeSkill', 0);
        expect($component->get('skills'))->not->toContain('Livewire');
    });

    it('saves a service', function () {
        $user = freelancerUser();

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->set('tab', 'services')
            ->call('openServiceForm')
            ->set('svcTitle', 'Laravel SaaS Development')
            ->set('svcCategory', 'software_dev')
            ->set('svcPriceFrom', 500)
            ->set('svcPriceTo', 2000)
            ->set('svcDeliveryDays', 14)
            ->call('saveService')
            ->assertHasNoErrors();

        expect(\App\Models\Service::where('user_id', $user->id)
            ->where('title', 'Laravel SaaS Development')
            ->exists())->toBeTrue();
    });

    it('enables marketplace and creates profile on first visit', function () {
        $user = User::factory()->create([
            'is_marketplace_enabled' => false,
            'active_mode'            => 'freelancer',
        ]);

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->assertOk();

        expect($user->fresh()->is_marketplace_enabled)->toBeTrue();
        expect(ServiceProfile::where('user_id', $user->id)->exists())->toBeTrue();
    });
});

// ── AVAILABILITY ──────────────────────────────────────────────────

describe('Availability Settings', function () {

    it('saves weekly schedule', function () {
        $user = freelancerUser();

        Livewire::actingAs($user)
            ->test(AvailabilitySettings::class)
            ->set('schedule.0.available', true)
            ->set('schedule.0.start_time', '08:00')
            ->set('schedule.0.end_time', '17:00')
            ->call('saveSchedule')
            ->assertHasNoErrors();

        expect(AvailabilitySchedule::where('user_id', $user->id)
            ->where('day_of_week', 0)
            ->where('is_available', true)
            ->exists())->toBeTrue();
    });

    it('adds a date override to block a day', function () {
        $user = freelancerUser();

        Livewire::actingAs($user)
            ->test(AvailabilitySettings::class)
            ->set('overrideDate', now()->addDays(5)->toDateString())
            ->set('overrideAvailable', false)
            ->set('overrideReason', 'Public holiday')
            ->call('addOverride')
            ->assertHasNoErrors();

        expect(AvailabilityOverride::where('user_id', $user->id)
            ->where('is_available', false)
            ->where('reason', 'Public holiday')
            ->exists())->toBeTrue();
    });

    it('fails when override date is in the past', function () {
        $user = freelancerUser();

        Livewire::actingAs($user)
            ->test(AvailabilitySettings::class)
            ->set('overrideDate', now()->subDay()->toDateString())
            ->call('addOverride')
            ->assertHasErrors(['overrideDate']);
    });
});

// ── PUBLIC BOOKING PAGE ───────────────────────────────────────────

describe('Public Booking Page', function () {

    beforeEach(function () {
        // Create a freelancer with Mon–Fri 9–17 schedule
        $this->provider = freelancerUser(['name' => 'booking-test']);

        for ($dow = 0; $dow < 5; $dow++) {
            AvailabilitySchedule::create([
                'user_id'      => $this->provider->id,
                'day_of_week'  => $dow,
                'is_available' => true,
                'start_time'   => '09:00',
                'end_time'     => '17:00',
            ]);
        }

        $this->provider->update(['is_marketplace_enabled' => true]);
    });

    it('renders the booking page', function () {
        Livewire::test(PublicBookingPage::class, ['username' => $this->provider->name])
            ->assertOk()
            ->assertSee('Book a session');
    });

    it('shows available dates for next 60 days', function () {
        $component = Livewire::test(PublicBookingPage::class, ['username' => $this->provider->name]);

        expect(count($component->get('availableDates')))->toBeGreaterThan(0);
    });

    it('submits a booking request successfully', function () {
        $client = clientUser();

        // Find a weekday date
        $date = now()->nextWeekday()->toDateString();

        Livewire::actingAs($client)
            ->test(PublicBookingPage::class, ['username' => $this->provider->name])
            ->call('selectDate', $date)
            ->call('selectSlot', '09:00')
            ->set('clientName', 'Moustapha Kamga')
            ->set('clientEmail', 'moustapha@test.cm')
            ->set('clientMessage', 'I would like to discuss my project')
            ->call('book')
            ->assertHasNoErrors();

        expect(Booking::where('provider_id', $this->provider->id)
            ->where('client_id', $client->id)
            ->where('status', 'pending')
            ->exists())->toBeTrue();
    });

    it('returns 404 for user without marketplace enabled', function () {
        $user = User::factory()->create(['is_marketplace_enabled' => false]);

        $this->get('/book/' . $user->name)->assertNotFound();
    });
});

// ── BOOKING INBOX ─────────────────────────────────────────────────

describe('Booking Inbox', function () {

    it('confirms a pending booking', function () {
        $provider = freelancerUser();
        $client   = clientUser();

        // Create availability
        AvailabilitySchedule::create([
            'user_id'      => $provider->id,
            'day_of_week'  => 0,
            'is_available' => true,
            'start_time'   => '09:00',
            'end_time'     => '17:00',
        ]);

        $booking = Booking::factory()->create([
            'provider_id' => $provider->id,
            'client_id'   => $client->id,
            'status'      => 'pending',
            'start_at'    => now()->nextWeekday()->setHour(10),
            'end_at'      => now()->nextWeekday()->setHour(11),
        ]);

        Livewire::actingAs($provider)
            ->test(BookingInbox::class)
            ->call('confirm', $booking->id)
            ->assertHasNoErrors();

        expect($booking->fresh()->status)->toBe('confirmed');
    });

    it('declines a pending booking', function () {
        $provider = freelancerUser();
        $client   = clientUser();

        $booking = Booking::factory()->create([
            'provider_id' => $provider->id,
            'client_id'   => $client->id,
            'status'      => 'pending',
            'start_at'    => now()->addDay()->setHour(10),
            'end_at'      => now()->addDay()->setHour(11),
        ]);

        Livewire::actingAs($provider)
            ->test(BookingInbox::class)
            ->set('declineNote', 'Sorry, I am unavailable that day.')
            ->call('decline', $booking->id)
            ->assertHasNoErrors();

        expect($booking->fresh()->status)->toBe('cancelled');
    });

    it('shows pending tab by default', function () {
        $provider = freelancerUser();

        Livewire::actingAs($provider)
            ->test(BookingInbox::class)
            ->assertSet('tab', 'pending');
    });
});

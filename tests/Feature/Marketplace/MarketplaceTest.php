<?php

// tests/Feature/Marketplace/MarketplaceTest.php
uses(Tests\TestCase::class);

use App\Models\JobPost;
use App\Models\JobApplication;
use App\Models\ServiceProfile;
use App\Livewire\Backend\ClientMarketplace;
use App\Livewire\Backend\MarketplaceBrowse;
use App\Livewire\Backend\ProfilePage;
use App\Livewire\Backend\JobBoard;
use App\Livewire\Backend\JobPostCreate;
use App\Livewire\Backend\JobPostDetail;
use App\Livewire\Backend\MyJobs;
use Livewire\Livewire;

// ── CLIENT MARKETPLACE ────────────────────────────────────────────

describe('Client Marketplace', function () {

    it('renders the client-facing marketplace', function () {
        $client = clientUser();

        Livewire::actingAs($client)
            ->test(ClientMarketplace::class)
            ->assertOk()
            ->assertSee('Find the right freelancer');
    });

    it('shows available freelancers', function () {
        $client     = clientUser();
        $freelancer = freelancerUser(['name' => 'TestFreelancer']);

        $freelancer->serviceProfile->update([
            'headline'            => 'Laravel Expert',
            'availability_status' => 'open_to_work',
        ]);

        Livewire::actingAs($client)
            ->test(ClientMarketplace::class)
            ->assertSee('TestFreelancer');
    });

    it('filters by category', function () {
        $client = clientUser();
        freelancerUser()->serviceProfile->update(['profession_category' => 'software_dev']);
        freelancerUser()->serviceProfile->update(['profession_category' => 'ui_ux']);

        $component = Livewire::actingAs($client)
            ->test(ClientMarketplace::class)
            ->set('category', 'software_dev');

        // Results should only show software_dev
        $profiles = $component->get('freelancers');
        foreach ($profiles as $p) {
            expect($p->profession_category)->toBe('software_dev');
        }
    });

    it('filters by availability', function () {
        $client = clientUser();
        freelancerUser()->serviceProfile->update(['availability_status' => 'not_available']);

        $component = Livewire::actingAs($client)
            ->test(ClientMarketplace::class)
            ->set('availability', 'open_to_work');

        $profiles = $component->get('freelancers');
        foreach ($profiles as $p) {
            expect($p->availability_status)->toBe('open_to_work');
        }
    });

    it('adds freelancer to shortlist', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        Livewire::actingAs($client)
            ->test(ClientMarketplace::class)
            ->call('shortlist', $freelancer->id)
            ->assertSet('shortlisted', [$freelancer->id]);
    });

    it('removes freelancer from shortlist on second click', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $component = Livewire::actingAs($client)
            ->test(ClientMarketplace::class)
            ->call('shortlist', $freelancer->id)
            ->call('shortlist', $freelancer->id); // toggle off

        expect($component->get('shortlisted'))->not->toContain($freelancer->id);
    });

    it('opens quick view panel', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        Livewire::actingAs($client)
            ->test(ClientMarketplace::class)
            ->call('openQuickView', $freelancer->id)
            ->assertSet('quickViewId', $freelancer->id)
            ->assertSee($freelancer->name);
    });
});

// ── JOB POSTS ─────────────────────────────────────────────────────

describe('Job Post Creation', function () {

    it('publishes a job post with valid data', function () {
        $client = clientUser();

        Livewire::actingAs($client)
            ->test(JobPostCreate::class)
            ->set('title', 'Looking for a Laravel Developer for 3-month project')
            ->set('description', 'We need a senior Laravel developer to build our SaaS dashboard. Must know Livewire and Flux UI.')
            ->set('category', 'software_dev')
            ->set('type', 'fixed')
            ->set('budgetMin', 500)
            ->set('budgetMax', 2000)
            ->set('currency', 'USD')
            ->set('experienceLevel', 'senior')
            ->call('publish')
            ->assertHasNoErrors();

        expect(JobPost::where('client_id', $client->id)
            ->where('status', 'open')
            ->exists())->toBeTrue();
    });

    it('saves as draft with only a title', function () {
        $client = clientUser();

        Livewire::actingAs($client)
            ->test(JobPostCreate::class)
            ->set('title', 'Draft Job Post')
            ->call('saveDraft')
            ->assertHasNoErrors();

        expect(JobPost::where('client_id', $client->id)
            ->where('status', 'draft')
            ->exists())->toBeTrue();
    });

    it('fails with title too short', function () {
        $client = clientUser();

        Livewire::actingAs($client)
            ->test(JobPostCreate::class)
            ->set('title', 'Short')
            ->set('description', str_repeat('a', 50))
            ->set('category', 'software_dev')
            ->call('publish')
            ->assertHasErrors(['title']);
    });

    it('fails with description too short', function () {
        $client = clientUser();

        Livewire::actingAs($client)
            ->test(JobPostCreate::class)
            ->set('title', 'Valid Long Title That Passes Validation')
            ->set('description', 'Too short')
            ->set('category', 'software_dev')
            ->call('publish')
            ->assertHasErrors(['description']);
    });

    it('adds and removes required skills', function () {
        $client = clientUser();

        $component = Livewire::actingAs($client)
            ->test(JobPostCreate::class)
            ->set('newSkill', 'Laravel')
            ->call('addSkill');

        expect($component->get('skills'))->toContain('Laravel');

        $component->call('removeSkill', 0);
        expect($component->get('skills'))->not->toContain('Laravel');
    });
});

// ── JOB BOARD (freelancer applies) ────────────────────────────────

describe('Job Board', function () {

    it('shows open public jobs', function () {
        $client = clientUser();
        $freelancer = freelancerUser();

        JobPost::factory()->create([
            'client_id'  => $client->id,
            'title'      => 'Open Laravel Position',
            'status'     => 'open',
            'visibility' => 'public',
        ]);

        Livewire::actingAs($freelancer)
            ->test(JobBoard::class)
            ->assertSee('Open Laravel Position');
    });

    it('does not show draft or closed jobs', function () {
        $client = clientUser();
        $freelancer = freelancerUser();

        JobPost::factory()->create(['client_id' => $client->id, 'title' => 'Draft Job', 'status' => 'draft']);
        JobPost::factory()->create(['client_id' => $client->id, 'title' => 'Closed Job', 'status' => 'closed']);

        Livewire::actingAs($freelancer)
            ->test(JobBoard::class)
            ->assertDontSee('Draft Job')
            ->assertDontSee('Closed Job');
    });

    it('submits an application', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $job = JobPost::factory()->create([
            'client_id'      => $client->id,
            'status'         => 'open',
            'visibility'     => 'public',
            'max_applicants' => 20,
        ]);

        Livewire::actingAs($freelancer)
            ->test(JobBoard::class)
            ->call('openApply', $job->id)
            ->set('coverLetter', 'I am a great fit for this role. I have 5 years of Laravel experience and have built many SaaS products.')
            ->set('proposedRate', 45)
            ->set('availability', 'within_1_week')
            ->call('submitApplication')
            ->assertHasNoErrors()
            ->assertSet('applied', true);

        expect(JobApplication::where('job_post_id', $job->id)
            ->where('freelancer_id', $freelancer->id)
            ->exists())->toBeTrue();
    });

    it('prevents duplicate applications', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $job = JobPost::factory()->create(['client_id' => $client->id, 'status' => 'open', 'visibility' => 'public']);

        // First application
        JobApplication::factory()->create([
            'job_post_id'  => $job->id,
            'freelancer_id'=> $freelancer->id,
        ]);

        Livewire::actingAs($freelancer)
            ->test(JobBoard::class)
            ->call('openApply', $job->id)
            ->set('coverLetter', str_repeat('x', 60))
            ->set('availability', 'immediately')
            ->call('submitApplication');

        // Should only have 1 application
        expect(JobApplication::where('job_post_id', $job->id)
            ->where('freelancer_id', $freelancer->id)
            ->count())->toBe(1);
    });

    it('requires cover letter min 50 chars', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $job = JobPost::factory()->create(['client_id' => $client->id, 'status' => 'open', 'visibility' => 'public']);

        Livewire::actingAs($freelancer)
            ->test(JobBoard::class)
            ->call('openApply', $job->id)
            ->set('coverLetter', 'Too short')
            ->set('availability', 'immediately')
            ->call('submitApplication')
            ->assertHasErrors(['coverLetter']);
    });
});

// ── JOB POST DETAIL (client manages applicants) ───────────────────

describe('Job Post Detail', function () {

    it('client can shortlist an applicant', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $job = JobPost::factory()->create(['client_id' => $client->id, 'status' => 'open']);
        $app = JobApplication::factory()->create([
            'job_post_id'  => $job->id,
            'freelancer_id'=> $freelancer->id,
            'status'       => 'pending',
        ]);

        Livewire::actingAs($client)
            ->test(JobPostDetail::class, ['id' => $job->id])
            ->call('shortlist', $app->id)
            ->assertHasNoErrors();

        expect($app->fresh()->status)->toBe('shortlisted');
    });

    it('client can hire an applicant and marks job as filled', function () {
        $client     = clientUser();
        $freelancer = freelancerUser();

        $job = JobPost::factory()->create(['client_id' => $client->id, 'status' => 'open']);
        $app = JobApplication::factory()->create([
            'job_post_id'  => $job->id,
            'freelancer_id'=> $freelancer->id,
            'status'       => 'shortlisted',
        ]);

        Livewire::actingAs($client)
            ->test(JobPostDetail::class, ['id' => $job->id])
            ->call('hire', $app->id)
            ->assertHasNoErrors();

        expect($app->fresh()->status)->toBe('hired');
        expect($job->fresh()->status)->toBe('filled');
    });

    it('non-owner cannot manage applicants', function () {
        $client     = clientUser();
        $outsider   = clientUser();
        $freelancer = freelancerUser();

        $job = JobPost::factory()->create(['client_id' => $client->id, 'status' => 'open']);
        $app = JobApplication::factory()->create([
            'job_post_id'  => $job->id,
            'freelancer_id'=> $freelancer->id,
        ]);

        Livewire::actingAs($outsider)
            ->test(JobPostDetail::class, ['id' => $job->id])
            ->call('shortlist', $app->id)
            ->assertForbidden();
    });
});

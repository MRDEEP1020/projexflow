<?php
// tests/Feature/Admin/AdminTest.php
uses(Tests\TestCase::class);

use App\Models\User;
use App\Models\Contract;
use App\Models\Dispute;
use App\Models\WithdrawalRequest;
use App\Models\Wallet;
use App\Models\JobPost;
use App\Models\Review;
use App\Livewire\Backend\AdminDashboard;
use App\Livewire\Backend\AdminUsers;
use App\Livewire\Backend\AdminDisputes;
use App\Livewire\Backend\AdminWithdrawals;
use App\Livewire\Backend\AdminModeration;
use Livewire\Livewire;

describe('Admin access guard', function () {

    it('blocks regular users from admin dashboard', function () {
        $this->actingAs(clientUser())->get(route('admin.dashboard'))->assertForbidden();
    });

    it('allows admin to access all admin routes', function () {
        $admin = adminUser();
        foreach (['admin.dashboard','admin.users','admin.disputes','admin.withdrawals','admin.moderation'] as $r) {
            $this->actingAs($admin)->get(route($r))->assertOk();
        }
    });
});

describe('Admin Dashboard', function () {

    it('renders KPIs', function () {
        Livewire::actingAs(adminUser())
            ->test(AdminDashboard::class)
            ->assertOk()
            ->assertSee('Platform revenue')
            ->assertSee('New users');
    });

    it('changes period', function () {
        Livewire::actingAs(adminUser())
            ->test(AdminDashboard::class)
            ->set('period', '7')
            ->assertSet('period', '7');
    });
});

describe('Admin User Management', function () {

    it('lists users', function () {
        $admin = adminUser();
        $user  = clientUser();
        Livewire::actingAs($admin)->test(AdminUsers::class)->assertSee($user->name);
    });

    it('searches by name', function () {
        $admin = adminUser();
        clientUser(['name' => 'SearchTarget123']);
        clientUser(['name' => 'Invisible999']);
        Livewire::actingAs($admin)->test(AdminUsers::class)
            ->set('search','SearchTarget123')
            ->assertSee('SearchTarget123')
            ->assertDontSee('Invisible999');
    });

    it('suspends a user', function () {
        $admin = adminUser(); $user = clientUser();
        Livewire::actingAs($admin)->test(AdminUsers::class)->call('suspend', $user->id);
        expect($user->fresh()->suspended_at)->not->toBeNull();
    });

    it('unsuspends a user', function () {
        $admin = adminUser(); $user = clientUser(['suspended_at' => now()]);
        Livewire::actingAs($admin)->test(AdminUsers::class)->call('unsuspend', $user->id);
        expect($user->fresh()->suspended_at)->toBeNull();
    });

    it('promotes user to admin', function () {
        $admin = adminUser(); $user = clientUser();
        Livewire::actingAs($admin)->test(AdminUsers::class)->call('makeAdmin', $user->id);
        expect($user->fresh()->role)->toBe('admin');
    });

    it('revokes admin role', function () {
        $admin = adminUser(); $other = adminUser();
        Livewire::actingAs($admin)->test(AdminUsers::class)->call('revokeAdmin', $other->id);
        expect($other->fresh()->role)->toBe('user');
    });

    it('verifies freelancer profile', function () {
        $admin = adminUser(); $f = freelancerUser();
        $f->serviceProfile->update(['is_verified' => false]);
        Livewire::actingAs($admin)->test(AdminUsers::class)->call('verifyFreelancer', $f->id);
        expect($f->serviceProfile->fresh()->is_verified)->toBeTrue();
    });
});

describe('Admin Dispute Resolution', function () {

    it('resolves dispute in favor of freelancer', function () {
        $admin = adminUser(); $client = clientUser(); $f = freelancerUser();
        $contract = contractBetween($client, $f, [
            'status' => 'disputed', 'total_amount' => 1000,
            'deposit_amount' => 300, 'platform_fee_amount' => 100,
            'platform_fee_percentage' => 10,
        ]);
        $dispute = Dispute::factory()->create([
            'contract_id' => $contract->id, 'raised_by' => $client->id,
            'against' => $f->id, 'status' => 'open', 'reason' => 'work_not_delivered',
        ]);
        walletFor($f, 0);

        Livewire::actingAs($admin)->test(AdminDisputes::class)
            ->set('viewId', $dispute->id)
            ->set('resolveFor', 'freelancer')
            ->set('resolution', 'After reviewing the evidence, the freelancer delivered the agreed scope. Payment released.')
            ->call('resolve', $dispute->id)
            ->assertHasNoErrors();

        expect($dispute->fresh()->status)->toBe('resolved');
        expect($contract->fresh()->status)->toBe('completed');
        expect(Wallet::where('user_id', $f->id)->value('available_balance'))->toBeGreaterThan(0);
    });

    it('resolves dispute with 60/40 split', function () {
        $admin = adminUser(); $client = clientUser(); $f = freelancerUser();
        $contract = contractBetween($client, $f, [
            'status' => 'disputed', 'total_amount' => 1000,
            'deposit_amount' => 300, 'platform_fee_amount' => 100,
            'platform_fee_percentage' => 10,
        ]);
        $dispute = Dispute::factory()->create([
            'contract_id' => $contract->id, 'raised_by' => $client->id, 'status' => 'open',
        ]);
        walletFor($f, 0);

        Livewire::actingAs($admin)->test(AdminDisputes::class)
            ->set('viewId', $dispute->id)
            ->set('resolveFor', 'split')
            ->set('splitPct', 60)
            ->set('resolution', 'Both parties share responsibility. Freelancer receives 60%, client receives 40% refund.')
            ->call('resolve', $dispute->id)
            ->assertHasNoErrors();

        expect($dispute->fresh()->status)->toBe('resolved');
    });

    it('fails with short resolution text', function () {
        $admin = adminUser(); $client = clientUser(); $f = freelancerUser();
        $contract = contractBetween($client, $f, ['status' => 'disputed']);
        $dispute = Dispute::factory()->create(['contract_id' => $contract->id, 'raised_by' => $client->id, 'status' => 'open']);

        Livewire::actingAs($admin)->test(AdminDisputes::class)
            ->set('viewId', $dispute->id)->set('resolveFor', 'freelancer')
            ->set('resolution', 'Short')
            ->call('resolve', $dispute->id)
            ->assertHasErrors(['resolution']);
    });
});

describe('Admin Withdrawal Management', function () {

    it('rejects withdrawal and refunds wallet', function () {
        $admin = adminUser(); $f = freelancerUser();
        walletFor($f, 0);
        $req = WithdrawalRequest::factory()->create([
            'user_id' => $f->id, 'amount' => 300, 'status' => 'pending', 'method' => 'bank',
        ]);

        Livewire::actingAs($admin)->test(AdminWithdrawals::class)
            ->set('viewId', $req->id)
            ->set('failNote', 'Bank account details are invalid. Please update your account information.')
            ->call('reject', $req->id)
            ->assertHasNoErrors();

        expect($req->fresh()->status)->toBe('failed');
        expect(Wallet::where('user_id', $f->id)->value('available_balance'))->toBe(300.0);
    });

    it('fails rejection with short reason', function () {
        $admin = adminUser(); $f = freelancerUser();
        walletFor($f, 0);
        $req = WithdrawalRequest::factory()->create(['user_id' => $f->id, 'amount' => 100, 'status' => 'pending']);

        Livewire::actingAs($admin)->test(AdminWithdrawals::class)
            ->set('viewId', $req->id)->set('failNote', 'Bad')
            ->call('reject', $req->id)->assertHasErrors(['failNote']);
    });
});

describe('Admin Moderation', function () {

    it('removes a job post', function () {
        $admin = adminUser(); $client = clientUser();
        $job = JobPost::factory()->create(['client_id' => $client->id, 'status' => 'open']);
        Livewire::actingAs($admin)->test(AdminModeration::class)->call('removeJob', $job->id);
        expect($job->fresh()->status)->toBe('removed');
    });

    it('removes review and recalculates rating', function () {
        $admin = adminUser(); $f = freelancerUser(); $client = clientUser();
        Review::factory()->create(['reviewer_id' => $client->id, 'reviewee_id' => $f->id, 'rating' => 5]);
        $bad = Review::factory()->create(['reviewer_id' => $client->id, 'reviewee_id' => $f->id, 'rating' => 1]);
        $f->serviceProfile->update(['avg_rating' => 3.0, 'total_reviews' => 2]);

        Livewire::actingAs($admin)->test(AdminModeration::class)
            ->set('tab', 'reviews')->call('removeReview', $bad->id);

        expect(Review::find($bad->id))->toBeNull();
        expect($f->serviceProfile->fresh()->avg_rating)->toBe(5.0);
        expect($f->serviceProfile->fresh()->total_reviews)->toBe(1);
    });

    it('disables freelancer marketplace profile', function () {
        $admin = adminUser(); $f = freelancerUser();
        Livewire::actingAs($admin)->test(AdminModeration::class)
            ->set('tab', 'profiles')->call('suspendProfile', $f->id);
        expect($f->fresh()->is_marketplace_enabled)->toBeFalse();
    });
});

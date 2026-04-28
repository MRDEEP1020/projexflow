<?php

// tests/Pest.php
// Central configuration for the entire ProjexFlow test suite

use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Project;
use App\Models\Task;
use App\Models\ServiceProfile;
use App\Models\Contract;
use App\Models\JobPost;
use App\Models\Booking;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->in('Feature');
// tests/Pest.php
// uses(Tests\TestCase::class)->in('Feature', 'Unit');
// ── Global helper functions ────────────────────────────────────────

/**
 * Create a fully-set-up client user with org + session.
 */
function clientUser(array $attrs = []): User
{
    $user = User::factory()->create(array_merge([
        'role'        => 'user',
        'active_mode' => 'client',
    ], $attrs));

    $org = Organization::factory()->create(['owner_id' => $user->id]);

    OrganizationMember::create([
        'org_id'    => $org->id,
        'user_id'   => $user->id,
        'role'      => 'owner',
        'joined_at' => now(),
    ]);

    // Set session state
    session(['active_org_id' => $org->id, 'active_mode' => 'client']);

    return $user;
}

/**
 * Create a fully-set-up freelancer user with service profile.
 */
function freelancerUser(array $attrs = []): User
{
    $user = User::factory()->create(array_merge([
        'role'                   => 'user',
        'active_mode'            => 'freelancer',
        'is_marketplace_enabled' => true,
    ], $attrs));

    ServiceProfile::factory()->create([
        'user_id'             => $user->id,
        'availability_status' => 'open_to_work',
        'hourly_rate'         => 50,
        'avg_rating'          => 4.5,
        'total_reviews'       => 10,
    ]);

    session(['active_mode' => 'freelancer']);

    return $user;
}

/**
 * Create an admin user.
 */
function adminUser(array $attrs = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'admin',
    ], $attrs));
}

/**
 * Create a project owned by the given user's active org.
 */
function projectFor(User $user, array $attrs = []): Project
{
    $orgId = session('active_org_id')
        ?? OrganizationMember::where('user_id', $user->id)->value('org_id');

    return Project::factory()->create(array_merge([
        'org_id' => $orgId,
        'status' => 'active',
    ], $attrs));
}

/**
 * Create a contract between client and freelancer.
 */
function contractBetween(User $client, User $freelancer, array $attrs = []): Contract
{
    return Contract::factory()->create(array_merge([
        'client_id'              => $client->id,
        'freelancer_id'          => $freelancer->id,
        'total_amount'           => 1000,
        'deposit_amount'         => 300,
        'deposit_percentage'     => 30,
        'platform_fee_percentage'=> 10,
        'platform_fee_amount'    => 100,
        'currency'               => 'USD',
        'status'                 => 'active',
    ], $attrs));
}

/**
 * Create a wallet for a user with a given balance.
 */
function walletFor(User $user, float $balance = 500): Wallet
{
    return Wallet::factory()->create([
        'user_id'           => $user->id,
        'available_balance' => $balance,
        'held_balance'      => 0,
        'total_earned'      => $balance,
    ]);
}

// ── Shared expectations ───────────────────────────────────────────

expect()->extend('toBeRedirectTo', function (string $route) {
    return $this->value->assertRedirect(route($route));
});

expect()->extend('toHaveFlash', function (string $key) {
    return $this->value->assertSessionHas($key);
});

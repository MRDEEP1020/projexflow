<?php

// tests/Feature/Auth/AuthTest.php
uses(Tests\TestCase::class);
use App\Models\User;
use App\Livewire\Backend\Auth\Register;
use App\Livewire\Backend\Auth\Login;
use Livewire\Livewire;

// ── REGISTRATION ──────────────────────────────────────────────────

describe('Registration', function () {

    it('renders the register page', function () {
        $this->get(route('register'))
            ->assertOk()
            ->assertSeeLivewire(Register::class);
    });

    it('creates user, organization, and owner membership on registration', function () {
        Livewire::test(Register::class)
            ->set('name', 'Moustapha Kamga')
            ->set('email', 'moustapha@test.cm')
            ->set('password', 'Password123!')
            ->set('password_confirmation', 'Password123!')
            ->set('orgName', 'Kamga Solutions')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect();

        expect(User::where('email', 'moustapha@test.cm')->exists())->toBeTrue();

        $user = User::where('email', 'moustapha@test.cm')->first();
        expect($user->organizations)->toHaveCount(1);
        expect($user->organizations->first()->name)->toBe('Kamga Solutions');
        expect($user->active_mode)->toBe('client');
    });

    it('fails validation with short password', function () {
        Livewire::test(Register::class)
            ->set('name', 'Test User')
            ->set('email', 'test@test.com')
            ->set('password', '123')
            ->set('password_confirmation', '123')
            ->call('register')
            ->assertHasErrors(['password']);
    });

    it('fails with duplicate email', function () {
        User::factory()->create(['email' => 'existing@test.com']);

        Livewire::test(Register::class)
            ->set('name', 'Another User')
            ->set('email', 'existing@test.com')
            ->set('password', 'Password123!')
            ->set('password_confirmation', 'Password123!')
            ->call('register')
            ->assertHasErrors(['email']);
    });

    it('sets active_mode to client by default', function () {
        Livewire::test(Register::class)
            ->set('name', 'New User')
            ->set('email', 'newuser@test.com')
            ->set('password', 'Password123!')
            ->set('password_confirmation', 'Password123!')
            ->set('orgName', 'New Org')
            ->call('register');

        $user = User::where('email', 'newuser@test.com')->first();
        expect($user->active_mode)->toBe('client');
        expect($user->role)->toBe('user');
    });
});

// ── LOGIN ─────────────────────────────────────────────────────────

describe('Login', function () {

    it('renders the login page', function () {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeLivewire(Login::class);
    });

    it('logs in with valid credentials', function () {
        $user = User::factory()->create([
            'email'    => 'user@test.com',
            'password' => bcrypt('Password123!'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'user@test.com')
            ->set('password', 'Password123!')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect();

        expect(auth()->check())->toBeTrue();
        expect(auth()->id())->toBe($user->id);
    });

    it('fails with wrong password', function () {
        User::factory()->create([
            'email'    => 'user@test.com',
            'password' => bcrypt('Password123!'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'user@test.com')
            ->set('password', 'wrongpassword')
            ->call('login')
            ->assertHasErrors(['email']);
    });

    it('rate limits after 5 failed attempts', function () {
        $user = User::factory()->create([
            'email'    => 'victim@test.com',
            'password' => bcrypt('correct'),
        ]);

        $component = Livewire::test(Login::class)
            ->set('email', 'victim@test.com');

        for ($i = 0; $i < 5; $i++) {
            $component->set('password', 'wrong')->call('login');
        }

        $component->set('password', 'correct')
            ->call('login')
            ->assertHasErrors(['email']); // rate limited
    });

    it('redirects client to client dashboard', function () {
        $user = clientUser(['password' => bcrypt('Password123!')]);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'Password123!')
            ->call('login')
            ->assertRedirect();
    });
});

// ── ROUTE PROTECTION ──────────────────────────────────────────────

describe('Route protection', function () {

    it('redirects unauthenticated users from dashboard', function () {
        $this->get(route('client.dashboard'))
            ->assertRedirect(route('login'));
    });

    it('blocks non-admin from admin panel', function () {
        $user = clientUser();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    });

    it('allows admin to access admin panel', function () {
        $admin = adminUser();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    });

    it('blocks suspended users', function () {
        $user = clientUser(['suspended_at' => now()]);

        $this->actingAs($user)
            ->get(route('client.dashboard'))
            ->assertForbidden();
    });
});

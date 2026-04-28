<?php

// ═══════════════════════════════════════════════════════════════
// app/Livewire/Backend/AdminUsers.php
// ═══════════════════════════════════════════════════════════════

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

#[Layout('components.layouts.admin')]
#[Title('User Management')]
class AdminUsers extends Component
{
    use WithPagination;

    public string $search   = '';
    public string $role     = 'all';
    public string $status   = 'all';
    public ?int   $viewId   = null;
    public string $newRole  = '';

    public function updatingSearch(): void { $this->resetPage(); }

    public function suspend(int $userId): void
    {
        User::where('id', $userId)->update(['suspended_at' => now()]);
        $this->dispatch('toast', ['message' => 'User suspended.', 'type' => 'info']);
    }

    public function unsuspend(int $userId): void
    {
        User::where('id', $userId)->update(['suspended_at' => null]);
        $this->dispatch('toast', ['message' => 'User unsuspended.', 'type' => 'success']);
    }

    public function makeAdmin(int $userId): void
    {
        User::where('id', $userId)->update(['role' => 'admin']);
        $this->dispatch('toast', ['message' => 'User promoted to admin.', 'type' => 'success']);
    }

    public function revokeAdmin(int $userId): void
    {
        User::where('id', $userId)->update(['role' => 'user']);
        $this->dispatch('toast', ['message' => 'Admin access revoked.', 'type' => 'info']);
    }

    public function verifyFreelancer(int $userId): void
    {
        \App\Models\ServiceProfile::where('user_id', $userId)->update(['is_verified' => true]);
        $this->dispatch('toast', ['message' => 'Freelancer verified.', 'type' => 'success']);
    }

    #[Computed]
    public function users()
    {
        return User::when($this->search, fn($q) => $q->where(function($q) {
                $q->where('name','like','%'.$this->search.'%')
                  ->orWhere('email','like','%'.$this->search.'%');
            }))
            ->when($this->role !== 'all', fn($q) => $q->where('role', $this->role))
            ->when($this->status === 'suspended', fn($q) => $q->whereNotNull('suspended_at'))
            ->when($this->status === 'active', fn($q) => $q->whereNull('suspended_at'))
            ->with('serviceProfile')
            ->latest()
            ->paginate(20);
    }

    public function render() { return view('livewire.backend.adminUsers'); }
}
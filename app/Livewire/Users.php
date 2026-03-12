<?php

namespace App\Livewire;

use App\Helpers\AuditHelper;
use App\Models\User;
use App\Notifications\NewUserPasswordNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Users extends Component
{
    use WithPagination;

    public function mount(): void
    {
        Gate::authorize('admin-only');
    }

    public bool $showModal = false;
    public ?string $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $role = 'DOCTOR';
    public string $formError = '';

    public function openNew(): void
    {
        $this->editingId = null;
        $this->name = $this->email = $this->formError = '';
        $this->role = 'DOCTOR';
        $this->showModal = true;
    }

    public function openEdit(string $id): void
    {
        $user = User::findOrFail($id);
        $this->editingId = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->formError = '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->formError = '';
        $rules = [
            'name'  => 'required|string|max:255',
            'email' => 'required|email',
            'role'  => 'required|in:ADMIN,DOCTOR',
        ];
        $this->validate($rules);

        try {
            if ($this->editingId) {
                $user = User::findOrFail($this->editingId);
                $data = ['name' => $this->name, 'email' => $this->email, 'role' => $this->role];
                $oldValues = $user->only(['name', 'email', 'role']);

                $user->update($data);

                AuditHelper::logAction('user.updated', $user, $oldValues, $data);

                $this->dispatch('toast', message: 'Usuário atualizado com sucesso!');
            } else {
                $plainPassword = Str::random(10);

                $newUser = User::create([
                    'name'          => $this->name,
                    'email'         => $this->email,
                    'password_hash' => Hash::make($plainPassword),
                    'role'          => $this->role,
                ]);

                $newUser->notify(new NewUserPasswordNotification($plainPassword));

                AuditHelper::logAction(
                    'user.created',
                    $newUser,
                    null,
                    $newUser->only(['name', 'email', 'role'])
                );

                $this->dispatch('toast', message: 'Usuário criado! Senha enviada por e-mail.');
            }
            $this->showModal = false;
        } catch (\Exception $e) {
            $this->formError = str_contains($e->getMessage(), 'email')
                ? 'Email já cadastrado'
                : 'Erro ao salvar usuário';
        }
    }

    public function deactivate(string $id): void
    {
        $user = User::findOrFail($id);
        $user->update(['active' => false]);

        AuditHelper::logAction(
            'user.deactivated',
            $user,
            ['active' => true],
            ['active' => false]
        );

        $this->dispatch('toast', message: 'Usuário desativado.');
    }

    public function activate(string $id): void
    {
        $user = User::findOrFail($id);
        $user->update(['active' => true]);

        AuditHelper::logAction(
            'user.activated',
            $user,
            ['active' => false],
            ['active' => true]
        );

        $this->dispatch('toast', message: 'Usuário reativado.');
    }

    public function render()
    {
        $users = User::orderBy('name')->paginate(20);
        return view('livewire.users', compact('users'))->layout('layouts.app');
    }
}

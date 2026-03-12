<?php

namespace App\Livewire;

use App\Helpers\AuditHelper;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class Users extends Component
{
    use WithPagination;

    public function mount(): void
    {
        abort_if(auth()->user()->role !== 'ADMIN', 403);
    }

    public bool $showModal = false;
    public ?string $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'DOCTOR';
    public string $formError = '';

    public function openNew(): void
    {
        $this->editingId = null;
        $this->name = $this->email = $this->password = $this->formError = '';
        $this->role = 'DOCTOR';
        $this->showModal = true;
    }

    public function openEdit(string $id): void
    {
        $u = User::findOrFail($id);
        $this->editingId = $id;
        $this->name = $u->name;
        $this->email = $u->email;
        $this->password = '';
        $this->role = $u->role;
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
        if (!$this->editingId) {
            $rules['password'] = 'required|string|min:6';
        }
        $this->validate($rules);

        try {
            if ($this->editingId) {
                $user = User::findOrFail($this->editingId);
                $data = ['name' => $this->name, 'email' => $this->email, 'role' => $this->role];
                $oldValues = $user->only(['name', 'email', 'role']);

                if ($this->password) {
                    $data['password_hash'] = Hash::make($this->password);
                    $oldValues['password_hash'] = $user->password_hash;
                }

                $user->update($data);

                AuditHelper::logAction(
                    'updated',
                    $user,
                    $oldValues,
                    collect($data)->except('password_hash')->toArray()
                );

                $this->dispatch('toast', message: 'Usuário atualizado com sucesso!');
            } else {
                $newUser = User::create([
                    'name'          => $this->name,
                    'email'         => $this->email,
                    'password_hash' => Hash::make($this->password),
                    'role'          => $this->role,
                ]);

                AuditHelper::logAction(
                    'inserted',
                    $newUser,
                    null,
                    $newUser->only(['name', 'email', 'role'])
                );

                $this->dispatch('toast', message: 'Usuário criado com sucesso!');
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
            'deactivated',
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
            'activated',
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

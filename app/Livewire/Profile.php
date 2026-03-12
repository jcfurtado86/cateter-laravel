<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Profile extends Component
{
    // Editar dados
    public bool $showEditModal = false;
    public string $editName = '';
    public string $editEmail = '';
    public string $editError = '';

    // Trocar senha
    public bool $showPasswordModal = false;
    public string $oldPassword = '';
    public string $newPassword = '';
    public string $confirmPassword = '';
    public string $passwordError = '';

    public function openEdit(): void
    {
        $this->editName  = auth()->user()->name;
        $this->editEmail = auth()->user()->email;
        $this->editError = '';
        $this->showEditModal = true;
    }

    public function saveProfile(): void
    {
        $this->editError = '';
        $this->validate([
            'editName'  => 'required|string|max:255',
            'editEmail' => 'required|email',
        ]);

        $user = auth()->user();

        if ($this->editEmail !== $user->email) {
            $exists = \App\Models\User::where('email', $this->editEmail)->where('id', '!=', $user->id)->exists();
            if ($exists) {
                $this->editError = 'Email já cadastrado por outro usuário';
                return;
            }
        }

        $user->update(['name' => $this->editName, 'email' => $this->editEmail]);
        $this->showEditModal = false;
        $this->dispatch('toast', message: 'Perfil atualizado com sucesso!');
    }

    public function openPassword(): void
    {
        $this->oldPassword = $this->newPassword = $this->confirmPassword = $this->passwordError = '';
        $this->showPasswordModal = true;
    }

    public function savePassword(): void
    {
        $this->passwordError = '';

        if (!Hash::check($this->oldPassword, auth()->user()->password_hash)) {
            $this->passwordError = 'Senha atual incorreta';
            return;
        }
        if (strlen($this->newPassword) < 6) {
            $this->passwordError = 'Nova senha deve ter no mínimo 6 caracteres';
            return;
        }
        if ($this->newPassword !== $this->confirmPassword) {
            $this->passwordError = 'As senhas não coincidem';
            return;
        }

        auth()->user()->update(['password_hash' => Hash::make($this->newPassword)]);
        $this->showPasswordModal = false;
        $this->dispatch('toast', message: 'Senha alterada com sucesso!');
    }

    public function render()
    {
        return view('livewire.profile');
    }
}

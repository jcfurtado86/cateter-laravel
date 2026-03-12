<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->string('email');
    }

    public function resetPassword(): void
    {
        $this->validate([
            'token'    => ['required'],
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password_hash'  => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));
            return;
        }

        Session::flash('status', 'Senha redefinida com sucesso!');
        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div>
    <form wire:submit="resetPassword" class="login-form">
        @error('email')
            <div class="alert alert-error">{{ $message }}</div>
        @enderror

        <div class="form-group">
            <label for="email">Email</label>
            <input wire:model="email" id="email" type="email" required autocomplete="username" />
        </div>
        <div class="form-group">
            <label for="password">Nova Senha</label>
            <input wire:model="password" id="password" type="password"
                   placeholder="••••••••" required autocomplete="new-password" />
            <small style="color:#666; margin-top:4px">Mínimo 6 caracteres</small>
        </div>
        <div class="form-group">
            <label for="password_confirmation">Confirmar Nova Senha</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password"
                   placeholder="••••••••" required autocomplete="new-password" />
        </div>

        <button type="submit" class="btn btn-primary btn-block" wire:loading.attr="disabled">
            <span wire:loading.remove>Redefinir Senha</span>
            <span wire:loading>Salvando...</span>
        </button>
    </form>
</div>

<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <form wire:submit="login" class="login-form">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @error('form.email')
            <div class="alert alert-error">{{ $message }}</div>
        @enderror

        <div class="form-group">
            <label for="email">Email</label>
            <input wire:model="form.email" id="email" type="email" name="email"
                   placeholder="medico@hospital.com" required autofocus autocomplete="username" />
        </div>

        <div class="form-group">
            <label for="password">Senha</label>
            <input wire:model="form.password" id="password" type="password" name="password"
                   placeholder="••••••••" required autocomplete="current-password" />
        </div>

        <button type="submit" class="btn btn-primary btn-block" wire:loading.attr="disabled">
            <span wire:loading.remove>Entrar</span>
            <span wire:loading>Entrando...</span>
        </button>

        @if (Route::has('password.request'))
            <p style="text-align:center; margin-top:12px;">
                <a href="{{ route('password.request') }}" wire:navigate>Esqueci minha senha</a>
            </p>
        @endif
    </form>
</div>

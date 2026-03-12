<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    public function sendPasswordResetLink(): void
    {
        $this->validate(['email' => ['required', 'string', 'email']]);

        $status = Password::sendResetLink($this->only('email'));

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));
            return;
        }

        $this->reset('email');
        session()->flash('status', 'Link de redefinição enviado para o seu e-mail.');
    }
}; ?>

<div>
    <form wire:submit="sendPasswordResetLink" class="login-form">
        <p style="color:#6b7280; font-size:13px; margin-bottom:16px">
            Informe seu e-mail e enviaremos um link para redefinir sua senha.
        </p>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @error('email')
            <div class="alert alert-error">{{ $message }}</div>
        @enderror

        <div class="form-group">
            <label for="email">Email</label>
            <input wire:model="email" id="email" type="email" name="email"
                   placeholder="medico@hospital.com" required autofocus />
        </div>

        <button type="submit" class="btn btn-primary btn-block" wire:loading.attr="disabled">
            <span wire:loading.remove>Enviar Link</span>
            <span wire:loading>Enviando...</span>
        </button>

        <p style="text-align:center; margin-top:12px;">
            <a href="{{ route('login') }}" wire:navigate>← Voltar ao login</a>
        </p>
    </form>
</div>

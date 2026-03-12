<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserPasswordNotification extends Notification
{
    public function __construct(public string $password) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bem-vindo ao Sistema Cateter — Sua Senha de Acesso')
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('Seu acesso ao Sistema Cateter foi criado.')
            ->line('**E-mail:** ' . $notifiable->email)
            ->line('**Senha:** ' . $this->password)
            ->action('Acessar o Sistema', url('/login'))
            ->line('Por segurança, recomendamos alterar sua senha após o primeiro acesso.')
            ->salutation('Atenciosamente, Sistema Cateter');
    }
}

<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'role',
        'active',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    // Laravel auth espera o campo "password" — mapeamos para password_hash
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function catheterRecords()
    {
        return $this->hasMany(CatheterRecord::class, 'created_by_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'sent_by_id');
    }
}

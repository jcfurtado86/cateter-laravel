<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuthLog extends Model
{
    use HasUuids;

    public $incrementing = false;
    public $timestamps   = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'user_id', 'email', 'event', 'ip_address', 'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

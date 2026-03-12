<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'full_name', 'record_number', 'birth_date',
        'sex', 'race', 'phone', 'active', 'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'active' => 'boolean',
        ];
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function catheterRecords()
    {
        return $this->hasMany(CatheterRecord::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}

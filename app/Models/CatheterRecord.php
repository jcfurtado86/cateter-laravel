<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CatheterRecord extends Model
{
    use HasUuids;

    protected $table = 'catheter_records';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'patient_id', 'created_by_id', 'had_previous_catheter',
        'insertion_date', 'procedure_type', 'indication', 'caliber',
        'insertion_side', 'passage_type', 'safety_wire',
        'min_days', 'max_days', 'min_removal_date', 'max_removal_date',
        'removed_at', 'removed_by_id',
    ];

    protected function casts(): array
    {
        return [
            'insertion_date' => 'datetime',
            'min_removal_date' => 'datetime',
            'max_removal_date' => 'datetime',
            'removed_at' => 'datetime',
            'had_previous_catheter' => 'boolean',
            'safety_wire' => 'boolean',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function removedBy()
    {
        return $this->belongsTo(User::class, 'removed_by_id');
    }
}

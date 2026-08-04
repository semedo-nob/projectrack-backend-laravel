<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'project_id',
        'title',
        'description',
        'activity_type',
        'occurred_at',
        'hours_spent',
        'performed_by',
        'location',
        'linked_expense_id',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'hours_spent' => 'float',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

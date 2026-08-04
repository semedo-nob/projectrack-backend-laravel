<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'project_id',
        'merchant',
        'amount',
        'date',
        'category',
        'notes',
        'receipt_image',
        'status',
        'quantity_original',
        'unit_original',
        'quantity_base',
        'unit_base',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'amount' => 'float',
            'quantity_original' => 'float',
            'quantity_base' => 'float',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }
}

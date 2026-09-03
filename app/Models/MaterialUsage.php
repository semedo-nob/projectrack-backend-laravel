<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialUsage extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'project_id',
        'inventory_item_id',
        'kind',
        'quantity_base',
        'unit_base',
        'quantity_original',
        'unit_original',
        'notes',
        'expense_id',
        'occurred_at',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_base' => 'float',
            'quantity_original' => 'float',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'expense_id',
        'image_url',
        'ocr_data',
        'confidence',
        'processed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}

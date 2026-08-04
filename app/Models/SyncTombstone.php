<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncTombstone extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'project_id',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }
}

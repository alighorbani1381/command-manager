<?php

namespace Alighorbani\CommandManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtisanCommand extends Model
{
    protected $fillable = [
        'command',
        'signature',
        'chain_id',
        'execution_time',
        'maintenance_mode',
        'status',
        'version',
        'started_at',
        'finished_at',
    ];

    public $timestamps = false;

    public function chain(): BelongsTo
    {
        return $this->belongsTo(ArtisanCommandChain::class, 'id', 'chain_id');
    }
}

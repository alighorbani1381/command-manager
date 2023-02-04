<?php

namespace Alighorbani\CommandManager\Models;

use Illuminate\Database\Eloquent\Model;

class ArtisanCommandChain extends Model
{
    protected $fillable = ['started_at', 'finished_at'];

    public $timestamps = false;
}

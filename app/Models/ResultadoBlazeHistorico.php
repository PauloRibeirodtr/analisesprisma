<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultadoBlazeHistorico extends Model
{
    protected $connection = 'historico';

    protected $table = 'blaze';

    public $timestamps = false;

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'color' => 'integer',
            'roll' => 'integer',
        ];
    }
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prize extends Model
{
    protected $table = 'prizes_am_level_up';

    protected $fillable = [
        'img',
        'name',
        'point',
        'stock',
    ];
}

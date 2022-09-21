<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sending_Parameter extends Model
{
    use HasFactory;

    protected $table = 'sending__parameters';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'icon',
        'properties',
        'is_activated'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Type_Signature extends Model
{
    use HasFactory;
    protected $table = 'type__signatures';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'feature',
        'type'
    ];
}

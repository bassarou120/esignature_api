<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pricing extends Model
{
    use HasFactory;
    protected $table = 'pricings';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration',
        'nbre_simple_signature',
        'nbre_simple_advanced',
        'nbre_advanced_sending',
    ];
}

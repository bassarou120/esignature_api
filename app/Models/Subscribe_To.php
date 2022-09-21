<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscribe_To extends Model
{
    use HasFactory;
    protected $table = 'subscribe__tos';
    public $timestamps = true;

    public function users(){

        return $this->belongsTo(User::class , 'id_user');

    }

    public function pricings(){

        return $this->belongsTo(Pricing::class , 'id_pricing');

    }

    protected $fillable = [
        'id_user',
        'id_pricing',
        'actual_nbre_simple_signature',
        'actual_nbre_simple_advanced',
        'actual_nbre_advanced_sending'
    ];
}

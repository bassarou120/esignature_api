<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signataire extends Model
{
    use HasFactory;

    protected $table = 'signataires';
    public $timestamps = true;

     public function sending(){
        return $this->belongsTo(Sending::class , 'id_sending');
    }

    protected $fillable = [
        'name',
        'email',
        'type',
        'id_sending',
        'widget',
        'signataire_answers'
    ];
}

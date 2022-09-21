<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statut_Sending extends Model
{
    use HasFactory;

    protected $table = 'statut__sendings';
    public $timestamps = true;

    public function sendings(){

        return $this->belongsTo(Sending::class , 'id_sending');

    }

    public function signataires(){

        return $this->belongsTo(Signataire::class , 'id_signataire');

    }

    public function statuts(){

        return $this->belongsTo(Status::class , 'id_statut');

    }
    protected $fillable = [
        'id_sending',
        'id_signataire',
        'id_statut',
    ];
}

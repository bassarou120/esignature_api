<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sending extends Model
{
    use HasFactory;

    protected $table = 'sendings';
    public $timestamps = true;

    public function documents(){
        return $this->belongsTo(Document::class , 'id_document');
    }

    public function type_signature(){
        return $this->belongsTo(Type_Signature::class , 'id_type_signature');
    }

    public function users(){
        return $this->belongsTo(User::class , 'created_by');
    }

    public function statues(){
        return $this->belongsTo(Status::class , 'statut');
    }

    protected $fillable = [
        'id_type_signature',
        'id_document',
        'created_by',
        'nbre_signataire',
        'configuration',
        'objet',
        'message',
        'callback',
        'expiration',
        'remember',
        'register_as_model',
        'is_config',
        'is_registed',
        'statut'
    ];
}

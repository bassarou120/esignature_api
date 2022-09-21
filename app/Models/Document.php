<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;
    protected $table = 'documents';
    public $timestamps = true;

    public function users(){

        return $this->belongsTo(User::class , 'id_user');

    }
    protected $fillable = [
        'title',
        'file',
        'preview',
        'nbre_page',
        'signated_file',
        'is_signed',
        'id_user'
    ];
}

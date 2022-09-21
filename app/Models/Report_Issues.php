<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report_Issues extends Model
{
    use HasFactory;

    protected $table = 'report__issues';
    public $timestamps = true;

    public function users(){

        return $this->belongsTo(User::class , 'id_user');

    }
    protected $fillable = [
        'content',
        'id_user',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;
    protected $table = 'contacts';
    public $timestamps = true;

    public function users()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    protected $fillable = [
        'name',
        'email',
        'activity',
        'id_user'
    ];
}

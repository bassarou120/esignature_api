<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;
    protected $table = 'members';
    public $timestamps = true;

    public function users()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    protected $fillable = [
        'name',
        'email',
        'role',
        'id_user'
    ];
}

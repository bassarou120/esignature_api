<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $table = 'groups';
    public $timestamps = true;

    public function members()
    {
        return $this->hasMany(GroupMember::class,'id_group');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    protected $fillable = [
        'name',
        'nbre_member',
        'id_user'
    ];
}

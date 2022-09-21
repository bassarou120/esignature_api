<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMember extends Model
{

    use HasFactory;
    protected $table = 'group_members';
    public $timestamps = true;

    public function members()
    {
        return $this->belongsTo(Member::class, 'id_member');
    }

    public function groups()
    {
        return $this->belongsTo(Group::class, 'id_group');
    }

    protected $fillable = [
        'id_member',
        'id_group',
        'is_responsible'
    ];
}

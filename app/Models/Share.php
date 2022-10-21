<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Share extends Model
{
    use HasFactory;
    protected $table = 'share';
    public $timestamps = true;

    public function sendings(){

        return $this->belongsTo(Sending::class , 'id_sending');

    }

    public function members(){

        return $this->belongsTo(Member::class , 'id_member');

    }

    public function group(){

        return $this->belongsTo(Group::class , 'id_group');

    }

    protected $fillable = [
        'id_member',
        'id_group',
        'id_sending'
    ];
}

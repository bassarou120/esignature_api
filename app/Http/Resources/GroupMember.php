<?php

namespace App\Http\Resources;

use Carbon\Carbon as c;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupMember extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'groups' =>[
                $this->groups->find($this->id_group)
            ],
            'members' =>[
                $this->members->find($this->id_member)
            ],
            'created_at' => c::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('d/m/Y H:i:s') ,
            'updated_at' => c::createFromFormat('Y-m-d H:i:s', $this->updated_at)->format('d/m/Y H:i:s') ,
        ];
    }
}

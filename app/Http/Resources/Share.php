<?php

namespace App\Http\Resources;

use Carbon\Carbon as c;
use Illuminate\Http\Resources\Json\JsonResource;

class Share extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
       // return parent::toArray($request);
        return [
            'id' => $this->id,
            'sending' =>[
                $this->sendings->find($this->id_sending)
            ],
            'member' =>$this->id_member==null ? null : [
                $this->members->find($this->id_member)
            ],
            'group' =>$this->id_group==null ? null : [
                $this->group->find($this->id_group)
            ],
            'created_at' => c::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('d/m/Y H:i:s') ,
            'updated_at' => c::createFromFormat('Y-m-d H:i:s', $this->updated_at)->format('d/m/Y H:i:s') ,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Carbon\Carbon as c;
use Illuminate\Http\Resources\Json\JsonResource;

class Signataire extends JsonResource
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
            'sending' =>[
                $this->sending->find($this->id_sending)
            ],
            'email' => $this->email,
            'name' => $this->name,
            'type' => $this->type,
            'widget' => $this->widget,
            'signataire_answer' => $this->signataire_answer,
            'created_at' => c::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('d/m/Y H:i:s') ,
            'updated_at' => c::createFromFormat('Y-m-d H:i:s', $this->updated_at)->format('d/m/Y H:i:s') ,
        ];
    }
}

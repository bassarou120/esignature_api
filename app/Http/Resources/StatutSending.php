<?php

namespace App\Http\Resources;

use Carbon\Carbon as c;
use Illuminate\Http\Resources\Json\JsonResource;

class StatutSending extends JsonResource
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
            'sendings' =>[
                $this->sendings->find($this->id_sending)
            ],
            'signataires' =>[
                $this->signataires->find($this->id_signataire)
            ],
            'statuts' =>[
                $this->statuts->find($this->id_statut)
            ],
            'created_at' => c::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('d/m/Y H:i:s') ,
            'updated_at' => c::createFromFormat('Y-m-d H:i:s', $this->updated_at)->format('d/m/Y H:i:s') ,
        ];
    }
}

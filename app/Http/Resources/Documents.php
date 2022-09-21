<?php

namespace App\Http\Resources;

use Carbon\Carbon as c;
use Illuminate\Http\Resources\Json\JsonResource;

class Documents extends JsonResource
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
            'user' =>[
                $this->users->find($this->id_user)
            ],
            'title' => $this->name,
            'preview' => $this->preview,
            'nbre_page' => $this->nbre_page,
            'file' => $this->file,
            'is_signed' => $this->is_signed,
            'signated_file' => $this->signated_file,
            'created_at' => c::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('d/m/Y H:i:s') ,
            'updated_at' => c::createFromFormat('Y-m-d H:i:s', $this->updated_at)->format('d/m/Y H:i:s') ,
        ];
    }
}

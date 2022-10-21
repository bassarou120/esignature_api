<?php

namespace App\Http\Resources;

use Carbon\Carbon as c;
use Illuminate\Http\Resources\Json\JsonResource;

class Sending extends JsonResource
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
             'created_by' =>[
                 $this->users->find($this->created_by)
             ],
             'type_signature' =>[
                 $this->type_signature->find($this->id_type_signature)
             ],
             'document' =>[
                 $this->documents->find($this->id_document)
             ],
             'configuration' => $this->configuration,
             'nbre_signataire' => $this->nbre_signataire,
             'objet' => $this->objet,
             'message' => $this->message,
             'callback' => $this->callback,
             'expiration' => $this->expiration,
             'remember' => $this->remember,
             'register_as_model' => $this->register_as_model,
             'is_config' => $this->is_config,
             'is_registed'=>$this->is_registed,
             'police'=>$this->police,
             'response'=>$this->response,
             'statut' =>[
                 $this->statues->find($this->statut)
             ],
             'created_at' => c::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('d/m/Y H:i:s') ,
             'updated_at' => c::createFromFormat('Y-m-d H:i:s', $this->updated_at)->format('d/m/Y H:i:s') ,
         ];
    }
}

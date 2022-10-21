<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Resources\Member;
use App\Jobs\ShareDocument;
use App\Models\Document;
use App\Models\Models;
use App\Models\Sending;
use App\Models\Share;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Resources\Sending as SendingResource;
use App\Http\Resources\Share as ShareResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class ModelsController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $model = Sending::where('register_as_model',1)
            ->where('is_registed',1)
            ->orderBy('id','DESC')
            ->get();
        return $this->sendResponse(SendingResource::collection($model), 'Model retrieved successfully.');

    }

    public function getModelByUser($id_user){
        $model = Sending::where('created_by',$id_user)->where('register_as_model',1)->where('is_registed',1)->orderBy('sendings.id','DESC')->get();
        return $this->sendResponse(SendingResource::collection($model), 'Model retrieved successfully.');
    }

    public function getModelByUserAndSignatureType($id_user,$id_type_signature){
        $model = Sending::where('created_by',$id_user)
                          ->where('register_as_model',1)
                          ->where('is_registed',1)
                          ->where('id_type_signature',$id_type_signature)
                          ->orderBy('sendings.id','DESC')
                          ->get();
        return $this->sendResponse(SendingResource::collection($model), 'Model retrieved successfully.');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Models  $models
     * @return \Illuminate\Http\Response
     */
    public function show(Models $models)
    {
        //
    }

    public function shareModel(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'id_sending' => 'required|numeric',
            'id_group' => 'nullable|numeric',
            'id_member' => 'nullable|numeric'
        ]);

        $fieldNames = array(
            'id_sending' => 'Envois',
            'id_group' => 'Group',
            'id_member' => 'Membre'
        );

        $validator->setAttributeNames($fieldNames);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(),400);
        }

        $sending = Sending::find($request->id_sending);
        $document = Document::find($sending->id_document);

        if($request->id_member != null){
            $member = Member::find($request->id_member);
            if(is_null($member)){
                return $this->sendError('Unknow member', [],400);
            }
            $share = Share::create( $input);

                $notif = new ShareDocument(
                    [
                        'email' => $member->email,
                        'sending_auth'=>Auth::user()->name,
                        'doc_title'=>$document->title,
                        'name'=>$member->name,
                    ]
                );
                $this->dispatch($notif);
            return $this->sendResponse(new ShareResource($share), 'shared successfully.');

        }

        if($request->id_group != null){
            $members = GroupMemberController::join('members', 'group_members.id_member', '=', 'members.id')
                ->where('id_group',$request->id_group )
                ->get(['members.*']);

            if(!is_null($members) && sizeof($members) !=0){
                foreach ($members as $m){
                    $share = Share::create( [
                        'id_sending'=>$request->id_sending,
                        'id_member'=>$m->id,
                    ]);
                    $notif = new ShareDocument(
                        [
                            'email' => $m->email,
                            'sending_auth'=>Auth::user()->name,
                            'doc_title'=>$document->title,
                            'name'=>$m->name,
                        ]
                    );
                    $this->dispatch($notif);
                }

            }

            return $this->sendResponse([], 'shared successfully.');

        }


    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Models  $models
     * @return \Illuminate\Http\Response
     */
    public function edit(Models $models)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Models  $models
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Models $models)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Models  $models
     * @return \Illuminate\Http\Response
     */
    public function destroy(Models $models)
    {
        //
    }
}

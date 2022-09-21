<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Resources\Group as GroupResource;
use App\Http\Resources\GroupMember as GroupMemberResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GroupController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $group = Group::orderBy('id','DESC')->get();
        return $this->sendResponse(GroupResource::collection($group), 'Group retrieved successfully.');

    }

    public function getGroupByUser($id_user){
        $group = Group::where('id_user',$id_user)->orderBy('id','DESC')->get();
        return $this->sendResponse(GroupResource::collection($group), 'Group retrieved successfully.');
    }


    public function getMembersOfGroup($id_group){
        $group = GroupMember::where('id_group',$id_group)->orderBy('id','DESC')->get();
        return $this->sendResponse(GroupMemberResource::collection($group), 'Members retrieved successfully.');
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
        $input = $request->all();

        $validator = Validator::make($input, [
            'name' => 'required|string'
        ]);

        $fieldNames = array(
            'name' => 'Nom'
        );

        $validator->setAttributeNames($fieldNames);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(),400);
        }

        $input['id_user']=Auth::id();

        try {
            $group = Group::updateOrCreate(
                ['id' => $request->id],$input
            );

            $count=0;
            $groupMembers = array();
            $mbre = is_null($request->members)? [] : json_decode( $request->members) ;
            $res = is_null($request->responsables)? [] : json_decode( $request->responsables) ;
            if(sizeof($mbre)!=0){
               foreach ($mbre as $m){
                   array_push($groupMembers,[
                       'id_group'=>$group->id,
                       'id_member'=>$m->value,
                       'is_responsible'=>0,
                   ]);
                   $count++;
               }
            }

            if(sizeof($res)!=0){
                foreach ($res as $r){
                    array_push($groupMembers,[
                        'id_group'=>$group->id,
                        'id_member'=>$r->value,
                        'is_responsible'=>1,
                    ]);
                    $count++;
                }
            }

            $group->nbre_member = $count ;
            GroupMember::insert($groupMembers);
            $group->save();

        }catch (QueryException $e){
            return $this->sendError('Database error', $validator->errors(),500);

        }

        // $contact = Contact::create($input);

        if($request->id){
            return $this->sendResponse(new GroupResource($group), 'Group updated successfully.');
        }
        else{
            return $this->sendResponse(new GroupResource($group), 'Group created successfully.');

        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $group = Group::find($id);

        if (is_null($id)) {
            return $this->sendError('Group not found.');
        }

        return $this->sendResponse(new GroupResource($group), 'Group retrieved successfully.');

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function edit(Group $group)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Group $group)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function destroy(Group $group)
    {
        if(!is_null($group)){
            $group->delete();
            return $this->sendResponse([], 'Group deleted successfully.');
        }
        else{
            return $this->sendError('Group not found',[],404);
        }
    }

    public function multidelete(Request $request)
    {
        $list = $request->groups;
        $ser = Group::whereIn('groups.id', $list)->delete();
        return $this->sendResponse([], 'Groups deleted successfully.');
    }
}

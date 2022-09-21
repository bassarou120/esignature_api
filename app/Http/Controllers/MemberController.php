<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\API\BaseController;
use App\Models\Group;
use App\Models\Member;
use App\Http\Resources\Member as MemberResource;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $members = Member::orderBy('id','DESC')->get();

        if ($request->ajax()) {
            $rows =MemberResource::collection($members);
            return datatables()::of($rows)
                ->addIndexColumn()
                ->addColumn('action', function ($row)  {
                    $btn = ' <a href="javascript:;" class="mx-3 edit" data-bs-toggle="tooltip" data-bs-original-title="Edit services" data-id="' . $row['id'] . '">
                    <i class="material-icons text-secondary position-relative text-lg">drive_file_rename_outline</i>
                     </a>';
                    $btn = $btn . '<a href="javascript:;" class="delete" data-bs-toggle="tooltip" data-bs-original-title="Delete services" data-id="' . $row['id'] . '">
                        <i class="material-icons text-secondary position-relative text-lg text-danger">delete</i>
                        </a>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return $this->sendResponse(MemberResource::collection($members), 'Members retrieved successfully.');

    }

    public function getStat(){
        $id_user = Auth::id();
        $members = Member::where('id_user',$id_user)->get()->count();
        $groups = Group::where('id_user',$id_user)->get()->count();
       return response()->json([
           'success'=>true,
           'message'=>'Statistique get succesfully',
           'data'=>[
               'members'=>$members,
               'groups'=>$groups,
           ]
       ]);
    }

    public function getMemberByUser(Request $request, $id_user)
    {
        $members = Member::where('id_user',$id_user)->orderBy('id','DESC')->get();
        if ($request->ajax()) {
            $rows =MemberResource::collection($members);
            return datatables()::of($rows)
                ->addIndexColumn()
                ->addColumn('action', function ($row)  {
                    $btn = ' <a href="javascript:;" class="mx-3 edit" data-bs-toggle="tooltip" data-bs-original-title="Edit services" data-id="' . $row['id'] . '">
                    <i class="fa-solid fa-pen-to-square"></i>
                     </a>';
                    $btn = $btn . '<a href="javascript:;" class="delete" data-bs-toggle="tooltip" data-bs-original-title="Delete services" data-id="' . $row['id'] . '">
                        <i class="position-relative text-lg text-danger fa fa-trash"></i>
                        </a>';

                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return $this->sendResponse(MemberResource::collection($members), 'Members retrieved successfully.');

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
            'name' => 'required|string',
            'email' => 'required|email',
            'role' => 'nullable|string'
        ]);

        $fieldNames = array(
            'name' => 'Nom',
            'email' => 'Email',
            'role' => 'Role'
        );

        $validator->setAttributeNames($fieldNames);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(),400);
        }

        $input['id_user']=Auth::id();

        try {
            $member = Member::updateOrCreate(
                ['id' => $request->id],$input
            );
        }catch (QueryException $e){
            return $this->sendError('Database error', $validator->errors(),500);

        }

        return $this->sendResponse(new MemberResource($member), 'Member created successfully.');

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Member  $member
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $member = Member::find($id);

        if (is_null($id)) {
            return $this->sendError('Member not found.');
        }

        return $this->sendResponse(new MemberResource($member), 'Member retrieved successfully.');

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Member  $member
     * @return \Illuminate\Http\Response
     */
    public function edit(Member $member)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Member  $member
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Member $member)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Member  $member
     * @return \Illuminate\Http\Response
     */
    public function destroy(Member $member)
    {
        if(!is_null($member)){
            $member->delete();
            return $this->sendResponse([], 'Member deleted successfully.');
        }
        else{
            return $this->sendError('Member not found',[],404);
        }
    }

    public function multidelete(Request $request)
    {
        $list = $request->members;
        $ser = Member::whereIn('members.id', $list)->delete();
        return $this->sendResponse([], 'Member deleted successfully.');
    }
}

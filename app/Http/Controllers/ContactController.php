<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\API\BaseController;
use App\Models\Contact;
use App\Http\Resources\Contact as ContactResource;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ContactController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $contact = Contact::orderBy('id','DESC')->get();

        if ($request->ajax()) {
            $rows =ContactResource::collection($contact);
            return datatables()::of($rows)
                ->addIndexColumn()
                ->addColumn('action', function ($row)  {
//                    $btn = '<a href="javascript:;" class="show view" data-bs-toggle="tooltip" data-bs-original-title="Preview services" data-id="' . $row['id'] . '">
//                    <i class="material-icons text-secondary position-relative text-lg">visibility</i>
//                    </a>&nbsp;';
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

        return $this->sendResponse(ContactResource::collection($contact), 'Contact retrieved successfully.');

    }

    public function getContactByUser(Request $request, $id_user)
    {
        $contact = Contact::where('id_user',$id_user)->orderBy('id','DESC')->get();
        if ($request->ajax()) {
            $rows =ContactResource::collection($contact);
            return datatables()::of($rows)
                ->addIndexColumn()
                ->addColumn('action', function ($row)  {
//                    $btn = '<a href="javascript:;" class="show view" data-bs-toggle="tooltip" data-bs-original-title="Preview services" data-id="' . $row['id'] . '">
//                    <i class=" fa-solid fa-info-circle"></i>
//                    </a>&nbsp;';
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
        return $this->sendResponse(ContactResource::collection($contact), 'Contact retrieved successfully.');

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
            'activity' => 'nullable|string'
        ]);

        $fieldNames = array(
            'name' => 'Nom',
            'email' => 'Email',
            'activity' => 'Activité'
        );

        $validator->setAttributeNames($fieldNames);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(),400);
        }

        $input['id_user']=Auth::id();

        try {
            $contact = Contact::updateOrCreate(
                ['id' => $request->id],$input
            );
        }catch (QueryException $e){
            return $this->sendError('Database error', $validator->errors(),500);

        }

       // $contact = Contact::create($input);

        return $this->sendResponse(new ContactResource($contact), 'Contact created successfully.');

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $contact = Contact::find($id);

        if (is_null($id)) {
            return $this->sendError('Contact not found.');
        }

        return $this->sendResponse(new ContactResource($contact), 'Contact retrieved successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Contact $contact)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact)
    {
        if(!is_null($contact)){
            $contact->delete();
            return $this->sendResponse([], 'Contact deleted successfully.');
        }
        else{
            return $this->sendError('Contact not found',[],404);
        }

    }

    public function multidelete(Request $request)
    {
        $list = $request->contacts;
        $ser = Contact::whereIn('contacts.id', $list)->delete();
        return $this->sendResponse([], 'Contacts deleted successfully.');
    }
}

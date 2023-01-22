<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Jobs\NotifyDocSignedToDocAuthor;
use App\Jobs\SendCcMailJob;
use App\Jobs\SendSignataireMailJob;
use App\Jobs\SendValidatorMailJob;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Member;
use App\Models\Sending;
use App\Models\Signataire;
use App\Models\Statut_Sending;
use Carbon\Carbon;
use Carbon\Carbon as c;
use FPDF;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Sending as SendingResource;
use App\Http\Resources\Signataire as SignataireResource;
use App\Http\Resources\StatutSending as StatutSendingResource;
use Lcobucci\JWT\Signer;
use Mpdf\Mpdf;
use PHPMailer\PHPMailer\PHPMailer;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Sign;
use PhpParser\Comment\Doc;
use Psy\Util\Json;
use setasign\Fpdi\Fpdi;
use Spatie\PdfToImage\Pdf as Spatie;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SendingController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sending = Sending::orderBy('id', 'DESC')->get();

        if ($request->ajax()) {
            $rows = SendingResource::collection($sending);
            return datatables()::of($rows)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item show" href="' . route('sending.detail', $row['id']) . '" data-id="' . $row['id'] . '"><i
                                        class="fa fa-eye me-1"></i> Voir</a>
                                        <a class="dropdown-item edit" href="javascript:;" data-id="' . $row['id'] . '"><i
                                        class="bx bx-edit-alt me-1"></i> Edit</a>
                                    <a class="dropdown-item delete" href="javascript:;" data-id="' . $row['id'] . '"><i
                                        class="bx bx-trash me-1"></i> Delete</a>
                                </div>
                            </div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return $this->sendResponse(SendingResource::collection($sending), 'Sending retrieved successfully.');

    }

    public function getSendingByUser(Request $request, $id_user, $type_signature = null)
    {
        switch ($request->statut) {
            case 'pending':
                $statut =EN_COURS;
                break;
            case 'ended':
                $statut =FINIR;
                break;
            case 'archived':
                $statut =ARCHIVER;
                break;
            default:
                    $statut =null;
        }

        if($statut!=null){
            $sending = Sending::when($type_signature !==null,function($query) use ($statut, $type_signature) {
                $query->where('id_type_signature', $type_signature);
                $query->where('statut', $statut);
            },function($query) use ($statut) {
                $query->where('statut', $statut);
            })->where('created_by', $id_user)
                ->where('register_as_model', 0)
                ->where('is_config', 1)
                ->orderBy('sendings.id', 'DESC');
        }else{

            if($type_signature != null){
                $sending = Sending::when($type_signature !==null,function($query) use ($statut, $type_signature) {
                    $query->where('id_type_signature', $type_signature);
                },function($query) use ($statut) {})
                    ->join('statuses', 'sendings.statut', '=', 'statuses.id')
                    ->where('sendings.statut','<>', ARCHIVER)
                    ->where('created_by', $id_user)
                    ->where('register_as_model', 0)
                    ->where('is_config', 1)
                    ->orderBy('sendings.id', 'DESC');
            }
            else{
                $sending = Sending::join('statuses', 'sendings.statut', '=', 'statuses.id')
                    ->where('sendings.statut','<>', ARCHIVER)
                    ->where('created_by', $id_user)
                    ->where('register_as_model', 0)
                    ->where('is_config', 1)
                    ->orderBy('sendings.id', 'DESC');
            }
        }

        if($request->search_val){
            $sending=$sending->join('documents', 'sendings.id_document', '=', 'documents.id')
                ->where('documents.title', 'LIKE', '%'.$request->search_val.'%');
        }

        $sending=$sending->get(['sendings.*']);

        /*if ($request->ajax()) {
            $rows = SendingResource::collection($sending);
            return datatables()::of($rows)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                        data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">
                                 <a class="dropdown-item show" href="' . route('sending.detail', $row['id']) . '" data-id="' . $row['id'] . '"><i
                                        class="fa fa-eye me-1"></i> Voir</a>
                                 <a class="dropdown-item archive" href="javascript:;" data-id="' . $row['id'] . '"><i
                                        class="bx bx-archive me-1"></i> Archiver</a>
                                 <a class="dropdown-item delete text-danger" href="javascript:;" data-id="' . $row['id'] . '"><i
                                        class="bx bx-trash me-1"></i> Delete</a>
                                </div>
                            </div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }*/

        return $this->sendResponse(SendingResource::collection($sending), 'Sending retrieved successfully.');

    }

    public function getTopPendingSending(Request $request, $id_user){
        $sending = Sending::where('created_by', $id_user)
            ->where('statut', EN_COURS)
            ->where('register_as_model', 0)
            ->where('is_config', 1)
            ->limit($request->rows)
            ->get();
        return $this->sendResponse(SendingResource::collection($sending), 'Sending retrieved successfully.');

    }

    private function custom_copy($src, $dst)
    {
        // open the source directory
        $dir = opendir($src);

        // Make the destination directory if not exist
        @mkdir($dst);

        // Loop through the files in source directory
        while ($file = readdir($dir)) {

            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {

                    // Recursively calling custom copy function
                    // for sub directory
                   $this->custom_copy($src . '/' . $file, $dst . '/' . $file);

                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'id_type_signature' => 'required|numeric',
            'model_id' => "nullable|numeric",
            'document' => "required_if:model_id,==,0|nullable|mimes:pdf|max:10000",
            'register_as_model' => "required|numeric",
        ]);

        $fieldNames = array(
            'id_type_signature' => 'Type de signature',
            'document' => 'Document'
        );

        $validator->setAttributeNames($fieldNames);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        if ($request->model_id != 0) {
            $send = Sending::find($request->model_id);
            $newSending = $send->replicate();
            $newSending->created_at = Carbon::now();
            $newSending->updated_at = Carbon::now();
            $newSending->register_as_model=0;

            $document = Document::find($send->id_document);
            $folder = explode('/', $document->preview)[0];
            $this->custom_copy(public_path('/previews/'.$folder),public_path('/previews/'.time()));

            $newDocument = $document->replicate();
            $newDocument->created_at = Carbon::now();
            $newDocument->updated_at = Carbon::now();

            $newDocument->save();
            $newSending->id_document = $newDocument->id;
            $newSending->save();
            return $this->sendResponse(new SendingResource($newSending), 'Sending created successfully.');

        }else{
            if ($request->hasFile('document')) {
                $doc = $request->file('document');
                $title = pathinfo($doc->getClientOriginalName(), PATHINFO_FILENAME);
                //$imageName1 = $doc->getClientOriginalName();
                $time =time();
                $imageName1 = $time.'.pdf';

//                if(file_exists(public_path('documents').'/'.$imageName1)){
//                    $imageName1 = $title.'_'.time().'.pdf';
//                    $cp_name = $title.'_'.time().'_copy.pdf';
//                }
//                else{
//                    $cp_name= $title.'_copy.pdf';
//                }
                $cp_name= $time.'_copy.pdf';
                $doc->move(public_path('documents'), $imageName1);
                copy(public_path('documents').'/'.$imageName1,public_path('documents').'/'.$cp_name);
               // $doc->move(public_path('documents'), $cp_name);

                //$pdf = new Spatie\PdfToImage\Pdf();
                $pdf = new Spatie(public_path('documents') . '/' . $imageName1);
                $nbre_page = $pdf->getNumberOfPages();
                $preview_name = $title . time();
                $t = $time;
                for ($i = 1; $i <= $nbre_page; $i++) {
                    $pdf->setPage($i);
                    Storage::makeDirectory(public_path('/previews/' . $t));
                    $p = public_path('/previews/' . $t);
                    if (!is_dir($p)) {
                        mkdir($p);
                    }
                    $pdf->saveImage(public_path('previews/' . $t . '/' . $i . '.jpeg'));
                }

                try {
                    $document = Document::create([
                        'title' => $title,
                        'file' => $imageName1,
                        'preview' => $t . '/1.jpeg',
                        'is_signed' => 0,
                        'nbre_page' => $nbre_page,
                        'id_user' => Auth::id()
                    ]);

                    $input['created_by'] = Auth::id();
                    $input['id_document'] = $document->id;
                    $input['is_registed'] = 0;
                    $input['register_as_model'] = $request->register_as_model;
                    $input['statut'] = EN_COURS;

                    try {
                        $send = Sending::create($input);
                    } catch (QueryException $e) {
                        return $this->sendError('Database error', $validator->errors(), 500);
                    }

                } catch (QueryException $e) {
                    return $this->sendError('Database error while saving document', $validator->errors(), 500);
                }
                return $this->sendResponse(new SendingResource($send), 'Sending created successfully.');
            } else {
                return $this->sendError('No file', [], 400);
            }
        }


    }

    public function saveModelRegistration(Sending $sending, Request $request)
    {
        $sending->is_registed = 1;
        $doc_info = Document::find($sending->id_document);
        if ($request->title != null) {
            $doc_info->title = $request->title;
            $doc_info->save();
        }
        $sending->save();
        return $this->sendResponse(new SendingResource($sending), 'Model created successfully.');

    }

    public function cancelModelRegistration(Sending $sending)
    {
        $sending->delete();
        return $this->sendResponse([], 'Model registration deleted successfully.');

    }

    public function archiveSending(Request $request){
        try{
            $sending = Sending::find($request->id);
            if(!is_null($sending)){
                $sending->statut= ARCHIVER;
                $sending->save();
                return $this->sendResponse(new SendingResource($sending), 'Sending archived successfully.');
            }
        }  catch (QueryException $e) {
            return $this->sendError('Database error while retrieving document', [], 500);
        }

    }
    /**
     * Display the specified resource.
     *
     * @param \App\Models\Sending $sending
     * @return \Illuminate\Http\Response
     */
    public function show(Sending $sending)
    {
        return $this->sendResponse(new SendingResource($sending), 'Sending retrieved successfully.');
    }

    public function getSendingInfoPublic($id_sendind){
        $sending = Sending::find($id_sendind);
        return $this->sendResponse(new SendingResource($sending), 'Sending retrieved successfully.');
    }

    public function sending_signataire_statut($id)
    {
       /* $signataire_statut = Statut_Sending::join('signataires', 'statut__sendings.id_signataire', '=', 'signataires.id')
            ->join('statuses', 'statut__sendings.id_statut', '=', 'statuses.id')
            ->where('statut__sendings.id_sending', $id)
            ->where('signataires.type', 'Signataire')
            ->whereRaw('statut__sendings.id IN (SELECT MAX(statut__sendings.id ) FROM statut__sendings GROUP BY statut__sendings.id_signataire)')
            ->get(['signataires.id','signataires.name','signataires.email','statuses.name as statut','statut__sendings.created_at']);*/

        $available_statut =['ENVOYER','EMAIL_REMIT','OPENED_EMAIL_MESSAGE','OUVRIR','SIGNER'];
        $signataire_statut = Statut_Sending::join('signataires', 'statut__sendings.id_signataire', '=', 'signataires.id')
            ->join('statuses', 'statut__sendings.id_statut', '=', 'statuses.id')
            ->where('statut__sendings.id_sending', $id)
            ->where('signataires.type', 'Signataire')
            ->whereIn('statuses.name',$available_statut )
            ->get(['signataires.id','signataires.name','signataires.email','statuses.name as statut','statut__sendings.created_at']);


        if(!is_null($signataire_statut)){
            $statut_signataire=[];
            foreach ($signataire_statut as $s){
                $nom = $s->email.'|'.$s->name;

                if(!empty(array_keys($statut_signataire)) && in_array($nom,array_keys($statut_signataire))){
                       $old=$statut_signataire[$nom];
                       array_push($old,[
                           'id'=>$s->id,
                           'statut'=>$s->statut,
                           'date'=>c::createFromFormat('Y-m-d H:i:s', $s->created_at)->format('d/m/Y H:i:s')
                       ]);
                    $statut_signataire[$nom]=$old;
//                       $new=[
//                           $old,[
//                               'id'=>$s->id,
//                               'statut'=>$s->statut,
//                               'date'=>c::createFromFormat('Y-m-d H:i:s', $s->created_at)->format('d/m/Y H:i:s')
//                           ]
//                       ];
//                       $statut_signataire[$nom]=$new;
                }
                   else{
                       $statut_signataire[$nom]=[
                           [
                           'id'=>$s->id,
                           'statut'=>$s->statut,
                           'date'=>c::createFromFormat('Y-m-d H:i:s', $s->created_at)->format('d/m/Y H:i:s')
                           ]
                       ];
                   }
            }
        }

        return response()->json([
            'message'=>'Statut retrieve successfully',
            'data'=>$statut_signataire,
            'success'=>true
        ]);
    }

    public function sending_validataire_statut($id)
    {
        $signataire_statut = Statut_Sending::join('signataires', 'statut__sendings.id_signataire', '=', 'signataires.id')
            ->join('statuses', 'statut__sendings.id_statut', '=', 'statuses.id')
            ->where('statut__sendings.id_sending', $id)
            ->where('signataires.type', 'Validataire')
            ->whereRaw('statut__sendings.id IN (SELECT MAX(statut__sendings.id ) FROM statut__sendings GROUP BY statut__sendings.id_signataire)')
            ->get();

        return $this->sendResponse(StatutSendingResource::collection($signataire_statut), 'Sending validataire statues retrieved successfully.');
    }

    public function search($value)
    {
        $signataire_statut = Statut_Sending::join('signataires', 'statut__sendings.id_signataire', '=', 'signataires.id')
            ->join('statuses', 'statut__sendings.id_statut', '=', 'statuses.id')
            ->where('statut__sendings.id_sending', $value)
            ->where('signataires.type', 'Validataire')
            ->whereRaw('statut__sendings.id IN (SELECT MAX(statut__sendings.id ) FROM statut__sendings GROUP BY statut__sendings.id_signataire)')
            ->get();

        return $this->sendResponse(StatutSendingResource::collection($signataire_statut), 'Sending validataire statues retrieved successfully.');
    }

    public function sending_cc($id)
    {
        $cc = Signataire::where('id_sending', $id)->where('type', 'CC')->get();
        return $this->sendResponse(SignataireResource::collection($cc), 'Sending cc retrieved successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Sending $sending
     * @return \Illuminate\Http\Response
     */
    public function edit(Sending $sending)
    {
        //
    }

    public function registerSendingConfig(Request $request)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Sending $sending
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sending $sending)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'id' => 'required|numeric',
            'signataire' => 'required|string',
            'objet' => 'nullable|string',
            'message' => 'nullable|string',
            'expiration' => 'required|string',
            'rappel' => 'required|string',
            'cc' => "nullable|string",
            'title' => "nullable|string"
        ]);

        $fieldNames = array(
            'signataire' => 'Signataires',
            'objet' => 'Objet',
            'message' => 'Message',
            'expiration' => 'Expiration',
            'rappel' => 'Rappel',
            'cc' => "Copie",
            'title' => "Titre"
        );

        $validator->setAttributeNames($fieldNames);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        // update sending with objet and message
        $sending = Sending::find($request->id);
        $sending->message = $request->message;
        $sending->objet = $request->objet;

        if(!in_array($request->rappel,['Quotidien','Hebdomadaire','Aucun','Personnalisé'])){
            $sending->callback = 'Personnalisé';
            $sending->callback_nbre = $request->rappel;
        }
        else{
            $sending->callback = $request->rappel;
        }

        if(!in_array($request->expiration,['Aucun','Personnalisé'])){
            $sending->expiration = 'Personnalisé';
            $sending->expiration_nbre = $request->expiration;
        }
        else{
            $sending->expiration = $request->expiration;
        }

        $sending->is_registed = 1;
        $sending->last_callback_date = date('Y-m-d H:i:s');
        //register signataire
        $signataire = json_decode($request->signataire);

        $signataires_array = [];
        $signataire_only_array = [];
        $validataire_only_array = [];
        $emails = [];
        $count_signataire = 0;
        $id_user = Auth::id();

        foreach ($signataire as $s) {
            if (!in_array($s->email, $emails)) {
                $emails[] = $s->email;
                if ($s->type == 'Signataire') {
                    $count_signataire++;
                    array_push($signataire_only_array, [
                        'name' => $s->name,
                        'email' => $s->email,
                        'id_user' => $id_user,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    array_push($validataire_only_array, [
                        'name' => $s->name,
                        'email' => $s->email
                    ]);
                }
                array_push($signataires_array, [
                    'name' => $s->name,
                    'email' => $s->email,
                    'type' => $s->type,
                    'id_sending' => $request->id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $save_signataire = [];
       // return response()->json($signataires_array);

        foreach ($signataires_array as $sa) {

            $si=  Signataire::where('type', 'Signataire')
                ->where('name', $sa['name'])
                ->where('id_sending', $request->id)
                ->first();

            array_push($save_signataire, [
                'id' => $si->id,
                'name' => $sa['name'],
                'email' => $sa['email'],
                'type' => $sa['type'],
            ]);
            $si->email = $sa['email'];
            $si->save();
        }
        // save to contact table

        try {
            $save_member = Contact::insert($signataire_only_array);
        }catch (QueryException $e){}

        // register persons who must receive doc copy
        $cc_persons = json_decode($request->cc);
        $cc_persons_array = [];

        foreach ($cc_persons as $c) {
            if (!in_array($c->email, $emails)) {
                $emails[] = $c->email;
                array_push($cc_persons_array, [
                    'name' => $s->name,
                    'email' => $s->email,
                    'type' => 'CC',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }


        $doc_id = Sending::find($request->id);
        $doc_info = Document::find($doc_id->id_document);
        if ($request->title != null) {
            $doc_info->title = $request->title;
            $doc_info->save();
        }

        foreach ($save_signataire as $s) {
            if ($s['type'] == 'Signataire') {

                $emailSignataire = new SendSignataireMailJob(
                    [
                        'id_sending' => $request->id,
                        'id_signataire' => $s['id'],
                        'email' => $s['email'],
                        'subject' => $request->objet == null ? 'Signature requise' : $request->objet,
                        'message' => $request->message == null ? '' : $request->message,
                        'view' => 'signataire_mail_view',
                        'mail_detail' => [
                            'name' => $s['name'],
                            'doc_title' => $doc_info->title,
                            'sending_auth' => Auth::user()->name,
                            'sending_expiration' => $doc_id->expiration !='Aucun' ? $doc_id->expiration : null,
                            'preview' => $doc_info->preview,
                        ]
                    ]

                );
                $this->dispatch($emailSignataire);
            }
            /*else {
                $emailValidataire = new SendValidatorMailJob(
                    [
                        'id_sending' => $request->id,
                        'id_validataire' => $s['id'],
                        'email' => $s['email'],
                        'subject' => $request->objet == null ? 'Validation requise' : $request->objet,
                        'message' => $request->message == null ? '' : $request->message,
                        'view' => 'validataire_mail_view',
                        'mail_detail' => [
                            'name' => $s['name'],
                            'doc_title' => $doc_info->title,
                            'sending_auth' => Auth::user()->name,
                            'sending_expiration' => $doc_id->expiration,
                            'preview' => $doc_info->preview,
                        ]
                    ]
                );
                $this->dispatch($emailValidataire);
            }*/
        }

        $sending->nbre_signataire = $count_signataire;
        $sending->is_config = 1;
        try {
            $sending->save();

        } catch (QueryException $ex) {
            return $this->sendError('Error while updating data', []);
        }
        return $this->sendResponse(new SendingResource($sending), 'Sending updated successfully.');

    }

    public function sendModelToSignataire(Request $request){
        $input = $request->all();

        $validator = Validator::make($input, [
            'id' => 'required|numeric',
            'signataire' => 'required|string',
            'objet' => 'nullable|string',
            'message' => 'nullable|string',
            'expiration' => 'required|string',
            'rappel' => 'required|string',
        ]);

        $fieldNames = array(
            'signataire' => 'Signataires',
            'objet' => 'Objet',
            'message' => 'Message',
            'expiration' => 'Expiration',
            'rappel' => 'Rappel'
        );

        $validator->setAttributeNames($fieldNames);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        $s = Sending::find($request->id);
        $sending = $s->replicate();
        $sending->created_at = Carbon::now();
        $sending->updated_at = Carbon::now();

        $document = Document::find($sending->id_document);

        $newDocument = $document->replicate();
        $newTitle = $document->title;
        $newDocument->title = $newTitle;

        $newDocument->created_at = Carbon::now();
        $newDocument->updated_at = Carbon::now();

        $preview_dir = explode('/',$newDocument->preview)[0] ;
        $time = time();
        File::copyDirectory( public_path('previews/'.$preview_dir), public_path('previews/'.$time));
        File::copy(public_path('documents/').$newDocument->file, public_path('documents/').$time.'.pdf');

        $newDocument->file = $time.'.pdf';
        $newDocument->preview = $time.'/1.jpeg';
        $newDocument->save();
        $sending->id_document = $newDocument->id;
        $sending->save();

        // update sending with objet and message
        $sending->message = $request->message;
        $sending->objet = $request->objet;

        if(!in_array($request->rappel,['Quotidien','Hebdomadaire','Aucun','Personnalisé'])){
            $sending->callback = 'Personnalisé';
            $sending->callback_nbre = $request->rappel;
        }
        else{
            $sending->callback = $request->rappel;
        }

        if(!in_array($request->expiration,['Aucun','Personnalisé'])){
            $sending->expiration = 'Personnalisé';
            $sending->expiration_nbre = $request->expiration;
        }
        else{
            $sending->expiration = $request->expiration;
        }

        $sending->is_registed = 1;
        $sending->register_as_model = 0;
        $sending->is_config = 1;
        $sending->last_callback_date = date('Y-m-d H:i:s');

        //register signataire

        $signataire = json_decode($request->signataire);

        $get_signataire_from_db =Signataire::where('id_sending',$request->id)->get();

        //creation des signataires de l'envois
        $genaral_widget=[];
        $saved_signataire =[];
        $count_signataire=0;
        if(!is_null($get_signataire_from_db)){
            for($i=0;$i< sizeof($get_signataire_from_db);$i++){

                if(isset($get_signataire_from_db[$i]->id) && $get_signataire_from_db[$i]->id == $signataire[$i]->id && $get_signataire_from_db[$i]->type=='Signataire'){

                    $w =json_decode($get_signataire_from_db[$i]->widget);
                    $new_widget =[];
                    foreach ($w as $r){
                        $r->signataire = $signataire[$i]->name;
                        array_push($new_widget,$r);
                        array_push($genaral_widget,$r);
                    }

                    $new_signataire= Signataire::create([
                        'id_sending'=>$sending->id,
                        'name'=>$signataire[$i]->name,
                        'email'=>$signataire[$i]->email,
                        'type'=>'Signataire',
                        'widget'=>json_encode($new_widget)
                    ]);

                    array_push($saved_signataire,[
                        'id'=>$new_signataire->id,
                        'name'=>$signataire[$i]->name,
                        'email'=>$signataire[$i]->email,
                        'type'=>'Signataire'
                    ]);
                    $count_signataire++;
                }
                else{
                    $new_signataire= Signataire::create([
                        'id_sending'=>$sending->id,
                        'name'=>$get_signataire_from_db[$i]->name,
                        'email'=>$get_signataire_from_db[$i]->email,
                        'type'=>$get_signataire_from_db[$i]->type,
                        'widget'=>'',
                    ]);
                    array_push($saved_signataire,[
                        'id'=>$new_signataire->id,
                        'name'=>$get_signataire_from_db[$i]->name,
                        'email'=>$get_signataire_from_db[$i]->email,
                        'type'=>$get_signataire_from_db[$i]->type
                    ]);
                }
            }

        }

        //Mettre à jour les widget généraux

         $sending->configuration=json_encode($genaral_widget);
         $sending->nbre_signataire = $count_signataire;

         $sending->save();

        //Envois des mail respectif

        foreach ($saved_signataire as $s) {
            if ($s['type'] == 'Signataire') {
                $emailSignataire = new SendSignataireMailJob(
                    [
                        'id_sending' => $sending->id,
                        'id_signataire' => $s['id'],
                        'email' => $s['email'],
                        'subject' => $request->objet == null ? 'Signature requise' : $request->objet,
                        'message' => $request->message == null ? '' : $request->message,
                        'view' => 'signataire_mail_view',
                        'mail_detail' => [
                            'name' => $s['name'],
                            'doc_title' => $newDocument->title,
                            'sending_auth' => Auth::user()->name,
                            'sending_expiration' => $sending->expiration,
                            'preview' => $newDocument->preview,
                        ]
                    ]
                );
                $this->dispatch($emailSignataire);
            } else {
                $emailValidataire = new SendValidatorMailJob(
                    [
                        'id_sending' => $sending->id,
                        'id_validataire' => $s['id'],
                        'email' => $s['email'],
                        'subject' => $request->objet == null ? 'Validation requise' : $request->objet,
                        'message' => $request->message == null ? '' : $request->message,
                        'view' => 'signataire_mail_view',
                        'mail_detail' => [
                            'name' => $s['name'],
                            'doc_title' => $newDocument->title,
                            'sending_auth' => Auth::user()->name,
                            'sending_expiration' => $sending->expiration,
                            'preview' => $newDocument->preview,
                        ]
                    ]
                    );
                $this->dispatch($emailValidataire);
            }
        }

        return $this->sendResponse(new SendingResource($sending), 'Sending updated successfully.');

    }

    public function shareModelWithMember(Request $request){
        $input = $request->all();

        $validator = Validator::make($input, [
            'id' => 'required|numeric',
            'signataire' => 'required|string',
            'objet' => 'nullable|string',
            'message' => 'nullable|string',
            'expiration' => 'required|string',
            'rappel' => 'required|string',
        ]);

        $fieldNames = array(
            'signataire' => 'Signataires',
            'objet' => 'Objet',
            'message' => 'Message',
            'expiration' => 'Expiration',
            'rappel' => 'Rappel'
        );

        $validator->setAttributeNames($fieldNames);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        $s = Sending::find($request->id);
        $sending = $s->replicate();
        $sending->created_at = Carbon::now();
        $sending->updated_at = Carbon::now();

        $document = Document::find($sending->id_document);

        $newDocument = $document->replicate();
        $newTitle = $document->title;
        $newDocument->title = $newTitle;

        $newDocument->created_at = Carbon::now();
        $newDocument->updated_at = Carbon::now();

        $preview_dir = explode('/',$newDocument->preview)[0] ;
        $time = time();
        File::copyDirectory( public_path('previews/'.$preview_dir), public_path('previews/'.$time));
        File::copy(public_path('documents/').$newDocument->file, public_path('documents/').$time.'.pdf');

        $newDocument->file = $time.'.pdf';
        $newDocument->preview = $time.'/1.jpeg';
        $newDocument->save();
        $sending->id_document = $newDocument->id;
        $sending->save();

        // update sending with objet and message
        $sending->message = $request->message;
        $sending->objet = $request->objet;

        if(!in_array($request->rappel,['Quotidien','Hebdomadaire','Aucun','Personnalisé'])){
            $sending->callback = 'Personnalisé';
            $sending->callback_nbre = $request->rappel;
        }
        else{
            $sending->callback = $request->rappel;
        }

        if(!in_array($request->expiration,['Aucun','Personnalisé'])){
            $sending->expiration = 'Personnalisé';
            $sending->expiration_nbre = $request->expiration;
        }
        else{
            $sending->expiration = $request->expiration;
        }

        $sending->is_registed = 1;
        $sending->register_as_model = 0;
        $sending->is_config = 1;
        $sending->last_callback_date = date('Y-m-d H:i:s');

        //register signataire

        $signataire = json_decode($request->signataire);

        $get_signataire_from_db =Signataire::where('id_sending',$request->id)->get();

        //creation des signataires de l'envois
        $genaral_widget=[];
        $saved_signataire =[];
        $count_signataire=0;
        if(!is_null($get_signataire_from_db)){
            for($i=0;$i< sizeof($get_signataire_from_db);$i++){

                if(isset($get_signataire_from_db[$i]->id) && $get_signataire_from_db[$i]->id == $signataire[$i]->id && $get_signataire_from_db[$i]->type=='Signataire'){

                    $w =json_decode($get_signataire_from_db[$i]->widget);
                    $new_widget =[];
                    foreach ($w as $r){
                        $r->signataire = $signataire[$i]->name;
                        array_push($new_widget,$r);
                        array_push($genaral_widget,$r);
                    }

                    $new_signataire= Signataire::create([
                        'id_sending'=>$sending->id,
                        'name'=>$signataire[$i]->name,
                        'email'=>$signataire[$i]->email,
                        'type'=>'Signataire',
                        'widget'=>json_encode($new_widget)
                    ]);

                    array_push($saved_signataire,[
                        'id'=>$new_signataire->id,
                        'name'=>$signataire[$i]->name,
                        'email'=>$signataire[$i]->email,
                        'type'=>'Signataire'
                    ]);
                    $count_signataire++;
                }
                else{
                    $new_signataire= Signataire::create([
                        'id_sending'=>$sending->id,
                        'name'=>$get_signataire_from_db[$i]->name,
                        'email'=>$get_signataire_from_db[$i]->email,
                        'type'=>$get_signataire_from_db[$i]->type,
                        'widget'=>'',
                    ]);
                    array_push($saved_signataire,[
                        'id'=>$new_signataire->id,
                        'name'=>$get_signataire_from_db[$i]->name,
                        'email'=>$get_signataire_from_db[$i]->email,
                        'type'=>$get_signataire_from_db[$i]->type
                    ]);
                }
            }

        }

        //Mettre à jour les widget généraux

         $sending->configuration=json_encode($genaral_widget);
         $sending->nbre_signataire = $count_signataire;

         $sending->save();

        //Envois des mail respectif

        foreach ($saved_signataire as $s) {
            if ($s['type'] == 'Signataire') {
                $emailSignataire = new SendSignataireMailJob(
                    [
                        'id_sending' => $sending->id,
                        'id_signataire' => $s['id'],
                        'email' => $s['email'],
                        'subject' => $request->objet == null ? 'Signature requise' : $request->objet,
                        'message' => $request->message == null ? '' : $request->message,
                        'view' => 'signataire_mail_view',
                        'mail_detail' => [
                            'name' => $s['name'],
                            'doc_title' => $newDocument->title,
                            'sending_auth' => Auth::user()->name,
                            'sending_expiration' => $sending->expiration,
                            'preview' => $newDocument->preview,
                        ]
                    ]
                );
                $this->dispatch($emailSignataire);
            } else {
                $emailValidataire = new SendValidatorMailJob(
                    [
                        'id_sending' => $sending->id,
                        'id_validataire' => $s['id'],
                        'email' => $s['email'],
                        'subject' => $request->objet == null ? 'Validation requise' : $request->objet,
                        'message' => $request->message == null ? '' : $request->message,
                        'view' => 'signataire_mail_view',
                        'mail_detail' => [
                            'name' => $s['name'],
                            'doc_title' => $newDocument->title,
                            'sending_auth' => Auth::user()->name,
                            'sending_expiration' => $sending->expiration,
                            'preview' => $newDocument->preview,
                        ]
                    ]
                    );
                $this->dispatch($emailValidataire);
            }
        }

        return $this->sendResponse(new SendingResource($sending), 'Sending updated successfully.');

    }

    public function addFinaliseConfiguration(Request $request, Sending $sending){
        $input = $request->all();

        $validator = Validator::make($input, [
            'id' => 'required|numeric',
            'signataire' => 'required|string',
            'objet' => 'nullable|string',
            'message' => 'nullable|string',
            'expiration' => 'required|string',
            'rappel' => 'required|string',
            'cc' => "nullable|string",
            'title' => "nullable|string"
        ]);

        $fieldNames = array(
            'signataire' => 'Signataires',
            'objet' => 'Objet',
            'message' => 'Message',
            'expiration' => 'Expiration',
            'rappel' => 'Rappel',
            'cc' => "Copie",
            'title' => "Titre"
        );

        $validator->setAttributeNames($fieldNames);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        // update sending with objet and message
        $sending = Sending::find($request->id);
        $sending->message = $request->message;
        $sending->objet = $request->objet;

        if(!in_array($request->rappel,['Quotidien','Hebdomadaire','Mensuel','Aucun','Personnalisé'])){
            $sending->callback = 'Personnalisé';
            $sending->callback_nbre = $request->rappel;
        }
        else{
            $sending->callback = $request->rappel;
        }

        if(!in_array($request->expiration,['Aucun','Personnalisé'])){
            $sending->expiration = 'Personnalisé';
            $sending->expiration_nbre = $request->expiration;
        }
        else{
            $sending->expiration = $request->expiration;
        }

        $sending->is_registed = 1;
        $sending->last_callback_date = date('Y-m-d H:i:s');
        //register signataire
        $signataire = json_decode($request->signataire);

        $signataires_array = [];
        $signataire_only_array = [];
        $validataire_only_array = [];
        $emails = [];
        $count_signataire = 0;
        $id_user = Auth::id();

        foreach ($signataire as $s) {
            if (!in_array($s->email, $emails)) {
                $emails[] = $s->email;
                if ($s->type == 'Signataire') {
                    $count_signataire++;
                    array_push($signataire_only_array, [
                        'name' => $s->name,
                        'email' => $s->email,
                        'id_user' => $id_user,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    array_push($validataire_only_array, [
                        'name' => $s->name,
                        'email' => $s->email
                    ]);
                }
                array_push($signataires_array, [
                    'name' => $s->name,
                    'email' => $s->email,
                    'type' => $s->type,
                    'id_sending' => $request->id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $save_signataire = [];

        foreach ($signataires_array as $sa) {

            $si=  Signataire::where('type', 'Signataire')
                ->where('name', $sa['name'])
                ->where('id_sending', $request->id)
                ->first();

            array_push($save_signataire, [
                'id' => $si->id,
                'name' => $sa['name'],
                'email' => $sa['email'],
                'type' => $sa['type'],
            ]);
            $si->email = $sa['email'];
            $si->save();
        }
        // save to contact table

        try {
            $save_member = Contact::insert($signataire_only_array);
        }catch (QueryException $e){}

        // register persons who must receive doc copy
        $cc_persons = json_decode($request->cc);
        $cc_persons_array = [];

        foreach ($cc_persons as $c) {
            if (!in_array($c->email, $emails)) {
                $emails[] = $c->email;
                array_push($cc_persons_array, [
                    'name' => $s->name,
                    'email' => $s->email,
                    'type' => 'CC',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }


        $doc_id = Sending::find($request->id);
        $doc_info = Document::find($doc_id->id_document);
        if ($request->title != null) {
            $doc_info->title = $request->title;
            $doc_info->save();
        }


        foreach ($save_signataire as $s) {
            if ($s['type'] == 'Signataire') {
                $emailSignataire = new SendSignataireMailJob(
                    [
                        'id_sending' => $request->id,
                        'id_signataire' => $s['id'],
                        'email' => $s['email'],
                        'subject' => $request->objet == null ? 'Signature requise' : $request->objet,
                        'message' => $request->message == null ? '' : $request->message,
                        'view' => 'signataire_mail_view',
                        'mail_detail' => [
                            'name' => $s['name'],
                            'doc_title' => $doc_info->title,
                            'sending_auth' => Auth::user()->name,
                            'sending_expiration' => $doc_id->expiration,
                            'preview' => $doc_info->preview,
                        ]
                    ]
                );

                $this->dispatch($emailSignataire);

            } else {
                $emailValidataire = new SendValidatorMailJob(
                    [
                        'id_sending' => $request->id,
                        'id_validataire' => $s['id'],
                        'email' => $s['email'],
                        'validataires' => $validataire_only_array,
                        'message' => $request->message == null ? '' : $request->message,
                        'subject' => $request->objet == null ? 'Validation requise' : $request->objet,
                        'view' => 'validataire_mail_view',
                        'mail_detail' => [
                            'name' => $s['name'],
                            'doc_title' => $doc_info->title,
                            'sending_auth' => Auth::user()->name,
                            'sending_expiration' => $doc_id->expiration,
                            'preview' => $doc_info->preview,
                        ]
                    ]);
                $this->dispatch($emailValidataire);
            }
        }

        $sending->nbre_signataire = $count_signataire;
        $sending->is_config = 1;
        try {
            $sending->save();

        } catch (QueryException $ex) {
            return $this->sendError('Error while updating data', []);
        }
        return $this->sendResponse(new SendingResource($sending), 'Sending updated successfully.');

    }

    public function get_signataire_by_sending($id_sending){
        $signataire = Signataire::where('id_sending',$id_sending)
                                  ->where('type','Signataire')
                                  ->get(['*']);
        if(is_null($signataire)){
            return $this->sendError([],'No signaitaire found');
        }
        else{
            return $this->sendResponse(SignataireResource::collection($signataire), 'Signataire retrieved successfully.');

        }
    }

    public function getSendingWidgetBySignataire($id_sending,$id_signataire){
        $signataire = Signataire::where('id_sending',$id_sending)
            ->where('id',$id_signataire)
//            ->where('type','Signataire')
            ->first(['*']);
        if(is_null($signataire)){
            return $this->sendError([],'No signaitaire found');
        }
        else{
            return $this->sendResponse(new SignataireResource($signataire), 'Signataire retrieved successfully.');

//            return $this->sendResponse(SignataireResource::collection($signataire), 'Signataire retrieved successfully.');
        }
    }

    public function getSignataireWidgetPublic($id_sending,$id_signataire){
        $signataire = Signataire::where('id_sending',$id_sending)
            ->where('id',$id_signataire)
            ->first(['*']);
        if(is_null($signataire)){
            return $this->sendError([],'No signaitaire found');
        }
        else{
            return $this->sendResponse(new SignataireResource($signataire), 'Signataire retrieved successfully.');
        }
    }

    public function addSendingWidget(Request $request){
        $input = $request->all();

        $validator = Validator::make($input, [
            'id' => 'required|numeric',
            'widget' => 'required|string'
        ]);

        $fieldNames = array(
            'widget' => 'Widget'
        );

        $validator->setAttributeNames($fieldNames);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        $sending = Sending::find($request->id);
        $sending->configuration = $request->widget ;
        $sending->save();

        return $this->sendResponse(new SendingResource($sending), 'Sending updated successfully.');
    }

    public function addSendingSignataire(Request $request){
        $input = $request->all();

        $validator = Validator::make($input, [
            'id' => 'required|numeric',
            'signataires' => 'required|string',
            'sign_widget' => 'required|string',
            'police' => 'required|numeric',
        ]);

        $fieldNames = array(
            'signataires' => 'Signataires',
            'sign_widget' => 'Widgets',
            'police' => 'Police',
        );

        $validator->setAttributeNames($fieldNames);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        $signataires_to_delete = Signataire::where('type','Signataire')
            ->where('id_sending',$request->id_sending);

        $count = $signataires_to_delete->get()->count();
        $signataires_to_delete->delete();

        $sign = json_decode($request->signataires);
        $w = json_decode($request->sign_widget);


        if(isset($sign) && sizeof($sign)!=0){
            $i=0;
            foreach ($sign as $s){
                $n= $s->value;
                Signataire::create([
                    'id_sending'=>$request->id_sending,
                    'name'=>$s->value,
                    'type'=>'Signataire',
                    'widget'=>json_encode($w->$n) ,
                ]);
                $i++;
            }
        }
        $sending = Sending::find($request->id);
        $nbre = $sending->nbre_signataire;
        $sending->nbre_signataire = $nbre -$count + sizeof($sign);
        $sending->police =$request->police;
        $sending->save();

        return $this->sendResponse(new SendingResource($sending), 'Signataire updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Sending $sending
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sending $sending)
    {
        $document = Document::find($sending->id_document);
        $doc_path = 'documents/'.$document->file ;
        $sp =explode('.pdf',$document->file);
        $doc_path_copy = 'documents/'.$sp[0].'_copy.pdf' ;
        $doc_img_path = 'previews/'.explode('/',$document->preview)[0];
        File::delete(public_path($doc_path));
        File::delete(public_path($doc_path_copy));
        File::deleteDirectory(public_path($doc_img_path));

        //signataire
        Signataire::where('id_sending', $sending->id)->delete();
        //statut__sendings
        Statut_Sending::where('id_sending', $sending->id)->delete();

        $sending->delete();
        return $this->sendResponse([], 'Sending deleted successfully.');
    }

    public function copySending($id)
    {
        $sending = Sending::find($id);
        $newSending = $sending->replicate();
        $newSending->created_at = Carbon::now();
        $newSending->updated_at = Carbon::now();

        $document = Document::find($sending->id_document);

        $newDocument = $document->replicate();
        $newTitle = $document->title . ' Copie';
        $newDocument->title = $newTitle;

        $newDocument->created_at = Carbon::now();
        $newDocument->updated_at = Carbon::now();

        $newDocument->save();
        $newSending->id_document = $newDocument->id;
        $newSending->save();

        return $this->sendResponse(new SendingResource($newSending), 'Sending replicated successfully.');

    }

    public function mail_opened($id_sending, $id_signataire)
    {
        $statut = Statut_Sending::create([
            'id_sending' => $id_sending,
            'id_signataire' => $id_signataire,
            'id_statut' => OPENED_EMAIL_MESSAGE
        ]);
        return $this->sendResponse(SignataireResource::collection($statut), 'Sending statut updated successfully.');
    }

    public function doc_opened($id_sending, $id_signataire)
    {
        $sd=Sending::find($id_sending);
       // if($sd->statut != EXPIRER){
            try {
                $statut = Statut_Sending::create([
                    'id_sending' => $id_sending,
                    'id_signataire' => $id_signataire,
                    'id_statut' => OUVRIR
                ]);
                if($statut){
                    $url=env('FRONT_URL_REDIRECT').$id_signataire.'/'.$id_sending;
                    return Redirect::to($url);
                }
                return $this->sendResponse(SignataireResource::collection($statut), 'Sending statut updated successfully.');

            }catch (QueryException $e){
                return $this->sendError('Error while registering data');
            }
        //}
    }

    private function getAnswerWithWidget($id,$answer_array){
        for ($i = 0;$i<sizeof($answer_array);$i++){
            if($answer_array[$i]->id==$id){
                return $i ;
            }
        }
    }

    public function doc_signed(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'id_sending' => 'required|numeric',
            'id_signataire' => 'required|numeric',
            'answer' => 'required|string',
            'mobile_info'=>'required'
        ]);

        $fieldNames = array(
            'id_sending' => 'envois',
            'id_signataire' => 'signataire',
            'answer' => 'Réponse',
            'mobile_info'=>'Mobile info'
        );

        $validator->setAttributeNames($fieldNames);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        $signataire = Signataire::find($request->id_signataire);
        $signataire ->signataire_answers = $request->answer;
        $signataire ->mobile_info = $request->mobile_info;

        $signataire->save();

        $send = Sending::join('users', 'sendings.created_by', '=', 'users.id')
            ->where('sendings.id',$request->id_sending)
            ->first(['sendings.*','users.name','users.email']);

        $statut = Statut_Sending::create([
            'id_sending' => $request->id_sending,
             'id_signataire' => $request->id_signataire,
             'id_statut' => $signataire->type=='Signataire' ? SIGNER : VALIDER
        ]);

        $signataires = Signataire::where('id_sending',$request->id_sending)->where('type','Signataire')->get();

        if(!is_null($signataires) && sizeof($signataires)!=0){
            $one = 1;
            $all_answer = [];
            foreach ($signataires as $s){
                $last_statut = Statut_Sending::where('id_sending',$request->id_sending)
                                               ->where('id_signataire',$s->id)
                                               ->orderBy('created_at','DESC')
                                               ->first();

                if(!is_null($last_statut)){
                    if($s->type=='Signataire'){
                        if($last_statut->id_statut!==SIGNER){
                            $one = $one * 0 ;
                            break;
                        }
                        else{
                            $one = $one * 1 ;
                        }
                    }

                   /* if($s->type=='Validataire'){
                        if($last_statut->id_statut!==VALIDER){
                            $one = $one * 0 ;
                            break;
                        }
                        else{
                            $one = $one * 1 ;
                        }
                    }*/
                }

                $all_answer = array_merge($all_answer,json_decode($s->signataire_answers) );
            }
            $send->response = json_encode($all_answer);
            if($one == 1){

                $doc = Document::find($send->id_document);
                $doc->is_signed = 1 ;
                $nbre_page = $doc->nbre_page;

                $validataire = Signataire::where('id_sending',$request->id_sending)->where('type','Validataire')->get();
                $send->response = json_encode($all_answer);
                if(!is_null($validataire)  && sizeof($validataire) !=0){
                    foreach ($validataire as $s){
                        $emailValidataire = new SendValidatorMailJob(
                            [
                                'id_sending' => $request->id_sending,
                                'id_validataire' => $s->id,
                                'email' => $s->email,
                                'subject' => $send->objet == null ? 'Validation requise' : $send->objet,
                                'message' => $send->message == null ? '' : $send->message,
                                'view' => 'validataire_mail_view',
                                'mail_detail' => [
                                    'name' => $s['name'],
                                    'doc_title' => $doc->title,
                                    'sending_auth' => Auth::user()->name,
                                    'sending_expiration' => $send->expiration,
                                    'preview' => $doc->preview,
                                ]
                            ]
                        );
                        $this->dispatch($emailValidataire);
                    }
                }
                else{
                    $send->statut = FINIR;
                    $send->save();

                    $the_modifying_file = public_path('/documents/'.explode('.pdf',$doc->file)[0].'_copy.pdf');

                    // add_answers_to_document
                    include base_path("vendor/autoload.php");
                    $pdf = new FPDI();
                    $pdf->setSourceFile($the_modifying_file);
                    $pdf->SetFontSize($send->police);
                    $pdf->SetFont('Helvetica');
                    $fontSize = 12;
                    $widget=json_decode($send->configuration);

                    //$folder =explode($doc->preview,'/')[0];

                   /* $pdf = new FPDF();
                    for($i=1;$i<=$nbre_page;$i++){
                        $pdf->AddPage();

                        foreach ($widget as $w){
                            if($i==$w->page){
                                if($w->type_widget=='signature'){
                                    $index= $this->getAnswerWithWidget('signature',$all_answer);
                                }
                                else{
                                    $index= $this->getAnswerWithWidget($w->widget_id,$all_answer);
                                }
                                header("Content-type: image/jpeg");
                                $imgPath = public_path('previews/1665840282/'.$i.'jpeg');
                                $image = imagecreatefromjpeg($imgPath);
                                $color = imagecolorallocate($image, 0, 0, 0);

                                if($w->type_widget !='certificat' && $w->type_widget !='image' && $w->type_widget !='signature'){
                                    $string = $all_answer[$index]->value;

                                    $x =$w->positionY ;
                                    $y =$w->positionX ;

                                    imagestring($image, $fontSize, $x, $y, $string, $color);
                                    imagejpeg($image);

                                    $pdf->Image($image,20,40,170,170);
                                }
                                else{
                                    $tmp = public_path('/previews/tempimg.png');
                                    $dataURI    = $all_answer[$index]->value;
                                    $dataPieces = explode(',',$dataURI);
                                    $encodedImg = $dataPieces[1];
                                    $decodedImg = base64_decode($encodedImg);

                                    if( $decodedImg!==false )
                                    {
                                        if( file_put_contents($tmp,$decodedImg)!==false )
                                        {
                                            $pdf->Image($tmp,$w->positionX*200/500, $w->positionY*200/500,(explode('px',$w->width)[0]*200/500),explode('px',$w->height)[0]*200/500);
                                        }
                                    }
                                    unlink($tmp);
                                }
                            }
                        }

                    }*/

                    for($i=1;$i<=$nbre_page;$i++){
                       // return response()->json('start loop');
                        $pdf->AddPage();
                        ${"template_" . $i} =  $pdf->importPage($i);
                        $pdf->useTemplate(${"template_" . $i},
                            [
                                'adjustPageSize' => true,
                                 'width'=>'205'
                            ]);
                        foreach ($widget as $w){
                            if($i==$w->page){
                                if($w->type_widget=='signature'){
                                    $index= $this->getAnswerWithWidget('signature',$all_answer);
                                }
                                else{
                                    $index= $this->getAnswerWithWidget($w->widget_id,$all_answer);
                                }
                                if($w->type_widget !='certificat' && $w->type_widget !='image' && $w->type_widget !='signature'){
                                    $pdf->SetXY($w->positionX*205/753, $w->positionY*205/753);
                                    //$pdf->SetXY($w->positionY*200/500, $w->positionX*200/500);
                                    //$pdf->SetXY($w->positionY*200/500, $w->positionX*200/500);
                                    $pdf->Write(0, $all_answer[$index]->value);
                                }
                                else{
                                    $tmp = public_path('/previews/tempimg.png');
                                    $dataURI    = $all_answer[$index]->value;
                                    $dataPieces = explode(',',$dataURI);
                                    $encodedImg = $dataPieces[1];
                                    $decodedImg = base64_decode($encodedImg);

                                    if( $decodedImg!==false )
                                    {
                                        if( file_put_contents($tmp,$decodedImg)!==false )
                                        {
                                            $pdf->Image($tmp,$w->positionX*200/753, $w->positionY*200/753,(explode('px',$w->width)[0]*200/753),explode('px',$w->height)[0]*200/753);
                                            //$pdf->Image($tmp,$w->positionX*200/500, $w->positionY*200/500,(explode('px',$w->width)[0]*200/500),explode('px',$w->height)[0]*200/500);
                                        }
                                    }
                                    unlink($tmp);
                                }
                            }
                        }
                    }

                    $pdf->Output(public_path('/documents/'.explode('.pdf',$doc->file)[0].'_signer.pdf'),'F');

                    //send notification to author
                    $notif = new NotifyDocSignedToDocAuthor(
                        [
                            'email' => $send->email,
                            'doc_title' => $doc->title,
                            'sending_auth' => $send->name,
                            'doc_link' => public_path('/documents/'.explode('.pdf',$doc->file)[0].'_signed.pdf'),

                        ]
                    );

                    $this->dispatch($notif);

                    //send cc of document
                    $cc = Signataire::where('type','CC')->where('id_sending',$request->id_sending)->get();
                    if(!is_null($cc)){
                        foreach ($cc as $s) {
                            $emailSignataire = new SendCcMailJob(
                                [
//                                'id_sending' => $request->id,
                                    'email' => $s['email'],
                                    'mail_detail' => [
                                        'name' => $s['name'],
                                        'doc_title' => $doc->title,
                                        'sending_auth' => Auth::user()->name,
                                        'doc_link' => public_path('/documents/'.explode('.pdf',$doc->file)[0].'_signed.pdf'),
                                    ]
                                ]
                            );

                            $this->dispatch($emailSignataire);
                        }
                    }
                }

            }
            $send->save();
        }

        return $this->sendResponse(new StatutSendingResource($statut), 'Sending statut updated successfully.');

    }



    public function validateDocument(Request $request){
        $input = $request->all();

        $validator = Validator::make($input, [
            'id_sending' => 'required|numeric',
            'id_signataire' => 'required|numeric',
            'answer' => 'required|string',
            'mobile_info'=>'required'
        ]);

        $fieldNames = array(
            'id_sending' => 'envois',
            'id_signataire' => 'signataire',
            'answer' => 'Réponse',
            'mobile_info'=>'Mobile info'
        );

        $validator->setAttributeNames($fieldNames);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        $signataire = Signataire::find($request->id_signataire);
        $signataire ->signataire_answers = $request->answer;
        $signataire ->mobile_info = $request->mobile_info;

        $signataire->save();

        $statut = Statut_Sending::create([
            'id_sending' => $request->id_sending,
            'id_signataire' => $request->id_signataire,
            'id_statut' =>  VALIDER
        ]);

        $validataires = Signataire::where('id_sending',$request->id_sending)->where('type','Validataire')->get();

        if(!is_null($validataires) && sizeof($validataires)!=0){
            $one = 1;
            $all_answer = [];
            foreach ($validataires as $v){
                $last_statut = Statut_Sending::where('id_sending',$request->id_sending)
                    ->where('id_signataire',$v->id)
                    ->orderBy('created_at','DESC')
                    ->first();

                if(!is_null($last_statut)){
                  /*  if($s->type=='Signataire'){
                        if($last_statut->id_statut!==SIGNER){
                            $one = $one * 0 ;
                            break;
                        }
                        else{
                            $one = $one * 1 ;
                        }
                    }*/

                    if($v->type=='Validataire'){
                        if($last_statut->id_statut!==VALIDER){
                            $one = $one * 0 ;
                            break;
                        }
                        else{
                            $one = $one * 1 ;
                        }
                    }
                }
            }

            $signataires = Signataire::where('id_sending',$request->id_sending)->where('type','Signataire')->get();

            if(!is_null($signataires) && sizeof($signataires)!=0){
                foreach ($signataires as $s){
                    $all_answer = array_merge($all_answer,json_decode($s->signataire_answers) ) ;
                }
            }
            $send = Sending::join('users', 'sendings.created_by', '=', 'users.id')
                ->where('id',$request->id_sending)
                ->first(['sendings.*','users.name','users.email']);
            $send->response = json_encode($all_answer);
            if($one == 1){
                $send->statut = FINIR;
                $doc = Document::find($send->id_document);
                $doc->is_signed = 1 ;
                $nbre_page = $doc->nbre_page;

                $the_modifying_file = public_path('/documents/'.explode('.pdf',$doc->file)[0].'_copy.pdf');

                // add_answers_to_document

                include base_path("vendor/autoload.php");
                $pdf = new FPDI();
                $pdf->setSourceFile($the_modifying_file);

                $pdf->SetFontSize($send->police);
                $pdf->SetFont('Helvetica');

                $widget=json_decode($send->configuration);

                for($i=1;$i<=$nbre_page;$i++){
                    $pdf->AddPage();
                    ${"template_" . $i} =  $pdf->importPage($i);
                    $pdf->useTemplate(${"template_" . $i},['adjustPageSize' => true]);
                    foreach ($widget as $w){
                        if($i==$w->page){
                            if($w->type_widget=='signature'){
                                $index= $this->getAnswerWithWidget('signature',$all_answer);
                            }
                            else{
                                $index= $this->getAnswerWithWidget($w->widget_id,$all_answer);

                            }
                            if($w->type_widget !='certificat' && $w->type_widget !='image' && $w->type_widget !='signature'){
                                $pdf->SetXY($w->positionX*200/500, $w->positionY*200/500);
                                $pdf->Write(0, $all_answer[$index]->value);
                            }
                            else{
                                $tmp = public_path('/previews/tempimg.png');
                                $dataURI    = $all_answer[$index]->value;
                                $dataPieces = explode(',',$dataURI);
                                $encodedImg = $dataPieces[1];
                                $decodedImg = base64_decode($encodedImg);

                                if( $decodedImg!==false )
                                {
                                    if( file_put_contents($tmp,$decodedImg)!==false )
                                    {
                                        $pdf->Image($tmp,$w->positionX*200/500, $w->positionY*200/500,(explode('px',$w->width)[0]*200/500),explode('px',$w->height)[0]*200/500);
                                    }
                                }
                                unlink($tmp);
                            }
                        }
                    }
                }
                $pdf->Output(public_path('/documents/'.explode('.pdf',$doc->file)[0].'_signer.pdf'),'F');
                // return response()->json('stop');

                //send notification to author
                $notif = new NotifyDocSignedToDocAuthor(
                    [
                        'email' => $send->email,
                        'doc_title' => $doc->title,
                        'sending_auth' => $send->name,
                        'doc_link' => public_path('/documents/'.explode('.pdf',$doc->file)[0].'_signed.pdf'),

                    ]
                );

                $this->dispatch($notif);

                //send cc of document
                $cc = Signataire::where('type','CC')->where('id_sending',$request->id_sending)->get();
                if(!is_null($cc)){
                    foreach ($cc as $s) {
                        $emailSignataire = new SendCcMailJob(
                            [
//                                'id_sending' => $request->id,
                                'email' => $s['email'],
                                'mail_detail' => [
                                    'name' => $s['name'],
                                    'doc_title' => $doc->title,
                                    'sending_auth' => Auth::user()->name,
                                    'doc_link' => public_path('/documents/'.explode('.pdf',$doc->file)[0].'_signed.pdf'),
                                ]
                            ]
                        );

                        $this->dispatch($emailSignataire);
                    }
                }
            }
            $send->save();
        }
        return $this->sendResponse(new StatutSendingResource($statut), 'Sending statut updated successfully.');
    }

    public  function revokeDocument(Request $request) {

        $input = $request->all();

        $validator = Validator::make($input, [
            'id_sending' => 'required|numeric',
            'id_signataire' => 'required|numeric',
            'answer' => 'required|string',
            'mobile_info'=>'required'
        ]);

        $fieldNames = array(
            'id_sending' => 'envois',
            'id_signataire' => 'signataire',
            'answer' => 'Réponse',
            'mobile_info'=>'Mobile info'
        );

        $validator->setAttributeNames($fieldNames);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        try {
            $statut = Statut_Sending::create([
                'id_sending' => $request->id_sending,
                'id_signataire' => $request->id_signataire,
                'id_statut' => REVOKE
            ]);
            $signataire = Signataire::find($request->id_signataire);
            $signataire ->mobile_info = $request->mobile_info;
            $signataire->save();

            if($statut){
                return $this->sendResponse(SignataireResource::collection($statut), 'Sending statut updated successfully.');
            }
            else{
                return $this->sendError([], 'Error while saving statut');
            }

        }catch (QueryException $e){
            return $this->sendError('Error while saving data');
        }
        //}
    }

    public function downloadTheSignedFile($id_sending)
    {
        $sending = Sending::find($id_sending);
        if($sending->statut==SIGNER){
            $doc= Doc::find($sending->id_document);
            $filePath = public_path('/documents/'.explode('.pdf',$doc->file)[0].'_signer.pdf');
            $headers = ['Content-Type: application/pdf'];
            $fileName = time().'.pdf';
            return response()->download($filePath, $fileName, $headers);
        }else{
            return $this->sendError([],'Document not signed');
        }
    }

    public function downloadTheOriginalFile($id_sending): BinaryFileResponse
    {
            $sending = Sending::find($id_sending);
            $doc= Document::find($sending->id_document);
            $filePath = public_path('/documents/'.$doc->file);
            $headers = ['Content-Type: application/pdf'];
            $fileName = time().'.pdf';
            $name=$doc->file;
            return Response::download($filePath, $name, $headers);

           // return Response::download($filePath, $fileName, $headers);
       // return response()->download($filePath, $fileName, $headers);

    }

    public function downloadTheProofFile($id_sending)
    {
        $sending = Sending::join('type__signatures','sendings.id_type_signature','=','type__signatures.id')
                            ->where('id',$id_sending)
                            ->get(['sendings.*','type__signatures.type']);
        if($sending->statut==SIGNER && $sending->type=='simple' ){
            $doc= Doc::find($sending->id_document);
            $filePath = public_path('/documents/'.explode('.pdf',$doc->file)[0].'_proof.pdf');
            $headers = ['Content-Type: application/pdf'];
            $fileName = time().'.pdf';
            return response()->download($filePath, $fileName, $headers);
        }else{
            return $this->sendError([],'Document not signed');
        }
    }

}

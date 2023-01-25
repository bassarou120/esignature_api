<?php

namespace App\Http\Controllers;

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
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Sending as SendingResource;
use App\Http\Resources\Signataire as SignataireResource;
use App\Http\Resources\StatutSending as StatutSendingResource;
use Mpdf\Mpdf;
use PHPMailer\PHPMailer\PHPMailer;
use setasign\Fpdi\Fpdi;

//use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
//use Spatie\PdfToImage\Pdf;

class SendingController extends Controller
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


    public function test_for_doc(){
        require base_path("vendor/autoload.php");
        $mpdf = new mPDF();

        $mpdf->AddPage();
// set the sourcefile
        $mpdf->setSourceFile(public_path('documents/test.pdf'));
// import page 1
        $tplIdx = $mpdf->importPage(1);
// use the imported page and place it at point 10,10 with a width of 200 mm   (This is the image of the included pdf)
        $mpdf->useTemplate($tplIdx, 10, 10, 200);
// now write some text above the imported page
        $mpdf->SetTextColor(0,0,255);

        $mpdf->SetFont('Arial','B',8);
       // $mpdf->SetXY(100, 300);
        $mpdf->SetX(100);
        $mpdf->SetY(300);
       // $mpdf->WriteHTML('<div>Hello word</div>');
        $mpdf->Write(0, "Mindfire");
        $mpdf->Output();
    }


    public function test_with_fpdf(){
       // include "vendor/autoload.php";
        include base_path("vendor/autoload.php");

// Create new Landscape PDF
        $pdf = new FPDI();

// Reference the PDF you want to use (use relative path)
        $pagecount = $pdf->setSourceFile( public_path('documents/test.pdf') );

// Import the first page from the PDF and add to dynamic PDF
        $tpl = $pdf->importPage(1);
        $pdf->AddPage();

// Use the imported page as the template
        $pdf->useTemplate($tpl,'0','0','205');

// Set the default font to use
        $pdf->SetFont('Helvetica');

// adding a Cell using:
// $pdf->Cell( $width, $height, $text, $border, $fill, $align);

// First box - the user's Name
        $pdf->SetFontSize('12'); // set font size
        $pdf->SetXY(139.328125*205/753, 363.265625*205/753); // set the position of the box
       // $pdf->Cell(0, 10, 'Niraj Shah', 1, 0, 'C');
        $pdf->Write(0, 'Niraj Shah');

// add the reason for certificate
// note the reduction in font and different box position
//        $pdf->SetFontSize('20');
//        $pdf->SetXY(80, 105);
//        $pdf->Cell(100, 10, 'creating an awesome tutorial', 1, 0, 'C');

// the day
//        $pdf->SetFontSize('20');
//        $pdf->SetXY(118,122);
//        $pdf->Cell(20, 10, date('d'), 1, 0, 'C');

// the month
//        $pdf->SetXY(160,122);
//        $pdf->Cell(30, 10, date('M'), 1, 0, 'C');

// the year, aligned to Left
//        $pdf->SetXY(200,122);
//        $pdf->Cell(20, 10, date('y'), 1, 0, 'L');

// render PDF to browser
        $pdf->Output();
    }

    public function test_with_img(){

//        header("Content-type: image/jpeg");
//        $imgPath = public_path('previews/1674430228/1.jpeg');
//        $image = imagecreatefromjpeg($imgPath);
//        $color = imagecolorallocate($image, 255, 0, 0);
//        $string = "http://recentsolutions.net";
//        $fontSize = 12;
////        $x = 526.65625;
////        $y = 248.734375;
//
//        $x =653.328125 ;
//        $y =1174.734375 ;
////        $x = 115;
////        $y = 185;
//        imagestring($image, $fontSize, $x, $y, $string, $color);
//        imagejpeg($image);

        $sending = Sending::join('type__signatures','sendings.id_type_signature','=','type__signatures.id')
            ->join('users','sendings.created_by','=','users.id')
            ->join('documents','sendings.id_document','=','documents.id')
            ->where('sendings.id',267)
            ->first(['sendings.*','type__signatures.type','users.name','users.email','documents.title','documents.file']);
        if($sending->statut==FINIR && $sending->type=='avanced' ){

            header("Content-type: image/jpeg");
            $imgPath = public_path('proof/tmp.jpeg');
            $arial =  public_path('proof/arial.ttf');
            $roboto =  public_path('proof/arial.ttf');
            $roboto_bold =  public_path('proof/Roboto-Bold.ttf');
            $image = imagecreatefromjpeg($imgPath);
            $color = imagecolorallocate($image, 87, 87, 87);
            $blue = imagecolorallocate($image, 41, 77, 135);
            $green = imagecolorallocate($image, 157, 184, 139);

            //email
            imagettftext($image, 19, 0, 545, 450, $blue, $roboto_bold, $sending->email);
            //Emetteur
            imagettftext($image, 19, 0, 660, 692, $blue, $roboto_bold, $sending->name.' ('.$sending->email.')');
            //Document
            imagettftext($image, 19, 0, 660, 750, $blue, $roboto_bold, $sending->title.'.pdf');
             //Size
            imagettftext($image, 19, 0, 660, 808, $blue, $roboto_bold, (filesize(public_path('documents/'.$sending->file))/1000).' kB' );
            //CRC du fichier
            imagettftext($image, 19, 0, 660, 876, $color, $roboto_bold, '71e870c744474c0f5e27756c8dbb5e96' );
            //CRC du fichier
            imagettftext($image, 19, 0, 660, 935, $color, $roboto_bold, '1ffd36dc-88dc-444b-823a-7b900ea639b9' );


            //Statut
            imagettftext($image, 19, 0, 350, 1056, $green, $roboto_bold, 'COMPLÉTÉ' );
            //Date
            imagettftext($image, 19, 0, 1050, 1056, $color, $roboto_bold, c::createFromFormat('Y-m-d H:i:s', $sending->updated_at)->format('d/m/Y H:i:s'));

            //Date
            imagettftext($image, 19, 0, 1030, 1240, $color, $roboto_bold, c::createFromFormat('Y-m-d H:i:s', $sending->updated_at)->format('H:i:s').' le '.c::createFromFormat('Y-m-d H:i:s', $sending->updated_at)->format('d/m/Y'));

            //Date
            imagettftext($image, 18, 0, 480, 1380, $color, $roboto_bold, '********************************');

            $tmp_dir = public_path('/previews/test_proof.jpeg');

            imagejpeg($image,$tmp_dir);

            include base_path("vendor/autoload.php");
            $pdf = new FPDI();
            $pdf->AddPage();
            $pdf->Image($tmp_dir,0,0);
            $pdf->Output();
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('user_front.sending.create');
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
                $imageName1 = $doc->getClientOriginalName();
                if(file_exists(public_path('documents').'/'.$imageName1)){

                    $imageName1 = $title.'_'.time().'.pdf';
                    $cp_name = $title.'_'.time().'_copy.pdf';
                }
                else{
                    $cp_name= $title.'_copy.pdf';
                }

                $doc->move(public_path('documents'), $imageName1);
                copy(public_path('documents').'/'.$imageName1,public_path('documents').'/'.$cp_name);
               // $doc->move(public_path('documents'), $cp_name);



                //$pdf = new Spatie\PdfToImage\Pdf();
                $pdf = new Pdf(public_path('documents') . '/' . $imageName1);
                $nbre_page = $pdf->getNumberOfPages();
                $preview_name = $title . time();
                $t = time();
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
        $sending->callback = $request->rappel;
        $sending->expiration = $request->expiration;
        $sending->is_registed = 1;

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
            // $save_signataire = Signataire::insert($signataires_array);
            $save = Signataire::create($sa);
            // $save_signataire[]=$save->id;
            array_push($save_signataire, [
                'id' => $save->id,
                'name' => $sa['name'],
                'email' => $sa['email'],
                'type' => $sa['type'],
            ]);
        }
        // save to contact table

        $save_member = Contact::insert($signataire_only_array);

        //register statut sending by signataire
        $statut_sending = [];
        for ($i = 0; $i < sizeof($save_signataire); $i++) {
            array_push($statut_sending, [
                'id_sending' => $request->id,
                'id_signataire' => $save_signataire[$i]['id'],
                'id_statut' => EN_COURS,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $save_statut_sending_by_signataire = Statut_Sending::insert($statut_sending);


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

        //send mail to signataire
        if ($request->message == null) {

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
                    ]);
                $this->dispatch($emailSignataire);
            } else {
                $emailValidataire = new SendValidatorMailJob(
                    [
                        'id_sending' => $request->id,
                        'id_signataire' => $s['id'],
                        'validataires' => $validataire_only_array,
                        'subject' => $request->objet == null ? 'Validation requise' : $request->objet,
                        'view' => 'validataire_mail_view',
                    ]);
                $this->dispatch($emailValidataire);
            }
        }


//       $r= send_mail([
//            'id_sending'=>$request->id,
//            'signataires'=>$signataire_only_array,
//            'subject'=> $request->objet==null ? 'Signature requise' : $request->objet ,
//            'view'=> 'signataire_mail_view',
//            'mail_detail'=>[
//                'doc_title'=>$doc_info->title,
//                'sending_auth'=>Auth::user()->name,
//                'sending_expiration'=>$doc_id->expiration,
//                'preview'=>$doc_info->preview,
//            ]
//
//        ]);
//
//        dd($r);
        // send mail to validator

        // send mail to cc personnes

        //update sending
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

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Sending $sending
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sending $sending)
    {

    }





}

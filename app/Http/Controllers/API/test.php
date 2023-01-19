<?php

use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Resources\StatutSending as StatutSendingResource;
use App\Jobs\NotifyDocSignedToDocAuthor;
use App\Jobs\SendCcMailJob;
use App\Jobs\SendValidatorMailJob;
use App\Models\Document;
use App\Models\Sending;
use App\Models\Signataire;
use App\Models\Statut_Sending;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use setasign\Fpdi\Fpdi;

class TestController extends BaseController
{
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

                 $pdf = new FPDF();
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
                                 //imagejpeg($image);

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
                                         $logo = imagecreatefrompng($tmp);

                                         imagecopy($image,$logo,0,0,$w->positionX,$w->positionY,$w->width,$w->height);

                                     }
                                        // $pdf->Image($tmp,$w->positionX*200/500, $w->positionY*200/500,(explode('px',$w->width)[0]*200/500),explode('px',$w->height)[0]*200/500);
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
}

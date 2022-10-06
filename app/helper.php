<?php


use App\Models\Statut_Sending;
use Illuminate\Support\Facades\DB;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function reformateDate($date){
    $e = explode(' ',$date);

    if(isset($e[0])){
        $date_part = $e[0];
        $inter = explode('-', $date_part);
        $newdate=$inter[2].'/'.$inter[1].'/'.$inter[0];
        if(isset($e[1])){
            $time_part= $e[1];
            $newdate = $newdate.' '.$time_part;
        }
        return $newdate;
    }
}


function callback($buffer,$data)
{
    return  str_replace(
        array(
            '[$name]',
            '[$sending_auth]',
            '[$doc_title]',
            '[$sending_expiration]',
            '$preview',
            '[$id_sending]',
            '[$id_signataire]',
            '[$app_url]',
            'my_link',
        ),
        array(
            $data['mail_detail']['name'],
            $data['mail_detail']['sending_auth'],
            $data['mail_detail']['doc_title'],
            isset($data['mail_detail']['sending_expiration']) ? 'Vous devriez faire cette action avant le '.$data['mail_detail']['sending_expiration'] : '' ,
            $data['mail_detail']['preview'],
            $data['id_sending'],
            $data['id_signataire'],
            url(''),
            url('').'/api/sendings/doc/opened/'.$data['id_sending'].'/'.$data['id_signataire'],

        ),
        $buffer );
}

function  send_mail($data,$statut_signataire){
    require base_path("vendor/autoload.php");
    $mail = new PHPMailer(true);     // Passing `true` enables exceptions
    try {
        // Email server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = env('PHP_MAILER_HOST');             //  smtp host
        $mail->SMTPAuth = true;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $mail->Username = env('PHP_MAILER_USERNAME');   //  sender username
        $mail->Password = env('PHP_MAILER_PASSWORD') ;       // sender password
        $mail->SMTPSecure = env('PHP_MAILER_SMTPSecure');                  // encryption - ssl/tls
        $mail->Port =  env('PHP_MAILER_PORT') ;                          // port - 587/465
        $mail->setFrom( env('PHP_MAILER_USERNAME'), env('PHP_MAILER_FROM_NAME') );


      //  foreach ($data['signataires'] as $s){
            $mail->addAddress($data['email']);
       // }

//        if(isset($data['cc'])) {
//            foreach ($data['cc'] as $c){
//                $mail->addCC($c['email']);
//            }
//        }


        // $mail->addBCC($request->emailBcc);

        // $mail->addReplyTo('anagoarmandine@gmail.com', 'Dine');

            if(isset($data['document'])) {
                foreach ($data['document'] as $c){
                    $mail->addAttachment($c->name, $c->link);

                }
            }

        $mail->isHTML(true);                // Set email content format to HTML

        $mail->Subject = $data['subject'];

        if($data['view']=='signataire_mail_view'){
            $mail->Body    = str_replace(
                array(
                    '[$name]',
                    '[$sending_auth]',
                    '[$doc_title]',
                    '[$sending_expiration]',
                    '$preview',
                    '[$id_sending]',
                    '[$id_signataire]',
                    '[$app_url]',
                    'my_link',
                ),
                array(
                    $data['mail_detail']['name'],
                    $data['mail_detail']['sending_auth'],
                    $data['mail_detail']['doc_title'],
                    isset($data['mail_detail']['sending_expiration']) ? 'Vous devriez faire cette action avant le '.$data['mail_detail']['sending_expiration'] : '' ,
                    $data['mail_detail']['preview'],
                    $data['id_sending'],
                    $data['id_signataire'],
                    url(''),
                    url('').'/api/sendings/doc/opened/'.$data['id_sending'].'/'.$data['id_signataire'],

                ),
                file_get_contents(resource_path('views/mail_template/signataire_mail_view.blade.php'))
            );
           // $mail->Body    = get_include_contents( resource_path('views/mail_template/signataire_mail_view.blade.php'),$data['mail_detail']);
        }
        else if($data['view']=='validataire_mail_view'){
            $mail->Body    = $data['view'];
        }
        else{
            $mail->Body    = $data['view'];
        }

        $envois =$mail->send();
        if($envois==false) {
            \Log::info("Erreur de l'envois du mail");
        }
        else {
            \Log::info("Mail envoyer");

            DB::table('statut__sendings')->insert([
                'id_sending' =>  $data['id_sending'],
                'id_signataire' => $data['id_signataire'],
                'id_statut' => ENVOYER,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

    } catch (Exception $e) {
        echo $e;
    }
}

function get_include_contents($filename,$data) {
    extract($data);
    if (is_file($filename)) {
        ob_start();
        include $filename;
        return ob_get_clean();
    }
    return false;
}


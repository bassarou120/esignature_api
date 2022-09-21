<?php


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

function  send_mail($data){
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
            $message = str_replace(
                array(
                    '[$name]',
                    '[$sending_auth]',
                    '[$doc_title]',
                    '[$sending_expiration]',
                    '$preview',
                    '[$id_sending]',
                    '[$id_signataire]',
                ),
                array(
                    $data['mail_detail']['name'],
                    $data['mail_detail']['sending_auth'],
                    $data['mail_detail']['doc_title'],
                    $data['mail_detail']['sending_expiration'],
                    $data['mail_detail']['preview'],
                    $data['id_sending'],
                    $data['id_signataire'],
                ),
                file_get_contents(resource_path('views/mail_template/signataire_mail_view.blade.php'))
            );
            $mail->Body    = $message;
           // $mail->Body    = get_include_contents( resource_path('views/mail_template/signataire_mail_view.blade.php'),$data['mail_detail']);
        }
        else if($data['view']=='validataire_mail_view'){
            $mail->Body    = $data['view'];
        }
        else{
            $mail->Body    = $data['view'];
        }

        // $mail->AltBody = plain text version of email body;

        if( !$mail->send() ) {
            \Log::info("Erreur de l'envois du mail");
            return false;
        }
        else {

            \Log::info("Cron is working fine!");
            return true;
        }

    } catch (Exception $e) {

        return $e;
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


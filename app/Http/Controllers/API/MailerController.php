<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailerController extends BaseController
{
    // =============== [ Email ] ===================
    public function email() {
        return view("email");
    }


    // ========== [ Compose Email ] ================
    public function composeEmail(Request $request) {
        require base_path("vendor/autoload.php");
        $mail = new PHPMailer(true);     // Passing `true` enables exceptions

       // try {

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
            $mail->Port =  env('PHP_MAILER_PORT') ;
            $mail->setFrom(env('PHP_MAILER_USERNAME'), env('PHP_MAILER_FROM_NAME') );

            $mail->addAddress('anagoarmandine@gmail.com');

            $mail->isHTML(true);                // Set email content format to HTML

            $mail->Subject = 'Test';
            $mail->Body    = 'Test';


            if( !$mail->send() ) {
//                return back()->with("failed", "Email not sent.")->withErrors($mail->ErrorInfo);
                return response()->json('Email not sent.',400);
            }
            else {
                return response()->json('Email has been sent.',200);
            }

//        } catch (Exception $e) {
//            return response()->json('Message could not be sent.',400);
//        }
    }
}

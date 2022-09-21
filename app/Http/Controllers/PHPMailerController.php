<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PHPMailerController extends Controller
{
    public function email() {
        return view("email");
    }


    // ========== [ Compose Email ] ================
    public function composeEmail(Request $request) {

        require base_path("vendor/autoload.php");
        $mail = new PHPMailer(true);     // Passing `true` enables exceptions
        try {

            // Email server settings
            $mail->SMTPDebug = 0;
            $mail->isSMTP();

            $mail->Host = env('PHP_MAILER_HOST');             //  smtp host
            $mail->SMTPAuth = true;
            $mail->Username = env('PHP_MAILER_USERNAME');   //  sender username
            $mail->Password = env('PHP_MAILER_PASSWORD') ;       // sender password
            $mail->SMTPSecure = env('PHP_MAILER_SMTPSecure');                  // encryption - ssl/tls
            $mail->Port =  env('PHP_MAILER_PORT') ;                          // port - 587/465

            $mail->setFrom( env('PHP_MAILER_USERNAME'), env('PHP_MAILER_FROM_NAME') );
            $mail->addAddress($request->emailRecipient);
            $mail->addCC($request->emailCc);
            $mail->addBCC($request->emailBcc);

           // $mail->addReplyTo('anagoarmandine@gmail.com', 'Dine');

//            if(isset($_FILES['emailAttachments'])) {
//                for ($i=0; $i < count($_FILES['emailAttachments']['tmp_name']); $i++) {
//                    $mail->addAttachment($_FILES['emailAttachments']['tmp_name'][$i], $_FILES['emailAttachments']['name'][$i]);
//                }
//            }


            $mail->isHTML(true);                // Set email content format to HTML

            $mail->Subject = $request->emailSubject;
            $mail->Body    = $request->emailBody;

            // $mail->AltBody = plain text version of email body;

            if( !$mail->send() ) {
                return back()->with("failed", "Email not sent.")->withErrors($mail->ErrorInfo)->withInput();
            }

            else {
                return back()->with("success", "Email has been sent.")->withInput();
            }

        } catch (Exception $e) {
            dd($e);
            return back()->with('error','Message could not be sent.');
        }
    }
}

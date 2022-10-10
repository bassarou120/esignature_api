<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class SendCcMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $cc;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($cc)
    {
        $this->cc = $cc;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
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

            $mail->addAddress($this->cc['email']);

            $mail->isHTML(true);

            $mail->Subject = 'Vous avez recu une copie du document signé';

            $mail->Body   = str_replace(
                array(
                    '[$name]',
                    '[$sending_auth]',
                    '[$doc_title]',
                    '$doc_link'
                ),
                array(
                    $this->cc['mail_detail']['name'],
                    $this->cc['mail_detail']['sending_auth'],
                    $this->cc['mail_detail']['doc_title'],
                    $this->cc['mail_detail']['doc_link']
                ),
                file_get_contents(resource_path('views/mail_template/cc_mail_view.blade.php'))
            );

            if(isset($cc['mail_detail']['doc_link'])) {
                    $mail->addAttachment($this->cc['mail_detail']['doc_title'], $cc['mail_detail']['doc_link']);
            }
            $envois =$mail->send();
            if($envois==false) {
                \Log::info("Erreur de l'envois du mail");
            }
            else {
                \Log::info("Mail envoyer");
            }

        } catch (Exception $e) {
            echo $e;
        }
    }
}

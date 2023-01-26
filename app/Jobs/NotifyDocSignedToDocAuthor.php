<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class NotifyDocSignedToDocAuthor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $detail;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($detail)
    {
        $this->detail = $detail;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        require base_path("vendor/autoload.php");
        $mail = new PHPMailer(true);
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

            $mail->addAddress($this->detail['email']);

            $mail->isHTML(true);

            $mail->Subject = 'Document signé';

            \Log::info($this->detail['doc_link']);
            $mail->Body   = str_replace(
                array(
                    '[$sending_auth]',
                    '[$doc_title]'
                ),
                array(
                    $this->detail['sending_auth'],
                    $this->detail['doc_title'],
                ),
                file_get_contents(resource_path('views/mail_template/doc_signed_mail_view.blade.php'))
            );
            $mail->addAttachment($this->detail['doc_link'],$this->detail['doc_title']);

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

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class NewMember implements ShouldQueue
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

            $mail->addAddress($this->detail['email']);

            $mail->isHTML(true);

            $mail->Subject = 'Joignez une nouvelle équipe';

            $mail->Body   = str_replace(
                array(
                    '[$sending_auth]',
                    '[$id_member]',
                    '[$app_url]',
                    'my_link'
                ),
                array(
                    $this->detail['sending_auth'],
                    $this->detail['id_member'],
                    env('APP_URL'),
                    env('APP_URL').'/api/sendings/member/accept/request/'.$this->detail['id_member'],
                ),
                file_get_contents(resource_path('views/mail_template/new_member_mail_view.blade.php'))
            );

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

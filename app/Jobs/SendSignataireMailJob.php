<?php

namespace App\Jobs;

use App\Models\Statut_Sending;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\DB;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
require base_path("vendor/autoload.php");

class SendSignataireMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $signataires;
    public $statut_signataire;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( $signataires)
    {
        $this->signataires = $signataires;

    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      // send_mail($this->signataires);


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
            $mail->addAddress($this->signataires['email']);
            $mail->isHTML(true);

            $mail->Subject = $this->signataires['subject'];

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
                        '$message'
                    ),
                    array(
                        $this->signataires['mail_detail']['name'],
                        $this->signataires['mail_detail']['sending_auth'],
                        $this->signataires['mail_detail']['doc_title'],
                        isset($this->signataires['mail_detail']['sending_expiration']) ? 'Vous devriez faire cette action avant le '.$this->signataires['mail_detail']['sending_expiration'] : '' ,
                        $this->signataires['mail_detail']['preview'],
                        $this->signataires['id_sending'],
                        $this->signataires['id_signataire'],
                        env('APP_URL'),
                        env('APP_URL').'/api/sendings/doc/opened/'.$this->signataires['id_sending'].'/'.$this->signataires['id_signataire'],
                        isset($this->signataires['message']) ? $this->signataires['message']  : '' ,

                    ),
                    file_get_contents(resource_path('views/mail_template/signataire_mail_view.blade.php'))
                );

            $envois =$mail->send();
            if($envois==false) {
                \Log::info("Erreur de l'envois du mail");
            }
            else {
                \Log::info("Mail envoyer");

                DB::table('statut__sendings')->insert([
                    'id_sending' =>  $this->signataires['id_sending'],
                    'id_signataire' => $this->signataires['id_signataire'],
                    'id_statut' => ENVOYER,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

        } catch (Exception $e) {
            echo $e;
        }

    }
}

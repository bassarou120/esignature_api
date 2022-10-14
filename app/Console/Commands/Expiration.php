<?php

namespace App\Console\Commands;

use App\Models\Sending;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class Expiration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expiration:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expiration des envois';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sending = Sending::join('users', 'sendings.created_by', '=', 'users.id')
            ->where('statut',EN_COURS)->where('expiration','<>','Aucun')->get(['sendings.created_at','sendings.expiration_nbre','users.name']);

        foreach ($sending as $s){
            $today = strtotime(date('Y-m-d'));
            $last_date = explode(' ',$s->cretead_at)[0];
            if ($s->expiration_nbre != null){
                $the_date=date('Y-m-d', strtotime($last_date. ' + '.$s->expiration_nbre.' days'));
                if($today<$the_date){
                    \Log::info("ici");
                    $s->statut=EXPIRER;
                    $s->save();
                }
            }
        }
    }
}

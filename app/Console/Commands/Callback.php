<?php

namespace App\Console\Commands;

use App\Mail\CallbackMail;
use App\Models\Document;
use App\Models\Sending;
use App\Models\Signataire;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class Callback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'callback:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rappelle aux utilisateurs';

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
       ->where('statut',EN_COURS)->where('callback','<>','Aucun')->get(['sendings.*','users.name']);
       foreach ($sending as $s){
           $all_signataire = Signataire::where('id_sending',$s->id)->where('type','Signataire')->get();
           $document = Document::find($s->id_document);
           switch ($s->callback) {
               case 'Personnalisé':
                   $today = strtotime(date('Y-m-d'));
                   $last_date = explode(' ',$s->last_callback_date)[0];
                   $the_date=date('Y-m-d', strtotime($last_date. ' + '.$s->callback_nbre.' days'));

                   if($today==$the_date){
                       foreach ($all_signataire as $a){
                           if($a->mobile_info !=null){
                               $detail =[
                                   'document'=>$document->name,
                                   'author'=>$s->name,
                                   'date'=>$s->created_at,
                               ];
                               $mail = new CallbackMail($detail);
                               Mail::to($s)->send($mail);
                               //sleep(2);
                           }
                       }
                       $s->last_callback_date=date('Y-m-d H:i:s');
                       $s->save();
                   }

                   break;
               case 'Quotidien':
                   foreach ($all_signataire as $a){
                       if($a->mobile_info !=null){
                           $detail =[
                               'document'=>$document->name,
                               'author'=>$s->name,
                               'date'=>$s->created_at,
                           ];
                           $mail = new CallbackMail($detail);
                           Mail::to($s)->send($mail);
                           //sleep(2);
                       }
                   }
                   break;
               case 'Hebdomadaire':
                   $today = strtotime(date('Y-m-d'));
                   $last_date = explode(' ',$s->last_callback_date)[0];
                   $the_date=date('Y-m-d', strtotime($last_date. ' + 7 days'));

                   if($today==$the_date){
                       foreach ($all_signataire as $a){
                           if($a->mobile_info !=null){
                               $detail =[
                                   'document'=>$document->name,
                                   'author'=>$s->name,
                                   'date'=>$s->created_at,
                               ];
                               $mail = new CallbackMail($detail);
                               Mail::to($s)->send($mail);
                               //sleep(2);
                           }
                       }
                       $s->last_callback_date=date('Y-m-d H:i:s');
                       $s->save();
                   }
                   break;
               case 'Mensuel':
                   $today = strtotime(date('Y-m-d'));
                   $last_date = explode(' ',$s->last_callback_date)[0];
                   $the_date=date('Y-m-d', strtotime($last_date. ' + 31 days'));

                   if($today==$the_date){
                       foreach ($all_signataire as $a){
                           if($a->mobile_info !=null){
                               $detail =[
                                   'document'=>$document->name,
                                   'author'=>$s->name,
                                   'date'=>$s->created_at,
                               ];
                               $mail = new CallbackMail($detail);
                               Mail::to($s)->send($mail);
                               //sleep(2);
                           }
                       }
                       $s->last_callback_date=date('Y-m-d H:i:s');
                       $s->save();
                   }
                   break;
           }
       }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\Sending;
use App\Models\Signataire;
use App\Models\Statut_Sending;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DeleteUnregisteredSending extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deletesending:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Suppression des envois non enregistrer';

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
        \Log::info("Cron is working fine!");

        $sending= Sending::where('is_registed',0)
            ->where('created_at','<',date('Y-m-d'))
            ->get();

        foreach ($sending as $s){
            $document = Document::find($s->id_document);
            $doc_path = 'documents/'.$document->file ;
            $sp =explode('.pdf',$document->file);
            $doc_path_copy = 'documents/'.$sp[0].'_copy.pdf' ;
            $doc_img_path = 'previews/'.explode('/',$document->preview)[0];
            File::delete(public_path($doc_path));
            File::delete(public_path($doc_path_copy));
            File::deleteDirectory(public_path($doc_img_path));

            //signataire
            Signataire::where('id_sending', $s->id)->delete();
            //statut__sendings
            Statut_Sending::where('id_sending', $s->id)->delete();

            $s->delete();
        }

    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatuesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('statuses')->insert([
            [
                'name' => 'EN_COURS',
                'description' => 'Document en cours de signature',
                'created_at'      => '2022-07-08 14:23:56',
                'updated_at'      => '2022-07-08 14:23:56',
            ],
            [
                'name' => 'FINIR',
                'description' => 'Document signer',
                'created_at'      => '2022-07-08 14:23:56',
                'updated_at'      => '2022-07-08 14:23:56',
            ],
            [
                'name' => 'INITIALISER',
                'description' => 'Initialisation de la configuration du document',
                'created_at'      => '2022-07-08 14:23:56',
                'updated_at'      => '2022-07-08 14:23:56',
            ],
            [
                'name' => 'VALIDER',
                'description' => 'Valider par le validateur',
                'created_at'      => '2022-07-08 14:23:56',
                'updated_at'      => '2022-07-08 14:23:56',
            ],
            [
                'name' => 'EXPIRER',
                'description' => 'Délai de signature passé',
                'created_at'      => '2022-07-08 14:23:56',
                'updated_at'      => '2022-07-08 14:23:56',
            ],
            [
                'name' => 'ENVOYER',
                'description' => 'E-mail envoyé',
                'created_at'      => '2022-07-08 14:23:56',
                'updated_at'      => '2022-07-08 14:23:56',
            ],
            [
                'name' => 'EMAIL_REMIT',
                'description' => 'E-mail remis',
                'created_at'      => '2022-07-08 14:23:56',
                'updated_at'      => '2022-07-08 14:23:56',
            ],
            [
                'name' => 'OPENED_EMAIL_MESSAGE',
                'description' => 'E-mail ouvert',
                'created_at'      => '2022-07-08 14:23:56',
                'updated_at'      => '2022-07-08 14:23:56',
            ],
            [
                'name' => 'OUVRIR',
                'description' => 'Document ouvert',
                'created_at'      => '2022-07-08 14:23:56',
                'updated_at'      => '2022-07-08 14:23:56',
            ],
            [
                'name' => 'SIGNER',
                'description' => 'Document signer',
                'created_at'      => '2022-07-08 14:23:56',
                'updated_at'      => '2022-07-08 14:23:56',
            ],
            [
                'name' => 'ARCHIVER',
                'description' => 'Document archivés',
                'created_at'      => '2022-07-08 14:23:56',
                'updated_at'      => '2022-07-08 14:23:56',
            ],
            [
                'name' => 'REVOKE',
                'description' => 'Validataire rejette document',
                'created_at'      => '2022-07-08 14:23:56',
                'updated_at'      => '2022-07-08 14:23:56',
            ],
        ]);
    }
}

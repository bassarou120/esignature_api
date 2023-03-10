<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeSignatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('type__signatures')->insert([
            [
                'name' => 'Signature avancée',
                'type' => 'avanced',
                'feature' => 'Test descriptions',
                'created_at'      => '2022-07-08 14:23:56',
                'updated_at'      => '2022-07-08 14:23:56',
            ],
            [
                'name' => 'Signature Simple',
                'type' => 'simple',
                'feature' => 'Signez en un clique',
                'created_at'      => '2022-07-08 14:23:56',
                'updated_at'      => '2022-07-08 14:23:56',
            ],
            [
                'name' => 'Envois recommandés',
                'type' => 'recommandes',
                'feature' => 'Description',
                'created_at'      => '2022-07-08 14:23:56',
                'updated_at'      => '2022-07-08 14:23:56',
            ]
        ]);

    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SendingWidget extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('sending__parameters')->insert([
            [
                'name' => 'signature',
                'label' => 'Signature',
                'icon' => 'fa fa-signature',
                'properties' => null,
                'is_activated' => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'certificate',
                'label' => 'Certificat',
                'icon' => 'fa fa-certificate',
                'properties' => null,
                'is_activated' => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'name',
                'label' => 'Prénom',
                'icon' => 'fa fa-user',
                'properties' => null,
                'is_activated' => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'last_name',
                'label' => 'Nom',
                'icon' => 'fa fa-user',
                'properties' => null,
                'is_activated' => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'city',
                'label' => 'Ville',
                'icon' => 'fa fa-map',
                'properties' => null,
                'is_activated' => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'activity',
                'label' => 'Profession',
                'icon' => 'fa fa-tasks',
                'properties' => null,
                'is_activated' => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'date',
                'label' => 'Date',
                'icon' => 'fa fa-calendar',
                'properties' => null,
                'is_activated' => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'entreprise',
                'label' => 'Entreprise',
                'icon' => 'fa fa-home',
                'properties' => null,
                'is_activated' => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'image',
                'label' => 'Image',
                'icon' => 'fa fa-image',
                'properties' => null,
                'is_activated' => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'text_field',
                'label' => 'Zone de texte',
                'icon' => 'fa fa-detail',
                'properties' => null,
                'is_activated' => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],

        ]);
    }
}

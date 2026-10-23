<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\AdminModel;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $model = new AdminModel();
        $model->insert([
            'auser'  => 'adminchina2',
            'aemail' => 'adminchina2@gmail.com',
            'apass'  => '123456',
            'adob'   => '1994-12-06',
            'aphone' => '5554539391',
        ]);
    }
}

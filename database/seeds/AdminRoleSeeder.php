<?php

use Illuminate\Database\Seeder;

class AdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('admin_role')->insert(
            array(
                array(
                      'role_name' => '技术人员',
                      'authid_set' => '1,2,3,4,5,6,7,8,9,10,11,12',
                    ),
                array(
                      'role_name' => '运营',
                      'authid_set' => '5,6,7,8,9,10',
                    ),
                )
            );
    }
}

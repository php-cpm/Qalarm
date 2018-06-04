<?php

use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('admin_user')->insert(
            array(
                array(
                    'roleid_set' => '1,2',
                    'username' => 'chenfei',
                    'password' => '2f393508c2e8db02c4c69111887b9276',
                    'nickname' => '陈飞',
                    'mobile' => '13658364971',
                    'cityid' => '0',
                    'cityids' => '0,12',
                    ),
                array(
                    'roleid_set' => '1',
                    'username' => 'baibing',
                    'password' => '2f393508c2e8db02c4c69111887b9276',
                    'nickname' => '白冰',
                    'mobile' => '13658364971',
                    'cityid' => '0',
                    'cityids' => '0,12',
                    ),
                array(
                    'roleid_set' => '2',
                    'username' => 'admin',
                    'password' => '2f393508c2e8db02c4c69111887b9276',
                    'nickname' => '管理员',
                    'mobile' => '13658364971',
                    'cityid' => '0',
                    'cityids' => '0,12',
                    )
                )
            );
    }
}

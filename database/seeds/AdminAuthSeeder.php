<?php

use Illuminate\Database\Seeder;

class AdminAuthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('admin_auth')->insert(
            array(
                array(
                    'mid' => 1,
                    'sid' => 0,
                    'auth_name' => '系统管理',
                    'auth_url'  => '',
                    'icon_class'=> 'glyphicon glyphicon-stats icon text-primary-dker',
                    'badge_class'=> 'label bg-primary pull-right',
                    'badge_name' => '',
                ),
                array(
                    'mid' => 1,
                    'sid' => 1,
                    'auth_name' => '用户管理',
                    'auth_url'  => 'app.admin.user',
                    'icon_class'=> '',
                    'badge_class'=> 'label bg-info pull-right',
                    'badge_name' => '',
                ),
                array(
                    'mid' => 1,
                    'sid' => 2,
                    'auth_name' => '权限管理',
                    'auth_url'  => 'app.admin.auth',
                    'icon_class'=> '',
                    'badge_class'=> 'label bg-info pull-right',
                    'badge_name' => '',
                ),
                array(
                    'mid' => 1,
                    'sid' => 3,
                    'auth_name' => '角色管理',
                    'auth_url'  => 'app.admin.role',
                    'icon_class'=> '',
                    'badge_class'=> 'label bg-info pull-right',
                    'badge_name' => '',
                ),
                array(
                    'mid' => 2,
                    'sid' => 0,
                    'auth_name' => '运营系统',
                    'auth_url'  => '',
                    'icon_class'=> 'glyphicon icon-user-following  icon text-info-lter',
                    'badge_class'=> 'label bg-info pull-right',
                    'badge_name' => '',
                ),
                array(
                    'mid' => 2,
                    'sid' => 1,
                    'auth_name' => '卡券管理',
                    'auth_url'  => 'app.opertor.coupon',
                    'icon_class'=> '',
                    'badge_class'=> 'label bg-info pull-right',
                    'badge_name' => '',
                ),
                array(
                    'mid' => 2,
                    'sid' => 2,
                    'auth_name' => '活动管理',
                    'auth_url'  => 'app.opertor.active',
                    'icon_class'=> '',
                    'badge_class'=> 'label bg-info pull-right',
                    'badge_name' => '',
                ),
                array(
                    'mid' => 2,
                    'sid' => 3,
                    'auth_name' => '推广管理',
                    'auth_url'  => '',
                    'icon_class'=> '',
                    'badge_class'=> 'label bg-info pull-right',
                    'badge_name' => '',
                ),
                array(
                    'mid' => 2,
                    'sid' => 4,
                    'auth_name' => '推送管理',
                    'auth_url'  => 'app.opertor.push',
                    'icon_class'=> 'glyphicon icon-user-following  icon text-info-lter',
                    'badge_class'=> 'label bg-info pull-right',
                    'badge_name' => '',
                ),
                array(
                    'mid' => 2,
                    'sid' => 5,
                    'auth_name' => '数据分析',
                    'auth_url'  => '',
                    'icon_class'=> '',
                    'badge_class'=> 'label bg-info pull-right',
                    'badge_name' => '',
                ),
                array(
                    'mid' => 3,
                    'sid' => 0,
                    'auth_name' => '用户中心',
                    'auth_url'  => '',
                    'icon_class'=> 'glyphicon icon-user-following  icon text-info-lter',
                    'badge_class'=> 'label bg-info pull-right',
                    'badge_name' => '',
                ),
                array(
                    'mid' => 3,
                    'sid' => 1,
                    'auth_name' => '用户信息',
                    'auth_url'  => 'app.user.list',
                    'icon_class'=> '',
                    'badge_class'=> 'label bg-info pull-right',
                    'badge_name' => '',
                ),
            ));
    }
}

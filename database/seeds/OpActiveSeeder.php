<?php

use Illuminate\Database\Seeder;

class OpActiveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('op_active')->insert(
            array(
                array(
                    'active_name'         => '七夕情',
                    'active_code'         => '1234567890',
                    'active_alise'        => '七夕',
                    'shared'              => 1,
                    'coupon_bind_code'    => '1234567',
                    'coupon_quota'        => 3000,
                    'coupon_take_count'   => 100,
                    'coupon_use_count'    => 12,
                    'coupon_use_way'      => 1,
                    'cityids'             => '1,2',
                    'active_url'          => 'http://demo.com',
                    'status'              => 1,

                    'start_time'         => '2015-09-12',
                    'end_time'           => '2015-10-10',
                    
                    'target'              => 1, 
                    'admin_user_id'      => 1,
                    'admin_user_name'     => 'cf',
                    )
                )
            );
    }
}

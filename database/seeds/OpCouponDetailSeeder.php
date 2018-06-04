<?php

use Illuminate\Database\Seeder;

class OpCouponDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('op_coupon_detail')->insert(
            array(
                array(
                    'coupon_id'           => 1,
                    'coupon_type'         => 10, //代金券
                    'buss_type'           => 10, //车服务
                    'coupon_code'         => '2131313131', //优惠券码
                    'cityids'             => '1',
                    'status'              => 1,
                    'send_time'           => '2015-10-01',
                    'user_id'             => 1,
                    'user_mobile'         => '13658364971',

                    'valid_begin'         => '2015-09-12',
                    'valid_end'           => '2015-10-10',
                    'goods_name'          => '',
                    'admin_user_id'       => 1,
                    'admin_user_name'     => 'cf',
                    ),
                array(
                    'coupon_id'           => 2,
                    'coupon_type'         => 10, //代金券
                    'buss_type'           => 11, //车服务
                    'coupon_code'         => '1213131', //优惠券码
                    'cityids'             => '1',
                    'status'              => 1,
                    'send_time'           => '2015-10-01',
                    'user_id'             => 1,
                    'user_mobile'         => '13658364971',

                    'valid_begin'         => '2015-09-12',
                    'valid_end'           => '2015-10-10',
                    'goods_name'          => '',
                    'admin_user_id'       => 1,
                    'admin_user_name'     => 'cf',
                    )
                )
            );
    }
}

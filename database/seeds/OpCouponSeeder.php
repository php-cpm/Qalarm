<?php

use Illuminate\Database\Seeder;

class OpCouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('op_coupon')->insert(
            array(
                array(
                    'coupon_name'         => '注册送20代金券',
                    'coupon_bind_code'    => '1234567',
                    'coupon_type'      => 10, //代金券
                    'buss_type'           => 10, //车服务
                    'coupon_x'            => 20,
                    'usage_condition'     => 1, //首次搭乘
                    'coupon_workday_type' => 0, 
                    'coupon_rushhour_type'=> 0,
                    'car_brand'           => '',
                    'cityids'             => '1',
                    'valid_begin'         => '2015-09-12',
                    'valid_end'           => '2015-10-10',
                    'valid_days'          => 29,  //领取之后的有效天数
                    'goods_name'          => '',
                    'admin_user_id'      => 1,
                    'admin_user_name'     => 'cf',
                    )
              //  array(
              //      'coupon_name'         => '任意金额代金券',
              //      'coupon_bind_code'    => '1234569',
              //      'coupon_type'         => 14, //任意金额代金券
              //      'buss_type'           => 10, //车服务
              //      'coupon_x'            => 0,
              //      'usage_condition'     => 1, //首次搭乘
              //      'coupon_workday_type' => 0, 
              //      'coupon_rushhour_type'=> 0,
              //      'car_brand'           => '',
              //      'cityids'             => '1',
              //      'valid_begin'         => '2015-09-12',
              //      'valid_end'           => '',
              //      'valid_days'          => 0,  //领取之后的有效天数
              //      'goods_name'          => '',
              //      'admin_user_id'      => 1,
              //      'admin_user_name'     => 'cf',
              //      )
                )
            );
    }
}

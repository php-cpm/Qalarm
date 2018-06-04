<?php

namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;

use App\Components\Utils\Constants;

class OpActive extends Gaea
{
     protected $table = 'op_active';  

     protected $dates = ['start_time', 'end_time'];

     public function coupon()
     {
         return $this->belongsTo('App\Models\Gaea\OpCoupon', 'coupon_bind_code', 'coupon_bind_code');
     }

     public function export()
     {
         $cp = $this->coupon;
         $ctype = Constants::$COUPON_TYPE[$cp->coupon_type];
         $btype = Constants::$BUSS_TYPE[$cp->buss_type];
         $couponDesc = $ctype['mname'].'-'.$ctype['sname'];
         $bussDesc   = $btype['mname'].'-'.$btype['sname'];

         $data = [
            "active_name"      =>  $this->active_name,
            "active_code"      =>  $this->active_code,
            "active_alise"     =>  $this->active_alise, 
            "shared"           =>  $this->shared,
            "coupon"           =>  [ 
                "coupon_type"  =>  $cp->coupon_type,
                "coupon_desc"  =>  $couponDesc,
                "buss_type"    =>  $cp->buss_type, 
                "buss_desc"    =>  $bussDesc,
                "argv1"        =>  $cp->coupon_x,
                "argv2"        =>  $cp->coupon_y
            ],
            "coupon_quota"     =>  $this->coupon_quota,
            "coupon_take_count"=>  $this->coupon_take_count,
            "coupon_use_count" =>  $this->coupon_use_count,
            "coupon_use_way"   =>  $this->coupon_use_way,
            "cityids"          =>  $this->cityids,
            "active_url"       =>  $this->active_url,
            "status"           =>  $this->status,
            "start_time"       =>  $this->start_time->timestamp,
            "end_time"         =>  $this->end_time->timestamp
         ];

         return $data;
     }
}

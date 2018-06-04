<?php

namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;

use App\Components\Utils\Constants;

class OpCoupon extends Gaea
{
     protected $table = 'op_coupon';  

     public function coupon_detail()
     {
         return $this->hasMany('App\Models\Gaea\OpCouponDetail', 'coupon_id', 'id');
     }
     
     public function export()
     {
         $ctype = Constants::$COUPON_TYPE[$this->coupon_type];
         $btype = Constants::$BUSS_TYPE[$this->buss_type];
         $couponDesc = $ctype['mname'].'-'.$ctype['sname'];
         $bussDesc   = $btype['mname'].'-'.$btype['sname'];

         $data = [
            "id"               =>  $this->id,
            "coupon_name"      =>  $this->coupon_name,
            "coupon_type_name" =>  $this->couponDesc,
            "buss_type_name"   =>  $this->bussDesc, 
            "coupon_bind_code" =>  $this->coupon_bind_code,
            "argv1"            =>  $this->coupon_x,
            "argv2"            =>  $this->coupon_y,
         ];

         return $data;
     }
}

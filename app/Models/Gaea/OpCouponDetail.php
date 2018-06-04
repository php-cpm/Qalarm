<?php

namespace App\Models\Gaea;

use Illuminate\Database\Eloquent\Model;
use App\Components\Utils\Constants;
use Carbon\Carbon;

class OpCouponDetail extends Gaea
{

    const COUPON_DETAIL_STATUS_NOT_ACTIVE = 0;
    const COUPON_DETAIL_STATUS_NOT_USE    = 1;
    const COUPON_DETAIL_STATUS_OCCUPY     = 2;
    const COUPON_DETAIL_STATUS_USED       = 3;
    const COUPON_DETAIL_STATUS_EXPIRE     = 4;


    protected $table = 'op_coupon_detail';  
    protected $dates = ['valid_begin', 'valid_end', 'send_time', 'usetime', 'refund_time'];

    public function getStatusNameAttribute($value)
    {
        static $dict = [
            self::COUPON_DETAIL_STATUS_NOT_ACTIVE       => '未激活',
            self::COUPON_DETAIL_STATUS_NOT_USE          => '未使用',
            self::COUPON_DETAIL_STATUS_OCCUPY           => '被占用',
            self::COUPON_DETAIL_STATUS_USED             => '已使用',
            self::COUPON_DETAIL_STATUS_EXPIRE           => '已失效',
            ];

        return $dict[$value];
    }

    public function getBussName($value)
    {
        $buss = Constants::$BUSS_TYPE[$value];
        return $buss['mname'].'-'.$buss['sname'];
    }

    public function coupon()
    {
        return $this->belongsTo('App\Models\Gaea\OpCoupon', 'coupon_id', 'id');
    }

    public function export()
    {
        $data = [
            'id'                 => $this->id,
            'userid'             => $this->user_id,
            'buss_type'          => $this->buss_type,
            'coupon_type'        => $this->coupon_type,
            'argv1'              => $this->coupon_type == (Constants::COUPON_TYPE_MANUAL || Constants::CAR_LIFE) ? (int)$this->remark:$this->coupon->coupon_x,
            'argv2'              => $this->coupon_type == (Constants::COUPON_TYPE_MANUAL || Constants::CAR_LIFE) ? 0:$this->coupon->coupon_y,
            'coupon_name'        => $this->coupon_type == (Constants::COUPON_TYPE_MANUAL || Constants::CAR_LIFE) ? $this->remark.'元代金券':$this->coupon->coupon_name,
            'send_reason'        => $this->send_reason,
            'status'             => $this->status,
            'valid_begin'        => $this->valid_begin->timestamp,
            'valid_end'          => $this->valid_end->timestamp
        ];

        return $data;
    }
    
    public function exportManualCoupon()
    {
        $data = [
            'id'                 => $this->id,
            'buss_name'          => $this->getBussName($this->buss_type),
            'argv1'              => (int)$this->remark,
            'send_reason'        => $this->send_reason,
            'status'             => $this->getStatusNameAttribute($this->status),
            'send_time'          => $this->send_time->toDateTimeString(),
            'valid_time'         => $this->valid_end->toDateTimeString(),
            'admin_name'         => $this->admin_user_name,
        ];

        // FIXME
        $data['user_info'] = $this->user_mobile;

        return $data;
    }

    public function statusThransfer($opertor)
    {
        switch($opertor) {
        case 'use':
            if ($this->status == self::COUPON_DETAIL_STATUS_NOT_USE) {
                $this->status = self::COUPON_DETAIL_STATUS_OCCUPY;
                $this->save();
                return true;
                
            } else {
                return false;
            }

            break;
        case 'done':
            if ($this->status == self::COUPON_DETAIL_STATUS_OCCUPY) {
                $this->status = self::COUPON_DETAIL_STATUS_USED;
                $this->usetime= Carbon::now();
                $this->save();
                return true;
                
            } else {
                return false;
            }
            break;
        case 'reback':
            if ($this->status == self::COUPON_DETAIL_STATUS_OCCUPY) {
                $this->status = self::COUPON_DETAIL_STATUS_NOT_USE;
                $this->refund_time = Carbon::now();

                $this->save();
                return true;
                
            } else {
                return false;
            }
            break;
        }

    }
}

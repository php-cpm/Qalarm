<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $table = 'user_settings';

    const CREATED_AT = 'createtime';
    const UPDATED_AT = 'updatetime';

    const PUSH_ENABLE = 1;

    const TIMETYPE_ALL_DAY          = 1;
    const TIMETYPE_SPECIFIC_TIME    = 2;

    // 指定时间的默认值
    const GOCOMPANY_START_TIME      = 0;        // 00:00
    const GOCOMPANY_END_TIME        = 43200;    // 12:00
    const GOHOME_START_TIME         = 43200;    // 12:00
    const GOHOME_END_TIME           = 86400;    // 24:00

    // 全天
    const GOCOMPANY_ALL_START_TIME  = 0;        // 00:00
    const GOCOMPANY_ALL_END_TIME    = 43200;    // 12:00
    const GOHOME_ALL_START_TIME     = 43200;    // 12:00
    const GOHOME_ALL_END_TIME       = 86400;    // 24:00

    // 格式转换
    protected $casts = [
        'home_location'     => 'json',
        'company_location'  => 'json',
        ];
}

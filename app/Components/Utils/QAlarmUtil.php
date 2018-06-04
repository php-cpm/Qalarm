<?php
namespace App\Components\Utils;

use Log;
use RuntimeException;

use Client\Qalarm\Qalarm;

class QAlarmUtil
{
    const P_NAME          = 'qalarm';
    const MOD_SLOW        = 'slow';
    const MOD_SMS         = 'sms';
    const MOD_OTHER       = 'other';
    const MOD_EXCEPTION   = 'exception';

    public static function send(  
                                $module, 
                                $code,
                                $message,
                                $server_ip = "",
                                $client_ip = "",
                                $script = ""
                            )

    {
        $env = Qalarm::ENV_SIT;
        if (app()->environment('production', 'staging')) {
            $env = Qalarm::ENV_PROD;
        }

        return Qalarm::send(self::P_NAME, $module, $code, $message, $env, $server_ip, $client_ip, $script);
    }
}

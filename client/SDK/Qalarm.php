<?php

namespace Client\Qalarm;

class Qalarm
{/*{{{*/
    const LOCAL_LOG_DIR = '/var/wd/wrs/logs/alarm/';
    const PUB_URL       = 'http://qalarm.infra.intra.ffan.com/api/pub';
    const FILE_PERM     = 0755;
    const LINE_MAX_SIZE = 102400;

    const ENV_SIT     = 'sit';
    const ENV_TEST    = 'test';
    const ENV_PRE     = 'pre';
    const ENV_PROD    = 'prod';

    const METHOD_SYNC = 'sync';
    const METHOD_ASYNC= 'async';

    private static $alarm_data = null;
    private static $async = true;

    public static function setSync()
    {
        static::$async = false;
    }

    public static function send($project,
                                $module,
                                $code,
                                $message,
                                $env         = self::ENV_PROD,
                                $server_ip   = '',
                                $client_ip   = '',
                                $script      = ''
                                )
    {/*{{{*/

        $timestamp = time();

        if (empty($server_ip)) {
            $server_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : php_uname('n');
        }

        if (empty($client_ip)) {
            $client_ip = empty($GLOBALES['HTTP_POST_VARS']['client_ip']) ? (isset($_SERVER['REMOTE_ADDR'])? $_SERVER['REMOTE_ADDR'] : '' ) : $GLOBALES['HTTP_POST_VARS']['client_ip'];
            $client_ip = empty($client_ip) ? '172.0.0.1' : $client_ip;
        }
        
        $url = empty($_SERVER['REQUEST_URI']) ? $_SERVER['SCRIPT_NAME'] : $_SERVER['REQUEST_URI'];

        if (empty($script)) {
            $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            $caller = array_merge($stack[0], isset($stack[1])?$stack[1]:[], isset($stack[2])?$stack[2]:[]);
            $caller_class = isset($caller['file'])?$caller['file']:'';
            $caller_line  = isset($caller['line'])?$caller['line']:'';
            
            $script = "$caller_class:$caller_line";
        }

        if ($script == ':') {
            $script = $url;
        }


        $cookie = isset($_SERVER['cookie']) ? $_SERVER['cookie'] : array();

        self::$alarm_data = $data = array(
            'project'      => $project,
            'module'       => $module,
            'code'         => $code,
            'env'          => $env,
            'time'         => $timestamp,
            'server_ip'    => $server_ip,
            'client_ip'    => $client_ip,
            'script'       => $script,
            'message'      => $message,
            'url'          => $url,
            'post_data'    => $_POST,
            'cookie'       => $cookie
        );

        if (static::$async) {
            return self::output($data);
        } else {
            return self::pub($data);
        }

    }/*}}}*/

    private static function output($data)
    {/*{{{*/
        $msg = json_encode($data);
        $log_file = self::LOCAL_LOG_DIR . 'alarm.log';
        if (!is_file($log_file)) {
            touch($log_file);
            chmod($log_file, self::FILE_PERM);
        }
        file_put_contents($log_file, $msg ."\n", FILE_APPEND|LOCK_EX);

        return true;
    }/*}}}*/

    private static function pub($data)
    {/*{{{*/
        $msg = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::PUB_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_POST, true);
        $postdata = http_build_query($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $response = curl_exec($ch);
        var_dump($response);
        if ($response === false) {
            $response['errno'] = curl_errno($ch);
            $response['errmsg'] = curl_error($ch);
        }
        curl_close($ch);
        return $response;
    }/*}}}*/
}/*}}}*/

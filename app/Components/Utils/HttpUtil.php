<?php
namespace App\Components\Utils;

use RuntimeException;

/**
 * 使用curl进行HTTP操作
 *
 * @see http://php.net/manual/zh/ref.curl.php
 * @author wufan
 */
class HttpUtil
{

    public static function get($url, $params, $is_res_json = true, $timeOutSecond = 5)
    {
        $query_string = '';
        if (is_array($params)) {
            $query_string = http_build_query($params);
        } else {
            $query_string = strval($params);
        }

        if (stripos($url, '?') === false) {
            $url = $url . '?' . $query_string;
        } else {
            $url = $url . '&' . $query_string;
        }

        $res = static::httpRequest($url, null, [], $timeOutSecond);
        return static::getResult($res, $is_res_json, $url);
    }

    public static function post($url, $params, $is_request_json = true, $is_response_json = true, $timeOutSecond = 5)
    {
        if ($is_request_json) {
            $res = static::httpRequest($url, json_encode($params), ['Content-type:application/json'], $timeOutSecond);
        } else {
            $res = static::httpRequest($url, http_build_query($params), [], $timeOutSecond);
        }
        return static::getResult($res, $is_response_json, $url);
    }

    public static function httpRequest($url, $postdata = null, $headers = [], $timeOutSecond = 0)
    {
        // Init
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Postdata
        if ($postdata) {
            curl_setopt($ch, CURLOPT_POST, true);
            if (is_array($postdata)) {
                $postdata = http_build_query($postdata);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);


        }

        // Header
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // Timeout
        if (!is_int($timeOutSecond) || $timeOutSecond == 0) {
            $timeOutSecond = 5;
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOutSecond);

        // Execute
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        if ($response === false) {
            $errno = curl_errno($ch);
            $errmsg = curl_error($ch);
        }
        curl_close($ch);
        return $response;
    }

    private static function getResult($res, $is_response_json, $url)
    {
        if (!$res) {
            return false;
        }
        if ($is_response_json) {
            $data = json_decode($res, true);
            if (!$data) {
                Log::warning(sprintf('url:%s  respone json decode failed', $url));
                return false;
            } else {
                return $data;
            }
        } else {
            return $res;
        }
    }
}

<?php

namespace App\Components\Utils;
/**
 * 单个CURL请求类
 * @author willas
 * @version 1.1
 *
 */
class CurlUtil
{
    /**
     * curl句柄
     * @var resource
     */
    private $curl = null;

    /**
     * URL地址
     * @var string
     */
    private $url = '';

    /**
     * curl选项数组。index => array('option' => curl option, 'value' => curl option value)
     * @var array
     */
    private $options = array();

    /**
     * 请求失败时的重试次数,默认不重试
     * @var int
     */
    private $retry = 0;

    /**
     * 请求超时时间
     * @var int
     */
    private $timeout = 30;

    /**
     * host地址
     * @var string
     */
    private $host = '';

    /**
     * 是否进行返回获取的信息
     * @var bool
     */
    private $returnTransfer = true;

    /**
     * 是否允许重定向
     * @var bool
     */
    private $followLocation = true;

    /**
     * 重定向的最大次数
     * @var int
     */
    private $maxRedirect = 3;

    /**
     * 是否获取文件的头信息
     * @var bool
     */
    private $header = false;

    /**
     * 是否获取HTML的body信息
     * @var bool
     */
    private $body = true;

    /**
     * POST数据
     * @var array
     */
    private $postFields = array();

    /**
     * 用户代理
     * @var string
     */
    private $userAgent = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1)';

    /**
     * HTTP HEADER信息
     * @var array
     */
    private $httpHeader = array();

    /**
     * curl资源信息
     * @var array
     */
    private $info = array();

    /**
     * curl POST 参数格式,默认否; true是json,false 否
     * @var bool
     */
    private $isJsonParams = false;

    /**
     * 构造函数
     * @param string $url 要请求的URL地址
     */
    public function __construct($url)
    {
        $this->url = $url;
        $this->curl = curl_init($url);
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        curl_close($this->curl);
    }

    /**
     * 设置请求失败时的重试次数
     * @param int $retry 重试次数
     */
    public function setRetry($retry)
    {
        $this->retry = $retry;
    }

    /**
     * 设置请求超时时间
     * @param int $timeout 超时时间，单位为秒
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * 设置是否以文件流的形式返回获取的信息
     * @param bool $returnTransfer true表示是，false表示否
     */
    public function setReturnTransfer($returnTransfer)
    {
        $this->returnTransfer = $returnTransfer;
    }

    /**
     * 设置重定向参数
     * @param bool $followLocation 是否重定向
     * @param int  $maxRedirect    最大重定向次数
     */
    public function setRedirect($followLocation, $maxRedirect = 3)
    {
        $this->followLocation = $followLocation;
        $this->maxRedirect = $maxRedirect;
    }

    /**
     * 设置是否获取文件的header
     * @param bool $header
     */
    public function setHeader($header)
    {
        $this->header = $header;
    }

    /**
     * 设置是否获取HTML的body
     * @param bool $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * 设置POST数据
     * @param array $postFields POST数据数组
     */
    public function setPostFields(array $postFields)
    {
        $this->postFields = $postFields;
    }

    /**
     * 设置用户代理
     * @param string $userAgent 用户代理
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }

    /**
     * 设置HTTP HEADER信息
     * @param array $httpHeader HTTP HEADER
     */
    public function setHttpHeader($httpHeader)
    {
        $this->httpHeader = $httpHeader;
    }

    /**
     * curl POST参数格式,默认否; true是json,false 否
     * @param bool $isJsonParams
     */
    public function setIsJsonParams($isJsonParams)
    {
        $this->isJsonParams = $isJsonParams;
    }

    /**
     * 获取curl资源信息
     * @return array curl资源信息数组
     */
    public function getInfo()
    {
        return (array)$this->info;
    }

    /**
     * 增加一个curl选项。程序将按照增加的顺序依次设置各个选项。
     * 如果这里设置的curl选项与其它函数设置的参数不一致，以这个函数设置的curl选项为准
     * @param int   $option curl选项
     * @param mixed $value  curl选项值
     */
    public function addOption($option, $value)
    {
        $this->options[] = array('option' => $option, 'value' => $value);
    }

    /**
     * 设置所有选项。对于一些常用的选项，如果用户不设置，就采用默认值
     */
    private function applyOptions()
    {
        $appliedOptions = array();

        foreach ($this->options as $option) {
            $appliedOptions[$option['option']] = $option['value'];
        }

        if ($this->returnTransfer && !array_key_exists(CURLOPT_RETURNTRANSFER, $appliedOptions)) {
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        }

        if (0 !== $this->timeout && !array_key_exists(CURLOPT_TIMEOUT, $appliedOptions)) {
            curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);
        }

        if ($this->followLocation && !array_key_exists(CURLOPT_FOLLOWLOCATION, $appliedOptions)) {
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
        }

        if (0 !== $this->maxRedirect && !array_key_exists(CURLOPT_MAXREDIRS, $appliedOptions)) {
            curl_setopt($this->curl, CURLOPT_MAXREDIRS, $this->maxRedirect);
        }

        if ($this->header && !array_key_exists(CURLOPT_HEADER, $appliedOptions)) {
            curl_setopt($this->curl, CURLOPT_HEADER, true);
        }

        if (!$this->body && !array_key_exists(CURLOPT_NOBODY, $appliedOptions)) {
            curl_setopt($this->curl, CURLOPT_NOBODY, true);
        }

        if (!empty($this->postFields) && !array_key_exists(CURLOPT_POST, $appliedOptions)) {
            curl_setopt($this->curl, CURLOPT_POST, true);
            //curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($this->postFields));
            if($this->isJsonParams){
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($this->postFields));
            }else{
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($this->postFields));
            }

        }

        if ('' !== $this->userAgent && !array_key_exists(CURLOPT_USERAGENT, $appliedOptions)) {
            curl_setopt($this->curl, CURLOPT_USERAGENT, $this->userAgent);
        }

        if (!empty($this->httpHeader) && !array_key_exists(CURLOPT_HTTPHEADER, $appliedOptions)) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->httpHeader);
        }

        foreach ($this->options as $option) {
            curl_setopt($this->curl, $option['option'], $option['value']);
        }
    }

    /**
     * 分析HTTP header字符串
     *
     * @param  string $header HTTP header字符串
     * @return array HTTP header数组
     */
    private function parseHeader($header)
    {
        $lines  = explode("\r\n", $header);
        $result = array();

        foreach ($lines as $index => $line) {
            $line = trim($line);

            if ('' === $line) {
                continue;
            }

            if (0 == $index) {
                $parts = explode(' ', $line);
                $result['HTTP-Version']  = $parts[0];
                $result['Status-Code']   = $parts[1];
                $result['Reason-Phrase'] = $parts[2];
                continue;
            }

            $parts = explode(':', $line);
            $key   = trim($parts[0]);
            $value = trim($parts[1]);

            if (isset($result[$key])) {
                if (is_array($result[$key])) {
                    $result[$key][] = $value;
                } else {
                    $result[$key] = array($result[$key], $value);
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 执行一个请求并返回结果
     * @return array 返回结果数组，格式：
     *               array(
     *                   'http_code' => HTTP代码，
     *                   'errno'     => curl返回的错误码，
     *                   'errmsg'    => curl返回的错误消息，
     *                   'header'    => HTTP的header（如果设置了返回header），
     *                   'data'      => 实际数据
     *               )
     */
    public function execute()
    {
        $this->applyOptions();
        $tryTime = $this->retry + 1;
        $return = array('http_code' => 0, 'errno' => 0, 'errmsg' => '', 'data' => '');

        for ($i = 0; $i < $tryTime; $i++) {
            $result = curl_exec($this->curl);
            $this->info = curl_getinfo($this->curl);

            $return['http_code'] = $this->info['http_code'];
            $return['errno']     = curl_errno($this->curl);
            $return['errmsg']    = curl_error($this->curl);

            if (false !== $result) {
                if ($this->header) {
                    $return['header'] = $this->parseHeader(substr($result, 0, $this->info['header_size']));
                    $return['data']   = substr($result, $this->info['header_size']);
                } else {
                    $return['data']   = $result;
                }
                break;
            }
        }

        return $return;
    }

    /**
     * 下载一个文件
     * @param  string $filePath 文件保存路径
     * @return array  返回结果数组，格式：
     *                array(
     *                    'http_code' => HTTP代码，
     *                    'errno'     => curl返回的错误码，
     *                    'errmsg'    => curl返回的错误消息，
     *                    'header'    => HTTP的header（如果设置了返回header），
     *                    'data'      => 实际数据（此时为空）
     *                )
     */


    public function download($filePath)
    {
        $fileHandle = fopen($filePath, 'w');
        $this->options[] = array('option' => CURLOPT_FILE, 'value' => $fileHandle);
        $result = $this->execute();
        fclose($fileHandle);
        return $result;
    }
}

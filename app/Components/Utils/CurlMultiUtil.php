<?php

namespace App\Components\Utils;
/**
 * 多个CURL请求类，这些CURL请求将并发执行
 * @author willas
 * @version 1.1
 *
 */
class CurlMultiUtil
{
    const CURL_MULTI_SELECT_ERROR = -1;

    /**
     *
     * curl_multi句柄
     * @var resource
     */
    private $curlMulti = null;

    /**
     *
     * 多个curl句柄
     * @var array
     */
    private $curls = array();

    /**
     *
     * 多个URL地址
     * @var array
     */
    private $urls = array();

    /**
     *
     * 请求失败的URL地址
     * @var array
     */
    private $badUrls = array();

    /**
     *
     * 执行并发请求后的返回结果
     * @var array
     */
    private $return = array();

    /**
     *
     * 请求并发数
     * @var int
     */
    private $concurrentNumber = 50;

    /**
     *
     * curl选项数组
     * @var array
     */
    private $options = array();

    /**
     *
     * 请求失败时的重试次数
     * @var int
     */
    private $retry = 2;

    /**
     *
     * 请求超时时间
     * @var int
     */
    private $timeout = 30;

    /**
     *
     * host地址
     * @var string
     */
    private $host = '';

    /**
     *
     * 是否进行返回获取的信息
     * @var bool
     */
    private $returnTransfer = true;

    /**
     *
     * 是否允许重定向
     * @var bool
     */
    private $followLocation = true;

    /**
     *
     * 重定向的最大次数
     * @var unknown_type
     */
    private $maxRedirect = 3;

    /**
     *
     * 是否获取文件的header
     * @var bool
     */
    private $header = false;

    /**
     *
     * 是否获取HTML的body
     * @var bool
     */
    private $body = true;

    /**
     *
     * POST数据
     * @var array
     */
    private $postFields = array();

    /**
     *
     * 用户代理
     * @var string
     */
    private $userAgent = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1)';

    /**
     *
     * 认为请求成功的HTTP代码
     * @var unknown_type
     */
    private $allowedHttpCodes = array(200);

    /**
     * HTTP HEADER信息
     * @var array
     */
    private $httpHeader = array();

    /**
     *
     * curl资源信息。url => info array
     * @var array
     */
    private $info = array();

    /**
     *
     * 构造函数
     * @param string $url 要请求的URL地址
     */
    public function __construct($urls)
    {
        $this->urls = $urls;
        $this->curlMulti = curl_multi_init();
    }

    /**
     *
     * 析构函数
     */
    public function __destruct()
    {
        curl_multi_close($this->curlMulti);
    }

    /**
     *
     * 设置请求并发数
     * @param int $concurrentNumber 请求并发数
     */
    public function setConcurrentNumber($concurrentNumber)
    {
        $this->concurrentNumber = $concurrentNumber;
    }

    /**
     *
     * 设置请求失败时的重试次数
     * @param int $retry 重试次数
     */
    public function setRetry($retry)
    {
        $this->retry = $retry;
    }

    /**
     *
     * 设置请求超时时间
     * @param int $timeout 超时时间，单位为秒
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     *
     * 设置是否返回获取的信息
     * @param bool $returnTransfer true表示是，false表示否
     */
    public function setReturnTransfer($returnTransfer)
    {
        $this->returnTransfer = $returnTransfer;
    }

    /**
     *
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
     *
     * 设置是否获取文件的header
     * @param unknown_type $header
     */
    public function setHeader($header)
    {
        $this->header = $header;
    }

    /**
     *
     * 设置是否获取HTML的body
     * @param unknown_type $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     *
     * 设置POST数据
     * @param string $url URL地址
     * @param array $postFields POST数据数组
     */
    public function setPostFields($url, array $postFields)
    {
        $this->postFields[$url] = $postFields;
    }

    /**
     *
     * 设置用户代理
     * @param string $userAgent 用户代理
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }

    /**
     *
     * 设置认为请求成功的HTTP代码
     * @param array $httpCodes HTTP代码
     */
    public function setAllowedHttpCodes(array $httpCodes)
    {
        $this->allowedHttpCodes = $httpCodes;
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
     *
     * 获取curl资源信息
     * @param  string $url URL地址
     * @return array  curl资源信息数组
     */
    public function getInfo($url = '')
    {
        if ('' != $url && array_key_exists($url, $this->info)) {
            return $this->info[$url];
        }

        return $this->info;
    }

    /**
     *
     * 增加一个curl选项。程序将按照增加的顺序依次设置各个选项。
     * 如果这里设置的curl选项与其它函数设置的参数不一致，以这个函数设置的curl选项为准
     * @param string       $url    URL地址
     * @param unknown_type $option curl选项
     * @param mixed        $value  curl选项值
     */
    public function addOption($url, $option, $value)
    {
        $this->options[$url][] = array('option' => $option, 'value' => $value);
    }

    /**
     *
     * 设置所有选项。对于一些常用的选项，如果用户不设置，就采用默认值
     * @param string $url URL地址
     */
    private function applyOptions($url)
    {
        $appliedOptions = array();

        foreach ((array)$this->options[$url] as $option) {
            $appliedOptions[$option['option']] = $option['value'];
        }

        if ($this->returnTransfer && !array_key_exists(CURLOPT_RETURNTRANSFER, $appliedOptions)) {
            curl_setopt($this->curls[$url], CURLOPT_RETURNTRANSFER, true);
        }

        if (0 !== $this->timeout && !array_key_exists(CURLOPT_TIMEOUT, $appliedOptions)) {
            curl_setopt($this->curls[$url], CURLOPT_TIMEOUT, $this->timeout);
        }

        if ($this->followLocation && !array_key_exists(CURLOPT_FOLLOWLOCATION, $appliedOptions)) {
            curl_setopt($this->curls[$url], CURLOPT_FOLLOWLOCATION, true);
        }

        if (0 !== $this->maxRedirect && !array_key_exists(CURLOPT_MAXREDIRS, $appliedOptions)) {
            curl_setopt($this->curls[$url], CURLOPT_MAXREDIRS, $this->maxRedirect);
        }

        if ($this->header && !array_key_exists(CURLOPT_HEADER, $appliedOptions)) {
            curl_setopt($this->curl, CURLOPT_HEADER, true);
        }

        if (!$this->body && !array_key_exists(CURLOPT_NOBODY, $appliedOptions)) {
            curl_setopt($this->curls[$url], CURLOPT_NOBODY, true);
        }

        if (!empty($this->postFields[$url]) && !array_key_exists(CURLOPT_NOBODY, $appliedOptions)) {
            curl_setopt($this->curls[$url], CURLOPT_POST, true);
            curl_setopt($this->curls[$url], CURLOPT_POSTFIELDS, http_build_query($this->postFields[$url]));
        }

        if ('' !== $this->userAgent && !array_key_exists(CURLOPT_NOBODY, $appliedOptions)) {
            curl_setopt($this->curls[$url], CURLOPT_USERAGENT, $this->userAgent);
        }

        if (!empty($this->httpHeader) && !array_key_exists(CURLOPT_HTTPHEADER, $appliedOptions)) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->httpHeader);
        }

        foreach ((array)$this->options[$url] as $option) {
            curl_setopt($this->curls[$url], $option['option'], $option['value']);
        }
    }

    /**
     *
     * 执行并发curl请求
     */
    private function execCurlMulti()
    {
        $running = 0;
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            do {
                $curlMultiCode = curl_multi_exec($this->curlMulti, $running);
            } while (CURLM_CALL_MULTI_PERFORM == $curlMultiCode);
            
            while ($running && CURLM_OK == $curlMultiCode) {
                if (self::CURL_MULTI_SELECT_ERROR == curl_multi_select($this->curlMulti)) {
                    usleep(100);
                }
                
                do {
                    $curlMultiCode = curl_multi_exec($this->curlMulti, $running);
                } while ($curlMultiCode == CURLM_CALL_MULTI_PERFORM);
            }
        } else {      
            do {
                $curlMultiCode = curl_multi_exec($this->curlMulti, $running);
            } while (CURLM_CALL_MULTI_PERFORM == $curlMultiCode);
            
            while ($running && CURLM_OK == $curlMultiCode) {
                if (self::CURL_MULTI_SELECT_ERROR != curl_multi_select($this->curlMulti)) {
                    do {
                        $curlMultiCode = curl_multi_exec($this->curlMulti, $running);
                    } while ($curlMultiCode == CURLM_CALL_MULTI_PERFORM);
                }
            }
        }
    }

    /**
     *
     * 执行一次并发请求并记录结果
     */
    private function executeOnce()
    {
        $urls = array_chunk($this->urls, $this->concurrentNumber ? $this->concurrentNumber : 50);

        foreach ($urls as $parts) {
            foreach ($parts as $url) {
                $this->curls[$url] = curl_init($url);
                $this->applyOptions($url);
                curl_multi_add_handle($this->curlMulti, $this->curls[$url]);
            }

            $this->execCurlMulti();

            foreach ($parts as $url) {
                $this->info[$url] = curl_getinfo($this->curls[$url]);
                $this->return[$url]['http_code'] = $this->info[$url]['http_code'];
                $this->return[$url]['errno'] = curl_errno($this->curls[$url]);
                $this->return[$url]['errmsg'] = curl_error($this->curls[$url]);
                $this->return[$url]['data'] = curl_multi_getcontent($this->curls[$url]);

                if (CURLE_OK !== $this->return[$url]['errno']
                    || !in_array($this->return[$url]['http_code'], $this->allowedHttpCodes)) {
                    $this->badUrls[] = $url;
                }

                curl_multi_remove_handle($this->curlMulti, $this->curls[$url]);
                curl_close($this->curls[$url]);
            }
        }
    }

    /**
     *
     * 执行并发请求
     * @return array 返回结果数组，格式：
     *     url  => array(
     *         'http_code' => HTTP代码，
     *         'errno'     => curl返回的错误码，
     *         'errmsg'    => curl返回的错误消息，
     *         'data'      => 实际数据
     *     )
     */
    public function execute()
    {
        $tryTime = $this->retry + 1;

        for ($i = 0; $i < $tryTime; $i++) {
            $this->executeOnce();

            if (empty($this->badUrls)) {
                break;
            }

            $this->urls = $this->badUrls;
            $this->badUrls = array();
        }

        return $this->return;
    }

    /**
     *
     * 执行一次并发下载并记录结果
     * @param array $filePaths 文件路径数组
     */
    private function downloadOnce($filePaths)
    {
        $fileHandles = array();
        $urls = array_chunk($this->urls, $this->concurrentNumber ? $this->concurrentNumber : 50);

        foreach ($urls as $parts) {
            foreach ($parts as $url) {
                $this->curls[$url] = curl_init($url);
                $this->applyOptions($url);
                $fileHandles[$url] = fopen($filePaths[$url], 'w');
                curl_setopt($this->curls[$url], CURLOPT_FILE, $fileHandles[$url]);
                curl_multi_add_handle($this->curlMulti, $this->curls[$url]);
            }

            $this->execCurlMulti();

            foreach ($parts as $url) {
                $this->info[$url] = curl_getinfo($this->curls[$url]);
                $this->return[$url]['http_code'] = $this->info[$url]['http_code'];
                $this->return[$url]['errno'] = curl_errno($this->curls[$url]);
                $this->return[$url]['errmsg'] = curl_error($this->curls[$url]);
                $this->return[$url]['data'] = curl_multi_getcontent($this->curls[$url]);

                if (CURLE_OK !== $this->return[$url]['errno']
                    || !in_array($this->return[$url]['http_code'], $this->allowedHttpCodes)) {
                    $this->badUrls[] = $url;
                }

                curl_multi_remove_handle($this->curlMulti, $this->curls[$url]);
                curl_close($this->curls[$url]);
                fclose($fileHandles[$url]);
            }
        }
    }

    /**
     *
     * 执行并发下载
     * @return array 返回结果数组，格式：
     *     url  => array(
     *         'http_code' => HTTP代码，
     *         'errno'     => curl返回的错误码，
     *         'errmsg'    => curl返回的错误消息，
     *         'data'      => 实际数据（此时为空）
     *     )
     */
    public function download($filePaths)
    {
        $tryTime = $this->retry + 1;

        for ($i = 0; $i < $tryTime; $i++) {
            $this->downloadOnce($filePaths);

            if (empty($this->badUrls)) {
                break;
            }

            $this->urls = $this->badUrls;
            $this->badUrls = array();
        }

        return $this->return;
    }
}

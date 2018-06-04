<?php
namespace App\Components\Utils;

use Cookie;
use App;

class DomainUserUtil
{
    var $_login_url;
    var $_logout_url;
    var $_timeout       = 10080;   // minutes  7days
    var $_tcookie;
    var $_ucookie;
    var $_salt          = 'ttycDomoinSalt';
    var $_ucInfo        = array();
    var $_tcInfo        = array();
    var $_cookieName    = array(
        'T'    => 'TD',
        'U'    => 'UD',
        'S'    => 'SD',
    );

    public function __construct()
    {/*{{{*/
       $this->_login_url     = env('TTYC_AUTH_SERVER').'/login';
       $this->_logout_url    = env('TTYC_AUTH_SERVER').'/logout';

       if (app()->environment('local')) { 
           $this->_cookieName = [
               'T'    => 'TTD',
               'U'    => 'TUD',
               'S'    => 'TSD',
           ];
       }

       $this->_tcookie       = Cookie::get($this->_cookieName['T'], '');
       $this->_ucookie       = Cookie::get($this->_cookieName['U'], '');
    }/*}}}*/

    public static function getInstance()
    {/*{{{*/
        static $instance;
        if('' == $instance)
        {
            return new DomainUserUtil();
        }
        return $instance;
    }/*}}}*/

    public function getSessionId()
    {/*{{{*/
       return Cookie::get($this->_cookieName['S'], '');
    }/*}}}*/

    public function checkUCookie($destUrl='')
    {/*{{{*/
        $userInfo = null;
        if ((false == $this->_isExistUC())           
            || (false == $this->_isValidLoginTime())
            || (false == $this->_isValidUC($userInfo))
        )
        {
            $this->_sendUserToUC($destUrl);
        }

        return $userInfo;
    }/*}}}*/

    private function _isExistUC()
    {/*{{{*/
        if ( ('' == $this->_tcookie) || ('' == $this->_ucookie) )
        {
            return false;
        }
        return true;
    }/*}}}*/

    private function _isValidLoginTime()
    {/*{{{*/

        $this->_getTCInfo();
        if (1 == (int)$this->_tcInfo['keepAlive'])
        {
            return true;
        }
        $term = (float)(time() - ($this->_tcInfo['loginTime']));
        return ($term <= ($this->_timeout * 60));
    }/*}}}*/

    private function _getUCInfo()
    {/*{{{*/
        $arr = array();
        parse_str($this->_ucookie, $arr);
        $arr['u'] = isset($arr['u'])?$arr['u']:'';
        $arr['m'] = isset($arr['m'])?$arr['m']:'';
        $arr['d'] = isset($arr['d'])?$arr['d']:'';
        $this->_ucInfo['userMail'] = $this->_decrypt($arr['m']);
        $this->_ucInfo['userName'] = $this->_decrypt($arr['u']);
        $this->_ucInfo['display']  = $this->_decrypt($arr['d']);
    }/*}}}*/

    private function _getTCInfo()
    {/*{{{*/
        $arr = array();
        parse_str($this->_tcookie, $arr);
        $this->_tcInfo['loginTime'] = isset($arr['t'])?$arr['t']:'';
        $this->_tcInfo['signature'] = isset($arr['s'])?$arr['s']:'';
        $this->_tcInfo['keepAlive'] = isset($arr['a'])?$arr['a']:'';
    }/*}}}*/

    public function _isValidUC(&$userInfo)
    {/*{{{*/
        if ($this->_vSign())
        {
            $userInfo = $this->_fillUserInfo();
            return (false == empty($userInfo));
        }
        return false;
    }/*}}}*/
    
    private function _vSign()
    {/*{{{*/
        $this->_getUCInfo();
        return $this->_verify($this->_ucInfo['userName'].$this->_ucInfo['userMail'].$this->_ucInfo['display'].$this->_tcInfo['loginTime'], $this->_tcInfo['signature']);
    }/*}}}*/
    
    public function _createDestUrl($destUrl)
    {/*{{{*/
    	$destUrl = trim($destUrl);
        if ('' == trim($destUrl))
        {
            $server = getenv('SERVER_NAME');
            $uri    = getenv('REQUEST_URI');

            /*
             * 如果HTTP请求，是非80端口的，则在跳转时，加上端口号
             */
            $port   = '';
            if ( '80' != getenv('SERVER_PORT') )
            {
                $port = ':'.getenv('SERVER_PORT');
            }

            $destUrl = 'http://'.$server.$port.$uri;
        }
        return urlencode($destUrl);
    }/*}}}*/
    
    public function _getUserInfoBySid($sid)
    {/*{{{*/
        $userInfo   = array();
        $url        = $this->_login_url."?sid=".$sid; 
        $ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    	$result = curl_exec($ch);
    	curl_close($ch);

        $response = json_decode($result, true);

        if($response['errno'] == 0) {
            $userInfo['userMail']  = $response['data']['userMail'];
            $userInfo['userName']  = $response['data']['userName'];
            $userInfo['display']   = $response['data']['display'];
            $userInfo['attachment']= $response['data']['attachment'];
        } else {
            $userInfo['userMail']  = '';
            $userInfo['userName']  = '';
            $userInfo['display']   = '';
            $userInfo['attachment']= '';
        }

        return $userInfo;
    }/*}}}*/

    private function _fillUserInfo()
    {/*{{{*/
        $userInfo['userMail']  = $this->_ucInfo['userMail'];
        $userInfo['userName']  = $this->_ucInfo['userName'];
        $userInfo['display']   = $this->_ucInfo['display'];
        $userInfo['loginTime'] = $this->_tcInfo['loginTime'];
        return $userInfo;
    }/*}}}*/

    private function _decrypt($data)
    {/*{{{*/
        return str_rot13($data);
    }/*}}}*/

    private function _verify($data, $signature)
    {/*{{{*/
        $newStr = md5($data.$this->_salt);
        return ($newStr == $signature);
    }/*}}}*/

    public function _sendUserToUC($destUrl='')
    {/*{{{*/
        $destUrl    = $this->_createDestUrl($destUrl);
        $url        = $this->_login_url.'?ref='.$destUrl;
        header('HTTP/1.1 302 Moved Permanently');
        header("Content-Type: application/json; charset=UTF-8");
        header('Location: '.$url);
        exit;
    }/*}}}*/
}

<?php
namespace App\Components\DNSPod;

use App\Components\Utils\HttpUtil;
use Log;

class DNSPod
{
    private $username, $password, $format, $lang, $error_on_empty, $user_id;
    public $base_domain = 'https://dnsapi.cn';

    public function __construct($username, $password, $format = 'json', $lang = 'en', $error_on_empty = 'yes', $user_id = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->format = $format;
        $this->lang = $lang;
        $this->error_on_empty = $error_on_empty;
        $this->user_id = $user_id;
    }

    // 获取用户基本信息
    public function userInfo()
    {
        return $this->request('/User.Detail');
    }

    // 修改用户基本信息
    public function modifyUserInfo($real_name, $nick, $telephone, $im)
    {
        if ($real_name) {
            $data['real_name'] = $real_name;
        }
        if ($nick) {
            $data['nick'] = $nick;
        }
        if ($telephone) {
            $data['telephone'] = $telephone;
        }
        if ($im) {
            $data['im'] = $im;
        }
        return $this->request('/User.Modify', $data);
    }

    // 修改用户密码
    public function modifyUserPassword($old_password, $new_password)
    {
        $data['old_password'] = $old_password;
        $data['new_password'] = $new_password;
        return $this->request('/Userpasswd.Modify', $data);
    }

    // 修改用户邮箱
    public function modifyUserEmail($old_email, $new_email, $password)
    {
        $data['old_email'] = $old_email;
        $data['new_email'] = $new_email;
        $data['password'] = $password;
        return $this->request('/Useremail.Modify', $data);
    }

    // 获取用户手机验证码
    public function telephoneVerifyCode($telephone)
    {
        $data['telephone'] = $telephone;
        return $this->request('/Telephoneverify.Code', $data);
    }

    // 获取用户操作日志
    public function userLog()
    {
        return $this->request('/User.Log', array());
    }

    // 新增一个域名托管
    public function createDomain($domain, $group_id = false, $is_mark = 'no')
    {
        $data['domain'] = $domain;
        if ($group_id) {
            $data['group_id'] = $group_id;
        }
        $data['is_mark'] = $is_mark;
        return $this->request('/Domain.Create', $data);
    }

    // 获取域名列表
    public function domainList($type = 'all', $offset = 0, $length = 20, $group_id = false, $keyword = false)
    {
        $data['type'] = $type;
        $data['offset'] = $offset;
        $data['length'] = $length;
        if ($group_id) {
            $data['group_id'] = $group_id;
        }
        if ($keyword) {
            $data['keyword'] = $keyword;
        }
        return $this->request('/Domain.List', $data);
    }

    // 移除一个域名
    public function removeDomain($domain, $type = 'domain')
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        return $this->request('/Domain.Remove', $data);
    }

    // 设置域名状态
    public function setDomainStatus($domain, $status, $type = 'domain_id')
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        $data['status'] = $status;
        return $this->request('/Domain.Status', $data);
    }

    // 获取域名信息
    public function domainInfo($domain, $type = 'domain')
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        return $this->request('/Domain.Info', $data);
    }

    // 获取域名的信息
    public function domainLog($domain, $type = 'domain', $offset = 0, $length = 20)
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        $data['offset'] = $offset;
        $data['length'] = $length;
        return $this->request('/Domain.Log', $data);
    }

    // 设置搜索引擎推送¶
    public function setDomainSearchEnginePush($domain, $type = 'domain', $status = 'yes')
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        $data['status'] = $status;
        return $this->request('/Domain.Searchenginepush', $data);
    }

    // 添加域名共享
    public function createDomainShare($domain, $type = 'domain', $email, $mode = 'r', $sub_domain = false)
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        $data[$email] = $email;
        $data['mode'] = $mode;
        if ($sub_domain) {
            $data['sub_domain'] = $sub_domain;
        }
        return $this->request('/Domainshare.Create', $data);
    }

    // 域名共享列表
    public function domainShareList($domain, $type = 'domain')
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        return $this->request('/Domainshare.List', $data);
    }

    //修改域名共享
    public function modifyDomainShare($domain, $type = 'domain', $email, $mode = 'r', $old_sub_domain = false, $new_sub_domain = false)
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        $data['email'] = $email;
        $data['mode'] = $mode;
        if ($old_sub_domain) {
            $data['old_sub_domain'] = $old_sub_domain;
            $data['new_sub_domain'] = $new_sub_domain;
        }
        return $this->request('/Domainshare.Modify', $data);
    }

    // 删除域名共享
    public function removeDomainShare($domain, $type = 'domain', $email)
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        $data['email'] = $email;
        return $this->request('/Domainshare.Remove', $data);
    }

    //域名过户
    public function transferDomain($domain, $type = 'domain', $email)
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        $data['email'] = $email;
        return $this->request('/Domain.Transfer', $data);
    }

    // 锁定域名
    public function lockDomain($domain, $type = 'domain', $days = 1)
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        $data['days'] = $days;
        return $this->request('/Domain.Lock', $data);
    }

    // 锁定状态
    public function domainLockStatus($domain, $type = 'domain')
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        return $this->request('/Domain.Lockstatus', $data);
    }

    // 锁定解锁
    public function unlockDomain($domain, $type = 'domain', $lock_code)
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        $data['lock_code'] = $lock_code;
        return $this->request('/Domain.Unlock', $data);
    }

    // 域名绑定列表
    public function domainAliasList($domain, $type = 'domain')
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        return $this->request('/Domainalias.List', $data);
    }

    // 添加域名绑定
    public function createDomainAlias($domain_id, $domain)
    {
        $data['domain'] = $domain;
        $data['domain_id'] = $domain_id;
        return $this->request('/Domainalias.Create', $data);
    }

    //删除域名绑定
    public function removeDomainAlias($domain, $type = 'domain', $alias_id)
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        $data['alias_id'] = $alias_id;
        return $this->request('/Domainalias.Remove', $data);
    }

    // 获取域名分组
    public function domainGroupList()
    {
        $data = array();
        return $this->request('/Domaingroup.List', $data);
    }

    // 添加域名分组
    public function createDomainGroup($group_name)
    {
        $data['group_name'] = $group_name;
        return $this->request('/Domaingroup.Create', $data);
    }

    // 修改域名分组
    public function modifyDomainGroup($group_id , $group_name)
    {
        $data['group_id'] = $group_id;
        $data['group_name'] = $group_name;
        return $this->request('/Domaingroup.Modify', $data);
    }

    //删除域名分组
    public function removeDomainGroup($group_id)
    {
        $data['group_id'] = $group_id;
        return $this->request('/Domaingroup.Remove', $data);
    }

    // 设置域名分组
    public function changeDomainGroup($domain, $type = 'domain', $group_id)
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        $data['group_id'] = $group_id;
        return $this->request('/Domain.Changegroup', $data);
    }

    // 设置域名星标
    public function starDomain($domain, $type = 'domain', $is_mark = 'yes')
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        $data['is_mark'] = $is_mark;
        return $this->request('/Domain.Ismark', $data);
    }

    // 设置域名备注
    public function markDomain($domain, $type = 'domain', $remark)
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        $data['remark'] = $remark;
        return $this->request('/Domain.Remark', $data);
    }

    // 获取域名权限
    public function domainPower($domain, $type = 'domain')
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        return $this->request('/Domain.Purview', $data);
    }

    // 域名取回获取邮箱列表
    public function acquireDomainEmail($domain)
    {
        $data['domain'] = $domain;
        return $this->request('/Domain.Acquire', $data);
    }

    // 域名取回发送验证码
    public function acquireDomainVerifyCode($domain, $email)
    {
        $data['domain'] = $domain;
        $data['email'] = $email;
        return $this->request('/Domain.Acquiresend', $data);
    }

    // 获取等级允许的记录类型
    public function recordType($domain_grade)
    {
        $data = array();
        $data['domain_grade'] = $domain_grade;
        return $this->request('/Record.Type', $data);
    }

    // 获取等级允许的线路线路
    public function recordLine($domain_grade, $domain, $type = 'domain')
    {
        if (!$domain) {
            return false;
        }
        $key = ($type == 'domain') ? 'domain' : 'domain_id';
        $data[$key] = $domain;
        $data['domain_grade'] = $domain_grade;
        return $this->request('/Record.Line', $data);
    }

    // 新增一条解析记录
    public function createRecord($domain_id, $sub_domain, $record_type, $value, $ttl = 3600, $mx = false, $record_line = '默认')
    {
        $data['domain_id'] = $domain_id;
        $data['sub_domain'] = $sub_domain;
        $data['record_type'] = $record_type;
        $data['value'] = $value;
        if ($record_type == 'MX') {
            $data['mx'] = $mx;
        }
        $data['ttl'] = intval($ttl);
        $data['record_line'] = $record_line;
        return $this->request('/Record.Create', $data);
    }

    //记录列表
    public function recordList($domain_id, $offset = 0, $length = 20, $sub_domain = false, $keyword = false)
    {
        $data['domain_id'] = $domain_id;
        $data['offset'] = $offset;
        $data['length'] = $length;
        if ($sub_domain) {
            $data['sub_domain'] = $sub_domain;
        }
        if ($keyword) {
            $data['keyword'] = $keyword;
        }
        return $this->request('/Record.List', $data);
    }

    // 修改记录
    public function modifyRecord($domain_id, $record_id, $record_type, $value, $ttl, $mx = false, $sub_domain = '@', $record_line = '默认')
    {
        $data['domain_id'] = $domain_id;
        $data['record_id'] = $record_id;
        $data['record_type'] = strtoupper($record_type);
        $data['value'] = $value;
        $data['ttl'] = $ttl;
        if ($record_type == 'MX') {
            $data['mx'] = $mx;
        }
        $data['sub_domain'] = $sub_domain;
        $data['record_line'] = $record_line;
        return $this->request('/Record.Modify', $data);
    }

    // 删除记录
    public function removeRecord($domain_id, $record_id)
    {
        $data['domain_id'] = $domain_id;
        $data['record_id'] = $record_id;
        return $this->request('/Record.Remove', $data);
    }

    // 更新动态DNS记录
    public function modifyRecordDdns($domain_id, $record_id, $sub_domain, $value = false, $record_line = '默认')
    {
        $data['domain_id'] = $domain_id;
        $data['record_id'] = $record_id;
        $data['sub_domain'] = $sub_domain;
        if ($value) {
            $data['value'] = $value;
        }
        $data['record_line'] = $record_line;
        return $this->request('/Record.Ddns', $data);
    }

    // 设置记录备注
    public function remarkRecord($domain_id, $record_id, $remark)
    {
        $data['domain_id'] = $domain_id;
        $data['record_id'] = $record_id;
        $data['remark'] = $remark;
        return $this->request('/Record.Remark', $data);
    }

    // 获取记录信息
    public function recordInfo($domain_id, $record_id)
    {
        $data['domain_id'] = $domain_id;
        $data['record_id'] = $record_id;
        return $this->request('/Record.Info', $data);
    }

    // 设置记录状态
    public function setRecordStatus($domain_id, $record_id, $status = 'disable')
    {
        $data['domain_id'] = $domain_id;
        $data['record_id'] = $record_id;
        $data['status'] = $status;
        return $this->request('/Record.Status', $data);
    }

    private function request($uri, $data = array(), $timeout = 5)
    {
        $url = sprintf("%s%s", $this->base_domain, $uri);
        $s = curl_init($url);
        curl_setopt($s, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($s, CURLINFO_HEADER_OUT, true);
        $data = array_merge($data, $this->gener_common_param());
        curl_setopt($s, CURLOPT_POST, true);
        curl_setopt($s, CURLOPT_POSTFIELDS, $data);
        // Set U.A
        $user_agent = sprintf("PHP DNSPod Client/1.0.0(%s)", $this->username);
        curl_setopt($s, CURLOPT_USERAGENT, $user_agent);

        $ret = curl_exec($s);
        $info = curl_getinfo($s);
        curl_close($s);
        if ($info['http_code'] == 200) {
            if ($this->format == 'json') {
                $ret_array = json_decode(trim($ret), true);
                if (is_array($ret_array)) {
                    return $ret_array;
                }
            } elseif ($this->format == 'xml') {
                // Return object
                return simplexml_load_string($ret);
            }

        }
        return false;
    }


    // 生成每次请求必须的参数
    private function gener_common_param()
    {
        $common_param = array(
            'login_email' => $this->username,
            'login_password' => $this->password,
            'format' => $this->format,
            'lang' => $this->lang,
            'error_on_empty' => $this->error_on_empty,
            'user_id' => $this->user_id,
        );
        return $common_param;
    }
}

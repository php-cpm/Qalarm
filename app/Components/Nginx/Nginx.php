<?php
namespace App\Components\Nginx;

use App\Components\Utils\HttpUtil;
use App\Components\Utils\MethodUtil;
use App\Components\Utils\LogUtil;

use App\Models\Gaea\CiProjectDns;

use App\Components\JobDone\JobDone;
use App\Models\Gaea\OpsScripts;

class Nginx
{
    const RENDER         = 1;
    const REVERSE_RENDER = 2;

    const ACCESS_LOG_PREFIX_PATH = '/data/logs/nginx';

    // jobdone执行常量
    const NGINX_CONF_ONWER_USER  = 'ttyc';

    const NGINX_CLUSTER_OFFICE = 'office';
    const NGINX_CLUSTER_ONLINE = 'online';

    private static $nginxCluster = [
        self::NGINX_CLUSTER_OFFICE => [
            'hosts'           => ['data01v.corp.ttyc.com'], 
            'nginx_path'      => '/usr/local/nginx/sbin/nginx',
            'nginx_conf_path' => '/usr/local/nginx/conf/vhost.d',
            'jobdone'         => JobDone::JOBDONE_ENV_TEST
        ],
        self::NGINX_CLUSTER_ONLINE => [
            'hosts' => ['nginx03v.uc', 'nginx04v.uc'], 
            'nginx_path'      => '/usr/sbin/nginx',
            'nginx_conf_path' => '/etc/nginx/vhost.d',
            'jobdone' => JobDone::JOBDONE_ENV_PRODUCTION
        ],
    ];

    public $configuration = [
        'servers_xxxx'       => ['func' => 'renderServers', 'params' => ''],
        'listen_port_xxxx'   => ['func' => 'renderListenPort', 'params' => ''],
        'server_name_xxxx'   => ['func' => 'renderServerName', 'params' => ''],
        'access_log_xxxx'    => ['func' => 'renderAccessLog', 'params' => ''],
        'project_name_xxxx'  => ['func' => 'renderProjectName', 'params' => ''],
    ];

    /**
     * @brief renderConf 生成nginx的配置文件
     * @param $dns
     * @return 
     */
    public function renderConf($dns)
    {/*{{{*/
        $conf = $dns->conf_template;
        if (empty($conf)) {
            $conf = $this->nginxOriginConf();
        }

        $this->fillParams($dns);

        foreach ($this->configuration as $key => $values) {
            $conf =  call_user_func_array(
                array(__NAMESPACE__ .'\Nginx', 'replaceConf'),
                array($conf, $key, $values['params'])
            );
        }

        return $conf;
    }/*}}}*/
    
    /**
     * @brief reverseRenderConf 通过nginx的配置文件反向解析出模板文件
     * @param $dns
     * @param $newConf
     * @return 
     */
    public function reverseRenderConf($dns, $newConf)
    {/*{{{*/
        $conf = $newConf;

        $this->fillParams($dns);

        foreach ($this->configuration as $key => $values) {
            $conf =  call_user_func_array(
                array(__NAMESPACE__ .'\Nginx', 'replaceConf'),
                array($conf, $key, $values['params'], self::REVERSE_RENDER)
            );
        }

        return $conf;
    }/*}}}*/

    /**
     * @brief updateConfAndReloadNginx 更新配置文件，并重启nginx是配置生效
     * @param $nginxCluster
     * @param $dns
     * @return 
     */
    public function updateConfAndReloadNginx($nginxCluster, $dns)
    {/*{{{*/
        $params['dirname']  = self::$nginxCluster[$nginxCluster]['nginx_conf_path'];

        // 域名的反向回文字符串
        $params['confname'] = $this->parseNginxDnsFileName($dns->name);
        
        // 如果机器全部下线，则这conf为空
        if ($this->isAllHostDown($dns) == true) {
            $params['conf']     = base64_encode('');
        } else {
            $params['conf']     = base64_encode($dns->conf);
        }
        $params['user']     = self::NGINX_CONF_ONWER_USER;
        $params['nginx']    = self::$nginxCluster[$nginxCluster]['nginx_path'];

        $result = app('jobdone')->jobdoneGoGoGo(
            OpsScripts::OWNER_GAEA,
            'sysinit',
            'ci_nginx.php',
            join(',', self::$nginxCluster[$nginxCluster]['hosts']),
            $params,
            100,
            JobDone::EXEC_SYNC,
            self::$nginxCluster[$nginxCluster]['jobdone']
        );

        return $result;
    }/*}}}*/

    /**
     * @brief parseNginxDnsFileName 使用域名得到配置文件的名称
     *        解析规则：gaea.ttyongche.com --> com.ttyongche.gaea
     * @param $dnsName
     * @return 
     */
    private function parseNginxDnsFileName($dnsName)
    {/*{{{*/
        $ret = '';
        $segment = explode('.', $dnsName);

        $count = count($segment);
        for ($i = 0; $i < $count; ++$i) {
            $ret .= array_pop($segment);
            if ($i+1 != $count) {
                $ret .= '.';
            }
        }

        // 文件名扩展名为.conf
        $ret .= '.conf';

        return $ret;
    }/*}}}*/

    /**
     * @brief getNginxCluster 根据域名类型获取nginx集群
     * @param $dnsType
     * @return 
     */
    public function getNginxCluster($dnsType)
    {/*{{{*/
        // 如果是local环境，则只能所有域名只能访问办公环境
        if (app()->environment('local')) {
            return self::NGINX_CLUSTER_OFFICE;
        }

        if (in_array($dnsType, [CiProjectDns::DNS_TYPE_SLAVE, CiProjectDns::DNS_TYPE_PRODUCTION])) {
            return self::NGINX_CLUSTER_ONLINE;
        }

        return self::NGINX_CLUSTER_OFFICE;
    }/*}}}*/


    /**
     * @brief isAllHostDown 判断是否所有的主机都下线
     * @param $dns
     * @return true | false
     */
    private function isAllHostDown($dns)
    {/*{{{*/
        $hosts = json_decode($dns->hostinfo, true);
        $servers = '';
        foreach ($hosts as $host) {
            if ($host['status']  == CiProjectDns::DNS_HOST_STATUS_UP) {
                $server = $host['host'];
                $servers .= $server;
            }
        }

        if (empty($servers)) {
            return true;
        }

        return false;
    }/*}}}*/
    /**
     * @brief fillParams  填充参数
     * @param $dns
     * @return 
     */
    public function fillParams($dns)
    {/*{{{*/
        // 项目名称
        $projectName = strtolower($dns->name) ;
        $projectName = preg_replace('/[^a-z0-9]+/i','_',$projectName);  

        foreach ($this->configuration as $key => $value) {
            // 后端服务器
            if ($key == 'servers_xxxx') {
                $hosts = json_decode($dns->hostinfo, true);
                $servers = '';
                foreach ($hosts as $host) {
                    if ($host['status']  == CiProjectDns::DNS_HOST_STATUS_UP) {
                        $server = sprintf("server  %s:%s;\n", $host['host'], $dns->port);
                        $servers .= $server;
                    }
                }
                $this->configuration[$key]['params'] = $servers;
            }


            // nginx监听端口, 默认为80
            if ($key == 'listen_port_xxxx') {
                $this->configuration[$key]['params'] = 80;
            }
            
            // 域名
            if ($key == 'server_name_xxxx') {
                $this->configuration[$key]['params'] = $dns->name;
            }
            
            // access log 路径
            if ($key == 'access_log_xxxx') {
                $this->configuration[$key]['params'] = sprintf("%s/%s__access_log", self::ACCESS_LOG_PREFIX_PATH, $projectName);
            }
            
            // 项目名称
            if ($key == 'project_name_xxxx') {
                $this->configuration[$key]['params'] = $projectName.'_servers';
            }
        }
    }/*}}}*/
    
    public function replaceConf($conf, $search, $replace, $type = self::RENDER)
    {/*{{{*/
        if ($type == self::RENDER) {
            return str_replace($search, $replace, $conf); 
        } else {
            return str_replace($replace, $search, $conf);
        }
    }/*}}}*/

    public function nginxOriginConf()
    {/*{{{*/
        $originConf = @file_get_contents(app_path("Components/Nginx/nginx_template/proxy.conf"));
        return $originConf;
    }/*}}}*/
}


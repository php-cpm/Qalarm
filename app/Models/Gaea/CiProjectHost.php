<?php

namespace App\Models\Gaea;

use DB;

use Illuminate\Database\Eloquent\Model;
use OpsHost;
use App\Components\Jenkins\CiCdConstants;

class CiProjectHost extends Gaea
{
     protected $table = 'ci_project_host';  

     // 主机类型：1 虚拟机 2 docker容器
     const HOST_TYPE_VM     = 1;
     const HOST_TYPE_DOCKER = 2;

     // 主机环境：1 测试环境 2 线上环境(只有线上机才能被设置成回归机)
     const HOST_ENV_TEST       = 1;
     const HOST_ENV_PRODUCTION = 2;

     public static $hostEnvDesc = [
         self::HOST_ENV_TEST        => ['desc' => '测试环境'],
         self::HOST_ENV_PRODUCTION  => ['desc' => '线上环境'],
     ];

     // 是否是回归机
     const HOST_SLAVE_YES      = 1;
     const HOST_SLAVE_NO       = 2;

     // 主机使用状态： 是否启用 1 启用 2 禁用
     const HOST_ENABLE     = 1;
     const HOST_DISABLE    = 2;

     public static $hostStatusDesc = [
         self::HOST_ENABLE      => ['color' => 'text-success-dker', 'desc' => '启用'],
         self::HOST_DISABLE     => ['color' => 'text-danger-dker',  'desc' => '停用'],
     ];

     public function opsHost() 
     {
         return $this->hasOne('App\Models\Gaea\OpsHost', 'id', 'vm_id');
     }

     public function k8sHost()
     {
         // FIXME
         return $this->hasOne('App\Models\Gaea\OpsK8sHost', 'id', 'k8s_id');
     }

     public static function getHostStatuses()
     {/*{{{*/
         return [
             self::HOST_INIT,
             self::HOST_UP,
             self::HOST_DOWN,
             self::HOST_DESTORY,
             self::HOST_ALL,
             ];
     }/*}}}*/

     public function export()
     {/*{{{*/
         // $scope.host.hostTypes    = [{'id':'1', 'name':'虚拟主机'}, {'id':'2', 'name':'docker'}];
         // $scope.host.hostType     = $scope.host.hostTypes[0].id;
         // $scope.host.hostEnvTypes = [{'id':'1', 'name':'测试环境'}, {'id':'2', 'name':'线上环境'}];
         // $scope.host.hostEnvType  = $scope.host.hostEnvTypes[0].id;

         $data = [];

         $data['id']                  = $this->id;

         if ($this->host_type == self::HOST_TYPE_VM) {
             $data['host_type_name']  = '虚拟机';
             $data['host_name']           = $this->opsHost->server_name;
         } else {
             $data['host_type_name']  = 'docker';
             $data['host_name']       = $this->k8sHost->ip;
         }


         $data['host_tag']            = self::$hostEnvDesc[$this->host_env_type]['desc']. ':'. $this->host_tag;
         $data['host_env_type']       = $this->host_env_type;



         if ($this->host_is_slave == self::HOST_SLAVE_YES) {
             $data['host_is_slave']   = '是';
         } else {
             $data['host_is_slave']   = '否';
         }

         $data['statusDesc']          = self::$hostStatusDesc[$this->enable]['desc'];
         $data['statusColor']         = self::$hostStatusDesc[$this->enable]['color'];
         $data['enable']              = $this->enable;

         return $data;;
     }/*}}}*/

     public static function fetchHostListByProjectId($projectId)
     {/*{{{*/
         $data = [];
         $hosts = CiProjectHost::where('project_id', $projectId)
                  ->where('enable', self::HOST_ENABLE)
                  ->get();

         foreach ($hosts as $host) {
             $clusterName = '';
             if ($host->host_env_type == self::HOST_ENV_PRODUCTION) {  
                 if ($host->host_is_slave == self::HOST_SLAVE_YES) {
                     $clusterName = "slave";
                 } else {
                     $clusterName = "online";
                 }
             } else {
                      $clusterName = "test";
             }

             $retHost = [];
             $retHost['host_type'] = $host->host_type;

             if ($host->host_type == self::HOST_TYPE_VM) {
                 $retHost['host_type_name']  = '虚拟机';
                 $retHost['server_name']     = $host->opsHost->server_name;
             } else {
                 $retHost['host_type_name']  = 'docker';
                 $retHost['server_name']     = $host->k8sHost->ip;
                 $retHost['docker_image']    = $host->image_name;
             }

             $retHost['enable']          = $host->enable;
             $data[$host->host_tag][$clusterName][] =  $retHost;
         }

         return $data;
     }/*}}}*/
     
     public static function fetchAllHostListByProjectId($projectId)
     {/*{{{*/
         $data = [];
         $hosts = CiProjectHost::where('project_id', $projectId)
                  ->get();

         foreach ($hosts as $host) {
             $retHost = [];
             $retHost['host_type'] = $host->host_type;

             if ($host->host_type == self::HOST_TYPE_VM) {
                 $retHost['host_type_name']  = '虚拟机';
                 $retHost['server_name']     = $host->opsHost->server_name;
                 $retHost['enable']          = $host->enable;
             } else {
                 $retHost['host_type_name']  = 'docker';
                 $retHost['server_name']     = $host->k8sHost->ip;
                 $retHost['docker_image']    = $host->image_name;
                 $retHost['enable']          = $host->enable;
             }

             $data[] =  $retHost;
         }

         return $data;
     }/*}}}*/

     public static function getALevelHostList ($projectId, $hostTarget='default')
    {/*{{{*/
        return self::getHostList($projectId, CiCdConstants::HOST_TYPE_SLAVE, $hostTarget);
    }/*}}}*/

     public static function getBLevelHostList ($projectId, $hostTarget='default')
    {/*{{{*/
        return self::getHostList($projectId, CiCdConstants::HOST_TYPE_ONLINE, $hostTarget);
    }/*}}}*/

     public static function getBetaHostList ($projectId, $hostTarget='default')
    {/*{{{*/
        return self::getHostList($projectId, CiCdConstants::HOST_TYPE_TEST, $hostTarget);
    }/*}}}*/

     public static function getHostList ($projectId, $cluster, $hostTarget='default')
    {/*{{{*/
        $result = [];
        $hostTarget = 'default';
        $hostList = CiProjectHost::fetchHostListByProjectId($projectId);
        if (!isset($hostList)) {
            return $result;
        }
        // 根据标签；集群名字；发布类型；获取机器
        foreach($hostList as $key=>$values) {
            if ($hostTarget == 'default') {

                foreach ($values as $clusterName => $hosts) {
                    if ($clusterName == $cluster) {
                       $result[$clusterName] = $hosts;
                    }
                }
            }
        }
        return $result;
    }/*}}}*/

     public static function getLevelHostState ($projectId) 
     {/*{{{*/
         $result = ['test' => false, 'slave' => false, 'online' => false];

         $hosts = CiProjectHost::where('project_id', $projectId)
                  ->where('enable', self::HOST_ENABLE)
                  ->get();

         foreach ($hosts as $host) {
             $clusterName = '';
             if ($host->host_env_type == self::HOST_ENV_PRODUCTION) {  
                 if ($host->host_is_slave == self::HOST_SLAVE_YES) {
                     if ($result['slave'] == false) {
                         $result['slave'] = true;
                     }
                 } else {
                     if ($result['online'] == false) {
                         $result['online'] = true;
                     }
                 }
             } 

             if ($host->host_env_type == self::HOST_ENV_TEST) {  
                 if ($result['test'] == false) {
                     $result['test'] = true;
                 }
             } 
         }

         return $result;
     }/*}}}*/
}

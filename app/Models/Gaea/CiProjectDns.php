<?php

namespace App\Models\Gaea;

use DB;

use Illuminate\Database\Eloquent\Model;
use OpsHost;

class CiProjectDns extends Gaea
{
     protected $table = 'ci_project_dns';

     // 域名类型：1 测试  2 回归 3 生产
     const DNS_TYPE_TEST         = 1;
     const DNS_TYPE_SLAVE        = 2;
     const DNS_TYPE_PRODUCTION   = 3;

     const DNS_HOST_STATUS_DOWN  = 'down';
     const DNS_HOST_STATUS_UP    = 'up';

     public static $dnsTypeDesc    = [
         self::DNS_TYPE_TEST        => ['prefix' => 'test',  'desc' => '测试'],
         self::DNS_TYPE_SLAVE       => ['prefix' => 'slave', 'desc' => '回归'],
         self::DNS_TYPE_PRODUCTION  => ['prefix' => '',      'desc' => '线上'],
     ];

     public static $statusDesc = [
         self::DNS_HOST_STATUS_DOWN => ['color' => 'text-danger-dker', 'desc' => '下线'],
         self::DNS_HOST_STATUS_UP   => ['color' => 'text-success-dker',  'desc' => '上线'],
     ];

     public function project() 
     {
         return $this->hasOne('App\Models\Gaea\CiProject', 'id', 'project_id');
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
         $data = [];

         $data['id']                  = $this->id;
         $data['name']                = $this->name;
         $data['port']                = $this->port;
         $data['conf']                = $this->conf;
         $data['type']                = $this->type;

         $hosts = json_decode($this->hostinfo, true);
         if ($hosts != null) {
             $data['hostinfo']   = $hosts;
         }

         // 得到已经选择的机器列表
         foreach ($hosts as $host) {
             $data['selected_host'][]  = $host['host'];
         }

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

             $data[$host->host_tag][$clusterName][] =  $retHost;
         }

         //dd($data);
         return $data;

     }/*}}}*/
}

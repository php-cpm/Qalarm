<?php

namespace App\Models\Gaea;

use DB;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class CiDockerImage extends Gaea
{
     protected $table = 'ci_docker_image';  

     const IMAGE_CREATE_STATUS_WAIT     = 1;
     const IMAGE_CREATE_STATUS_CREATING = 2;
     const IMAGE_CREATE_STATUS_DONE     = 3;
     const IMAGE_CREATE_STATUS_FAILED   = 4;

     private static $statusDesc = [
         self::IMAGE_CREATE_STATUS_WAIT       => ['color' => 'text-warning', 'desc' => '准备环境'],
         self::IMAGE_CREATE_STATUS_CREATING   => ['color' => 'text-info-dker', 'desc' =>'创建镜像'],
         self::IMAGE_CREATE_STATUS_DONE       => ['color' => 'text-success-dker', 'desc' => '创建完成'],
         self::IMAGE_CREATE_STATUS_FAILED     => ['color' => 'text-danger-dker', 'desc' => '创建失败']
     ];

     /**
      * @brief generateCiDeployDockerImage 生成应用镜像
      * 步骤： 1、生成新的dockerfile and supervisor 
      *        2、调用 jobdone 执行ci_docker.php 生成新镜像
      *
      * @return jobdone的异步调用结果
      */
     public function generateCiDeployDockerImage($imageName, $version, $params = [])
     {/*{{{*/
         list($dockerfile, $supervisor) = $this->fetchDeployDockerFileAndSupervisorV1($params);

         $startupShell = $this->fetchDockerStartupShell($params);

         // params dockerfile,supervisor,name,version,dockerregistry,startupshell
         // 为了保证传输的正确性，做base64编码，到对端再解密
         $params['dockerfile']     = base64_encode($dockerfile);
         $params['supervisor']     = base64_encode($supervisor);
         $params['name']           = $imageName;
         $params['version']        = $version;
         $params['dockerregistry'] = env('CI_KUBERNETES_DOCKER_REGISTRY');
         $params['startupshell']   = base64_encode($startupShell);

         $result = app('jobdone')->jobdoneGoGoGo(
             OpsScripts::OWNER_GAEA,
             'sysinit',
             'ci_docker.php',
             '',
             $params,
             900
          );

         return $result;
     }/*}}}*/
     
     public function fetchDeployDockerFileAndSupervisorV1($params = [])
     {/*{{{*/
         $dockerfile = $this->dockerfile;
         $supervisor = $this->supervisor;

         if (!empty($params)) {
             $deploySegment = CiDockerImageSegment::fetchSegment('app', '1.0', $params);

             $sep = "VOLUME";
             // 把代码生成片段插入VOLUME之前，才能共享数据
             list($before, $after) = explode($sep, $this->dockerfile);

             $dockerfile = $before. "\n" . $deploySegment->command. "\n" .$sep.$after;
             $supervisor = $this->supervisor . "\n\n" . $deploySegment->supervisor;
         }
         return [$dockerfile, $supervisor];
     }/*}}}*/
     
     // docker的启动脚本
     public function fetchDockerStartupShell($params)
     {/*{{{*/
         $runImageShell = '';
         if (!empty($params)) {
         $runImageShell = "sh /resource/ci_gaea_deploy.sh run_image";
         }
         $shell = sprintf("
#!/bin/bash
%s
/etc/init.d/crond start
/usr/bin/supervisord  -c /resource/supervisord.conf
tail -f /var/log/messages
", $runImageShell);

         return $shell;
     }/*}}}*/

     public function export()
     {/*{{{*/
         $data = [];

         $data['id']            = $this->id;
         $data['name']          = $this->name;
         $data['segments']      = $this->segments;
         $data['status']        = self::$statusDesc[$this->status]['desc'];
         $data['statusColor']   = self::$statusDesc[$this->status]['color'];
         $data['registry']      = env('CI_KUBERNETES_DOCKER_REGISTRY').'/'.$this->name;
         $data['ports']         = $this->ports;
         $data['use_time']      = $this->use_time;
         if (empty($this->use_time)) {
            $data['use_time']      = 0;
         }

         $data['last_build_time'] = with(new Carbon())->timestamp($this->updated_at->timestamp)->format('Y-m-d H:d:s'); 

         return $data;
     }/*}}}*/
}

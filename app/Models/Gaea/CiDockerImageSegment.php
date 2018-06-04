<?php

namespace App\Models\Gaea;

use DB;

use Illuminate\Database\Eloquent\Model;

class CiDockerImageSegment extends Gaea
{
     protected $table = 'ci_docker_image_segment';  

     // 1 头部声明，2、公共依赖安装（通过apt，yum等） \n3、环境变量 4、语言环境安装  5、软件安装  6、其他  (EXPOSE, WORKDIR, VOLUME, CMD)  7 安装应用
     const DOCKER_SEGMENT_TYPE_HEADER      = 1;
     const DOCKER_SEGMENT_TYPE_DEPENDENCY  = 2;
     const DOCKER_SEGMENT_TYPE_ENV         = 3;
     const DOCKER_SEGMENT_TYPE_LANGUAGE    = 4;
     const DOCKER_SEGMENT_TYPE_SOFTWARE    = 5;
     const DOCKER_SEGMENT_TYPE_OTHER       = 6;
     const DOCKER_SEGMENT_TYPE_APP         = 7;

     public static function softwares()
     {/*{{{*/
         $segments = CiDockerImageSegment::where('type', self::DOCKER_SEGMENT_TYPE_SOFTWARE)->orderBy('name', 'desc')->get();

         return static::formatData($segments);
     }/*}}}*/

     // 格式化数据
     private static function formatData($segments) 
     {/*{{{*/
         $data = [];
         $ret  = [];
         foreach ($segments as $seg) {
             $data[$seg->name][] = ['version' => $seg->version];
         }

         foreach ($data as $name => $versions) {
             $ret[] = ['name' => $name, 'versions' => $versions];
         }

         return $ret;
     }/*}}}*/
     
     public static function languages()
     {/*{{{*/
         $segments = CiDockerImageSegment::where('type', self::DOCKER_SEGMENT_TYPE_LANGUAGE)->orderBy('name', 'desc')->get();

         return static::formatData($segments);
     }/*}}}*/

     public static function fetchDockerFileAndSupervisor($segments, $params)
     {/*{{{*/
         $list = [];
         $list[] = static::fetchSegment('centos6.6', '1.0');
         $list[] = static::fetchSegment('user', '1.0');
         $list[] = static::fetchSegment('tools', '1.0');
         $list[] = static::fetchSegment('supervisor', '3.0');
         $list[] = static::fetchSegment('env', '1.0');

         foreach ($segments as $segmentName => $version) { 
             $list[] = static::fetchSegment($segmentName, $version);
         }
         
         $list[] = static::fetchSegment('other', '1.0', $params);

         return static::assembleSemgents($list);
     }/*}}}*/

     public static function fetchSegment($name, $version, $params = [])
     {/*{{{*/
         $segment = CiDockerImageSegment::where('name', $name)->where('version', $version)->first();
         if (!empty($segment->params)) {
             $tmp = explode(',', $segment->params);
             // 把属性替换为对应的值
             foreach ($tmp as $param) {
                 if (isset($params[$param])) {
                     $segment->command = str_replace($param, $params[$param], $segment->command);
                 }
             }
         }

         return $segment;
     }/*}}}*/

     private static function assembleSemgents($list)
     {/*{{{*/
         $dockerfile = '';
         $supervisor = '';

         foreach ($list as $segment) {
             $dockerfile .= $segment->command;
             $dockerfile .= "\n\n";
             if (!empty($segment->supervisor)) {
                 $supervisor .= $segment->supervisor;
                 $supervisor .= "\n\n";
             }
         }

         return [$dockerfile, $supervisor];
     }/*}}}*/

     public function export()
     {/*{{{*/
         return $this;
     }/*}}}*/
}

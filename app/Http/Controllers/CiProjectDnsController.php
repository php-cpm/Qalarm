<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Redis;
use Cookie;
use Exception;
use RuntimeException;
use App\Exceptions\ApiException;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Carbon\Carbon;

use App\Components\Utils\Paginator; 
use App\Components\Utils\ErrorCodes;
use App\Components\Utils\Constants;
use App\Components\Utils\MethodUtil;
use App\Components\Utils\LogUtil;
use App\Components\JobDone\JobDone;


use App\Models\Gaea\AdminUser;
use App\Models\Gaea\CiProject;
use App\Models\Gaea\CiProjectDns;
use App\Models\Gaea\CiProjectHost;

class CiProjectDnsController extends Controller
{
    public function fetchCiProjectDnses(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'project_id'    =>  'required'
        ]);

        $projectId = $request->input('project_id');
        $query = CiProjectDns::orderBy('name', 'desc');
        $query->where('project_id' , $projectId);

        $paginator = new Paginator($request);
        $hosts = $paginator->runQuery($query);

        return $this->responseList($paginator, $hosts);
    }/*}}}*/
    
    public function updateCiProjectDns(Request $request)
    {/*{{{*/
        $this->validate($request, [
            'action'              => 'required|in:add,update,delete,online,offline',
            'project_id'          => 'required_if:action,add',
            'dns_type'            => 'required_if:action,add,update',
            'dns_name'            => 'required_if:action,add,update,delete',
            'dns_port'            => 'required_if:action,add,update',
            'hosts'               => 'required_if:action,add,update',
            'dns_conf'            => 'required_if:action,update',
            'host_name'           => 'required_if:action,online,offline',
        ]);


        $action = $request->input('action');
        $projectId    = $request->input('project_id');

        $ciProjectDns = null;
        // 新建添加
        if ($action == 'add') {/*{{{*/
            $ciProjectDns = CiProjectDns::where('name', $request->input('dns_name'))->first();
            if ($ciProjectDns != null) {
                 return response()->clientError(ErrorCodes::ERR_FAILURE, '域名已存在');
            }
            $ciProjectDns = new CiProjectDns();
            {
                $ciProjectDns->project_id    = $request->input('project_id');
                $ciProjectDns->type          = $request->input('dns_type');
                $ciProjectDns->name          = $request->input('dns_name');
                $ciProjectDns->port          = $request->input('dns_port');

                // 处理hosts
                $tmpHost    = explode('|', $request->input('hosts'));
                $dnsHosts   = [];
                $projectHosts = CiProjectHost::fetchAllHostListByProjectId($request->input('project_id'));
                foreach ($tmpHost as $hostName) {
                    $dnsHost = ['host' => $hostName, 'status' => CiProjectDns::DNS_HOST_STATUS_DOWN];
                    foreach ($projectHosts as $h) {
                        if ($h['server_name'] == $hostName && $h['enable'] == CiProjectHost::HOST_ENABLE) {
                            $dnsHost['status'] = CiProjectDns::DNS_HOST_STATUS_UP;
                        }
                    }

                    $dnsHost['statusColor'] = CiProjectDns::$statusDesc[$dnsHost['status']]['color'];
                    $dnsHosts[] = $dnsHost;
                }

                $ciProjectDns->hostinfo = json_encode($dnsHosts);

                $nginxConf = app('nginx1')->renderConf($ciProjectDns);
                $ciProjectDns->conf = $nginxConf;

                $nginxConfTemplate = app('nginx1')->reverseRenderConf($ciProjectDns, $nginxConf);
                $ciProjectDns->conf_template  = $nginxConfTemplate;

            }
        }/*}}}*/

        if ($action == 'update') {/*{{{*/
            $ciProjectDns = CiProjectDns::where('name', $request->input('dns_name'))->first();
            if ($ciProjectDns == null) {
                 return response()->clientError(ErrorCodes::ERR_FAILURE, '域名不存在');
            }
            
            $ciProjectDns->port          = $request->input('dns_port');
            
            // 使用老的dns信息和新的conf信息，生成新的template conf
            if ($ciProjectDns->conf != $request->input('dns_conf')) {
                $ciProjectDns->conf = $request->input('dns_conf'); 
                $nginxConfTemplate = app('nginx1')->reverseRenderConf($ciProjectDns, $ciProjectDns->conf);
                $ciProjectDns->conf_template  = $nginxConfTemplate;
            }

            // 处理hosts
            $tmpHost    = explode('|', $request->input('hosts'));
            $dnsHosts   = [];
            $projectHosts = CiProjectHost::fetchAllHostListByProjectId($request->input('project_id'));
            foreach ($tmpHost as $hostName) {
                $dnsHost = ['host' => $hostName, 'status' => CiProjectDns::DNS_HOST_STATUS_DOWN];
                foreach ($projectHosts as $h) {
                    if ($h['server_name'] == $hostName && $h['enable'] == CiProjectHost::HOST_ENABLE) {
                        $dnsHost['status'] = CiProjectDns::DNS_HOST_STATUS_UP;
                    }
                }

                $dnsHost['statusColor'] = CiProjectDns::$statusDesc[$dnsHost['status']]['color'];
                $dnsHosts[] = $dnsHost;
            }

            $ciProjectDns->hostinfo = json_encode($dnsHosts);
            
            // 渲染出新的配置文件
            $nginxConf = app('nginx1')->renderConf($ciProjectDns);
            $ciProjectDns->conf = $nginxConf;
        }/*}}}*/

        if ($action == 'delete') {/*{{{*/
            $ciProjectDns = CiProjectDns::where('name', $request->input('dns_name'))->first();
            if ($ciProjectDns == null) {
                 return response()->clientError(ErrorCodes::ERR_FAILURE, '域名不存在');
            }
            // 设置conf为空
            $ciProjectDns->conf = '';

        }/*}}}*/

        if ($action == 'online' || $action == 'offline') {/*{{{*/
            $ciProjectDns = CiProjectDns::where('name', $request->input('dns_name'))->first();
            if ($ciProjectDns == null) {
                 return response()->clientError(ErrorCodes::ERR_FAILURE, '域名不存在');
            }
            $hosts = json_decode($ciProjectDns->hostinfo, true);
            $hostName = $request->input('host_name');
            foreach ($hosts as $key => $host) {
                if ($host['host'] == $hostName) {
                    if ($action == 'online') {
                        // 上线同时要判断机器是否已经被停用，停用之后则不能上线
                        $allHosts = CiProjectHost::fetchAllHostListByProjectId($ciProjectDns->project_id);
                        foreach ($allHosts as $host) {
                            if ($host['server_name'] == $hostName && $host['enable'] == CiProjectHost::HOST_DISABLE) {
                                return response()->clientError(ErrorCodes::ERR_FAILURE, '此后端机器已经停用，不能上线');
                            }
                        }
                        $hosts[$key]['status'] = CiProjectDns::DNS_HOST_STATUS_UP;
                    } else if ($action == 'offline') {
                        $hosts[$key]['status'] = CiProjectDns::DNS_HOST_STATUS_DOWN;
                    }
                    $hosts[$key]['statusColor'] = CiProjectDns::$statusDesc[$hosts[$key]['status']]['color'];
                }
            }
            $ciProjectDns->hostinfo = json_encode($hosts);

            // 从新渲染出新的配置文件
            $nginxConf = app('nginx1')->renderConf($ciProjectDns);
            $ciProjectDns->conf = $nginxConf;
        }/*}}}*/

        $nginxCluster = app('nginx1')->getNginxCluster($ciProjectDns->type);
        $result = app('nginx1')->updateConfAndReloadNginx($nginxCluster, $ciProjectDns);

        DB::connection('gaea')->beginTransaction();

        if ($action == 'delete') {
            CiProjectDns::destroy($ciProjectDns->id);
        } else {
            $ciProjectDns->save();
        }

        $response = null;
        if ($result['errno'] != ErrorCodes::ERR_SUCCESS) {
            $response = response()->clientError(ErrorCodes::ERR_FAILURE, 'jobdone服务端错误:'. $result['errmsg']);
        } else {
            $data = $result['data'];

            foreach ($data['return'] as $cube) {
                if ($cube['ret'] != JobDone::NODE_JOB_FINISHED) {
                    $response = response()->clientError(ErrorCodes::ERR_FAILURE, $cube['output']);
                }
            }
        }

        if ($response == null) {
            DB::connection('gaea')->commit();
            return response()->clientSuccess(['id' => $ciProjectDns->id]);
        } else {
            DB::connection('gaea')->rollback();
            return $response;
        }
    }/*}}}*/

    protected function responseList($paginator, $collection, $callee='export')
    {/*{{{*/
        return response()->clientSuccess([
            'page'     => $paginator->info($collection),
                'results'  => $collection->map(function($item, $key) use ($callee) {
                    return call_user_func([$item, $callee]);
                }),
                ]);
    }/*}}}*/
}

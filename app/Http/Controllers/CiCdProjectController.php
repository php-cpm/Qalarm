<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

use DB;
use Log;
use App\Components\Utils\LogUtil;
use App\Components\GitLab\GitLabHook;
use Carbon\Carbon;
use App\Components\Utils\Paginator;

use App\Components\Utils\Constants;
use App\Components\Utils\MethodUtil;
use App\Components\JobDone\JobDone;
use App\Components\Utils\ErrorCodes;

use App\Models\Gaea\CiProject;
use App\Models\Gaea\CiBuildProject;
use App\Models\Gaea\CiProjectHost;
use App\Models\Gaea\CiProjectMember;
use App\Models\Gaea\CiCdProject;
use App\Models\Gaea\CiCdStep;
use App\Models\Gaea\CiCdServerLog;
use App\Models\Gaea\CiProjectChange;

use App\Jobs\CiCdDeployJob;

use App\Components\Jenkins\CiCdConstants;

class CiCdProjectController extends Controller
{
    public function ciCdProject (Request $request)
    {/*{{{*/
        $actions = MethodUtil::getActions();
        $this->validate($request, [
            'action'  => "required|in:$actions"
        ]);

        $action = $request->input('action');
        switch ($action) {
            case Constants::REQUEST_TYPE_ADD:

                $fileterDeployStep = join(',', [CiCdConstants::DEPLOY_STEP_BETA, CiCdConstants::DEPLOY_STEP_A_LEVEL]);
                $this->validate($request, [
                    'gaea_build_id'    => "required",
                    'deploy_step'      => "required|in:$fileterDeployStep",
                    'title'            => "required",
                    'desc'             => "required"
                ]);
                $gaeaBuildId   = $request->Input('gaea_build_id');
                $deployStep    = $request->Input('deploy_step');
                //$deployStep    = $request->Input('deploy_step');
                //$title         = $request->Input('title');
                //$desc          = $request->Input('desc');

                $buildInfo     = CiBuildProject::where('gaea_build_id', '=', $gaeaBuildId)->first();
                $projectInfo   = CiProject::where('project_id', '=', $buildInfo->project_id)->first();
                $projectId     = $projectInfo->project_id;
                $projectName   = $projectInfo->project_name;
                $checkoutSha   = $buildInfo->update_id;

                $deployDirPath = $request->input('deploy_dir');
                if (empty($deployDirPath)) {
                    $deployDirPath = $projectInfo->deploy_dir;
                }

                //生成部署id
                $deployId = uniqid();
                $saveedCiCdProject = null;
                //判断如果发布过；不再增加新的数据；与构建表一对一关系
                //$isDeployedData = CiDeployOperate::where('gaea_build_id', '=', $gaeaBuildId)->first();
                $isDeployedData = CiCdProject::where('gaea_build_id', '=', $gaeaBuildId)->first();
                if (isset($isDeployedData)) {
                    $deployId = $isDeployedData->deploy_id;
                    $dataCiCdProject = [
                        //'deploy_id'     => $deployId,
                        //'gaea_build_id' => $gaeaBuildId,
                        //'project_id'    => $projectId,
                        //'project_name'  => $projectName,
                        'deploy_step'   => $deployStep,
                        'deploy_action' => CiCdConstants::DEPLOY_ACTION_DEPLOY,
                        'deploy_status' => CiCdConstants::DEPLOY_STATUS_WAIT,
                        'started_time'  => Carbon::now(),
                        'title'         => $request->Input('title'),
                        'desc'          => $request->Input('desc'),
                        'user_id'       => Constants::getAdminId(),
                        'user_name'     => Constants::getAdminName(),
                    ];
                    $saveedCiCdProject = CiCdProject::where('gaea_build_id', '=', $gaeaBuildId)->update($dataCiCdProject);
                } else {
                    $dataCiCdProject = [
                        'deploy_id'     => $deployId,
                        'gaea_build_id' => $gaeaBuildId,
                        'project_id'    => $projectId,
                        'project_name'  => $projectName,
                        'deploy_step'   => $deployStep,
                        'deploy_action' => CiCdConstants::DEPLOY_ACTION_DEPLOY,
                        'deploy_status' => CiCdConstants::DEPLOY_STATUS_WAIT,
                        'started_time'  => Carbon::now(),
                        'title'         => $request->Input('title'),
                        'desc'          => $request->Input('desc'),
                        'user_id'       => Constants::getAdminId(),
                        'user_name'     => Constants::getAdminName(),
                    ];
                    $saveedCiCdProject = CiCdProject::create($dataCiCdProject);
                }

                $deployAction = CiCdConstants::DEPLOY_ACTION_DEPLOY;
                $result = $this->deployLogic($projectId, $deployId, $gaeaBuildId, $deployAction, $deployStep);

                if ($result['errno'] == 0 ) {
                    $deployStepId = $result['data']['saveed_cicd_step_id'];
                    return response()->clientSuccess(['deploy_step_id' => $deployStepId]);
                } else {
                    return response()->clientError($result['errno'], $result['errmsg']);
                }

                break;
            case Constants::REQUEST_TYPE_LIST:
                $projectId = $request->input('project_id', '');
                $status    = $request->input('deploy_status') == 'ALL' ? '' : $request->input('deploy_status', '');
                $isCiSuperUser = CiProjectMember::isCiSuperUser(Constants::getAdminAccount());
                //$query = CiDeployOperate::getCiDeployListByAdminAccount(Constants::getAdminAccount(), $projectId, $status, $isCiSuperUser);
                $query = CiCdProject::getCiDeployListByAdminAccount(Constants::getAdminAccount(), $projectId, $status, $isCiSuperUser);
                $paginator = new Paginator($request);
                $data = $paginator->runQuery($query);
                return $this->responseList($paginator, $data);
                break;
            case Constants::REQUEST_TYPE_GET:
                $deployId = $request->Input('deploy_id');
                //$data = CiDeployOperate::where('deploy_id', '=', $deployId)->first();
                $data = CiCdProject::where('deploy_id', '=', $deployId)->first();
                if (!isset($data)) {
                    return response()->clientError(-1, 'not find data');
                }
                return response()->clientSuccess($data);
                break;
        };
    }/*}}}*/

    public function deployAction (Request $request)
    {/*{{{*/
        $this->validate($request, [
            'deploy_id'     => "required",
            'project_id'    => "required",
            'gaea_build_id' => "required",
            'deploy_action' => "required",
        ]);

        $projectId    = $request->input('project_id');
        $deployId     = $request->input('deploy_id');
        $gaeaBuildId  = $request->input('gaea_build_id');
        $deployAction = $request->input('deploy_action');
        
        $result = null;
        switch ($deployAction) {

            case CiCdConstants::DEPLOY_ACTION_DEPLOY :
                //部署动作 
                $result = $this->deployLogic($projectId, $deployId, $gaeaBuildId, $deployAction);
                break;

            case CiCdConstants::DEPLOY_ACTION_ROLLBACK :
                //回滚动作 
                $result = $this->deployLogic($projectId, $deployId, $gaeaBuildId, $deployAction);
                break;

            case CiCdConstants::DEPLOY_ACTION_CANCEL :
                //取消动作 
                $result = $this->deployLogic($projectId, $deployId, $gaeaBuildId, $deployAction);
                break;

            default :
                return response()->clientError(-1, '非法部署方法');
                break;
        }


        if ($result['errno'] == 0 ) {
            $deployStepId = $result['data']['saveed_cicd_step_id'];
            return response()->clientSuccess(['deploy_step_id' => $deployStepId]);
        } else {
            return response()->clientError($result['errno'], $result['errmsg']);
        }

    }/*}}}*/

    public function deployAfterWebHook (Request $request)
    {/*{{{*/
        $this->validate($request, [
            'content'   => "required",
            'deploy_id' => "required",
            'time'      => "required",
            'host_name' => "required",
            'save_log_id' => "required"
        ]);

        LogUtil::info('deploy after hook',[$request->Input()], LogUtil::LOG_CI);

        $content  = $request->Input('content');
        $deployId = $request->Input('deploy_id');
        $time     = $request->Input('time');
        $hostName = $request->Input('host_name');
        $saveLogId = $request->Input('save_log_id');

        //$data = CiDeployProjectLog::where('id', '=', $saveLogId)->first();
        $data = CiCdServerLog::where('id', '=', $saveLogId)->first();
        if ($data) {
            //$data->step_log = empty($data->step_log) ? $content : $data->step_log . '|' . $content;
            $data->callback_step_log = empty($data->callback_step_log) ? $content : $data->callback_step_log . '|' . $content;
        }
        $data->save();

        echo 'success';
        return;
    }/*}}}*/

    public function deployProjectHostLogs (Request $request)
    {/*{{{*/
        $this->validate($request, [
            'deploy_id'  => "required"
        ]);

        $data = [];
        if ($request->has('deploy_step_id')) {
            //$data = CiDeployProjectLog::where('parent_id', '=', $request->input('deploy_step_id'))->get();
            $data = CiCdServerLog::where('parent_id', '=', $request->input('deploy_step_id'))->get();
        } else {
            //$data = CiDeployProjectLog::where('deploy_id', '=', $request->input('deploy_id'))->get();
            $data = CiCdServerLog::where('deploy_id', '=', $request->input('deploy_id'))->get();
        }

        $result = [];
        foreach ( $data as $item ) {
            $result[] = $item->export();
        }

        return response()->clientSuccess($result);
    }/*}}}*/

    public function checkHostSet (Request $request)
    {/*{{{*/
        $this->validate($request, [
            'project_id' => "required"
        ]);
        $data = CiProjectHost::getLevelHostState($request->input('project_id'));
        return response()->clientSuccess($data);
    }/*}}}*/

    public function getGitlabChangeByChangeId (Request $request)
    {/*{{{*/
        $this->validate($request, [
            'update_id'  => "required"
        ]);
        $data = CiProjectChange::where('checkout_sha', '=', $request->input('update_id'))->first();
        if (!isset($data)) {
            return response()->clientError(-1, 'no find data;');
        }
        $commits = json_decode($data->commits);
        $data->commits = $commits;
        return response()->clientSuccess($data);
    }/*}}}*/

    private function deployLogic ($projectId, $deployId, $gaeaBuildId, $deployAction, $selectedDeployStep = '') 
    {/*{{{*/
        $result = ['errno' => -1, 'errmsg' => '', 'data' => []];
        //$jobType      = '';
        $hostList     = [];
        $deployStep   = '';
        $deployStatus = '';
        $deployAfterShRunType = '';

        //$deployStep = empty($selectedDeployStep) ? $this->getDeployStep($deployId, $deployAction) : $selectedDeployStep;
        ////dd($deployStep);
        //if ($deployStep == CiCdConstants::DEPLOY_STEP_BETA) {
            ////$jobType      = CiCdConstants::DEPLOY_STEP_BETA;
            ////$hostList     = CiProjectHost::getBetaHostList($projectId);
            //$hostList     = $this->getHostByProjectIdHostType($projectId, CiCdConstants::HOST_TYPE_TEST);
            //$deployStep   = CiCdConstants::DEPLOY_STEP_BETA;
            //$deployStatus = CiCdConstants::DEPLOY_STATUS_WAIT;
            //$deployAfterShRunType = CiCdConstants::DEPLOY_STEP_BETA;
        //}

        //if ($deployStep == CiCdConstants::DEPLOY_STEP_A_LEVEL) {
            ////$jobType      = CiCdConstants::DEPLOY_STEP_A_LEVEL;
            //$hostList     = $this->getHostByProjectIdHostType($projectId, CiCdConstants::HOST_TYPE_SLAVE);
            ////$hostList     = CiProjectHost::getALevelHostList($projectId);
            //$deployStep   = CiCdConstants::DEPLOY_STEP_A_LEVEL;
            //$deployStatus = CiCdConstants::DEPLOY_STATUS_WAIT;
            //$deployAfterShRunType = CiCdConstants::DEPLOY_STEP_A_LEVEL;
        //}

        //if ($deployStep == CiCdConstants::DEPLOY_STEP_B_LEVEL) {
            ////$jobType      = CiCdConstants::DEPLOY_STEP_B_LEVEL;
            //$hostList     = $this->getHostByProjectIdHostType($projectId, CiCdConstants::HOST_TYPE_ONLINE);
            ////$hostList     = CiProjectHost::getBLevelHostList($projectId);
            //$deployStep   = CiCdConstants::DEPLOY_STEP_B_LEVEL;
            //$deployStatus = CiCdConstants::DEPLOY_STATUS_WAIT;
            //$deployAfterShRunType = CiCdConstants::DEPLOY_STEP_B_LEVEL;
        //}

        $deployStep = empty($selectedDeployStep) ? $this->getDeployStep($deployId, $deployAction) : $selectedDeployStep;
        switch ($deployStep)
        {
            case CiCdConstants::DEPLOY_STEP_BETA:

                //$jobType      = CiCdConstants::DEPLOY_STEP_BETA;
                //$hostList     = CiProjectHost::getBetaHostList($projectId);
                $hostList     = $this->getHostByProjectIdHostType($projectId, CiCdConstants::HOST_TYPE_TEST);
                $deployStep   = CiCdConstants::DEPLOY_STEP_BETA;
                $deployStatus = CiCdConstants::DEPLOY_STATUS_WAIT;
                $deployAfterShRunType = CiCdConstants::DEPLOY_STEP_BETA;
                break;

            case CiCdConstants::DEPLOY_STEP_A_LEVEL:

                //$jobType      = CiCdConstants::DEPLOY_STEP_A_LEVEL;
                $hostList     = $this->getHostByProjectIdHostType($projectId, CiCdConstants::HOST_TYPE_SLAVE);
                //$hostList     = CiProjectHost::getALevelHostList($projectId);
                $deployStep   = CiCdConstants::DEPLOY_STEP_A_LEVEL;
                $deployStatus = CiCdConstants::DEPLOY_STATUS_WAIT;
                $deployAfterShRunType = CiCdConstants::DEPLOY_STEP_A_LEVEL;
                break;

            case CiCdConstants::DEPLOY_STEP_B_LEVEL:

                //$jobType      = CiCdConstants::DEPLOY_STEP_B_LEVEL;
                $hostList     = $this->getHostByProjectIdHostType($projectId, CiCdConstants::HOST_TYPE_ONLINE);
                //$hostList     = CiProjectHost::getBLevelHostList($projectId);
                $deployStep   = CiCdConstants::DEPLOY_STEP_B_LEVEL;
                $deployStatus = CiCdConstants::DEPLOY_STATUS_WAIT;
                $deployAfterShRunType = CiCdConstants::DEPLOY_STEP_B_LEVEL;
                break;

            default:

                $result = ['errno' => -1, 'errmsg' => '部署状态错误!!!!', 'data' => []];
                return $result;
                break;
        }

        if (empty ($hostList)) {
            $result = ['errno' => -1, 'errmsg' => '没有可以发布的机器', 'data' => []];
            return $result;
        }

        if (empty($deployStatus)) {
            $result = ['errno' => -1, 'errmsg' => '初始部署状态失败', 'data' => []];
            return $result;
        }

        //$packageUrl = CiBuildProject::getDownDeployFilesUrl($gaeaBuildId);

        $packageUrl = '';
        if ($deployAction == CiCdConstants::DEPLOY_ACTION_ROLLBACK) {

            $rollbackGaeaBuildId = CiCdProject::getDownRollbackDeployFilesUrl($projectId, $deployStep, $deployId);
            $packageUrl = CiBuildProject::getDownDeployFilesUrl($rollbackGaeaBuildId);

        } else {

            $packageUrl = CiBuildProject::getDownDeployFilesUrl($gaeaBuildId);

        }

        if (!isset($packageUrl)) {
            $result = ['errno' => -1, 'errmsg' => '获取部署包失败', 'data' => []];
            return $result;
        }

        //todo:事务保存数据
        //根据项目ID获取项目部署配置信息
        $projectInfo   = CiProject::where('project_id', '=', $projectId)->first();
        $projectName   = $projectInfo->project_name;
        $sshUser       = $projectInfo->ssh_user;
        $deployDirPath = $projectInfo->deploy_dir;

        //更新cicdproject 数据
        $dataCiCdProject = [
            'deploy_step'   => $deployStep,
            'deploy_action' => $deployAction,
            'deploy_status' => CiCdConstants::DEPLOY_STATUS_WAIT,
            //'started_time'  => Carbon::now(),
            'user_id'       => Constants::getAdminId(),
            'user_name'     => Constants::getAdminName(),
        ];
        $saveedCiCdProject = CiCdProject::where('gaea_build_id', '=', $gaeaBuildId)->update($dataCiCdProject);

        //保存ci_cd_step
        $dataCiCdStep = [
            'deploy_id'     => $deployId,
            'gaea_build_id' => $gaeaBuildId,
            'project_id'    => $projectId,
            'project_name'  => $projectName,
            'deploy_step'   => $deployStep,
            'deploy_action' => $deployAction,
            'deploy_status' => CiCdConstants::DEPLOY_STATUS_WAIT,
            'user_id'       => Constants::getAdminId(),
            'user_name'     => Constants::getAdminName(),
            'started_time'  => Carbon::now()
        ];
        $saveedCiCdStep = CiCdStep::create($dataCiCdStep);

        //保存ci_cd_server_log
        $result = [];
        foreach ($hostList as $key=>$items) {
            foreach ($items as $hostInfo) {

                $dataCiCdServerLog = [
                    'project_id'    => $projectId,
                    'project_name'  => $projectName,
                    'gaea_build_id' => $gaeaBuildId,
                    'deploy_id'     => $deployId,
                    'deploy_step'   => $deployStep,
                    'deploy_action' => $deployAction,
                    'deploy_status' => CiCdConstants::DEPLOY_STATUS_WAIT,
                    'started_time'  => Carbon::now(),
                    'host_name'     => $hostInfo['server_name'],
                    'host_type'     => $hostInfo['host_type'],
                    'host_ip'       => '',
                    'deploy_dir'    => $deployDirPath,
                    'host_is_test'  => $key == CiCdConstants::HOST_TYPE_TEST ? 1 : 0,
                    //'host_is_test'  => $key == CiCdConstants::HOST_TYPE_TEST ? 1 : 1,
                    'host_cluster'  => $key,
                    'parent_id'     => $saveedCiCdStep->id,
                ];
                $saveedServerLog = CiCdServerLog::create($dataCiCdServerLog);
                //dd($saveedServerLog->id);

                //记录保存信息进入数据库信息；传递给jobdone 
                $result[$key][] = [
                    'server_name'  => $hostInfo['server_name'],
                    'host_type'    => $hostInfo['host_type'],
                    'save_log_id'  => $saveedServerLog->id,
                    'host_is_test' => $saveedServerLog->host_is_test
                ];

            }
        }
        $hostList = $result;
        //dd($hostList);

        // 锁定当前准备部署项目
        $this->setProjectDeployStatus($projectId,true); 

        //$jobType      = CiCdConstants::DEPLOY_JOBDONE_TYPE_BETA;
        $deployAction      =  $deployAction;
        ////将部署信息添加进部署队列
        $loadResult = $this->loadDeployJobDone ($projectId, $projectName, $deployId, $sshUser, $deployDirPath, $hostList, $deployAction, $packageUrl, $gaeaBuildId, $deployAfterShRunType, $saveedCiCdStep->id);

        $result = [
            'errno'  => 0, 
            'errmsg' => 'success', 
            'data'   => ['deploy_id' => $deployId, 'saveed_cicd_step_id' => $saveedCiCdStep->id]
        ];
        return $result;
        //返回部署信息
        //return response()->clientSuccess(['deploy_step_id' => $saveedCiCdStep->id]);
        //return $saveedCiCdStep->id;
        //return response()->clientSuccess(['deploy_step_id' => $deployStepId]);
        //dd('success');
    }/*}}}*/

    //判断发布阶段
    private function getDeployStep ($deployId, $deployAction) 
    {/*{{{*/

        $data = CiCdProject::where('deploy_id', '=', $deployId)
                            ->orderBy('id','desc')
                            ->first();

        // 发布动作
        if ($deployAction == CiCdConstants::DEPLOY_ACTION_DEPLOY) {

            if (!isset($data)) {
                //构建结果没有发布过；发布测试环境
                return CiCdConstants::DEPLOY_STEP_BETA;
            } else {

                if ($data->deploy_step == CiCdConstants::DEPLOY_STEP_BETA) {
                    if ($data->deploy_action == CiCdConstants::DEPLOY_ACTION_DEPLOY && 
                        $data->deploy_status == CiCdConstants::DEPLOY_STATUS_SUCCESS) {
                        //测试环境部署成功；返回salve 阶段 
                        return CiCdConstants::DEPLOY_STEP_A_LEVEL; 
                    }
                    return CiCdConstants::DEPLOY_STEP_BETA; 
                }

                if ($data->deploy_step == CiCdConstants::DEPLOY_STEP_A_LEVEL) {
                    if ($data->deploy_action == CiCdConstants::DEPLOY_ACTION_DEPLOY && 
                        $data->deploy_status == CiCdConstants::DEPLOY_STATUS_SUCCESS) {
                        //slave环境部署成功；返回线上 阶段 
                        return CiCdConstants::DEPLOY_STEP_B_LEVEL; 
                    }
                    return CiCdConstants::DEPLOY_STEP_A_LEVEL; 
                }

                if ($data->deploy_step == CiCdConstants::DEPLOY_STEP_B_LEVEL) {
                    //发布项目；处于发布线上阶段,返回线上阶段
                    return CiCdConstants::DEPLOY_STEP_B_LEVEL; 
                }

            }
        }

        // 回滚动作
        if ($deployAction == CiCdConstants::DEPLOY_ACTION_ROLLBACK) {

            if (!isset($data)) {
                return '';
            }

            if ($data->deploy_step == CiCdConstants::DEPLOY_STEP_BETA) {
                return CiCdConstants::DEPLOY_STEP_BETA; 
            }

            if ($data->deploy_step == CiCdConstants::DEPLOY_STEP_A_LEVEL) {
                return CiCdConstants::DEPLOY_STEP_A_LEVEL; 
            }

            if ($data->deploy_step == CiCdConstants::DEPLOY_STEP_B_LEVEL) {
                return CiCdConstants::DEPLOY_STEP_B_LEVEL; 
            }

        }

        return ''; 
    }/*}}}*/

    private function setProjectDeployStatus ($project_id = 0, $isLockProject = true)
    {/*{{{*/
        $deployStatus = $isLockProject == true ? CiCdConstants::PROJECT_LOCK_TATUS_LOCKED : CiCdConstants::PROJECT_LOCK_TATUS_UNLOCKED;
        $updateRows = CiProject::where('project_id', '=', $project_id)
            ->update(['deploy_status' => $deployStatus]);
        return $updateRows > 0 ? true : false;
    }/*}}}*/

    private function getHostByProjectIdHostType ($projectId, $hostType, $deployAction='deploy')
    {/*{{{*/
        //todo:rollback 获取服务器列表；应获取部署表信息
        $hostTarget = 'default';
        $hostList   = CiProjectHost::getHostList($projectId, $hostType, $hostTarget);
        return $hostList;
    }/*}}}*/

    //调用加载部署job
    private function loadDeployJobDone ($projectId, $projectName, $deployId, $sshUser, $deployDirPath, $deployHostList, $deployAction, $packageUrl, $gaeaBuildId, $deployAfterShRunType, $saveedCiCdStepId, $shRunNginxConf = 'ThisIsDefaultNginxParams')
    {/*{{{*/
        $deployData = [
            'deploy_id'       => $deployId,
            'ssh_user'        => $sshUser,
            'deploy_dir'      => $deployDirPath,
            //'project_package' => 'build_'.$projectName.'.tar.gz',
            'package_url'     => $packageUrl,
            'hostlist'        => $deployHostList,
            'project_id'      => $projectId,
            'gaea_build_id'   => $gaeaBuildId,
            'project_name'    => $projectName,
            //'job_type'        => $jobType,
            'deploy_action'        => $deployAction,
            'deploy_after_sh_run_type'      => $deployAfterShRunType, 
            'saveed_cicd_step_id'    => $saveedCiCdStepId,
            'sh_run_nginx_conf'    => $shRunNginxConf,
        ];
        $jobData = MethodUtil::assemblyJobData($deployData);
        $job  = new CiCdDeployJob($jobData);
        $this->dispatch($job);
        return true;
    }/*}}}*/

    protected function responseList($paginator, $collection, $callee = 'export')
    {/*{{{*/ 
        return response()->clientSuccess([
            'page' => $paginator->info($collection),
                'results' => $collection->map(function ($item, $key) use ($callee) {
                    return call_user_func([$item, $callee]);
                }),
            ]);
    }/*}}}*/
}

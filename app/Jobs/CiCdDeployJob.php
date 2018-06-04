<?php
namespace App\Jobs;

use Log;
use Carbon\Carbon;

use App\Components\Utils\LogUtil;
use App\Components\Util\TimeUtil;
use App\Components\Utils\MethodUtil;
use App\Components\Notice\Notice;

use App\Components\Utils\Constants;
use App\Components\JobDone\JobDone;
use App\Components\Utils\ErrorCodes;

use App\Models\Gaea\CiProject;
use App\Models\Gaea\CiDeployProject;
//use App\Models\Gaea\CiDeployProjectLog;
use App\Models\Gaea\OpsScripts;
use App\Models\Gaea\CiProjectHost;
use App\Models\Gaea\OpsK8sHost;
use App\Models\Gaea\CiDockerImage;
use App\Models\Gaea\CiProjectMember;
use App\Models\Gaea\CiStepStateLog;
use App\Components\Jenkins\CiCdConstants;
use App\Components\Kubernetes\Kubernetes; 

//重构
use App\Models\Gaea\CiCdProject;
use App\Models\Gaea\CiCdStep;
use App\Models\Gaea\CiCdServerLog;
use App\Models\Gaea\AdminUser;

class CiCdDeployJob extends BaseJob
{
    protected $ciDeployProjectLog;

    public function __construct($ciDeployLog) 
    {/*{{{*/
        $this->ciDeployProjectLog = $ciDeployLog;
        parent::__construct($ciDeployLog);
    }/*}}}*/

    public function doHandle()
    {/*{{{*/
         //return true;
        $data = $this->ciDeployProjectLog;
        // 此处参数顺序，不可调整；需要与gaea job done 参数顺序一致
        $params = [
            'ssh_user'        => $data['ssh_user'],
            'deploy_dir'      => $data['deploy_dir'],
            //'project_package' => $data['project_package'],
            'project_package' => '',
            'package_url'     => $data['package_url'],
            'deploy_after_sh_run_type'     => isset($data['deploy_after_sh_run_type'])?$data['deploy_after_sh_run_type']:'',
            'sh_run_nginx_conf'     => isset($data['sh_run_nginx_conf'])?$data['sh_run_nginx_conf']:''
        ];
        $deployId       = $data['deploy_id'];
        $projectId      = $data['project_id'];
        $deployHostList = $data['hostlist'];
        $deployAction  = $data['deploy_action'];                   // 部署/回滚
        $gaeaBuildId    = $data['gaea_build_id'];
        $saveedCiCdStepId    = $data['saveed_cicd_step_id'];

        // 用gaea_build_id作为docker镜像的版本号
        $versionBuildId        = $data['gaea_build_id'];
        $projectName    = $data['project_name'];
        
        $params['gaea_deploy_id'] = $deployId;

        if (env('APP_ENV') == 'local') {
            $params['callback_url'] = env('GAEA_DOMAIN_TEST') . '/api/v1/ci/deployafter';
        } else {
            $params['callback_url'] = env('GAEA_DOMAIN_PRODUCTION') . '/api/v1/ci/deployafter';
        }

        //$params['deploy_acton_type'] = $this->deployActionType($deployJobType); 
        $params['deploy_acton_type'] = $deployAction; 

        // 更具jobdone 类型；回滚或事部署;获取状态
        //$deployStatusAll = $this->getDeployStatusAll($deployJobType);

        // 设置当前部署为回滚状态
        //CiDeployProject::where('deploy_id', '=', $deployId)->update(['status' => $deployStatusAll[CiCdConstants::KEY_RUNNING]]);
        //CiCdStep::where('deploy_id', '=', $deployId)->update(['deploy_status' => CiCdConstants::DEPLOY_STATUS_RUNNING]);
        CiCdStep::where('id', '=', $saveedCiCdStepId)->update(['deploy_status' => CiCdConstants::DEPLOY_STATUS_RUNNING]);

        LogUtil::info('Start deploy...', [$data], LogUtil::LOG_CI);
        LogUtil::info('Start deploy...', [$data], LogUtil::LOG_JOB);

        // 循环执行部署任务
        $isJobSuccess = true;
        foreach  ($deployHostList as $key=>$items) {
            foreach ($items as $hostInfo) {
                $hostName    = $hostInfo['server_name'];            // server 名字 
                $hostType    = $hostInfo['host_type'];              // 主机类型：1.vm ,0 docker
                $saveLogId   = $hostInfo['save_log_id'];            // 记录唯一标示
                $hostIsTest  = $hostInfo['host_is_test'];           // 主机类型是否在内网环境；还是外网环境;决定jobdone 调用
                $jobDoneUrl  = $hostIsTest == 1 ? JobDone::JOBDONE_ENV_TEST : JobDone::JOBDONE_ENV_PRODUCTION ;

                //CiDeployProjectLog::where('id', '=', $saveLogId)->update(['status' => $deployStatusAll[CiCdConstants::KEY_RUNNING]]);
                //CiDeployProjectLog::where('id', '=', $saveLogId)->update(['deploy_status' => CiCdConstants::DEPLOY_STATUS_RUNNING]);
                CiCdServerLog::where('id', '=', $saveLogId)->update(['deploy_status' => CiCdConstants::DEPLOY_STATUS_RUNNING]);

                $params['save_log_id'] = $saveLogId; 
                $params['host_name'] = $hostName; 

                // 获取当前job done 结果
                if ($hostType == CiProjectHost::HOST_TYPE_DOCKER) {
                    // FIXME
                    // 根据server_name查询k8s service
                    $service     = OpsK8sHost::where('ip', $hostName)->first();
                    $dockerImage = CiDockerImage::where('name', $service->image_name)->first();
                    $dockerPorts = explode('|', $dockerImage->ports);
                    $ciCdStepCurrentStep = $this->getCiCdCurrentStep($saveedCiCdStepId);

                    //避免一次构建；多次部署bug;添加四位随机数
                    $randNum = rand(1000, 9999);
                    $version = $versionBuildId.'-'.$randNum;

                    LogUtil::info('Start deploy k8s service'.$hostName,  [], LogUtil::LOG_JOB);
                    Kubernetes::setEnv($hostIsTest == 1 ? Kubernetes::ENV_TEST : Kubernetes::ENV_PRODUCTION);
                    list($oldRcName, $oldVesion) = Kubernetes::getCurrentRc($projectName, $ciCdStepCurrentStep);

                    // 启动新的rc
                    if ($oldRcName == null) {
                        //$this->saveJobLogResult($saveLogId, -1, $deployStatusAll[CiCdConstants::KEY_RUNNING], "\n1、启动rc");
                        $this->saveJobLogResult($saveLogId, -1, CiCdConstants::DEPLOY_STATUS_RUNNING, "\n1、启动rc");
                        //$result = Kubernetes::createOrUpdateRc($projectName, $dockerPorts, [], [], $service->replica, $version, $dockerImage->name);
                        $result = Kubernetes::createOrUpdateRc($projectName, $dockerPorts, [], [], $service->replica, $version, $dockerImage->name, $ciCdStepCurrentStep, $params);
                        if ($result['errno'] == ErrorCodes::ERR_SUCCESS) {
                            // 持续查询主机启动状态
                            //
                            //$result = $this->checkK8sPodsFinish($projectName, $version, $saveLogId, $deployStatusAll, $service->replica);
                            //$result = $this->checkK8sPodsFinish($projectName, $version, $saveLogId,CiCdConstants::DEPLOY_STATUS_RUNNING, $service->replica);
                            //$result = $this->checkK8sPodsFinish($projectName, $version, $saveLogId, $service->replica );
                            $result = $this->checkK8sPodsFinish($projectName, $version, $saveLogId, $service->replica, $ciCdStepCurrentStep);
                            if ($result) {
                                //$deployStatus = $deployStatusAll[CiCdConstants::KEY_SUCCESS];
                                $deployStatus = CiCdConstants::DEPLOY_STATUS_SUCCESS;
                                $this->saveJobLogResult($saveLogId, -1, $deployStatus, '部署完成');
                            } else {
                                //$deployStatus = $deployStatusAll[CiCdConstants::KEY_FAIL];
                                $deployStatus = CiCdConstants::DEPLOY_STATUS_FAIL;
                                $this->saveJobLogResult($saveLogId, -1, $deployStatus, '启动docker超时，请联系管理员');
                            }
                        } else {
                            //$this->parseK8sErrorResult($result, 'Deploy docker failed', $saveLogId, $deployStatusAll);
                            $this->parseK8sErrorResult($result, 'Deploy docker failed', $saveLogId);
                        }
                    } else {    // 滚动升级rc
                        /*
                         * 滚动升级步骤：
                         * 1、启动新的rc，设置rc的replica为1；检查完成状态；
                         * 2、把老的rc的replica减1，检查完成状态;
                         * 3、新的rcreplica加1,检查完成状态;
                         * 4、replica数到规定数量之前，重复2和3步骤；
                         * 5、删除老的rc
                         */

                        //$deployStatus = $deployStatusAll[CiCdConstants::KEY_RUNNING];
                        $deployStatus = CiCdConstants::DEPLOY_STATUS_RUNNING;
                        $this->saveJobLogResult($saveLogId, -1, $deployStatus, "\n1、启动新的rc");
                        //$result = Kubernetes::createOrUpdateRc($projectName, $dockerPorts, [], [], 0, $version, $dockerImage->name); 
                        $result = Kubernetes::createOrUpdateRc($projectName, $dockerPorts, [], [], 0, $version, $dockerImage->name, $ciCdStepCurrentStep, $params);
                        if ($result['errno'] != ErrorCodes::ERR_SUCCESS) {
                            //$this->parseK8sErrorResult($result, '创建新的rc失败', $saveLogId, $deployStatusAll);
                            $this->parseK8sErrorResult($result, '创建新的rc失败', $saveLogId);
                            break;
                        }

                        for ($idx = 1; $idx <= $service->replica; ++$idx) {
                            // 增加新rc的实例数
                            $outputLog = sprintf("2(%s)、增加新rc的实例数 +1, 当前数量：%s, 总数：%s", $idx, $idx, $service->replica);
                            $this->saveJobLogResult($saveLogId, -1, $deployStatus, $outputLog);

                            //$result = Kubernetes::updateRcReplica(Kubernetes::getRcName($projectName, $version), $idx);
                            $newRcName =  Kubernetes::getRcName($projectName, $version, $ciCdStepCurrentStep);
                            


                            $result = Kubernetes::updateRcReplica($newRcName, $idx);

                            if ($result['errno'] != ErrorCodes::ERR_SUCCESS) {
                                //$this->parseK8sErrorResult($result, '增加新的rc实例失败', $saveLogId, $deployStatusAll);
                                $this->parseK8sErrorResult($result, '增加新的rc实例失败', $saveLogId);
                            }
                            // 只有新启动时才会出现启动不了的问题,如果出现问题直接设置失败，退出
                            //$result = $this->checkK8sPodsFinish($projectName, $version, $saveLogId, $deployStatusAll, $idx);
                            //$result = $this->checkK8sPodsFinish($projectName, $version, $saveLogId, CiCdConstants::DEPLOY_STATUS_RUNNING, $idx);
                            //$result = $this->checkK8sPodsFinish($projectName, $version, $saveLogId, $idx);
                            $result = $this->checkK8sPodsFinish($projectName, $version, $saveLogId, $idx, $ciCdStepCurrentStep );
                            if ($result == false) {
                                //$deployStatus = $deployStatusAll[CiCdConstants::KEY_FAIL];
                                $deployStatus = CiCdConstants::DEPLOY_STATUS_FAIL;
                                $this->saveJobLogResult($saveLogId, -1, $deployStatus, '启动docker超时，请联系管理员');
                                return true;
                            }

                            // 减少老rc的实例数
                            $outputLog = sprintf("3(%s)、减少老rc的实例数 -1, 当前数量：%s, 总数：%s", $idx, $service->replica-$idx, $service->replica);
                            $this->saveJobLogResult($saveLogId, -1, $deployStatus, $outputLog);
                            
                            $result = Kubernetes::updateRcReplica($oldRcName, $service->replica-$idx);
                            if ($result['errno'] != ErrorCodes::ERR_SUCCESS) {
                                //$this->parseK8sErrorResult($result, '删除老的rc实例失败', $saveLogId, $deployStatusAll);
                                $this->parseK8sErrorResult($result, '删除老的rc实例失败', $saveLogId);
                            }
                            //$this->checkK8sPodsFinish($projectName, $oldVesion, $saveLogId, $deployStatusAll, $service->replica-$idx);
                            //$this->checkK8sPodsFinish($projectName, $oldVesion, $saveLogId, CiCdConstants::DEPLOY_STATUS_RUNNING, $service->replica-$idx);
                            //$this->checkK8sPodsFinish($projectName, $oldVesion, $saveLogId, $service->replica-$idx);
                            $this->checkK8sPodsFinish($projectName, $oldVesion, $saveLogId, $service->replica-$idx, $ciCdStepCurrentStep );
                        }

                        $this->saveJobLogResult($saveLogId, -1, $deployStatus, "4、删除老rc");
                        //$result = Kubernetes::deleteRc($projectName, $oldVesion);
                        $result = Kubernetes::deleteRc($projectName, $oldVesion, $ciCdStepCurrentStep );
                        if ($result['errno'] != ErrorCodes::ERR_SUCCESS) {
                            //$this->parseK8sErrorResult($result, '删除老的rc失败', $saveLogId, $deployStatusAll);
                            $this->parseK8sErrorResult($result, '删除老的rc失败', $saveLogId);
                        }

                        //$deployStatus = $deployStatusAll[CiCdConstants::KEY_SUCCESS];
                        $deployStatus = CiCdConstants::DEPLOY_STATUS_SUCCESS;
                        $this->saveJobLogResult($saveLogId, -1, $deployStatus, '部署完成');
                    }

                } else {
                    // 获取要执行的 脚本
                    $script = OpsScripts::where('owner', OpsScripts::OWNER_GAEA)
                        ->where('scriptdir', 'soft')
                        ->where('scriptname', 'ci_cd_deploy.sh')
                        ->first();
                    // 生成job 参数
                   //sh_user,deploy_dir,project_package,package_url,deploy_after_sh_run_type,sh_run_nginx_conf,gaea_deploy_id,host_name

                    $cmd = app('jobdone')->fetchJobDoneCommand($script, $params);

                    $jobDoneResult = $this->deployOneHost($hostName,$deployId, $cmd['cmd'],$jobDoneUrl);

                    //LogUtil::info('deploy cmd params', $cmd, LogUtil::LOG_CI);

                    // 根据当前deploy type , jobdone resut ,获取部署状态
                    //$deployStatus = $jobDoneResult['err_code'] == CiCdConstants::ERR_SUCC ? $deployStatusAll[CiCdConstants::KEY_SUCCESS] : $deployStatusAll[CiCdConstants::KEY_FAIL];
                    //$this->saveJobLogResult($saveLogId, $jobDoneResult['jid'], $deployStatus, $jobDoneResult['err_msg']);
                    $deployStatus = $jobDoneResult['err_code'] == CiCdConstants::ERR_SUCC ? CiCdConstants::DEPLOY_STATUS_SUCCESS : CiCdConstants::DEPLOY_STATUS_FAIL;
                    $this->saveJobLogResult($saveLogId, $jobDoneResult['jid'], $deployStatus, $jobDoneResult['err_msg']);
                    
                    LogUtil::info('deploy test result', $jobDoneResult, LogUtil::LOG_CI);

                    // 只要一个失败；此次部署就失败
                    if ($jobDoneResult['err_code'] != CiCdConstants::ERR_SUCC) {
                        $isJobSuccess = false;
                    }
                }
            }
        }
        //保存部署job 状态
        //$currentAllJobStatus = $isJobSuccess == true ? $deployStatusAll[CiCdConstants::KEY_SUCCESS] : $deployStatusAll[CiCdConstants::KEY_FAIL];
        $currentDeployStepStatus = $isJobSuccess == true ? CiCdConstants::DEPLOY_STATUS_SUCCESS : CiCdConstants::DEPLOY_STATUS_FAIL;

        //$this->saveJobResult ($deployId, $currentAllJobStatus);
        //$this->updateCiCdStepStatus($deployId, $currentDeployStepStatus);
        $this->updateCiCdStepStatus($deployId, $currentDeployStepStatus, $saveedCiCdStepId);

        //todo:判断结束状态
        //$this->saveDeployOperate ($deployId, $currentAllJobStatus);
        $this->saveCiCdProject($deployId, $currentDeployStepStatus);
        $this->unlockProject ($projectId);
        
        //$deployInfo = CiDeployProject::where('deploy_id', '=', $deployId)->first();
        //$deployInfo = CiCdStep::where('deploy_id', '=', $deployId)->first();
        $deployInfo = CiCdStep::where('id', '=', $saveedCiCdStepId)->first();
        if (isset($deployInfo)) {
            //保存整体流程log
            $statusDesc = CiCdConstants::getDeployStepACtionStatusDesc($deployInfo->deploy_step, $deployInfo->deploy_action, $deployInfo->deploy_status); 
            $ciLog = new CiStepStateLog ();
            {
                $ciLog->project_id    = $deployInfo->project_id;
                $ciLog->project_name  = $deployInfo->project_name;
                $ciLog->gaea_build_id = $deployInfo->gaea_build_id;
                //$ciLog->state         = CiCdConstants::$deployStatusDesc[$currentAllJobStatus];
                //$ciLog->log_content   = CiCdConstants::$deployStatusDesc[$currentAllJobStatus];
                //$ciLog->state         = $this->getStepStateByDeployState ($deployJobType, $isJobSuccess);
                $ciLog->task_step    = CiCdConstants::CICD_TASK_STEP_DEPLOY;
                $ciLog->task_step_status  = $deployInfo->deploy_step . '|' . $deployInfo->deploy_action . '|' . $deployInfo->deploy_status;
                $ciLog->task_step_status_desc    = $statusDesc;
                $ciLog->user_id       = $deployInfo->user_id;
                $ciLog->user_name     = $deployInfo->user_name;
                $ciLog->started_time     = $deployInfo->started_time;
                $ciLog->finished_time    = Carbon::now();
                $ciLog->save();
            }
        }

        $this->sendDingDingByProjectId($projectId, $projectName, $deployId, $isJobSuccess, $saveedCiCdStepId);
        return true;
    }/*}}}*/

    //获取当前项目的人员；
    private function sendDingDingByProjectId($projectId , $projectName, $deployId, $isSuccess, $saveedCiCdStepId)
    {/*{{{*/
        
        $userMobiles = $this->getSendDingDingMobiles ($projectId, $deployId, $isSuccess, $saveedCiCdStepId);
        if (!isset ($userMobiles)) {
            LogUtil::info('Send Dindin error', ["not find user mobiles, projectid=$projectId, deployId=$deployId, isSuccess=$isSuccess"], LogUtil::LOG_CI);
            return false;
        }

        $content  = $this->getSendDingDingContent ($projectId, $projectName, $deployId, $isSuccess, $saveedCiCdStepId);
        if (empty ($content)) {
            LogUtil::info('Send Dindin error', ["not find send content, projectid=$projectId, deployId=$deployId, isSuccess=$isSuccess"], LogUtil::LOG_CI);
            return false;
        }

        LogUtil::warning('Send Dindin error', ["mobiles" => $userMobiles, "content" => $content], LogUtil::LOG_CI);
        $result = app('notice')->sendDindin($userMobiles, $content);
        if ($result == false) {
            LogUtil::warning('Send Dindin error', ["mobiles" => $userMobiles, "content" => $content], LogUtil::LOG_JOB);
            LogUtil::info('Send Dindin error', ["request dingding error, projectid=$projectId, deployId=$deployId, isSuccess=$isSuccess"], LogUtil::LOG_CI);
            // 抛出异常 FIXME
        }

        return $result;
    }/*}}}*/

    private function getSendDingDingMobiles ($projectId, $deployId, $isSuccess, $saveedCiCdStepId)
    {/*{{{*/
        $mobiles = [];

        if ($isSuccess) {

            //成功；通知该项目的所有人
            $data = CiProjectMember::fetchMemberByProjectId($projectId);
            foreach ( $data as $item ) {
                if ( $item->mobile ) {
                    if (!in_array($item->mobile, $mobiles)) {
                        $mobiles[] = $item->mobile;
                    }
                }
            }

        } else {

            //失败；只通知一个人;触发部署的人
            //$data = CiDeployOperate::getDeployUser($projectId, $deployId);
            $data = CiCdStep::where('id', '=', $saveedCiCdStepId)->first();
            $userData = AdminUser::where('id', '=', $data->user_id)->first();
            if (isset($userData)) {
                $mobiles[] = $userData->mobile;
            }

        }

        return $mobiles;
    }/*}}}*/

    private function getSendDingDingContent ($projectId, $projectName, $deployId, $isSuccess, $saveedCiCdStepId)
    {/*{{{*/
        //$data = CiDeployOperate::where('project_id', '=', $projectId)
            //->where('deploy_id', '=', $deployId)->first();
        $data = CiCdStep::where('id', '=', $saveedCiCdStepId)->first();
        $data1 = CiCdProject::where('deploy_id', '=', $deployId)->first();

        $content = ''; 
        if (isset($data)) {
            $content .="[发布项目]：%s \n";
            $content .="[发 布 人]：%s \n";
            $content .= $isSuccess ? "[发布结果]: 成功 \n" : "[发布结果]: 失败 \n" ;
            $content .="[发布描述]：%s \n";
            $content .="[完成时间]：%s \n";
            //$content .="测 试 人：%s \n";
            //$content = sprintf( $content, $data->user_name, $data->title, '' );
            $content = sprintf(
                $content,
                $projectName,
                $data->user_name,
                $data1->title,
                Carbon::now()
            );
        }

        return $content;
    }/*}}}*/

    // 错误结果记录
    //private function parseK8sErrorResult ($result, $errmsg, $saveLogId, $deployStatusAll)
    private function parseK8sErrorResult ($result, $errmsg, $saveLogId)
    {/*{{{*/
        LogUtil::error($errmsg, [$result], LogUtil::LOG_JOB);
        //$deployStatus = $deployStatusAll[CiCdConstants::KEY_FAIL];
        $deployStatus = CiCdConstants::DEPLOY_STATUS_FAIL;
        $this->saveJobLogResult($saveLogId, -1, $deployStatus, $errmsg .'错误原因：'.$result['errmsg']);
    }/*}}}*/

    // 检查是否有指定数量的pod正确启动,并做记录
    //private function checkK8sPodsFinish ($projectName, $version, $saveLogId, $deployStatusAll, $replica)
    //private function checkK8sPodsFinish ($projectName, $version, $saveLogId, $deployStatus, $replica)
    //private function checkK8sPodsFinish ($projectName, $version, $saveLogId, $replica)
    private function checkK8sPodsFinish ($projectName, $version, $saveLogId, $replica, $ciCdStepCurrentStep )
    {/*{{{*/
        $interval  = 1;
        $zeroLimit = 3;       // 最大的启动等待时间
        $timeout   =  180;    // 最大检测时间设置为180秒

        while ($timeout > 0) {
            $podsDesc = "\n";
            //$pods = Kubernetes::getPodsByRc($projectName, $version);
            $pods = Kubernetes::getPodsByRc($projectName, $version, $ciCdStepCurrentStep );
            if ($zeroLimit > 0) {
                if (count($pods) == 0) {
                    sleep($interval);
                    $timeout = $timeout - $interval;
                    --$zeroLimit;
                    continue;
                }
            } 


            $finishFlag = true;
            $notPendingCount = 0;

            foreach ($pods as $pod) {
                // 只要有PENDING状态的pod存在，就表示没有执行完成
                if ($pod['phase'] == Kubernetes::POD_PHASE_PENDING) {
                    $finishFlag = false;
                } else {
                    ++$notPendingCount;
                }
                $podsDesc .= $pod['desc'];
            }
            $podsDesc .= "\n";

            // 一次查询输出一个.号
            //$this->saveJobLogResult($saveLogId, -1, $deployStatusAll[CiCdConstants::KEY_RUNNING], ".");
            $this->saveJobLogResult($saveLogId, -1, CiCdConstants::DEPLOY_STATUS_RUNNING, ".");

            // 必须有指定数量的pod处于not pending状态，同时应用于增加和缩减replica数
            if ($finishFlag == true && $notPendingCount == $replica) {
                //$this->saveJobLogResult($saveLogId, -1, $deployStatusAll[CiCdConstants::KEY_RUNNING], $podsDesc);
                $this->saveJobLogResult($saveLogId, -1, CiCdConstants::DEPLOY_STATUS_RUNNING, $podsDesc);
                return true;
            } 

            sleep($interval);
            $timeout = $timeout - $interval;
        }

        return false;
    }/*}}}*/

    private function unlockProject ($projectId)
    {/*{{{*/
        // 解除锁定当前准备部署项目
        //$deployStatus = 0;
        $deployStatus = CiCdConstants::PROJECT_LOCK_TATUS_UNLOCKED;
        $updateRows = CiProject::where('project_id', '=', $projectId)
            ->update(['deploy_status' => $deployStatus]);
    }/*}}}*/

    private function saveCiCdProject ($deployId, $status)
    {/*{{{*/
        //todo:临时用内存保存记录的job状态判断,判断job ok
        CiCdProject::where('deploy_id', '=', $deployId)
            ->update(['deploy_status' => $status, 'finished_time' => Carbon::now()]);
        return true;
    }/*}}}*/

    //private function saveDeployOperate ($deployId, $deployStatus)
    //{[>{{{<]
        ////todo:临时用内存保存记录的job状态判断,判断job ok

        //CiDeployOperate::where('deploy_id', '=', $deployId)
            //->update(['status' => $deployStatus, 'finishtime' => Carbon::now()]);
        ////->update(['status' => $deployStatus, 'finishtime' => Carbon::now()]);
        //return true;

    //}[>}}}<]

    private function updateCiCdStepStatus ($deployId, $deployStepStatus, $saveedCiCdStepId)
    {/*{{{*/
        //todo:临时用内存保存记录的job状态判断,判断job ok
        //CiCdStep::where('deploy_id', '=', $deployId)
            //->update(['deploy_status' => $deployStepStatus, 'finished_time' => Carbon::now()]);
        //return true;
        CiCdStep::where('id', '=', $saveedCiCdStepId)
            ->update(['deploy_status' => $deployStepStatus, 'finished_time' => Carbon::now()]);
        return true;

    }/*}}}*/

    //获取当前部署阶段
    private function getCiCdCurrentStep ($saveedCiCdStepId)
    {/*{{{*/
        $data = CiCdStep::where('id', '=', $saveedCiCdStepId)->first();
        if (!is_null($data)) {
            return $data->deploy_step;
        }
        return '';
    }/*}}}*/

    //private function saveJobResult ($deployId, $deployStepStatus)
    //{[>{{{<]
        ////todo:临时用内存保存记录的job状态判断,判断job ok
        //CiCdStep::where('deploy_id', '=', $deployId)
            //->update(['deploy_status' => $deployStepStatus, 'finishtime' => Carbon::now()]);
        //return true;

        ////CiDeployProject::where('deploy_id', '=', $deployId)
            ////->update(['status' => $deployStatus, 'finishtime' => Carbon::now()]);
        ////return true;

        //////修改此次构建结果
        ////$noSuccessRows = CiDeployProjectLog::where('deploy_id', '=', $deployId)
        //////->where('status', '<>', CiCdConstants::JENKINS_JOB_STATUS_SUCCESS)->count();
        ////->where('status', '<>', $deployStatus2)->count();
        //////读写分离；可能导致延迟
        ////$deployStatus = CiCdConstants::DEPLOY_STATUS_BETA_SUCCESS;
        ////if ($isJobSuccess == true && $noSuccessRows > 0) {
        //////$deployStatus = CiCdConstants::JENKINS_JOB_STATUS_SUCCESS;
        ////$deployStatus = CiCdConstants::DEPLOY_STATUS_BETA_SUCCESS;
        ////}
        ////if ($isJobSuccess == true) {
        //////$deployStatus = CiCdConstants::JENKINS_JOB_STATUS_SUCCESS;
        ////$deployStatus = CiCdConstants::DEPLOY_STATUS_BETA_SUCCESS;
        ////}
        ////CiDeployProject::where('deploy_id', '=', $deployId)
        ////->update(['status' => $deployStatus, 'finishtime' => Carbon::now()]);
    //}[>}}}<]

    private function saveJobLogResult ($saveLogId, $jid, $result, $resultLog)
    {/*{{{*/
        //$data = CiDeployProjectLog::where('id', '=', $saveLogId)->first();
        $data = CiCdServerLog::where('id', '=', $saveLogId)->first();
        if (!isset($data)) {
            return false;
        }

        $data->jid              = $jid;
        $data->deploy_status    = $result;
        $data->deploy_log      .= $resultLog;
        $data->deploy_log      .= $resultLog != "." ? "\n" : "";
        $data->finished_time    = Carbon::now();
        //if ($resultLog != ".") {
            //$data->deploy_log .= "\n";
        //}
        
        $data->save();
        return true;
    }/*}}}*/

    private function deployOneHost ($hostName, $deployId ,$cmd, $jobDoneUrl)
    {/*{{{*/
        $jobDoneResult = [];

        ////取消和停止状态；不在调用jobdone
        //$isContinueStatus = [CiCdConstants::DEPLOY_STATUS_BETA_CANCEL, CiCdConstants::DEPLOY_STATUS_BETA_STOP] ;
        //if (in_array($deployProjectLog->status, $isContinueStatus)) {
        //continue;
        //}

        $result = app('jobdone')->doJob(JobDone::API_CMD_EXEC, [
            //'tgt'   => join(',', $deployHostNames), //并行执行方式
            'tgt'   => $hostName,
            //'cmd'   => $cmd['cmd'],
            'cmd'   => $cmd,
            'sync'  => JobDone::EXEC_ASYNC
            ], $output, $jobDoneUrl);
        LogUtil::info('deploy jobdone result', ['result' => $result, 'tgt'   => $hostName, 'jobdoneUrl' => $jobDoneUrl], LogUtil::LOG_CI);

        //LogUtil::info('deploy jobdone output', ['out' => $output], LogUtil::LOG_CI);
            
        if (!$result) {
            return $this->formatJobDoneResult('-1', CiCdConstants::ERR_DEPLOY_JOBDONE_LOAD_FAIL);
        }
        $jid = '';
        if ($result) {
            $jid = $output['jid'];
            //if ($output['status'] == JobDone::JOB_FINISHED) {
            //if ($output['return'][0]['ret'] == JobDone::NODE_JOB_FINISHED) {
            //$jid = $output['jid'];
            //}
            //}
        }
        if ($jid == '') {
            return $this->formatJobDoneResult('-1', CiCdConstants::ERR_DEPLOY_JOBDONE_LOAD_FAIL);
        }
        //  查询结果
        $timeout = 180;
        $interval = 1000 * 1000;  // 1s    
        $tickCount = ($timeout * 1000 * 1000) / $interval;
        while ($tickCount > 0) {        
            $result = app('jobdone')->doJob(JobDone::API_RET_QUERY, ['jid' => $jid], $output, $jobDoneUrl);
            //LogUtil::info('query job result11111111', ['query job' => $result, 'output' => $output , 'job_url' => $jobDoneUrl], LogUtil::LOG_CI);

            if ($result == false) {
                LogUtil::info('query job result error', ['query job' => $result, 'output' => $output ], LogUtil::LOG_CI);
                return $this->formatJobDoneResult($jid, CiCdConstants::ERR_DEPLOY_JOBDONE_SEARCH_RESULT_FAIL,'失败');
            }

            if ($output['status'] == JobDone::JOB_RUNNING) {
                usleep($interval);     
                $tickCount--;
            } else {
                LogUtil::info('query job result success', ['query job' => $result, 'output' => $output ], LogUtil::LOG_CI);
                if ($output['status'] == JobDone::JOB_FINISHED) {
                    if ($output['return'][0]['ret'] == JobDone::NODE_JOB_FINISHED) {
                        //$isJobSuccess = $isJobSuccess == true ? true : false;
                        $log = $output['return'][0]['output'];
                        //success 
                        return $this->formatJobDoneResult($jid, CiCdConstants::ERR_SUCC, $log);
                    } else {
                        return $this->formatJobDoneResult($jid, CiCdConstants::ERR_DEPLOY_JOBDONE_NODE_FAIL);
                    }
                } else {
                    return $this->formatJobDoneResult($jid, CiCdConstants::ERR_DEPLOY_JOBDONE_RUN_FAIL);
                }
                // job 结束；退出循环
                break;
            }
        }

        if($tickCount <=0) {
            return $this->formatJobDoneResult($jid, CiCdConstants::ERR_DEPLOY_JOBDONE_SEARCH_RESULT_TIMEOUT);
        }
    }/*}}}*/

    private function formatJobDoneResult ($jobDoneId = 0, $errCode = 0, $errMsg='')
    {/*{{{*/
        if (!empty ($errMsg)) {
            return [ 'jid' => $jobDoneId ,'err_code' => $errCode, 'err_msg' => CiCdConstants::$errDescription[$errCode]."\n".$errMsg ];
        }
        return [ 'jid' => $jobDoneId, 'err_code' => $errCode, 'err_msg' => CiCdConstants::$errDescription[$errCode] ];
    }/*}}}*/

    //private function getDeployStatusAll ($deployJobType)
    //{[>{{{<]
        //$deployStatus = [];
        //switch ($deployJobType) {
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_BETA:
            //// 测试环境发布 
            //$deployStatus = CiCdConstants::$deployStatusBeta;
            //break;
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_BETA_ROLLBACK:
            //// 测试环境回滚 
            //$deployStatus = CiCdConstants::$deployStatusBetaRollBack;
            //break;
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_A_LEVEL:
            ////slave 发布
            //$deployStatus = CiCdConstants::$deployStatusALevel;
            //break;
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_A_LEVEL_ROLLBACK:
            ////slave 回滚
            //$deployStatus = CiCdConstants::$deployStatusALevelRollBack;
            //break;
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_B_LEVEL:
            ////正式发布
            //$deployStatus = CiCdConstants::$deployStatusBLevel;
            //break;
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_B_LEVEL_ROLLBACK:
            ////正式发布线上
            //$deployStatus = CiCdConstants::$deployStatusBLevelRollBack;
            //break;
        //default:
        //}
        //return $deployStatus;
    //}[>}}}<]

    //private function getStepStateByDeployState ($deployJobType, $isSuccess=false)
    //{[>{{{<]
        //$deployLogState = 0;
        //switch ($deployJobType) {
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_BETA:
            //// 测试环境发布 
            //$deployLogState = $isSuccess == true ? CiCdConstants::LOG_DEPLOY_BETA_SUCCESS : CiCdConstants::LOG_DEPLOY_BETA_FAIL;
            //break;
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_BETA_ROLLBACK:
            //// 测试环境回滚 
            //$deployLogState = $isSuccess == true ? CiCdConstants::LOG_DEPLOY_BETA_ROLLBACK_SUCCESS : CiCdConstants::LOG_DEPLOY_BETA_ROLLBACK_SUCCESS;
            //break;
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_A_LEVEL:
            ////slave 发布
            //$deployLogState = $isSuccess == true ? CiCdConstants::LOG_DEPLOY_A_LEVEL_SUCCESS : CiCdConstants::LOG_DEPLOY_A_LEVEL_FAIL;
            //break;
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_A_LEVEL_ROLLBACK:
            ////slave 回滚
            //$deployLogState = $isSuccess == true ? CiCdConstants::LOG_DEPLOY_A_LEVEL_ROLLBACK_SUCCESS : CiCdConstants::LOG_DEPLOY_A_LEVEL_ROLLBACK_FAIL;
            //break;
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_B_LEVEL:
            ////正式发布
            //$deployLogState = $isSuccess == true ? CiCdConstants::LOG_DEPLOY_B_LEVEL_SUCCESS : CiCdConstants::LOG_DEPLOY_B_LEVEL_FAIL;
            //break;
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_B_LEVEL_ROLLBACK:
            ////正式发布线上
            //$deployLogState = $isSuccess == true ? CiCdConstants::LOG_DEPLOY_B_LEVEL_ROLLBACK_SUCCESS : CiCdConstants::LOG_DEPLOY_B_LEVEL_ROLLBACK_FAIL;
            //break;
        //default:
        //}
        //return $deployLogState;
    //}[>}}}<]

    //private function deployActionType ($deployJobType)
    //{[>{{{<]
        //$deployActionType = '';
        //switch ($deployJobType) {
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_BETA:
            //// 测试环境发布 
            //$deployActionType = 'deploy';
            //break;
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_BETA_ROLLBACK:
            //// 测试环境回滚 
            //$deployActionType = 'rollback';
            //break;
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_A_LEVEL:
            ////slave 发布
            //$deployActionType = 'deploy';
            //break;
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_A_LEVEL_ROLLBACK:
            ////slave 回滚
            //$deployActionType = 'rollback';
            //break;
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_B_LEVEL:
            ////正式发布
            //$deployActionType = 'deploy';
            //break;
        //case CiCdConstants::DEPLOY_JOBDONE_TYPE_B_LEVEL_ROLLBACK:
            ////正式发布线上回滚
            //$deployActionType = 'rollback';
            //break;
        //default:
        //}
        //return $deployActionType;
    //}[>}}}<]
}

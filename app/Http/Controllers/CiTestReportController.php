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

use App\Components\Jenkins\CiCdConstants;

use App\Models\Gaea\CiBuildProject;
use App\Models\Gaea\CiTestReport;
use App\Models\Gaea\CiStepStateLog;
use App\Models\Gaea\AdminUser;

use App\Components\Kubernetes\Kubernetes;

class CiTestReportController extends Controller
{
    public function testReport (Request $request)
    {/*{{{*/
        $actions = MethodUtil::getActions();
        $this->validate($request, [
            'action'  => "required|in:$actions"
        ]);

        $action = $request->Input('action');
        switch ($action) {
        case Constants::REQUEST_TYPE_ADD:
            //研发申请测试；测试
            $this->validate( $request, [
                'gaea_build_id'    => 'required',
                'commit_title'     => 'required',
                'commit_desc'      => 'required',
                'test_member'      => 'required',
                'test_member_name' => 'required',
            ]); 

            $gaeaBuildId  = $request->Input('gaea_build_id');
            $buildProject = CiBuildProject::where('gaea_build_id', '=', $gaeaBuildId)->first();

            if ($buildProject == null) {
                LogUtil::info('not find data',["data is $gaeaBuildId"], LogUtil::LOG_CI);
                return response()->clientError(-1, 'not find data');
            }

            $ciTestRepot = new CiTestReport();
            { 
                $ciTestRepot->gaea_build_id      = $buildProject->gaea_build_id;
                $ciTestRepot->commit_time        = Carbon::now();
                $ciTestRepot->commit_user        = Constants::getAdminId();
                $ciTestRepot->commit_user_name   = Constants::getAdminName();
                $ciTestRepot->test_member        = $request->Input('test_member');
                $ciTestRepot->test_member_name   = $request->Input('test_member_name');
                $ciTestRepot->commit_title       = $request->Input('commit_title');
                $ciTestRepot->commit_desc        = $request->Input('commit_desc');
                $ciTestRepot->test_type          = CiCdConstants::TEST_TYPE_BETA;
                $ciTestRepot->test_result_status = CiCdConstants::TEST_RESULT_STATUS_WAITING;
                $ciTestRepot->save();
            }

            //保存log
            $ciLog = new CiStepStateLog ();
            {
                $ciLog->project_id    = $buildProject->project_id;
                $ciLog->project_name  = $buildProject->project_name;
                $ciLog->gaea_build_id = $buildProject->gaea_build_id;
                //$ciLog->state         = "提交测试代码";
                //$ciLog->log_content   = "提交测试代码; 等待测试人员 ".Constants::getAdminName()." 进行测试";
                //$ciLog->state         = CiCdConstants::LOG_APPLY_TEST;
                $ciLog->task_step    = CiCdConstants::CICD_TASK_STEP_TEST;
                $ciLog->task_step_status  = CiCdConstants::LOG_APPLY_TEST;
                $ciLog->task_step_status_desc = CiCdConstants::$logStepStateDesc[CiCdConstants::LOG_APPLY_TEST];

                $ciLog->user_id       = Constants::getAdminId();
                $ciLog->user_name     = Constants::getAdminName();
                $ciLog->started_time     = Carbon::now();
                $ciLog->finished_time    = Carbon::now();
                $ciLog->save();
            }

            //发送丁丁
            $sendUserId = $request->Input('test_member');
            $content  = "提测申请 \n" ;
            $content .= "[项目名称]：%s \n";
            $content .= "[提 测 人]：%s \n" ;
            $content .= "[构 建 Id]：%s \n";
            $content .= "[提交时间]：%s \n";
            $content  = sprintf( $content, $buildProject->project_name, Constants::getAdminName(), $buildProject->gaea_build_id, Carbon::now());
            $this->addTestReportSendMsg($sendUserId, $content);
          
            //todo:起点暂时不是提测点
            ////记录记录
            //$gaeaBuildId = $request->Input('gaea_build_id');
            //$buildInfo     = CiBuildProject::where('gaea_build_id', '=',$gaeaBuildId)->first();
            //$projectInfo   = CiProject::where('project_id', '=', $buildInfo->project_id)->first();
            //$projectName   = $projectInfo->project_name;
            //$projectId     = $projectInfo->project_id;

            //$deployId = uniqid();
            //$deployOperate = new CiDeployOperate();
            //{
                //$deployOperate->deploy_id     = $deployId;
                //$deployOperate->gaea_build_id = $gaeaBuildId;
                //$deployOperate->project_id    = $projectId;
                //$deployOperate->project_name  = $projectName;
                //$deployOperate->status        = CiCdConstants::DEPLOY_STATUS_WAITING_RUN;
                //$deployOperate->createtime    = Carbon::now();
                //$deployOperate->user_id       = Constants::getAdminId();
                //$deployOperate->user_name     = Constants::getAdminName(); 
                //$deployOperate->title         = $request->Input('commit_title'); 
                //$deployOperate->desc          = $request->Input('commit_desc');;
                //$deployOperate->save();
            //}

            return response()->clientSuccess([]);
            break;
        case Constants::REQUEST_TYPE_UPDATE:
            //研发申请测试；测试
            $this->validate( $request, [
                'gaea_build_id'      => 'required',
                //'test_result'      => 'required',
                'test_result_status' => 'required',
                'test_content'       => 'required'
            ]); 

            $gaeaBuildId  = $request->Input('gaea_build_id');
            $buildProject = CiBuildProject::where('gaea_build_id', '=', $gaeaBuildId)->first();
            if ($buildProject == null) {
                LogUtil::info('not find data',["data is $gaeaBuildId"], LogUtil::LOG_CI);
                return response()->clientError(-1, 'not find data');
            }

            //$gaeaBuildId = $request->Input('gaea_build_id');
            CiTestReport::where('gaea_build_id', '=', $gaeaBuildId)
                ->update(['begin_test_time' => Carbon::now()]);

            $updateData = [
                'end_test_time'      => Carbon::now(),
                'test_result_status' => $request->Input('test_result_status'),
                'test_content'       => $request->Input('test_content'),
            ];
            $ciTestRepot = CiTestReport::where('gaea_build_id', '=', $gaeaBuildId)
                ->update($updateData);

            //保存log
            $testState = CiCdConstants::LOG_DEPLOY_BETA_TESTED_NOTPASS;
            switch ($request->Input('test_result_status')){
                case CiCdConstants::TEST_RESULT_STATUS_PASS:
                    $testState = CiCdConstants::LOG_DEPLOY_BETA_TESTED_PASS;
                    break;
                case CiCdConstants::TEST_RESULT_STATUS_NOTPASS:
                    $testState = CiCdConstants::LOG_DEPLOY_BETA_TESTED_NOTPASS;
                    break;
                case CiCdConstants::TEST_RESULT_STATUS_HASBUG:
                    $testState = CiCdConstants::LOG_DEPLOY_BETA_TESTED_HAVEBUG;
                    break;
                default:
                    $testState = CiCdConstants::LOG_DEPLOY_BETA_TESTED_NOTPASS;
                    break;
            }

            $ciLog = new CiStepStateLog ();
            {
                $ciLog->project_id    = $buildProject->project_id;
                $ciLog->project_name  = $buildProject->project_name;
                $ciLog->gaea_build_id = $buildProject->gaea_build_id;
                //$ciLog->state         = "测试代码完成";
                //$ciLog->log_content   = "测试代码完成, 测试结果: $testResultDesc";
                //$ciLog->state         = CiCdConstants::LOG_DEPLOY_BETA_TESTED_PASS;
                //$ciLog->state         = $testState;
                $ciLog->task_step    = CiCdConstants::CICD_TASK_STEP_TEST;
                $ciLog->task_step_status  = $testState;
                $ciLog->task_step_status_desc = CiCdConstants::$logStepStateDesc[$testState];

                $ciLog->user_id       = Constants::getAdminId();
                $ciLog->user_name     = Constants::getAdminName();
                $ciLog->started_time     = Carbon::now();
                $ciLog->finished_time    = Carbon::now();
                $ciLog->save();
            }

            //$ciLog = new CiStepStateLog ();
            //{
                //$ciLog->project_id    = $buildProject->project_id;
                //$ciLog->project_name  = $buildProject->project_name;
                //$ciLog->gaea_build_id = $buildProject->gaea_build_id;
                ////$ciLog->state         = "测试代码完成";
                ////$ciLog->log_content   = "测试代码完成, 测试结果: $testResultDesc";
                ////$ciLog->state         = CiCdConstants::LOG_DEPLOY_BETA_TESTED_PASS;
                //$ciLog->state         = $testState;
                //$ciLog->user_id       = Constants::getAdminId();
                //$ciLog->user_name     = Constants::getAdminName();
                //$ciLog->save();
            //}

            $testResultDesc = CiCdConstants::$testResultStatusDesc[$request->Input('test_result_status')];
            //发送丁丁
            //$sendUserId = $buildProject->user_id; // $request->Input('test_member');
            $ciTestRepotData = CiTestReport::where('gaea_build_id', '=', $gaeaBuildId)->first();
            $sendUserId = $ciTestRepotData->commit_user; // $request->Input('test_member');

            //$this->addTestReportSendMsg($sendUserId, $content);
            $content  = "测试完成 \n" ;
            $content .= "[项目名称]：%s \n";
            $content .= "[测 试 人]：%s \n" ;
            $content .= "[测试结果]：%s \n";
            $content .= "[构 建 Id]：%s \n";
            $content .= "[完成时间]：%s \n";
            $content  = sprintf (
                $content, 
                $buildProject->project_name, 
                Constants::getAdminName(), 
                $testResultDesc,
                $buildProject->gaea_build_id, 
                Carbon::now()
            );

            $this->addTestReportSendMsg($sendUserId, $content);

            return response()->clientSuccess([]);
            break;
        case Constants::REQUEST_TYPE_GET:
            $this->validate( $request, [
                'gaea_build_id' => 'required',
            ]); 

            $gaeaBuildId = $request->Input('gaea_build_id');
            $data = CiTestReport::where('gaea_build_id', '=', $gaeaBuildId)->first();
            
            if (!isset($data)) {
                return response()->clientError(-1, 'not find data');
            }
            return response()->clientSuccess($data);
            break;
        case Constants::REQUEST_TYPE_LIST:
            return response()->clientSuccess([]);
        };
    }/*}}}*/

    private function addTestReportSendMsg ($sendUserId, $content) 
    {/*{{{*/ 
        $userData = AdminUser::where('id', '=', $sendUserId)->first();
        //LogUtil::info('add test report user info', ["$userData"], LogUtil::LOG_CI);
        if (!isset ($userData)) {
            LogUtil::info(' Send Dindin error', ["not find user mobiles "], LogUtil::LOG_CI);
            return false;
        }

        $result = app('notice')->sendDindin($userData->mobile, $content);
        if ($result == false) {
            LogUtil::warning('Send Dindin error', ["mobiles" => $userData->mobile, "content" => $content], LogUtil::LOG_JOB);
            LogUtil::info('Send Dindin error', ["request dingding error, mobiles= $userData->mobile, content= $content"], LogUtil::LOG_CI);
        }

        return $result;
    }/*}}}*/
}

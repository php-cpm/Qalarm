<?php
namespace App\Components\CiJob;

use App\Components\Utils\HttpUtil;

use Log;
use App\Components\Utils\LogUtil;

use App\Components\Jenkins\JenkinsDslHelper;
use App\Components\Jenkins\JenkinsHelp;
use App\Components\Jenkins\CiCdConstants;

class CiJob 
{
    public function __construct()
    {/*{{{*/
    }/*}}}*/

    /**
     * @brief createAndUpdateJobs 
     * @param $projectName 项目名
     * @param $repo  仓库名
     * @param $branch 分支
     *
     * @return false | true
     */
    public function createAndUpdateJobs($projectName, $repo, $branch)
    {/*{{{*/
        $csmDsl = JenkinsDslHelper::dslSCMBlock($repo, $branch, env('CI_JENKINS_CREDENTIAL'));
        //构建参数
        //$paramBlockDsl = JenkinsDslHelper::dslOneParamBlock('gaga','gaea_id');
        $paramBlockDsl   = JenkinsDslHelper::dslOneParamBlock(CiCdConstants::BUILD_PARAMS_EXT);
        $paramBlockDsl2  = JenkinsDslHelper::dslOneParamBlock(CiCdConstants::BUILD_PARAMS_DEPLOY_AFTER_SH);
        $paramBlockDsl3  = JenkinsDslHelper::dslOneParamBlock(CiCdConstants::BUILD_PARAMS_BUILD_BEFORE_SH);
        $paramBlockDsl4  = JenkinsDslHelper::dslOneParamBlock(CiCdConstants::BUILD_PARAMS_PROJECT_BRANCH);
        $paramBlockDsl5  = JenkinsDslHelper::dslOneParamBlock(CiCdConstants::BUILD_PARAMS_SSH_USER);
        $paramBlockDsl6  = JenkinsDslHelper::dslOneParamBlock(CiCdConstants::BUILD_PARAMS_DEPLOY_FILES);
        $paramBlockDsl7  = JenkinsDslHelper::dslOneParamBlock(CiCdConstants::BUILD_PARAMS_DEPLOY_BLACK_FILES);
        //$paramBlockDsl8  = JenkinsDslHelper::dslOneParamBlock(CiCdConstants::BUILD_PARAMS_DIFF_FILES_SH);
        $paramBlockDsl8  = JenkinsDslHelper::dslOneParamBlock(CiCdConstants::BUILD_PARAMS_OLD_BUILD_PACKAGE);
        $paramBlockDsl9  = JenkinsDslHelper::dslOneParamBlock(CiCdConstants::BUILD_PARAMS_GAEA_BUILD_ID);
        //$callbackCmd   = JenkinsDslHelper::dslCallbackCmd('http://172.16.10.30:12815/');
        $callbackCmd     = JenkinsDslHelper::dslCallbackCmd();
        $checkoutCmd     = JenkinsDslHelper::dslCheckoutBranchCmdBuildJob();
        $backupSrcCmd    = JenkinsDslHelper::dslBackupSrcCmd();
        $diffFilesCmd    = JenkinsDslHelper::dslDiffFilesCmd();
        //编译包指令；数据来源于 gaea 数据库项目的配置中；在jenkins中生成sh 脚本，然后执行 //这个在最后执行
        $buildBeforeCmd  = JenkinsDslHelper::dslBuildBeforeCmd();

        $buildBeforeDsl = JenkinsDslHelper::dslExecuteBlock([$callbackCmd,$checkoutCmd,$backupSrcCmd,$diffFilesCmd,$buildBeforeCmd]); 
        $blocks = [$csmDsl, $buildBeforeDsl,$paramBlockDsl,$paramBlockDsl2,$paramBlockDsl3,$paramBlockDsl4,$paramBlockDsl5,$paramBlockDsl6,$paramBlockDsl7,$paramBlockDsl8,$paramBlockDsl9];

        $dsl = JenkinsDslHelper::dslJobBlock($projectName, $blocks, JenkinsDslHelper::GAEA_STEP_BUILD);
        
        /*LogUtil::info('调用 构建系统',['project_name' => $projectName, 'dsl_config' => $dsl]);*/
        $result = JenkinsHelp::gaeaCreateJenkinsJob2($projectName, $dsl);

        return $result;
    }/*}}}*/

    /**
     * @brief launchSonarJobs 执行job
     *
     * @param $projectName
     * @param $branch
     * @param $gitHead
     * @param $checkDirs
     *
     * @return 
     */
    public function launchJobs($projectName, $branch='master', $post=[], $gaeaBuildId)
    {/*{{{*/
        //$jobName = JenkinsDslHelper::$gaeaSteps[JenkinsDslHelper::GAEA_STEP_BUILD]['name'] .'_'.$projectName.'_'.$branch;
        $jobName = JenkinsDslHelper::$gaeaSteps[JenkinsDslHelper::GAEA_STEP_BUILD]['name'] .'_'.$projectName;
        $result = JenkinsHelp::launchJenkinsJobs($jobName, $post, $gaeaBuildId);
        return $result;
    }/*}}}*/

}

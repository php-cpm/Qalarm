<?php
namespace App\Components\Jenkins;

use JenkinsKhan\Jenkins;
use App\Components\Utils\HttpUtil;
use App\Components\Utils\MethodUtil;
use App\Components\Utils\LogUtil;
use App\Components\Utils\XmlArrayHelp;

class JenkinsDslHelper
{

    const GAEA_STEP_CODE   = 1;
    const GAEA_STEP_BUILD  = 2;

    const GAEA_DEPLOY_SERVER_NAME = 'gaea_deploy_server';
    const GAEA_TARGET_DIR         = 'gaea_targets';

    const GAEA_JENKINS_CALLBACK_NAME = 'gaea_jenkins_callback';

    public static $gaeaSteps = [
        self::GAEA_STEP_CODE => [
            'name'           => 'checkcode',
            'sname'          => '代码规范检查',
            'weight'         => '111',
            'job_type'       => '1'
        ],
        self::GAEA_STEP_BUILD => [
            'name'            => 'build',
            'sname'           => '项目构建',
            'weight'          => '115',
            'job_type'        => '2'
        ]
    ];

    /**
     * @brief dslSCMBlock 获取代码控制DSL块
     * @param $repo
     * @param $branch
     * @return 
     */
    public static function dslSCMBlock($repo, $branch, $credentials)
    {/*{{{*/
        $prefix = "scm  {git  {remote  {";
        $suffix = "}}}";

        // git@gitlab.corp.ttyongche.com:core/ttyc_zeus.git
        list($gitUrl, $repoName) = explode(':', $repo);
        list($git, $url) = explode('@', $gitUrl);

        // 去掉.git标示
        $repoName = str_replace('.git', '', $repoName);

        $gitlab = sprintf("github('%s', 'ssh', '%s')", $repoName, $url);
        $credentials = sprintf("credentials('%s')", $credentials);
        $branch      = sprintf("branch('*/%s')", $branch);

        return "$prefix$gitlab\n$credentials\n$branch\n$suffix\n";
    }/*}}}*/

    public static function dslExecuteBlock($cmds = [])
    {/*{{{*/
        $context = $prefix = "steps {";

        foreach ($cmds as $cmd) {
            $shell = sprintf("shell '''%s'''.stripIndent().trim()\n", $cmd);
            $context .= $shell;
        }

        $suffix = "}\n";
        $context .= $suffix;

        return $context;
    }/*}}}*/

    /**
     * @brief sonarExecuteCmd sonar执行的命令
     * @param $params
     * @return 
     */
    public static function sonarExecuteCmd($params = [])
    {/*{{{*/
        $bash        = "/bin/bash";
        $sonarRunner = "/data/gaea-ci/sonar/sonar-runner-2.3/bin/sonar-runner";

        $javaParams = [];
        foreach ($params as $javaParam => $jenkinsParam) {
            $javaParams[] = '-D'.$javaParam .'=${'.$jenkinsParam.'}';
        }

        $javaParamsString = join(' ', $javaParams);

        $cmd = sprintf('%s %s %s', $bash, $sonarRunner, $javaParamsString);


        return $cmd;
    }/*}}}*/

    public static function dslOneParamBlock($param)
    {/*{{{*/
        $context = $prefix = "parameters {";
        $context .= "stringParam '$param' ";
        $suffix = "}\n";

        $context .= $suffix;

        return $context;
    }/*}}}*/

    public static function getSonarJobParams()
    {/*{{{*/
        $params = [
            'sonar.projectKey'      => 'sonar_projectKey',
            'sonar.projectName'     => 'sonar_projectName',
            'sonar.projectVersion'  => 'sonar_projectVersion',
            'sonar.sources'         => 'sonar_sources',
            'sonar.branch'          => 'sonar_branch'
        ];

        return $params;
    }/*}}}*/

    public static function sonarJobsBlock($branch)
    {/*{{{*/
        $context = " \n";

        $params = static::getSonarJobParams();

        foreach ($params as $javaParma => $jenkinsParam ) {
            $context .= static::dslOneParamBlock($jenkinsParam);
        }
        
        // 添加callback参数
        //$context      .= static::dslOneParamBlock('ext');
        $context      .= static::dslOneParamBlock(CiCdConstants::BUILD_PARAMS_GAEA_BUILD_ID);

        $callbackCmd  = static::dslCallbackCmd();
        $checkoutCmd  = static::dslCheckoutBranchCmdSonarJob();
        $backupSrcCmd = static::dslBackupSrcCmd();
        $cmd = static::sonarExecuteCmd($params);
        $context .= static::dslExecuteBlock([$callbackCmd, $checkoutCmd, $backupSrcCmd, $cmd]);

        return $context;
    }/*}}}*/

    /**
     * @brief dslJobBlock 添加通
     *
     * @param $projectName
     * @param $blocks
     * @param $step
     *
     * @return 
     */
    public static function dslJobBlock($projectName, $blocks = [], $step = self::GAEA_STEP_BUILD) 
    {/*{{{*/
        $jobName = self::$gaeaSteps[$step]['name'].'_'.$projectName;
        $context = $prefix = "job('$jobName'){";

        foreach ($blocks as $block) {
            $context  .= $block; 
        }

        // dslPublishBlock
        $context .= static::dslPublishBlock([static::dslPublishOverSsh()]);

        $suffix = "}\n";
        $context .= $suffix;

        return $context;
    }/*}}}*/

    /**
     * @brief dslPublishOverSsh 生成统一的上传部署包的dsl
     * @return 
     */
    public static function dslPublishOverSsh() 
    {/*{{{*/
        $context = $prefix = "publishOverSsh { ";
        $context .= 'server("'.self::GAEA_DEPLOY_SERVER_NAME.'") {';
        $env = MethodUtil::getLaravelEnvName();
        $remoteDir = sprintf('%s/%s', env('CI_GAEA_DEPLOY_DIR'), $env);
        $publishDestDir = sprintf('%s/${JOB_NAME}/last_build', $remoteDir);
        $cmd = @file_get_contents(app_path("Components/Jenkins/jenkins_template/publishOverSsh.sh"));
        // 替换shell脚本中的根目录
        $cmd = str_replace('gaea_remote_path', $remoteDir, $cmd);

        $fileSource = '**/'.self::GAEA_TARGET_DIR.'/*.tar.gz';

        $context .= sprintf(" transferSet { sourceFiles('%s') \n remoteDirectory('%s') \nexecCommand('''%s''') }", $fileSource, $publishDestDir, $cmd);

        $context .= '}';
        $context .= "\n";
        $context .= 'failOnError(true) ';


        $suffix = "}\n";
        $context .= $suffix;


        return $context;
    }/*}}}*/

    /**
     * @brief dslPublishBlock 生成构建后脚本dsl
     * @return 
     */
    public static function dslPublishBlock($cmds = [])
    {/*{{{*/
        $context = $prefix = "publishers { ";

        foreach ($cmds as $cmd) {
            $context .= $cmd;
            $context .= "\n";
        }

        $suffix = " }";
        $context .= $suffix;

        return $context;
    }/*}}}*/

    /**
     * @brief dslCheckoutBranchCmdSonarJob 切换分支dsl
     * @return 
     */
    public static function dslCheckoutBranchCmdSonarJob()
    {/*{{{*/
        $cmd = '/usr/bin/git checkout origin/${sonar_branch}';
        return $cmd;
    }/*}}}*/

    /**
     * @brief dslCheckoutBranchCmdBuildJob 切换分支dsl
     * @return 
     */
    public static function dslCheckoutBranchCmdBuildJob()
    {/*{{{*/
        $cmd = '/usr/bin/git checkout origin/${project_branch}';
        return $cmd;
    }/*}}}*/

    /**
     * @brief dslCheckoutBranchCmd 切换分支dsl
     * @param $branch
     * @return 
     */
    //public static function dslCheckoutBranchCmd($branch)
    //{[>{{{<]
        ////$cmd = sprintf('/usr/bin/git checkout %s', $branch);
        //$cmd = '/usr/bin/git checkout origin/${project_branch}';

        //return $cmd;
    //}[>}}}<]

    /**
     * @brief dslBackupSrcCmd
     * @param $branch
     * @return 
     */
    public static function dslBackupSrcCmd()
    {/*{{{*/
        $cmd = @file_get_contents(app_path("Components/Jenkins/jenkins_template/backupSrc.sh"));

        return $cmd;
    }/*}}}*/

    /**
     * @brief dslBuildBeforeCmd
     * @param 
     * @return 
     */
    public static function dslBuildBeforeCmd()
    {/*{{{*/
        $cmd = @file_get_contents(app_path("Components/Jenkins/jenkins_template/build_before.sh"));
        return $cmd;
    }/*}}}*/

    /**
     * @brief dslCallbackCmd
     * @param $branch
     * @return 
     */
    public static function dslCallbackCmd($xmlFileName = '')
    {/*{{{*/
        if (empty($xmlFileName)) {
            $xmlFileName = 'jobCallback.sh';
        }

        $cmd = @file_get_contents(app_path("Components/Jenkins/jenkins_template/$xmlFileName"));
        // 把GAEA_JENKINS_CALLBACK_NAME替换成当前站点
        $callback = env('CI_GAEA_JOB_CALLBACK');
        $cmd = str_replace(self::GAEA_JENKINS_CALLBACK_NAME, $callback, $cmd);

        return $cmd;
    }/*}}}*/

    /**
     * @brief dslBuildBeforeCmd
     * @param 
     * @return 
     */
    public static function dslDiffFilesCmd()
    {/*{{{*/
        $cmd = @file_get_contents(app_path("Components/Jenkins/shell/DiffFiles.sh"));
        return $cmd;
    }/*}}}*/
}


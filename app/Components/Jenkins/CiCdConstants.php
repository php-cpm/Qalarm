<?php
namespace App\Components\Jenkins;

use JenkinsKhan\Jenkins;
use App\Components\Utils\HttpUtil;
use App\Components\Utils\MethodUtil;
use App\Components\Utils\LogUtil;
use App\Components\Utils\XmlArrayHelp;

class CiCdConstants 
{
    //jenkins base job name
    const BASE_JENKINS_DSL_JOB = 'base_dsl_job';

    //jenkins 拉取所有分支带；然后checkout 远程分支
    const JENKINS_PULL_BRANCHS = '*';

    //gaea build status 
    const CI_BUILD_STATUS_WAITING = 'WAITING';
    const CI_BUILD_STATUS_RUNNING = 'RUNNING';
    const CI_BUILD_STATUS_SUCCESS = 'SUCCESS';
    const CI_BUILD_STATUS_FAILURE = 'FAILURE';

    // jenkins job status
    const JENKINS_JOB_STATUS_WAITING  = 'WAITING';
    const JENKINS_JOB_STATUS_RUNNING  = 'RUNING';
    const JENKINS_JOB_STATUS_FAILURE  = 'FAILURE';
    const JENKINS_JOB_STATUS_SUCCESS  = 'SUCCESS';
    const JENKINS_JOB_STATUS_UNSTABLE = 'UNSTABLE';
    const JENKINS_JOB_STATUS_ABORTED  = 'ABORTED';

    static public $JenkinsJobStatusDesc = array(
       self::JENKINS_JOB_STATUS_WAITING  => '等待..',
       self::JENKINS_JOB_STATUS_RUNNING  => '运行中..',
       self::JENKINS_JOB_STATUS_FAILURE  => '失败',
       self::JENKINS_JOB_STATUS_SUCCESS  => '成功',
       self::JENKINS_JOB_STATUS_UNSTABLE => 'UNSTABLE',
       self::JENKINS_JOB_STATUS_ABORTED  => 'ABORTED',
    );

    //jenkins build params //todo:废除ext
    const BUILD_PARAMS_EXT                = 'ext';
    const BUILD_PARAMS_DEPLOY_AFTER_SH    = 'deploy_after_sh';
    const BUILD_PARAMS_BUILD_BEFORE_SH    = 'build_before_sh';
    const BUILD_PARAMS_PROJECT_BRANCH     = 'project_branch';
    const BUILD_PARAMS_SSH_USER           = 'ssh_user';
    const BUILD_PARAMS_DEPLOY_FILES       = 'deploy_files';
    const BUILD_PARAMS_DEPLOY_BLACK_FILES = 'deploy_black_files';
    const BUILD_PARAMS_DIFF_FILES_SH      = 'diff_files_sh';
    const BUILD_PARAMS_OLD_BUILD_PACKAGE  = 'old_build_package';
    const BUILD_PARAMS_GAEA_BUILD_ID      = 'gaea_build_id';

    //deploy 状态
    // 生产环境
    const DEPLOY_STATUS_B_LEVEL_RUNNING              = 10;  
    const DEPLOY_STATUS_B_LEVEL_STOP                 = 11; 
    const DEPLOY_STATUS_B_LEVEL_SUCCESS              = 12; 
    const DEPLOY_STATUS_B_LEVEL_FAIL                 = 13; 
    const DEPLOY_STATUS_B_LEVEL_CANCEL               = 14; 
    const DEPLOY_STATUS_B_LEVEL_ROLLBACK             = 15; 
    const DEPLOY_STATUS_B_LEVEL_ROLLBACK_RUNNING     = 16; 
    const DEPLOY_STATUS_B_LEVEL_ROLLBACK_FAIL        = 17; 
    const DEPLOY_STATUS_B_LEVEL_ROLLBACK_SUCCESS     = 18; 

    //slave 环境 
    const DEPLOY_STATUS_A_LEVEL_RUNNING              = 20;  
    const DEPLOY_STATUS_A_LEVEL_STOP                 = 21; 
    const DEPLOY_STATUS_A_LEVEL_SUCCESS              = 22; 
    const DEPLOY_STATUS_A_LEVEL_FAIL                 = 23; 
    const DEPLOY_STATUS_A_LEVEL_CANCEL               = 24; 
    const DEPLOY_STATUS_A_LEVEL_ROLLBACK             = 25; 
    const DEPLOY_STATUS_A_LEVEL_ROLLBACK_RUNNING     = 26; 
    const DEPLOY_STATUS_A_LEVEL_ROLLBACK_FAIL        = 27; 
    const DEPLOY_STATUS_A_LEVEL_ROLLBACK_SUCCESS     = 28; 

    // 测试环境
    const DEPLOY_STATUS_BETA_WAITING              = 29;
    const DEPLOY_STATUS_BETA_RUNNING              = 30;
    const DEPLOY_STATUS_BETA_STOP                 = 31;
    const DEPLOY_STATUS_BETA_SUCCESS              = 32;
    const DEPLOY_STATUS_BETA_FAIL                 = 33;
    const DEPLOY_STATUS_BETA_CANCEL               = 34;
    const DEPLOY_STATUS_BETA_ROLLBACK             = 35;
    const DEPLOY_STATUS_BETA_ROLLBACK_RUNNING     = 36;
    const DEPLOY_STATUS_BETA_ROLLBACK_FAIL        = 37;
    const DEPLOY_STATUS_BETA_ROLLBACK_SUCCESS     = 38;
    //const DEPLOY_STATUS_BETA_FINISH             = 27;
    const DEPLOY_STATUS_WAITING_RUN              = 100;

    //项目锁定状态
    const PROJECT_LOCK_TATUS_UNLOCKED     = 0;
    const PROJECT_LOCK_TATUS_LOCKED       = 1;

    //task 状态描述
    static public $deployStatusDesc = array(
        //self::DEPLOY_STATUS_UNLOCKED                  => '任务解锁',
        //self::DEPLOY_STATUS_UNLOCKED_BY_SUPER         => '后台强制解锁',

        // B level环境
        self::DEPLOY_STATUS_B_LEVEL_RUNNING              => '正式环境发布进行中', 
        self::DEPLOY_STATUS_B_LEVEL_STOP                 => '正式环境发布停止',
        self::DEPLOY_STATUS_B_LEVEL_SUCCESS              => '正式环境发布成功', 
        self::DEPLOY_STATUS_B_LEVEL_FAIL                 => '正式环境发布失败',
        self::DEPLOY_STATUS_B_LEVEL_CANCEL               => '正式环境发布取消',
        self::DEPLOY_STATUS_B_LEVEL_ROLLBACK             => '正式环境发布回滚',
        self::DEPLOY_STATUS_B_LEVEL_ROLLBACK_RUNNING     => '正式环境回滚进行中',
        self::DEPLOY_STATUS_B_LEVEL_ROLLBACK_FAIL        => '正式环境回滚失败',
        self::DEPLOY_STATUS_B_LEVEL_ROLLBACK_SUCCESS     => '正式环境回滚成功',

        // A level环境
        self::DEPLOY_STATUS_A_LEVEL_RUNNING              => 'slave发布进行中', 
        self::DEPLOY_STATUS_A_LEVEL_STOP                 => 'slave发布停止',
        self::DEPLOY_STATUS_A_LEVEL_SUCCESS              => 'slave发布成功', 
        self::DEPLOY_STATUS_A_LEVEL_FAIL                 => 'slave发布失败',
        self::DEPLOY_STATUS_A_LEVEL_CANCEL               => 'slave发布取消',
        self::DEPLOY_STATUS_A_LEVEL_ROLLBACK             => 'slave发布回滚',
        self::DEPLOY_STATUS_A_LEVEL_ROLLBACK_RUNNING     => 'slave回滚进行中',
        self::DEPLOY_STATUS_A_LEVEL_ROLLBACK_FAIL        => 'slave回滚失败',
        self::DEPLOY_STATUS_A_LEVEL_ROLLBACK_SUCCESS     => 'slave回滚成功',

        // 测试环境
        self::DEPLOY_STATUS_BETA_WAITING              => '测试环境发布等待',
        self::DEPLOY_STATUS_BETA_RUNNING              => '测试环境发布进行中', 
        self::DEPLOY_STATUS_BETA_STOP                 => '测试环境发布停止',
        self::DEPLOY_STATUS_BETA_SUCCESS              => '测试环境发布成功', 
        self::DEPLOY_STATUS_BETA_FAIL                 => '测试环境发布失败',
        self::DEPLOY_STATUS_BETA_CANCEL               => '测试环境发布取消',
        self::DEPLOY_STATUS_BETA_ROLLBACK             => '测试环境发布回滚',
        self::DEPLOY_STATUS_BETA_ROLLBACK_RUNNING     => '测试环境回滚进行中',
        self::DEPLOY_STATUS_BETA_ROLLBACK_FAIL        => '测试环境回滚失败',
        self::DEPLOY_STATUS_BETA_ROLLBACK_SUCCESS     => '测试环境回滚成功',
        //const DEPLOY_STATUS_BETA_FINISH             = 27;
        self::DEPLOY_STATUS_WAITING_RUN               => '等待执行...',
    );

    //deploy jobdone error //错误码
    const ERR_SUCC                                 = 0;
    const ERR_DEPLOY_JOBDONE_LOAD_FAIL             = 2101;
    const ERR_DEPLOY_JOBDONE_SEARCH_RESULT_FAIL    = 2102;
    const ERR_DEPLOY_JOBDONE_NODE_FAIL             = 2103;
    const ERR_DEPLOY_JOBDONE_RUN_FAIL              = 2104;
    const ERR_DEPLOY_JOBDONE_SEARCH_RESULT_TIMEOUT = 2105;

    static public $errDescription = array(
        self::ERR_SUCC                                 => '成功',
        self::ERR_DEPLOY_JOBDONE_LOAD_FAIL             => '添加JOB失败',
        self::ERR_DEPLOY_JOBDONE_SEARCH_RESULT_FAIL    => '查询JOB结果失败',
        self::ERR_DEPLOY_JOBDONE_NODE_FAIL             => 'JOB节点执行失败',
        self::ERR_DEPLOY_JOBDONE_RUN_FAIL              => 'JOB运行失败',
        self::ERR_DEPLOY_JOBDONE_SEARCH_RESULT_TIMEOUT => 'JOB运行查询结果超时',
    );

    //部署阶段说明
    //const DEPLOY_STEP_BETA_WAITING_TEST         = 10;
    //const DEPLOY_JOBDONE_TYPE_BETAa             = 10;

    //deploy load jobdone type
    const DEPLOY_JOBDONE_TYPE_BETA             = 10;
    const DEPLOY_JOBDONE_TYPE_BETA_CANCEL      = 11;
    const DEPLOY_JOBDONE_TYPE_BETA_ROLLBACK    = 12;
    const DEPLOY_JOBDONE_TYPE_BETA_RETRY       = 13;

    const DEPLOY_JOBDONE_TYPE_A_LEVEL          = 20;
    const DEPLOY_JOBDONE_TYPE_A_LEVEL_CANCEL   = 21;
    const DEPLOY_JOBDONE_TYPE_A_LEVEL_ROLLBACK = 22;
    const DEPLOY_JOBDONE_TYPE_A_LEVEL_RETRY    = 23;

    const DEPLOY_JOBDONE_TYPE_B_LEVEL          = 30;
    const DEPLOY_JOBDONE_TYPE_B_LEVEL_CANCEL   = 31;
    const DEPLOY_JOBDONE_TYPE_B_LEVEL_ROLLBACK = 32;
    const DEPLOY_JOBDONE_TYPE_B_LEVEL_RETRY    = 33;


    const KEY_WAITING = 'waiting';
    const KEY_RUNNING = 'running';
    const KEY_SUCCESS = 'success';
    const KEY_FAIL    = 'fail';

    // 测试环境
    static public $deployStatusBeta = [
        self::KEY_WAITING => self::DEPLOY_STATUS_WAITING_RUN,
        self::KEY_RUNNING => self::DEPLOY_STATUS_BETA_RUNNING,
        self::KEY_SUCCESS => self::DEPLOY_STATUS_BETA_SUCCESS,
        self::KEY_FAIL    => self::DEPLOY_STATUS_BETA_FAIL,
    ];
    
    static public $deployStatusBetaRollBack = [
        self::KEY_WAITING => self::DEPLOY_STATUS_WAITING_RUN,
        self::KEY_RUNNING => self::DEPLOY_STATUS_BETA_ROLLBACK_RUNNING,
        self::KEY_SUCCESS => self::DEPLOY_STATUS_BETA_ROLLBACK_SUCCESS,
        self::KEY_FAIL    => self::DEPLOY_STATUS_BETA_ROLLBACK_FAIL,
    ];

    static public $deployStatusBetaCancel = [
        self::KEY_WAITING => self::DEPLOY_STATUS_WAITING_RUN,
        self::KEY_RUNNING => self::DEPLOY_STATUS_BETA_ROLLBACK_RUNNING,
        self::KEY_SUCCESS => self::DEPLOY_STATUS_BETA_ROLLBACK_SUCCESS,
        self::KEY_FAIL    => self::DEPLOY_STATUS_BETA_ROLLBACK_FAIL,
    ];

    // slave 环境 
    static public $deployStatusALevel = [
        self::KEY_WAITING => self::DEPLOY_STATUS_WAITING_RUN,
        self::KEY_RUNNING => self::DEPLOY_STATUS_A_LEVEL_RUNNING,
        self::KEY_SUCCESS => self::DEPLOY_STATUS_A_LEVEL_SUCCESS,
        self::KEY_FAIL    => self::DEPLOY_STATUS_A_LEVEL_FAIL,
    ];
    
    static public $deployStatusALevelRollBack = [
        self::KEY_WAITING => self::DEPLOY_STATUS_WAITING_RUN,
        self::KEY_RUNNING => self::DEPLOY_STATUS_A_LEVEL_ROLLBACK_RUNNING,
        self::KEY_SUCCESS => self::DEPLOY_STATUS_A_LEVEL_ROLLBACK_SUCCESS,
        self::KEY_FAIL    => self::DEPLOY_STATUS_A_LEVEL_ROLLBACK_FAIL,
    ];

    static public $deployStatusALevelCancel = [
        self::KEY_WAITING => self::DEPLOY_STATUS_WAITING_RUN,
        self::KEY_RUNNING => self::DEPLOY_STATUS_A_LEVEL_ROLLBACK_RUNNING,
        self::KEY_SUCCESS => self::DEPLOY_STATUS_A_LEVEL_ROLLBACK_SUCCESS,
        self::KEY_FAIL    => self::DEPLOY_STATUS_A_LEVEL_ROLLBACK_FAIL,
    ];

    //   生产环境
    static public $deployStatusBLevel = [
        self::KEY_WAITING => self::DEPLOY_STATUS_WAITING_RUN,
        self::KEY_RUNNING => self::DEPLOY_STATUS_B_LEVEL_RUNNING,
        self::KEY_SUCCESS => self::DEPLOY_STATUS_B_LEVEL_SUCCESS,
        self::KEY_FAIL    => self::DEPLOY_STATUS_B_LEVEL_FAIL,
    ];
    
    static public $deployStatusBLevelRollBack = [
        self::KEY_WAITING => self::DEPLOY_STATUS_WAITING_RUN,
        self::KEY_RUNNING => self::DEPLOY_STATUS_B_LEVEL_ROLLBACK_RUNNING,
        self::KEY_SUCCESS => self::DEPLOY_STATUS_B_LEVEL_ROLLBACK_SUCCESS,
        self::KEY_FAIL    => self::DEPLOY_STATUS_B_LEVEL_ROLLBACK_FAIL,
    ];

    static public $deployStatusBLevelCancel = [
        self::KEY_WAITING => self::DEPLOY_STATUS_WAITING_RUN,
        self::KEY_RUNNING => self::DEPLOY_STATUS_B_LEVEL_ROLLBACK_RUNNING,
        self::KEY_SUCCESS => self::DEPLOY_STATUS_B_LEVEL_ROLLBACK_SUCCESS,
        self::KEY_FAIL    => self::DEPLOY_STATUS_B_LEVEL_ROLLBACK_FAIL,
    ];


    static public $deployStatusForAlevelRollBack = array(
        // A level环境
        self::DEPLOY_STATUS_A_LEVEL_RUNNING,
        self::DEPLOY_STATUS_A_LEVEL_STOP,
        self::DEPLOY_STATUS_A_LEVEL_SUCCESS,
        self::DEPLOY_STATUS_A_LEVEL_FAIL,
        self::DEPLOY_STATUS_A_LEVEL_CANCEL,
        self::DEPLOY_STATUS_A_LEVEL_ROLLBACK,
        self::DEPLOY_STATUS_A_LEVEL_ROLLBACK_RUNNING,
        self::DEPLOY_STATUS_A_LEVEL_ROLLBACK_FAIL,
        self::DEPLOY_STATUS_A_LEVEL_ROLLBACK_SUCCESS,
        self::DEPLOY_STATUS_WAITING_RUN
    );

    static public $deployStatusForBlevelRollBack = array(
        // B level环境
        self::DEPLOY_STATUS_B_LEVEL_RUNNING,
        self::DEPLOY_STATUS_B_LEVEL_STOP,
        self::DEPLOY_STATUS_B_LEVEL_SUCCESS,
        self::DEPLOY_STATUS_B_LEVEL_FAIL,
        self::DEPLOY_STATUS_B_LEVEL_CANCEL,
        self::DEPLOY_STATUS_B_LEVEL_ROLLBACK,
        self::DEPLOY_STATUS_B_LEVEL_ROLLBACK_RUNNING,
        self::DEPLOY_STATUS_B_LEVEL_ROLLBACK_FAIL,
        self::DEPLOY_STATUS_B_LEVEL_ROLLBACK_SUCCESS,
        self::DEPLOY_STATUS_WAITING_RUN
    );

    static public $deployStatusForBetalevelRollBack = array(
        // Beta level环境
        self::DEPLOY_STATUS_BETA_WAITING,
        self::DEPLOY_STATUS_BETA_RUNNING,
        self::DEPLOY_STATUS_BETA_STOP,
        self::DEPLOY_STATUS_BETA_SUCCESS,
        self::DEPLOY_STATUS_BETA_FAIL,
        self::DEPLOY_STATUS_BETA_CANCEL,
        self::DEPLOY_STATUS_BETA_ROLLBACK,
        self::DEPLOY_STATUS_BETA_ROLLBACK_RUNNING,
        self::DEPLOY_STATUS_BETA_ROLLBACK_FAIL,
        self::DEPLOY_STATUS_BETA_ROLLBACK_SUCCESS,
        //self::t DEPLOY_STATUS_BETA_FINISH,
        self::DEPLOY_STATUS_WAITING_RUN,
        //self::DEPLOY_STATUS_Beta_LEVEL_RUNNING,
        //selfeta::DEPLOY_STATUS_Beta_LEVEL_STOP,
        //self::DEPLOY_STATUS_Beta_LEVEL_SUCCESS,
        //self::DEPLOY_STATUS_Beta_LEVEL_FAIL,
        //self::DEPLOY_STATUS_Beta_LEVEL_CANCEL,
        //self::DEPLOY_STATUS_Beta_LEVEL_ROLLBACK,
        //self::DEPLOY_STATUS_Beta_LEVEL_ROLLBACK_RUNNING,
        //self::DEPLOY_STATUS_Beta_LEVEL_ROLLBACK_FAIL,
        //self::DEPLOY_STATUS_Beta_LEVEL_ROLLBACK_SUCCESS,
    );

    static public $isCanWriteTestReport = array(
        
        self::DEPLOY_STATUS_BETA_SUCCESS,
        self::DEPLOY_STATUS_BETA_FAIL,
        self::DEPLOY_STATUS_BETA_CANCEL,
        self::DEPLOY_STATUS_BETA_ROLLBACK,
        self::DEPLOY_STATUS_BETA_ROLLBACK_RUNNING,
        self::DEPLOY_STATUS_BETA_ROLLBACK_FAIL,
        self::DEPLOY_STATUS_BETA_ROLLBACK_SUCCESS,

    );
    //==================== deploy step log ========================
    //// 测试环境
    //const LOG_DEPLOY_BETA_SKIP                 = 299;
    //const LOG_DEPLOY_BETA_WAIT_TEST            = 300;
    //const LOG_DEPLOY_BETA_SUCCESS              = 301;
    //const LOG_DEPLOY_BETA_FAIL                 = 302;
    //const LOG_DEPLOY_BETA_ROLLBACK_FAIL        = 303;
    //const LOG_DEPLOY_BETA_ROLLBACK_SUCCESS     = 304;
    ////beta 测试
    //const LOG_DEPLOY_BETA_TESTED_PASS        = 305;
    //const LOG_DEPLOY_BETA_TESTED_NOTPASS     = 306;
    //const LOG_DEPLOY_BETA_TESTED_HAVEBUG     = 307;
    ////slave 环境
    //const LOG_DEPLOY_A_LEVEL_SUCCESS              = 308;
    //const LOG_DEPLOY_A_LEVEL_FAIL                 = 309;
    //const LOG_DEPLOY_A_LEVEL_ROLLBACK_FAIL        = 310;
    //const LOG_DEPLOY_A_LEVEL_ROLLBACK_SUCCESS     = 311;
    ////slave 测试
    //const LOG_DEPLOY_A_LEVEL_TESTED_PASS        = 312;
    //const LOG_DEPLOY_A_LEVEL_TESTED_NOTPASS     = 313;
    //const LOG_DEPLOY_A_LEVEL_TESTED_HAVEBUG     = 314;
    //// 生产环境
    //const LOG_DEPLOY_B_LEVEL_SUCCESS              = 315 ;
    //const LOG_DEPLOY_B_LEVEL_FAIL                 = 316;
    //const LOG_DEPLOY_B_LEVEL_ROLLBACK_FAIL        = 317;
    //const LOG_DEPLOY_B_LEVEL_ROLLBACK_SUCCESS     = 318;
    //// log deploy description
    //public static $LogDescription = array(
        //self::LOG_DEPLOY_BETA_SKIP                 => '跳过测试环境',
        //self::LOG_DEPLOY_BETA_WAIT_TEST            => '等待测试环境测试',
        //self::LOG_DEPLOY_BETA_SUCCESS              => '测试环境发布成功',
        //self::LOG_DEPLOY_BETA_FAIL                 => '测试环境发布失败',
        //self::LOG_DEPLOY_BETA_ROLLBACK_FAIL        => '测试环境回滚失败',
        //self::LOG_DEPLOY_BETA_ROLLBACK_SUCCESS     => '测试环境回滚成功',
        //self::LOG_DEPLOY_BETA_TESTED_PASS        => '测试通过',
        //self::LOG_DEPLOY_BETA_TESTED_NOTPASS     => '测试未通过，打回',
        //self::LOG_DEPLOY_BETA_TESTED_HAVEBUG     => '有部分bug,可以上线',
        //self::LOG_DEPLOY_A_LEVEL_SUCCESS              => 'slave 发布成功',
        //self::LOG_DEPLOY_A_LEVEL_FAIL                 => 'slave 发布失败',
        //self::LOG_DEPLOY_A_LEVEL_ROLLBACK_FAIL        => 'slave 回滚失败',
        //self::LOG_DEPLOY_A_LEVEL_ROLLBACK_SUCCESS     => 'slave 回滚成功',
        //self::LOG_DEPLOY_A_LEVEL_TESTED_PASS     => 'slave 测试通过',
        //self::LOG_DEPLOY_A_LEVEL_TESTED_NOTPASS     => 'slave 测试未通过，打回',
        //self::LOG_DEPLOY_A_LEVEL_TESTED_HAVEBUG     => 'slave 有部分bug, 可以上线',
        //self::LOG_DEPLOY_B_LEVEL_SUCCESS              => '正式环境 发布成功',
        //self::LOG_DEPLOY_B_LEVEL_FAIL                 => '正式环境 发布失败',
        //self::LOG_DEPLOY_B_LEVEL_ROLLBACK_FAIL        => '正式环境 回滚失败',
        //self::LOG_DEPLOY_B_LEVEL_ROLLBACK_SUCCESS     => '正式环境 回滚成功',
    //);

    ////============ 测试状态；及其相关定义 start ===================
    
    const TEST_TYPE_BETA    = 0;
    const TEST_TYPE_A_LEVLE = 1;

    public static $testTypeDesc = array(
       self::TEST_TYPE_BETA    => '测试环境',
       self::TEST_TYPE_A_LEVLE => 'Slave环境'
    );

    const TEST_RESULT_STATUS_WAITING = 0;
    const TEST_RESULT_STATUS_PASS    = 1;
    const TEST_RESULT_STATUS_NOTPASS = 2;
    const TEST_RESULT_STATUS_HASBUG  = 3;
    const TEST_RESULT_STATUS_IGNORE_BETA  = 4;

    public static $testResultStatusDesc = array(
       self::TEST_RESULT_STATUS_WAITING => '等待测试',
       self::TEST_RESULT_STATUS_PASS    => '测试通过',
       self::TEST_RESULT_STATUS_NOTPASS => '测试未通过',
       self::TEST_RESULT_STATUS_HASBUG  => '测试通过(含有部分bug)',
       self::TEST_RESULT_STATUS_IGNORE_BETA  => '忽略测试'
    );
    
    //============ 测试状态；及其相关定义 end   ===================

    //============  部署阶段定义 其相关定义 start ===================
    
    //const DEPLOY_STEP_BETA    = 0;
    //const DEPLOY_STEP_A_LEVEL = 1;
    //const DEPLOY_STEP_B_LEVEL = 2;

    //根据部署阶段；控制deploy after 执行时。传递的参数
    public static $deployAfterShRunTypeDesc = array(
       self::DEPLOY_STEP_BETA    => 'test',
       self::DEPLOY_STEP_A_LEVEL => 'slave',
       self::DEPLOY_STEP_B_LEVEL => 'pro',
    );
    //============ 部署阶段定义  其相关定义 end   ===================
    
    //============ host cluster type  start ===================
    
    const HOST_TYPE_TEST    = 'test';
    const HOST_TYPE_SLAVE   = 'slave';
    const HOST_TYPE_ONLINE  = 'online';
    
    //============ 部署阶段定义  其相关定义 end   ===================
    
    //============ log step state  start ===================
    
    const LOG_GET_GIT_WEBHOOK               = 293;
    const LOG_CREATE_BUILD_AUTO             = 294;
    const LOG_CREATE_BUILD_HANDLE           = 295;
    const LOG_BUILD_SUCCESS                 = 296;
    const LOG_BUILD_FAIL                     = 297;
    const LOG_APPLY_TEST                     = 298;

    const LOG_DEPLOY_BETA_SKIP                 = 299;
    const LOG_DEPLOY_BETA_WAIT_TEST            = 300;
    const LOG_DEPLOY_BETA_SUCCESS              = 301;
    const LOG_DEPLOY_BETA_FAIL                 = 302;
    const LOG_DEPLOY_BETA_ROLLBACK_FAIL        = 303;
    const LOG_DEPLOY_BETA_ROLLBACK_SUCCESS     = 304;
    //beta 测试
    const LOG_DEPLOY_BETA_TESTED_PASS        = 305;
    const LOG_DEPLOY_BETA_TESTED_NOTPASS     = 306;
    const LOG_DEPLOY_BETA_TESTED_HAVEBUG     = 307;
    //slave 环境
    const LOG_DEPLOY_A_LEVEL_SUCCESS              = 308;
    const LOG_DEPLOY_A_LEVEL_FAIL                 = 309;
    const LOG_DEPLOY_A_LEVEL_ROLLBACK_FAIL        = 310;
    const LOG_DEPLOY_A_LEVEL_ROLLBACK_SUCCESS     = 311;
    //slave 测试
    const LOG_DEPLOY_A_LEVEL_TESTED_PASS        = 312;
    const LOG_DEPLOY_A_LEVEL_TESTED_NOTPASS     = 313;
    const LOG_DEPLOY_A_LEVEL_TESTED_HAVEBUG     = 314;
    // 生产环境
    const LOG_DEPLOY_B_LEVEL_SUCCESS              = 315 ;
    const LOG_DEPLOY_B_LEVEL_FAIL                 = 316;
    const LOG_DEPLOY_B_LEVEL_ROLLBACK_FAIL        = 317;
    const LOG_DEPLOY_B_LEVEL_ROLLBACK_SUCCESS     = 318;

    public static $logStepStateDesc = array(
        self::LOG_GET_GIT_WEBHOOK                 => 'git 提交时间',
        self::LOG_CREATE_BUILD_AUTO               => '自动触发构建',
        self::LOG_CREATE_BUILD_HANDLE             => '手动触发构建',
        self::LOG_BUILD_SUCCESS                   => '构建成功',
        self::LOG_BUILD_FAIL                      => '构建失败',
        self::LOG_APPLY_TEST                      => '申请测试',
        self::LOG_DEPLOY_BETA_SKIP                => '跳过测试环境',

        self::LOG_DEPLOY_BETA_WAIT_TEST           => '等待测试环境测试',
        self::LOG_DEPLOY_BETA_SUCCESS             => '测试环境发布成功',
        self::LOG_DEPLOY_BETA_FAIL                => '测试环境发布失败',
        self::LOG_DEPLOY_BETA_ROLLBACK_FAIL       => '测试环境回滚失败',
        self::LOG_DEPLOY_BETA_ROLLBACK_SUCCESS    => '测试环境回滚成功',
        self::LOG_DEPLOY_BETA_TESTED_PASS         => '测试通过',
        self::LOG_DEPLOY_BETA_TESTED_NOTPASS      => '测试未通过，打回',
        self::LOG_DEPLOY_BETA_TESTED_HAVEBUG      => '有部分bug,可以上线',
        self::LOG_DEPLOY_A_LEVEL_SUCCESS          => 'slave 发布成功',
        self::LOG_DEPLOY_A_LEVEL_FAIL             => 'slave 发布失败',
        self::LOG_DEPLOY_A_LEVEL_ROLLBACK_FAIL    => 'slave 回滚失败',
        self::LOG_DEPLOY_A_LEVEL_ROLLBACK_SUCCESS => 'slave 回滚成功',
        self::LOG_DEPLOY_A_LEVEL_TESTED_PASS      => 'slave 测试通过',
        self::LOG_DEPLOY_A_LEVEL_TESTED_NOTPASS   => 'slave 测试未通过，打回',
        self::LOG_DEPLOY_A_LEVEL_TESTED_HAVEBUG   => 'slave 有部分bug, 可以上线',
        self::LOG_DEPLOY_B_LEVEL_SUCCESS          => '正式环境 发布成功',
        self::LOG_DEPLOY_B_LEVEL_FAIL             => '正式环境 发布失败',
        self::LOG_DEPLOY_B_LEVEL_ROLLBACK_FAIL    => '正式环境 回滚失败',
        self::LOG_DEPLOY_B_LEVEL_ROLLBACK_SUCCESS => '正式环境 回滚成功',
    );
    
    //============ log step state  end  ===================
    //

    //==================================================
    //====================重构==========================

    //'部署阶段枚举：beta,level_a,level_b',
    const DEPLOY_STEP_BETA       = 'sandbox';
    const DEPLOY_STEP_A_LEVEL    = 'stage';
    const DEPLOY_STEP_B_LEVEL    = 'production';

    //'部署动作枚举:deploy,rollback,cancel',
    const DEPLOY_ACTION_DEPLOY   = 'deploy';
    const DEPLOY_ACTION_ROLLBACK = 'rollback';
    const DEPLOY_ACTION_CANCEL   = 'cancel';

    //'部署状态枚举:wait,running,success,fail',
    const DEPLOY_STATUS_WAIT      = 'wait';
    const DEPLOY_STATUS_RUNNING   = 'running';
    const DEPLOY_STATUS_SUCCESS   = 'success';
    const DEPLOY_STATUS_FAIL      = 'fail';

    public static $deployStepDesc = array(
        self::DEPLOY_STEP_BETA    => '测试环境',
        self::DEPLOY_STEP_A_LEVEL => 'slave环境',
        self::DEPLOY_STEP_B_LEVEL => '线上环境',
    );

    public static $deployActionDesc = array(
        self::DEPLOY_ACTION_DEPLOY   => '部署',
        self::DEPLOY_ACTION_ROLLBACK => '回滚',
        self::DEPLOY_ACTION_CANCEL   => '取消',
    );

    public static $deployStatusDesc2 = array(
        self::DEPLOY_STATUS_WAIT    => '等待',
        self::DEPLOY_STATUS_RUNNING => '运行中...',
        self::DEPLOY_STATUS_SUCCESS => '成功',
        self::DEPLOY_STATUS_FAIL    => '失败',
    );

    //根据部署阶段；动作；状态 获取说明
    public static function getDeployStepACtionStatusDesc ($deployStep, $deployAction, $deployStatus)
    {/*{{{*/
        $deployStepArr   = array_keys(self::$deployStepDesc);
        $deployActionArr = array_keys(self::$deployActionDesc);
        $deployStatusArr = array_keys(self::$deployStatusDesc2);

        if (in_array($deployStep,   $deployStepArr) && in_array($deployAction, $deployActionArr) && in_array($deployStatus, $deployStatusArr)) {
            $deployStepDesc   = self::$deployStepDesc[$deployStep];
            $deployActionDesc = self::$deployActionDesc[$deployAction];
            $deployStatusDesc = self::$deployStatusDesc2[$deployStatus];
            return $deployStepDesc . $deployActionDesc  . $deployStatusDesc;
        }

        return '';
    } /*}}}*/

    //======= ci cd task step  定义 =====
    const CICD_TASK_STEP_BUILD   = 'build';
    const CICD_TASK_STEP_TEST    = 'test';
    const CICD_TASK_STEP_DEPLOY  = 'deploy';

    //'测试阶段枚举:beta,a_level,b_level',
    const TEST_STEP_BETA        = 'beta';
    const TEST_STEP_A_LEVEL     = 'a_level';
    const TEST_STEP_B_LEVEL     = 'b_level';

    //'测试动作枚举:handle, auto',
    const TEST_ACTION_HANDLE    = 'handle';
    const TEST_ACTION_AUTO      = 'auto';

    //'测试结果枚举:handle, auto',
    const TEST_RESULT_PASS      = 'pass';
    const TEST_RESULT_NOPASS    = 'nopass';
    const TEST_RESULT_HAVEBUG   = 'havebug';

    public static $testStepDesc = array(
        self::TEST_STEP_BETA    => '测试环境',
        self::TEST_STEP_A_LEVEL => 'slave环境',
        self::TEST_STEP_B_LEVEL => '线上环境',
    );

    public static $testActionDesc = array(
        self::TEST_ACTION_HANDLE  => '手动测试',
        self::TEST_ACTION_AUTO    => '自动测试',
    );

    public static $testResultDesc = array(
        self::TEST_RESULT_PASS    => '通过',
        self::TEST_RESULT_NOPASS  => '未通过',
        self::TEST_RESULT_HAVEBUG => '有BUG，可上线',
    );

    //todo: 构建类型；分阶段
}

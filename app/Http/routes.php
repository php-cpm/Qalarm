<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::any('/',                                    ['uses' => 'IndexController@index', 'middleware' => ['auth']]);
Route::any('/harview',                             ['uses' => 'IndexController@harview', 'middleware' => ['auth']]);
Route::any('/harviewfile',                         ['uses' => 'IndexController@harviewFile', 'middleware' => ['auth']]);

Route::group([
    'prefix' => 'api/v1',
], function() {
    Route::get('pong',                             ['uses' => 'IndexController@pong']);
    Route::get('user/list',                        ['uses' => 'IndexController@users']);
});

Route::any('api/admin/login',                      ['uses' => 'AdminController@login', 'middleware' => ['auth']]);
Route::any('api/admin/logout',                     ['uses' => 'AdminController@logout']);


/*优惠券对外接口*/
Route::any('api/v1/coupons',                       ['uses' => 'CouponController@fetchCoupons']);
Route::any('api/v1/coupons/detail',                ['uses' => 'CouponController@fetchCouponById']);
Route::any('api/v1/coupons/exchange',              ['uses' => 'CouponController@exchangeCoupon']);
Route::any('api/v1/coupons/operation',             ['uses' => 'CouponController@playCoupon']);
Route::any('api/v1/coupons/dispense',              ['uses' => 'CouponController@dispenseCoupon']);
Route::any('api/v1/coupons/batchdispense',         ['uses' => 'CouponController@batchDispenseCoupon']);
Route::any('api/v1/coupons/correlate',             ['uses' => 'CouponController@correlateUser']);

Route::any('api/v1/actives',                       ['uses' => 'ActiveController@fetchActives']);
Route::any('api/v1/util/coupontypes',              ['uses' => 'CommonController@fetchCouponTypes']);
Route::any('api/v1/util/manualcoupontypes',        ['uses' => 'CommonController@fetchManualCouponTypes']);
Route::any('api/v1/util/busstypes',                ['uses' => 'CommonController@fetchBussTypes']);
Route::any('api/v1/util/citys',                    ['uses' => 'CommonController@fetchCitys']);
Route::any('api/v1/util/cars',                     ['uses' => 'CommonController@fetchCarModels']);
Route::any('api/v1/util/pushjumptypes',            ['uses' => 'CommonController@fetchPushJumpTypes']);

Route::any('api/v1/util/smsreply',                 ['uses' => 'CommonController@fetchSmsUserReply']);


/*通知*/
Route::any('api/v1/notices/push',                  ['uses' => 'NoticeController@addNotice']);

Route::any('api/v1/usermobiles',                   ['uses' => 'UserCenterController@fetchUsers']);

Route::any('api/v1/awards',                        ['uses' => 'AwardsController@fetchAwards']);

Route::any('api/v1/contenttypestree',              ['uses' => 'ContentController@fetchContentTypesTree']);
Route::any('api/v1/contenttypes',                  ['uses' => 'ContentController@fetchContentTypes']);
Route::any('api/v1/contenttypes/update',           ['uses' => 'ContentController@updateContentTypes']);
Route::any('api/v1/contents',                      ['uses' => 'ContentController@fetchContents']);
Route::any('api/v1/content',                       ['uses' => 'ContentController@fetchContent']);
Route::any('api/v1/content/status',                ['uses' => 'ContentController@contentStatusTransfer']);
Route::any('api/v1/contents/update',               ['uses' => 'ContentController@updateContent']);

Route::group([
    'prefix' => 'api/admin',
    'middleware'=> ['auth']
], function() {
    Route::any('nav',                              ['uses' => 'AdminController@nav']);
    Route::any('auth',                             ['uses' => 'AdminController@adminAuth']);
    Route::any('role',                             ['uses' => 'AdminController@adminRole']);
    Route::any('user',                             ['uses' => 'AdminController@adminUser']);
    
    // 工作流人员相关接口
    Route::any('wfparticipators',                  ['uses' => 'AdminController@fetchWorkflowParticipators']);
    Route::any('wfparticipatorupdate',             ['uses' => 'AdminController@updateWorkflowParticipator']);




});

//=== llw update start
Route::any('api/admin/menupermit',                           ['uses' => 'AdminController@adminMenuPermit']);
//=== llw update end

Route::group([
    'prefix' => 'api/v1',
    'middleware'=> ['auth']
], function() {
    /*优惠券*/
    Route::any('coupons/manual_coupons',           ['uses' => 'CouponController@fetchManualCoupons']);
    Route::any('coupons/gaeadispense',             ['uses' => 'CouponController@dispenseCoupon']);

    /*通知*/
    Route::any('notices/add',                      ['uses' => 'NoticeController@addNotice']); 
    Route::any('notices',                          ['uses' => 'NoticeController@fetchNotices']); 

});

//Route::any('api/v1/users',                         ['uses' => 'UserController@userList']);
Route::any('api/v1/user/auth',                     ['uses' => 'UserController@userAuth']);
Route::any('api/v1/util/usertypes',                ['uses' => 'CommonController@userTypes']);
Route::any('api/v1/util/usernames',                ['uses' => 'CommonController@fetchUsernames']);
Route::any('api/v1/util/cartypes',                 ['uses' => 'CommonController@carTypes']);
//Route::any('api/v1/realnamecheck',                 ['uses' => 'RealNameCheckController@realName']);
Route::any('api/v1/util/checkstatetypes',          ['uses' => 'CommonController@checkStateTypes']);

Route::any('api/v1/util/user/auth/states',         ['uses' => 'CommonController@userAuthStates']);



Route::any('api/v1/user/detail',                   ['uses' => 'UserController@userDetail']);

//Route::any('api/v1/account',                   ['uses' => 'UserController@userList']);
//Route::any('api/v1/account/auth',                   ['uses' => 'UserController@userAuth']);
//Route::any('api/v1/account/detail',                   ['uses' => 'UserController@userDetail']);
//Route::any('api/v1/util/accounttypes',                ['uses' => 'CommonController@userTypes']);
//Route::any('api/v1/util/accountauthstates',         ['uses' => 'CommonController@userAuthStates']);

Route::any('api/v1/account',                        ['uses' => 'AccountController@accountList']);
Route::any('api/v1/account/auth',                   ['uses' => 'AccountController@accountAuth']);
Route::any('api/v1/account/detail',                 ['uses' => 'AccountController@accountDetail']);
//Route::any('api/v1/account/checklist',              ['uses' => 'AccountController@accountCheckList']);
Route::any('api/v1/account/accountauthupdate',      ['uses' => 'AccountController@accountAuthUpdate']);
Route::any('api/v1/account/basicupdate',            ['uses' => 'AccountController@accountBasicUpdate']);
Route::any('api/v1/account/headimgchecklist',       ['uses' => 'AccountController@accountHeadImgCheckList']);

Route::any('api/v1/account/licensechecklist',       ['uses' => 'AccountController@accountLicenseCheckList']);
Route::any('api/v1/account/driverupdate',           ['uses' => 'AccountController@driverUpdate']);
Route::any('api/v1/account/carupdate',              ['uses' => 'AccountController@carUpdate']);


Route::any('api/v1/account/notcheckcarimglicense',  ['uses' => 'AccountController@accountNotCheckCarImgLicense']);
Route::any('api/v1/account/carbrand',               ['uses' => 'AccountController@carBrand']);

Route::any('api/v1/account/photos',                 ['uses' => 'AccountController@photos']);
Route::any('api/v1/account/photos/delete',          ['uses' => 'AccountController@photosDel']);
Route::any('api/v1/account/photos/deletelogs',      ['uses' => 'AccountController@photosDelLogs']);
Route::any('api/v1/account/voice/deletelogs',       ['uses' => 'AccountController@voiceDelLogs']);


Route::any('api/v1/util/accounttypes',              ['uses' => 'CommonController@userTypes']);
Route::any('api/v1/util/accountauthstates',         ['uses' => 'CommonController@accountAuthStates']);
Route::any('api/v1/util/accountcheckstates',        ['uses' => 'CommonController@checkStateTypes']);
Route::any('api/v1/util/licensecheckstate',         ['uses' => 'CommonController@licenseCheckStates']);
Route::any('api/v1/util/headimgcheckstate',         ['uses' => 'CommonController@headImgCheckStates']);
Route::any('api/v1/util/carimgcheckstate',          ['uses' => 'CommonController@carImgCheckStates']);
Route::any('api/v1/util/accountsex',                ['uses' => 'CommonController@accountSex']);
Route::any('api/v1/util/carprefix',                 ['uses' => 'CommonController@carPrefix']);

// 需要直接访问
Route::any('api/v1/ops/clientversion',              ['uses' => 'ExternalController@fetchGaeaClientVersion']);
Route::any('api/v1/ops/clientreport',               ['uses' => 'ExternalController@reportClientInfo']);
Route::any('api/v1/ops/hostnames',                  ['uses' => 'ExternalController@fetchHostNames']);

Route::any('api/v1/app/authorization',              ['uses' => 'ExternalController@fetchSystemAuthStatus']);

Route::group([
    'prefix' => 'api/v1',
    'middleware'=> ['auth']
], function() {
    Route::any('ops/changepasswd',               ['uses' => 'HostController@changeHostPasswd']);
    Route::any('ops/hosts',                      ['uses' => 'HostController@fetchHosts']);
    Route::any('ops/updatehost',                 ['uses' => 'HostController@updateHost']);
    Route::any('util/captcha',                   ['uses' => 'CommonController@fetchCaptcha']);

    /*jobdone*/
    Route::any('jobdone/scripts',                ['uses' => 'ScriptController@fetchScripts']);
    Route::any('jobdone/script',                 ['uses' => 'ScriptController@fetchScript']);
    Route::any('jobdone/updatescript',           ['uses' => 'ScriptController@updateScript']);
    Route::any('jobdone/scripttypes',            ['uses' => 'ScriptController@fetchScriptTypes']);

    
    // 执行脚本
    Route::any('jobdone/scriptexecs',            ['uses' => 'ScriptController@fetchScriptsExecs']);
    Route::any('jobdone/scriptexec',             ['uses' => 'ScriptController@fetchScriptExec']);
    Route::any('jobdone/updatescriptexec',       ['uses' => 'ScriptController@updateScriptExec']);
    Route::any('jobdone/goonscriptexec',         ['uses' => 'ScriptController@goonScriptExec']);
    Route::any('jobdone/execresults',            ['uses' => 'ScriptController@fetchExecResult']);

});

Route::group([
    'prefix' => 'api/v1',
    'middleware'=> ['auth']
], function() {
    Route::any('account/checkskip',              ['uses' => 'AccountController@saveAccountCheckSkip']);
    Route::any('account/startchecklicense',      ['uses' => 'AccountController@AccountStartCheckData']);
    Route::any('account/checkaccount',           ['uses' => 'AccountController@AccountCheckLicense']);
    
    Route::any('account/carnumbers',             ['uses' => 'AccountController@carNumbers']);
    Route::any('util/realtimeservice',           ['uses' => 'CommonController@fetchRealtimeServiceAddr']);

    Route::any('market/myapp',                   ['uses'  => 'MyAppController@fetchMyApps']);
    Route::any('market/apps',                    ['uses'  => 'MyAppController@fetchAllApps']);

    Route::any('flow/workflows',                 ['uses'  => 'WorkflowController@fetchWorkflows']);
    Route::any('flow/jobworkflows',              ['uses'  => 'WorkflowController@fetchJobWorkflows']);
    Route::any('flow/workflowstatustransfer',    ['uses'  => 'WorkflowController@workflowStatusTransfer']);
    Route::any('flow/serverperm',                ['uses'  => 'WorkflowController@applyServerPerm']);
    Route::any('flow/applyvpn',                  ['uses'  => 'WorkflowController@applyVpn']);
    Route::any('flow/recoverperm',               ['uses'  => 'WorkflowController@recoverPermission']);
    Route::any('flow/applyapp',                  ['uses'  => 'WorkflowController@applyApp']);
});

Route::group([
        'prefix' => 'api/v1',
        'middleware'=> ['auth']
], function() {
    Route::any('app/clientconf/index',            ['uses' => 'AppClientConfController@idnex']);
    Route::any('app/clientconf/insert',           ['uses' => 'AppClientConfController@insert']);
    Route::any('app/clientconf/update',           ['uses' => 'AppClientConfController@update']);
    Route::any('app/clientconf/releasedata',      ['uses' => 'AppClientConfController@releaseDataState']);
});

Route::group([
    'prefix' => 'api/v1/graph',
    'middleware'=> ['auth']
], function() {
    Route::any('index',         'GraphController@index');
    Route::any('checkauth',     'GraphController@checkauth');
    Route::any('history',        'GraphController@history');
    Route::any('detail',        'GraphController@detail');
    Route::any('message',       'GraphController@message');
});

Route::group([
    'prefix'   => 'api',
], function() {
    Route::any('pub',        'ApiController@publish');
    Route::any('pubv2',        'ApiController@publishV2');
    Route::any('sms',        'ApiController@sms');
    Route::any('hosts',       'ApiController@hostHeartbeat');

});

Route::group([
    'prefix' => 'api/v1/page',
], function() {
    Route::any('index',         'PageController@index');
    Route::any('list',          'PageController@pagelist');
    Route::any('report',        'ApiController@phoenixReport');
    Route::any('jobs',          'ApiController@phoenixJobs');
});

Route::group([
    'prefix' => 'api/v1/project',
    'middleware'=> ['auth']
], function() {
    Route::any('index',     'ProjectController@index');
    Route::any('option',    'ProjectController@option');
    Route::any('update',    'ProjectController@update');
    Route::any('alarmhistory',    'ProjectController@alarmHistory');
    Route::any('messagehistory',    'ProjectController@messageHistory');
    Route::any('submodule',    'ProjectController@subModule');
    Route::any('updatesubmodule',    'ProjectController@updateSubModuel');
});

Route::any('/api/v1/ci/webhook'                               , 'WebhookController@gitLab');
Route::any('/api/v1/ci/jenkinscallback'                       , 'WebhookController@jenkinsBuildStartHook');
Route::any('/api/v1/ci/buildfinish'                           , 'WebhookController@jenkinsBuildFinishHook');
Route::any('testxml'                                          , 'WebhookController@test');
//Route::any('/api/v1/ci/deployafter'                           , 'CiDeployController@deployAfterWebHook'); 
Route::any('/api/v1/ci/deployafter'                           , 'CiCdProjectController@deployAfterWebHook'); 

Route::any('/api/v1/ci/t1'                           , 'CiCdProjectController@deployAction');

Route::group([
        'prefix' => 'api/v1',
        'middleware'=> ['auth']
], function() {

    Route::any('develop'                                          , 'DevelopController@index');
    //Route::any('/api/v1/ci/jenkinsdifffiles'                      , 'WebhookController@jenkinsDiffFilesHook');
    //Route::any('app/ciproject/gitlabproject'              , 'WebhookController@gitLabProject');
    Route::any('app/ciproject/gitlabproject'              , 'CiProjectController@ciProject');
    Route::any('app/ciproject/buildproject'               , 'WebhookController@buildProject');
    Route::any('app/ciproject/gaeajenkinsjob'             , 'WebhookController@gaeaJenkinsJob');
    Route::any('app/ciproject/buildsteps'                 , 'WebhookController@buildSteps');
    Route::any('app/ciproject/createbuild'                , 'WebhookController@createBuild');
    Route::any('app/ciproject/createjenkinsjob'           , 'WebhookController@gaeaCreateJenkins');
    Route::any('app/ciproject/difffiles'                  , 'WebhookController@diffFiles');

    Route::any('app/ciproject/buildlog'                   , 'WebhookController@buildProjectLog');
    Route::any('app/ciproject/steplog'                    , 'WebhookController@ciStepStateLog');

    //Route::any('/api/v1/app/ciproject/deployprojectcreate'        , 'CiDeployController@deployProjectCreate');
    //Route::any('app/ciproject/deployprojectcancel'        , 'CiDeployController@deployProjectCancel');
    //Route::any('app/ciproject/deployproject'              , 'CiDeployController@deployProject');
    //Route::any('app/ciproject/deployprojecthostlogs'      , 'CiDeployController@deployProjectHostLogs');
    Route::any('app/ciproject/deployprojecthostlogs'      , 'CiCdProjectController@deployProjectHostLogs');
    //Route::any('app/ciproject/projectchange'              , 'CiDeployController@getGitlabChangeByChangeId');
    Route::any('app/ciproject/projectchange'              , 'CiCdProjectController@getGitlabChangeByChangeId');

    //Route::any('app/ciproject/rollback'                   , 'CiDeployController@rollBack');
    //Route::any('app/ciproject/deployaction'               , 'CiDeployController@deployAction');
    Route::any('app/ciproject/deployaction'               , 'CiCdProjectController@deployAction');

    //保存部署数据 
    //Route::any('app/ciproject/deployoperate'              , 'CiDeployController@deployOperate');
    //重构
    Route::any('app/ciproject/deployoperate'              , 'CiCdProjectController@ciCdProject');

    //Route::any('app/ciproject/checkhostset'              , 'CiDeployController@checkHostSet');
    Route::any('app/ciproject/checkhostset'              , 'CiCdProjectController@checkHostSet');

    //Route::any('app/ciproject/testreport'                 , 'CiDeployController@testReport');
    Route::any('app/ciproject/testreport'                 , 'CiTestReportController@testReport');

    Route::any('app/ciproject/members'                    , 'CiProjectMemberController@ciProjectMemberManager'); /*member manager*/ 
    Route::any('app/ciproject/fetchmembers'               , 'CiProjectMemberController@fetchCiMemberByProjectId'); /*member manager*/ 


    Route::any('ci/sonar/metrics'                          , ['uses' => 'SonarController@fetchMetrics']);
    Route::any('util/ciprojectlist'                        , ['uses' => 'CommonController@ciProjectList']);
    Route::any('util/cibuildstatuslist'                    , ['uses' => 'CommonController@ciBuildStatusList']);
    Route::any('util/cihostlist'                           , ['uses' => 'CommonController@ciHostList']);
    Route::any('util/cideploystatuslist'                   , ['uses' => 'CommonController@ciDeployStatusList']);
    //Route::any('api/v1/util/test'                               , ['uses' => 'CommonController@fetchCouponTypes']);
    //
    //
    Route::any('util/ciprojecthosts'                       , ['uses' => 'CiHostController@fetchCiProjectHosts']);
    Route::any('util/ciprojecthostupdate'                  , ['uses' => 'CiHostController@updateCiProjectHost']);

    Route::any('util/cidockersegments'                     , ['uses' => 'CiDockerImageController@fetchCiDockerServices']);
    Route::any('util/cidockerimages'                       , ['uses' => 'CiDockerImageController@fetchCiDockerImages']);
    Route::any('util/cidockerimagereassemble'                , ['uses' => 'CiDockerImageController@reAssembleCiDockerImage']);
    Route::any('util/cidockerimageassemble'                , ['uses' => 'CiDockerImageController@assembleCiDockerImage']);
    Route::any('util/cidockerbuildlog'                     , ['uses' => 'CiDockerImageController@fetchCiDockerBuildLog']);
    Route::any('util/cidockerimageupdate'                  , ['uses' => 'CiDockerImageController@updateCiDockerImage']);

    Route::any('util/ciprojectdnses'                       , ['uses' => 'CiProjectDnsController@fetchCiProjectDnses']);
    Route::any('util/ciprojectdnsupdate'                  , ['uses' => 'CiProjectDnsController@updateCiProjectDns']);
    //Route::any('util/citestmember'                         , ['uses' => 'CiTestMemberController@fetchCiTestMember']);
});

Route::any('/api/v1/ci/kub'                           , 'KuberTestController@index');

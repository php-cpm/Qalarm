/** * * Created by admin on 15/10/11.  */

app.factory('adminModel', ['$resource', function ($resource) {
    var api = {};
    var defaults = {
        'add':      {method:'POST',     params: {'action': 'add'}},
        'delete':   {method:'DELETE',   params: {'action': 'delete'}},
        'update':   {method:'POST',     params: {'action': 'update'}},
        'query': {method: 'GET', isArray: false},
        'get': {method: 'POST', params: {'action': 'get'}},
        'list': {method: 'POST', params: {'action': 'list'}},
    };
    api.auth = function (params) {
        return $resource('/api/admin/auth', params, defaults);
    }

    api.role = function (params) {
        return $resource('/api/admin/role', params, defaults);
    }

    api.user = function (params) {
        return $resource('/api/admin/user', params, defaults);
    }

    api.login = function() {
        return $resource('/api/admin/login', {}, defaults);
    }

    api.logout = function() {
        return $resource('/api/admin/logout', {}, defaults);
    }

    api.index= function() {
        return $resource('/', {}, defaults);
    }

    api.permit = function(params){
        return $resource('/api/admin/permit', params,defaults);
    };

    api.menuPermit = function(params){
        return $resource('/api/admin/menupermit', params,defaults);
    };

    api.wfParticipators = function(){
        return $resource('/api/admin/wfparticipators', {}, queryOptions);
    };

    api.wfParticipatorUpdate = function(){
        return $resource('/api/admin/wfparticipatorupdate', {}, queryOptions);
    };

    return api;
}]);

//用户中心模型 用户中心:account
app.factory('accountModel', ['$resource', function ($resource) {
    var api = {};

    var defaults = {
        'get':      {method:'GET',      params: {'action': 'get'}},
        'save':     {method:'POST'},
        'query':    {method:'GET',      isArray:false},
        'remove':   {method:'DELETE'},
        'delete':   {method:'DELETE'},
        'list':     {method: 'POST',    params: {'action': 'list'}}
    };

    api.account = function(params){
        return $resource('/api/v1/account', params,defaults);
    };

    api.accountAuth = function (params) {
        return $resource('/api/v1/account/auth', params,defaults
        );
    };

    api.accountDetail = function (params){
        return $resource('/api/v1/account/detail',params,defaults
        );
    };

    api.accountCheck = function(params){
        return $resource('/api/v1/account/checklist', params,defaults);
    };

    api.accountHeadImgCheckList = function(params){
        return $resource('/api/v1/account/headimgchecklist', params,defaults);
    };

    api.accountLicenseCheckList = function(params){
        return $resource('/api/v1/account/licensechecklist', params,defaults);
    };

    //api.accountNotCheckCarImgLicense = function(params){
    //    return $resource('/api/v1/account/notcheckcarimglicense', params,defaults);
    //};


    //修改用户基本信息
    api.accountBasicUpdate = function(params){
        return $resource('/api/v1/account/basicupdate', params,defaults);
    };

    //修改车辆信息
    api.accountCarUpdate = function(params){
        return $resource('/api/v1/account/carupdate', params,defaults);
    };

    //修改证件信息
    api.accountDriverUpdate = function(params){
        return $resource('/api/v1/account/driverupdate', params,defaults);
    };

    //修改实名信息
    api.accountAuthUpdate = function(params){
        return $resource('/api/v1/account/accountauthupdate', params,defaults);
    };

    //审核跳过
    api.accountCheckSkip = function(params){
        return $resource('/api/v1/account/checkskip', params,defaults);
    };

    //获取开始审核数据
    api.startCheckLicense = function(params){
        return $resource('/api/v1/account/startchecklicense', params,defaults);
    };

    //审核数据本地
    api.checkAcc = function(params){
        return $resource('/api/v1/account/checkaccount', params,defaults);
    };

    api.accountCarNumbers = function(params){
        return $resource('/api/v1/account/carnumbers', params,defaults);
    };

    api.photos = function(params){
        return $resource('/api/v1/account/photos', params,defaults);
    };

    api.photosDel = function(params){
        return $resource('/api/v1/account/photos/delete', params,defaults);
    };

    api.photosDelLogs = function(params){
        return $resource('/api/v1/account/photos/deletelogs', params,defaults);
    };

    api.voiceDelLogs = function(params){
        return $resource('/api/v1/account/voice/deletelogs', params,defaults);
    };

        //============ 用户信息各种状态 ========

    //用户类型:1,正式用户；2,测试用户
    api.accountTypes = function(params) {
        return $resource('/api/v1/util/accounttypes').get({},{}).$promise;
    };
    //实名认证状态 0:未认证 1:认证中 2:成功 3:失败
    api.accountAuthStates = function(params){
        return $resource('/api/v1/util/accountauthstates').get({},{}).$promise;
    };
    api.accountCheckStates = function(params){
        return $resource('/api/v1/util/accountcheckstates').get({},{}).$promise;
    };
    // 证件审核状态(驾驶证) 0:未审核 1:审核中 2:成功 3:失败
    api.licenseCheckStates = function(params){
        return $resource('/api/v1/util/licensecheckstate').get({},{}).$promise;
    };
    // 头像，审核状态 0:未上传 1:审核中 2:成功 3:失败
    api.headImgCheckStates = function(params){
        return $resource('/api/v1/util/headimgcheckstate').get({},{}).$promise;
    };
    // 车照审核状态 0:未审核 1:审核中 2:成功 3:失败
    api.carImgCheckStates = function(params){
        return $resource('/api/v1/util/carimgcheckstate').get({},{}).$promise;
    };
    // 性别。1:男;2:女。
    api.accountSex = function(params){
        return $resource('/api/v1/util/accountsex').get({},{}).$promise;
    };
    // 汽车品牌
    api.carBrands = function(params){
        return $resource('/api/v1/account/carbrand').get({},{}).$promise;
    };

    //todo:行驶本状态没有定义

    return api;
}]);

//手机APP客户端配置管理
app.factory('appClientConf', ['$resource', function ($resource) {
    var api = {};

    var defaults = {
        'get':      {method:'GET',      params: {'action': 'get'}},
        'save':     {method:'POST'},
        'query':    {method:'GET',      isArray:false},
        'remove':   {method:'DELETE'},
        'delete':   {method:'DELETE'},
        'list':     {method: 'POST',    params: {'action': 'list'}}
    };

    api.index = function(params){
        return $resource('/api/v1/app/clientconf/index', params,defaults);
    };

    api.insert = function(params){
        return $resource('/api/v1/app/clientconf/insert', params,defaults);
    };

    api.update = function(params){
        return $resource('/api/v1/app/clientconf/update', params,defaults);
    };

    api.releasedata = function(params){
        return $resource('/api/v1/app/clientconf/releasedata', params,defaults);
    };


    return api;
}]);


//构建项目api Url
app.factory('ciProjectModel', ['$resource', function ($resource) {
    var api = {};

    var defaults = {
        //'get':      {method:'GET',      params: {'action': 'get'}},
        //'save':     {method:'POST'},
        //'query':    {method:'GET',      isArray:false},
        //'remove':   {method:'DELETE'},
        //'delete':   {method:'DELETE'},
        //'list':     {method: 'POST',    params: {'action': 'list'}}
        
        'add'    : {method : 'POST',     params : {'action' : 'add'}},
        'delete' : {method : 'DELETE',   params : {'action' : 'delete'}},
        'update' : {method : 'POST',     params : {'action' : 'update'}},
        'get'    : {method : 'POST', params     : {'action' : 'get'}},
        'list'   : {method : 'POST', params     : {'action' : 'list'}},
    };

    api.buildProject = function(params){
        return $resource('/api/v1/app/ciproject/buildproject', params,defaults);
    };

    api.gitLabProject = function(params){
        return $resource('/api/v1/app/ciproject/gitlabproject', params,defaults);
    };

    api.gaeaJenkinsJob = function(params){
        return $resource('/api/v1/app/ciproject/gaeajenkinsjob', params,defaults);
    };

    api.buildSteps = function(params){
        return $resource('/api/v1/app/ciproject/buildsteps', params,defaults);
    };

    api.createBuild = function(params){
        return $resource('/api/v1/app/ciproject/createbuild', params,defaults);
    };

    api.createJenkinsJob = function(params){
        return $resource('/api/v1/app/ciproject/createjenkinsjob', params,defaults);
    };

    api.deployProject = function(params){
        return $resource('/api/v1/app/ciproject/deployproject', params,defaults);
    };

    //api.deployProjectCreate = function(params){
        //return $resource('/api/v1/app/ciproject/deployprojectcreate', params,defaults);
    //};

    api.deployProjectCancel = function(params){
        return $resource('/api/v1/app/ciproject/deployprojectcancel', params,defaults);
    };

    //api.deployProjectRollBack = function(params){
        //return $resource('/api/v1/app/ciproject/rollback', params,defaults);
    //};

    api.deployProjectHostLogs = function(params){
        return $resource('/api/v1/app/ciproject/deployprojecthostlogs', params,defaults);
    };

    api.getGitlabChangeByChangeId = function(params){
        return $resource('/api/v1/app/ciproject/projectchange', params,defaults);
    };

    api.diffFiles = function(params){
        return $resource('/api/v1/app/ciproject/difffiles', params,defaults);
    };

    // projcet host
    api.ciProjectHosts = function(params){
        return $resource('/api/v1/util/ciprojecthosts', params,defaults);
    };

    api.ciProjectHostUpdate = function(params){
        return $resource('/api/v1/util/ciprojecthostupdate', params,defaults);
    };

    // projcet dns
    api.ciProjectDnses = function(params){
        return $resource('/api/v1/util/ciprojectdnses', params,defaults);
    };

    api.ciProjectDnsUpdate = function(params){
        return $resource('/api/v1/util/ciprojectdnsupdate', params,defaults);
    };

    // docker 镜像
    api.ciDockerSegments = function(params){
        return $resource('/api/v1/util/cidockersegments', params,defaults);
    };

    api.ciDockerImages = function(params){
        return $resource('/api/v1/util/cidockerimages', params,defaults);
    };

    api.ciDockerImageAssemble = function(params){
        return $resource('/api/v1/util/cidockerimageassemble', params,defaults);
    };

    api.ciDockerImageReassemble = function(params){
        return $resource('/api/v1/util/cidockerimagereassemble', params,defaults);
    };

    api.ciDockerImageUpdate = function(params){
        return $resource('/api/v1/util/cidockerimageupdate', params,defaults);
    };

    api.ciDockerBuildLog = function(params){
        return $resource('/api/v1/util/cidockerbuildlog', params,defaults);
    };

    api.deployAction = function(params){
        return $resource('/api/v1/app/ciproject/deployaction', params,defaults);
    };

    api.rollBack = function(params){
        return $resource('/api/v1/app/ciproject/rollback', params,defaults);
    };

    api.deployOperate = function(params){
        return $resource('/api/v1/app/ciproject/deployoperate', params,defaults);
    };

    api.testReport = function(params){
        return $resource('/api/v1/app/ciproject/testreport', params,defaults);
    };

    api.buildProjectLog = function(params){
        return $resource('/api/v1/app/ciproject/buildlog', params,defaults);
    };

    api.fetchCiMembers = function(params){
        return $resource('/api/v1/app/ciproject/fetchmembers', params,defaults);
    };

    api.projectMemberManager = function(params){
        return $resource('/api/v1/app/ciproject/members', params,defaults);
    };

    api.ciStepStateLog = function(params){
        return $resource('/api/v1/app/ciproject/steplog', params,defaults);
    };

    api.checkHostSet = function(params){
        return $resource('/api/v1/app/ciproject/checkhostset', params,defaults);
    };
    return api;
}]);


app.factory('commonModel', ['$resource', function ($resource) {
    var  utils = {};
    utils.couponTypes = function(params) {
        var couponTypes = $resource('/api/v1/util/coupontypes');
        return couponTypes.get(params,{}).$promise;
    };
    utils.manualCouponTypes = function(params) {
        var couponTypes = $resource('/api/v1/util/manualcoupontypes');
        return couponTypes.get(params,{}).$promise;
    };
    utils.bussTypes = function(params) {
        var bussTypes = $resource('/api/v1/util/busstypes')
        return bussTypes.get(params, {}).$promise;
    };
    utils.usernames = function(params) {
        var usernames = $resource('/api/v1/util/usernames')
        return usernames.get(params, {}).$promise;
    };

    utils.citys = function(params) {
        var citys = $resource('/api/v1/util/citys')
        return citys.get(params, {}).$promise;
    };
    utils.carBrands = function(params) {
        var cars = $resource('/api/v1/util/cars')
        return cars.get(params, {}).$promise;
    };
    utils.carPrefix = function(params) {
        var carPrefix = $resource('/api/v1/util/carprefix');
        return carPrefix.get({},{}).$promise;
    }


    utils.accountTypes = function(params) {
        var accountTypes = $resource('/api/v1/util/accounttypes');
        return accountTypes.get({},{}).$promise;
    };
    utils.accountAuthStates = function(params) {
        var accountAuthStates = $resource('/api/v1/util/accountauthstates');
        return accountAuthStates.get({},{}).$promise;
    };
    utils.pushJumpTypes = function(params) {
        var jumpTypes = $resource('/api/v1/util/pushjumptypes')
        return jumpTypes.get(params, {}).$promise;
    };
    
    utils.realtimeServiceAddr = function(params) {
        var realtimeService = $resource('/api/v1/util/realtimeservice')
        return realtimeService.get(params, {}).$promise;
    };

    utils.captcha = function(params) {
        var realtimeService = $resource('/api/v1/util/captcha')
        return realtimeService.get(params, {}).$promise;
    };

    utils.ciProjectList = function(params) {
        var realtimeService = $resource('/api/v1/util/ciprojectlist')
        return realtimeService.get(params, {}).$promise;
    };

    utils.ciBuildStatusList = function(params) {
        var realtimeService = $resource('/api/v1/util/cibuildstatuslist')
        return realtimeService.get(params, {}).$promise;
    };

    utils.ciHostList = function(params) {
        var realtimeService = $resource('/api/v1/util/cihostlist')
        return realtimeService.get(params, {}).$promise;
    };

    utils.ciDeployStatusList = function(params) {
        var realtimeService = $resource('/api/v1/util/cideploystatuslist')
        return realtimeService.get(params, {}).$promise;
    };
    return utils;
}]);



var queryOptions =  {
    'query': {method: 'GET', isArray: false},
    'get'  : {method: 'POST', params: {'action': 'get'}},
    'del'  : {method: 'POST', params: {'action': 'delete'}},
    'list' : {method: 'POST', params: {'action': 'list'}},
    'save' : {method:'POST', data:{}},
};

app.factory('QalarmModel', ['$resource', function ($resource) {
    var  api = {};
    api.getGraphs = function() {
        return $resource('/api/v1/graph/index', {}, queryOptions);
    };
    api.getGraphHistorys = function() {
        return $resource('/api/v1/graph/history', {}, queryOptions);
    };

    api.getGraphDetails = function() {
        return $resource('/api/v1/graph/detail', {}, queryOptions);
    };

    api.getMessages = function() {
        return $resource('/api/v1/graph/message', {}, queryOptions);
    };

    api.projects = function() {
        return $resource('/api/v1/project/index', {}, queryOptions);
    };
    api.update = function() {
        return $resource('/api/v1/project/update', {}, queryOptions);
    };
    api.option = function() {
        return $resource('/api/v1/project/option', {}, queryOptions);
    };
    api.alarmHistory = function() {
        return $resource('/api/v1/project/alarmhistory', {}, queryOptions);
    };
    api.messageHistory = function() {
        return $resource('/api/v1/project/messagehistory', {}, queryOptions);
    };
    api.subModule = function() {
        return $resource('/api/v1/project/submodule', {}, queryOptions);
    };
    api.updateSubModule = function() {
        return $resource('/api/v1/project/updatesubmodule', {}, queryOptions);
    };
    return api;
}]);
app.factory('PageModel', ['$resource', function ($resource) {
    var  api = {};
    api.getSpeeds = function() {
        return $resource('/api/v1/page/index', {}, queryOptions);
    };
    api.getPageList = function() {
        return $resource('/api/v1/page/list', {}, queryOptions);
    };

    return api;
}]);


app.factory('contentModel', ['$resource', function ($resource) {
    var api = {};
    api.contents = function() {
        return $resource('/api/v1/contents', {}, queryOptions);
    }

    api.content = function() {
        return $resource('/api/v1/content', {}, queryOptions);
    }

    api.contentUpdate = function() {
        return $resource('/api/v1/contents/update', {}, queryOptions);
    }

    api.contentStatus = function() {
        return $resource('/api/v1/content/status', {}, queryOptions);
    }

    api.contentTypes = function() {
        return $resource('/api/v1/contenttypes', {}, queryOptions);
    }

    api.contentTypeUpdate = function() {
        return $resource('/api/v1/contenttypes/update', {}, queryOptions);
    }

    api.contentTypesTree = function() {
        return $resource('/api/v1/contenttypestree', {}, queryOptions);
    }

    return api;
}]);

app.factory('marketModel', ['$resource', function ($resource) {
    var api = {};
    api.myapp = function() {
        return $resource('/api/v1/market/myapp', {}, queryOptions);
    }
    
	api.apps = function() {
        return $resource('/api/v1/market/apps', {}, queryOptions);
    }

    return api;
}]);

app.factory('flowModel', ['$resource', function ($resource) {
    var api = {};
    api.vpn = function() {
        return $resource('/api/v1/flow/applyvpn', {}, queryOptions);
    }
    api.recoverPermission = function() {
        return $resource('/api/v1/flow/recoverperm', {}, queryOptions);
    }

    api.serverPerm = function() {
        return $resource('/api/v1/flow/serverperm', {}, queryOptions);
    }

    api.changePasswd = function() {
        return $resource('/api/v1/ops/changepasswd', {}, queryOptions);
    }

    api.applyApp = function() {
        return $resource('/api/v1/flow/applyapp', {}, queryOptions);
    }

    api.workflows = function() {
        return $resource('/api/v1/flow/workflows', {}, queryOptions);
    }

    api.jobWorkflows = function() {
        return $resource('/api/v1/flow/jobworkflows', {}, queryOptions);
    }

    api.workflowStatusTransfer = function() {
        return $resource('/api/v1/flow/workflowstatustransfer', {}, queryOptions);
    }

    return api;
}]);

app.factory('jobDoneModel', ['$resource', function ($resource) {
    var api = {};
    api.scripts = function() {
        return $resource('/api/v1/jobdone/scripts', {}, queryOptions);
    }

    api.scriptTyps = function() {
        return $resource('/api/v1/jobdone/scripttypes', {}, queryOptions);
    }

    api.updateScript = function() {
        return $resource('/api/v1/jobdone/updatescript', {}, queryOptions);
    }

    api.script = function() {
        return $resource('/api/v1/jobdone/script', {}, queryOptions);
    }

    // 执行脚本
    api.scriptExecs = function() {
        return $resource('/api/v1/jobdone/scriptexecs', {}, queryOptions);
    }

    api.scriptExec = function() {
        return $resource('/api/v1/jobdone/scriptexec', {}, queryOptions);
    }

    api.updateScriptExec = function() {
        return $resource('/api/v1/jobdone/updatescriptexec', {}, queryOptions);
    }

    api.goonScriptExec = function() {
        return $resource('/api/v1/jobdone/goonscriptexec', {}, queryOptions);
    }

    api.execResult = function() {
        return $resource('/api/v1/jobdone/execresults', {}, queryOptions);
    }

    return api;
}]);

app.factory('OpsModel', ['$resource', function ($resource) {
    var api = {};
    api.hosts = function () {
        return $resource('/api/v1/ops/hosts', {}, queryOptions);
    }

    api.hostnames = function () {
        return $resource('/api/v1/ops/hostnames', {}, queryOptions);
    }

    api.updateHost = function () {
        return $resource('/api/v1/ops/updatehost', {}, queryOptions);
    }

    return api;
}]);

app.factory('activeModel', ['$resource', function ($resource) {
    return $resource('/api/admin/auth', {},
        {
            'query': {method: 'GET', isArray: false},
            'get': {method: 'POST', params: {'action': 'get'}},
            'list': {method: 'POST', params: {'action': 'list'}},
        }
    );
}]);

app.factory('couponModel', ['$resource', function ($resource) {
    return $resource('/api/v1/awards', {},
        {
            'query': {method: 'GET', isArray: false},
            'get': {method: 'POST', params: {'action': 'get'}},
            'list': {method: 'POST', params: {'action': 'list'}},
        }
    );
}]);

app.factory('manualCouponModel', ['$resource', function ($resource) {
    return $resource('/api/v1/coupons/manual_coupons', {},
        {
            'query': {method: 'GET', isArray: false},
            'get': {method: 'POST', params: {'action': 'get'}},
            'list': {method: 'POST', params: {'action': 'list'}},
        }
    );
}]);

app.factory('userModel', ['$resource', function ($resource) {
    return $resource('/api/v1/usermobiles', {},
        {
            'query': {method: 'GET', isArray: false},
            'get': {method: 'POST', params: {'action': 'get'}},
            'list': {method: 'POST', params: {'action': 'list'}},
        }
    );
}]);

app.factory('NoticeModel', ['$resource', function ($resource) {
    return $resource('/api/v1/notices', {},
        {
            'query': {method: 'GET', isArray: false},
            'get': {method: 'POST', params: {'action': 'get'}},
            'list': {method: 'POST', params: {'action': 'list'}},
        }
    );
}]);

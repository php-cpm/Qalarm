app.controller("GitLabCtrl", ['$state', '$scope', '$modal', 'NgTableParams','toaster', 'ciProjectModel',
    function ($state, $scope, $modal, NgTableParams, toaster, ciProjectModel) {
        var self = this;
        var api = ciProjectModel.gitLabProject();

        self.createTable = function () {

            self.tableParams = new NgTableParams({}, {
                getData: function (params) {
                    var data = getNgTableData(params);
                    return data;
                }
            });

        };

        function getNgTableData(pageParams) {

            return api.list({}, {}).$promise.then(function (response) {
                if(response.errno == 0 || response.errno == '0'){
                    return response.data.data;
                } else {
                    return {};
                }
            });

        }

        self.createTable();
        
        self.buildProject = function (project_id,branch) {
            //从新构建最新项目
            ciProjectModel.createBuild().get({'project_id':project_id,'branch':branch}).$promise.then(function(response){
                if (response.errno == 0) {
                    toaster.pop('success', '通知', '项目构建开始');
                    $state.go('app.ci_project.buildproject');
                } else {
                    toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                }
            });

        };

        self.createJenkinsJob = function (rowData) {
            //创建新项目
            ciProjectModel.createJenkinsJob().get({'job_name':rowData.project_job_name,'project_id':rowData.project_id}).$promise.then(function(response){
            //ciProjectModel.createJenkinsJob().get({'job_name':rowData.project_job_name,'project_addr':rowData.project_addr}).$promise.then(function(response){
                if (response.errno == 0) {
                    toaster.pop('success', '通知', '创建job 成功');

                } else {
                    toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                }
            });

        };
        
        self.formatRowData = {
            buildIsSuccess : function (status) {
                console.log(status);
                return 'aa';
               //return status == 'SUCCESS' ? true : false;
            },
            isDownProject  : function (status) {
               return status == 'SUCCESS' ? true : false;
            }
        }

        ciProjectModel.gaeaJenkinsJob().list({}, {}).$promise.then(function(data){
            if(data.errno == 0 || data.errno == '0'){
                $scope.jobs = data.data;
                //console.log($scope.jobs);
            }else{
                return {};
            }
        });

        //self.editData = function (row) {

            //var modalInstance = $modal.open({
                //templateUrl: 'ci.gitlab.edit.html',
                //controller: EditCtrl,
                //size: 'md',
                //windowClass: 'modal-gaea',
                //resolve: {
                    //params: function () {
                        //return {
                            //"data" : row,
                            //"jenkins_jobs" : $scope.jobs 
                        //}
                    //}
                //}
            //});

            //modalInstance.result.then(function () {
                //console.log('ok');
                //self.createTable();
            //}, function () {
                //console.log('cancel');
            //});
        //};

        //var EditCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {

            //$scope.data = {};
            //$scope.data.editorOptions = {
                //lineNumbers: true,
                //mode: 'shell',
                //fixedGutter: true,
                //keyMap: 'vim',
                //indentWithTabs: true,
            //};

            //$scope.data = params.data;
            //$scope.data.build_before_sh = params.data.build_before_sh;
            //// FIXME 根据用户选择保存选中状态, 没有好的方法
            ////$scope.data.is_scm_open     = true;
            ////$scope.data.is_shell_open   = false;
            ////$scope.data.is_blacklist_open= true;
            //$scope.data.deply_after_sh  = params.data.deply_after_sh;
            
            ////todo:bug chosen 临时写法
            //$scope.selectedBranchs = getSelectedBranchs($scope.data.listener_branchs); 
            //function getSelectedBranchs (data){
                //var branchs = [];
                //for(var i=0; i<data.length; i++) {
                    //branchs.push(data[i].name);
                //}
                //return branchs.join('|');
            //}

            ////$scope.jenkinsJobs = params.jenkins_jobs;
            //$scope.jobList = params.data.jobs;
            //jenkinsJobs1 = []; 
            ////jenkinsJobs1 = {}; 
            //selected =[];
            //buildSteps = params.data.ci_build_steps;
            ////console.log($scope.jobList);
            ////查找下拉框；应绑定什么数据
            //for(objJob in $scope.jobList) {
                //for(obj in buildSteps) {
                    //if ($scope.jobList[objJob].name == buildSteps[obj].job_name_pre) {
                        ////selected.push($scope.jobList[obj].id);
                        //selected.push($scope.jobList[obj].id);
                    //}
                //}
            //}

            //$scope.data.jobsSelected = selected;

            ////$scope.data = params.data;
            //$scope.ok = function () {

                //if ($scope.data.language_version == '' || $scope.data.language == '') {
                    //toaster.pop('error', '提示', '项目语言必须选择');
                    //return;
                //}

                ////if ($scope.data.language_version == '' || $scope.data.language == '') {
                    ////toaster.pop('error', '提示', '项目语言必须选择');
                    ////return;
                ////}

                //var pData = {
                    //"id"                  : $scope.data.id,
                    ////"project_id"        : $scope.data.project_id,
                    ////"project_name"      : $scope.data.project_name,
                    ////"project_addr"      : $scope.data.project_addr,
                    ////"project_desc"      : $scope.data.project_desc,
                    ////"project_job_name"  : $scope.data.project_job_name,
                    ////"project_branch"    : $scope.data.project_branch,
                    ////"listener_branchs"  : $scope.data.listener_branchs,
                    //"listener_branchs"    : $scope.selectedBranchs,
                    //"ssh_user"            : $scope.data.ssh_user,
                    //"build_before_sh"     : $scope.data.build_before_sh,
                    //"deploy_after_sh"     : $scope.data.deploy_after_sh,
                    //"language"            : $scope.data.language,
                    //"language_version"    : $scope.data.language_version,
                    //"build_steps"         : $scope.data.jobsSelected,
                    //"deploy_dir"          : $scope.data.deploy_dir,
                    //"checkcode_dir"       : $scope.data.checkcode_dir,
                    ////"is_scm_open"       : $scope.data.is_scm_open,
                    ////"is_shell_open"     : $scope.data.is_shell_open,
                    ////"is_blacklist_open" : $scope.data.is_blacklist_open
                    //"deploy_files"        : $scope.data.deploy_files,
                    //"deploy_black_files"  : $scope.data.deploy_black_files,
                    //"deploy_dir"          : $scope.data.deploy_dir
                //};

                //ciProjectModel.gitLabProject().update(pData).$promise.then(function(response){
                    //if (response.errno == 0) {
                        //toaster.pop('success', '通知', '编辑成功');
                        //$modalInstance.close(1);
                        ////console.log(response.data.data);
                    //} else {
                        //toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                    //}
                //});
            //};

            //$scope.cancel = function () {
                //$modalInstance.dismiss('cancel');
            //}

            //$scope.languages = ['php','java'];
            //$scope.$watch('data.language', function(newVal) {
                ////if (newVal) $scope.cities = ['Los Angeles', 'San Francisco'];
                //if (newVal == 'php' ) $scope.languageVersions = ['5.3', '5.4','5.5','5.6','5.7'];
                //if (newVal == 'java') $scope.languageVersions = ['jdk 1.6', 'jdk 1.7', 'jdk 1.8'];
                ////$scope.data.language_version = '';
            //});
        //};

        //self.showCDResources = function (row, size) {
            //var modalInstance = $modal.open({
                //templateUrl: 'ci.cdresource.html',
                //controller: CdResourceCtrl,
                //size: size,
                //windowClass: 'modal-gaea',
                //resolve: {
                    //params: function () {
                        //return {
                            //"data" : row,
                        //}
                    //}
                //}
            //});

            //modalInstance.result.then(function () {
            //}, function () {
            //});
        //};

        //var CdResourceCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            //$scope.project = {};
            //$scope.project.ProjectRow = params.data;

            //$scope.ok = function () {
            //};

            //$scope.cancel = function () {
                //$modalInstance.dismiss('cancel');
            //}
        //}

        //self.showCiMembers = function (row, size) {
            //var modalInstance = $modal.open({
                //templateUrl: 'ci.member.html',
                //controller: CiMemberCtrl,
                //size: size,
                //windowClass: 'modal-gaea',
                //resolve: {
                    //params: function () {
                        //return {
                            //"data" : row,
                        //}
                    //}
                //}
            //});

            //modalInstance.result.then(function () {
            //}, function () {
            //});
        //};

        //var CiMemberCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            //$scope.project = {};
            //$scope.project.ProjectRow = params.data;

            //$scope.ok = function () {
            //};

            //$scope.cancel = function () {
                //$modalInstance.dismiss('cancel');
            //}
        //}
    }
]);


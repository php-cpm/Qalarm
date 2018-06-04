app.controller("DeployProjectCtrl", ['$state', '$scope', '$modal', 'NgTableParams', 'ciProjectModel', '$timeout','toaster','commonModel',
    function ($state, $scope, $modal, NgTableParams, ciProjectModel, $timeout, toaster, commonModel) {
        var self = this;
        //var api = ciProjectModel.deployProject();
        var api = ciProjectModel.deployOperate();

        self.currentPage = 1;

        self.createTable = function() {
            var pageSize = 15;
            self.tableParams = new NgTableParams({count: pageSize, page: self.currentPage}, {
                getData: function (params) {
                    self.currentPage  = params.page();
                    var data = getNgTableData(params);
                    return data;
                }
            });
        };

        function getNgTableData(pageParams){
            return api.list(setNgTableParams(pageParams, self.searchParams), {})
                .$promise.then(function (data) {
                    //后台js 返回数据判断; 0 成功,正确绑定数据; 非0 返回空
                    if(data.errno == 0 || data.errno == '0'){
                        pageParams.total(data.data.page.total);
                        pageParams.page(data.data.page.index);
                        return data.data.results;
                    }else{
                        return {};
                    }
                });
        }

        //设置 查询参数
        function setNgTableParams(pageParams,fileterParams){
            var result = {};
            for(var key in fileterParams){
                if(fileterParams[key].currentVal != fileterParams[key].ignoreVal){
                    result[fileterParams[key].urlKeyName] = fileterParams[key].currentVal;
                }
            }
            result.page_index = pageParams.page();
            result.page_size = 15;
            return result;
        }

        //初始化查询条件
        function initSearchParams(){
            return {
                filters: {
                    "projectId":  { "currentVal":"", "ignoreVal":"", "urlKeyName": "project_id"},
                    "deployStatus":  { "currentVal":"", "ignoreVal":"", "urlKeyName": "deploy_status"}
                }
            };
        }

        //调用初始化函数
        self.searchParams = initSearchParams().filters;

        self.search = function() {
            self.currentPage = 1;
            self.tableParams.reload();
        };
        
        self.searchParams.projectId.currentVal = 0;
        self.searchParams.deployStatus.currentVal = "ALL";
        //获取project list列表
        commonModel.ciProjectList().then(function (response) {
            console.log(response.data);
            self.ciProjectList = response.data;
            //self.searchParams.projectId.currentVal = self.ciDeployStatusList[0].id;
            self.ciProjectSelect = function () {
                self.currentPage = 1;
                console.log(self.searchParams.projectId);
            };
        });

        //获取deploy status list列表
        commonModel.ciDeployStatusList().then(function (response) {
            console.log(response.data);
            self.ciDeployStatusList = response.data;
            //self.searchParams.deployStatus.currentVal = self.ciDeployStatusList[0].id;
            self.ciDeployStatusSelect = function () {
                self.currentPage = 1;
                console.log(self.searchParams.deployStatus);
            };
        });

        /* 执行页面的定时刷新*/
        $scope.onTimeout = function () {
            self.tableParams.reload();
            timer = $timeout($scope.onTimeout,3000);
        }
        var timer = $timeout($scope.onTimeout, 3000);
        $scope.$on('$destroy', function (event) {
            var result = $timeout.cancel(timer);
            console.log(result);
        });

        self.createTable();

    //const DEPLOY_STATUS_BETA_WAITING              = 29;
    //const DEPLOY_STATUS_BETA_RUNNING              = 30;
    //const DEPLOY_STATUS_BETA_STOP                 = 31;
    //const DEPLOY_STATUS_BETA_SUCCESS              = 32;
    //const DEPLOY_STATUS_BETA_FAIL                 = 33;
    //const DEPLOY_STATUS_BETA_CANCEL               = 34;
    //const DEPLOY_STATUS_BETA_ROLLBACK             = 35;
    //const DEPLOY_STATUS_BETA_ROLLBACK_RUNNING     = 36;
    //const DEPLOY_STATUS_BETA_ROLLBACK_FAIL        = 37;
    //const DEPLOY_STATUS_BETA_ROLLBACK_SUCCESS     = 38;

        self.formatDeployStatus = {
            success : function (status) {
                return status == 'SUCCESS' ? true : false;
            },
            running : function (status) {
               return status == 'RUNNING' ? true : false;
            },
            //waiting : function (status) {
               //return status == 'WAITING' ? true : false;
            //},
            cancel    : function (status) {
               return status == 'CANCEL' ? true : false;
            },
            fail    : function (status) {
               return status == 'FAILURE' ? true : false;
            }
        }

        self.beginTest = {
            beta:  function (){
                $scope.setTestResult = function () {
                    var urlParams = { "begin_test" : 'begin_test' }
                    ciProjectModel.testReport().update(urlParams, {})
                        .$promise.then(function (data) {
                            if(data.errno == 0 || data.errno == '0'){
                                toaster.pop('success', '提示', '保存数据成功');
                                $modalInstance.close(1);
                                return {};
                            }else{
                                toaster.pop('error', '提示', '保存失败');
                                return {};
                            }
                        });
                };
            }
        };

        self.deployTestReport = function(row, isShowContent, isShowTestReportBtn, isShowSetResultBtn) {

            var modalInstance = $modal.open({
                templateUrl: 'ci.deploy.testreport.html',
                controller: deployTestReportCtrl,
                size: 'lg',
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                            "data"   : row,
                            "input_status" : {
                                "is_show_content" : isShowContent,
                                "is_show_testreport_btn" : isShowTestReportBtn,
                                "is_show_set_result_btn" : isShowSetResultBtn
                            }
                        }
                    }
                }
            });

            modalInstance.result.then(function () {
                console.log('ok');
            }, function () {
                console.log('cancel');
            });

        };
        var deployTestReportCtrl = function ($scope, $http, $modalInstance, $state, commonModel, toaster, params) {

            deployTypeList = [{"id":1, "name":"新功能"},{"id":2, "name":"Bug修复"},{"id":3, "name":"改进优化"}];
            $scope.data = params.data;
            $scope.inputStatus = params.input_status;

            $scope.data.deployTypeList = deployTypeList;
            $scope.data.deployType = 1;
            $scope.data.deployStep = 0;
            $scope.deployTypeSelect = function () {
                console.log($scope.data.deployType);
            };

            ciProjectModel.getGitlabChangeByChangeId().get({"update_id": $scope.data.update_id}, {})
                .$promise.then(function (data) {
                    if(data.errno == 0 || data.errno == '0'){
                        var commits = data.data.commits;
                        var desc = '';
                        for(var i=1; i<commits.length+1; i++){
                            //desc += commits[i-1].message + '\n';
                            desc += i + '.' + commits[i-1].message + '';
                        }
                        $scope.data.desc = desc;
                        $scope.data.title = commits[0].message;
                    }
                });
            ciProjectModel.gitLabProject().get({"project_id": $scope.data.project_id}, {})
                .$promise.then(function (data) {
                    if(data.errno == 0 || data.errno == '0'){
                        //console.log(data.data);
                        //console.log(data.data.deploy_dir);
                        $scope.data.deploy_dir = data.data.deploy_dir;
                    }
                });

            //获取测试数据
            ciProjectModel.testReport().get({'gaea_build_id': $scope.data.gaea_build_id}, {})
                .$promise.then(function (data) {
                    if(data.errno == 0 || data.errno == '0'){
                        $scope.data.test_content = data.data.test_content;
                        $scope.data.test_result = data.data.test_result;
                        $scope.testReportData = data.data;

                        $scope.data.test_user = data.data.test_user;
                        $scope.data.test_user_name = data.data.test_user_name;
                        $scope.data.desc = data.data.commit_desc;
                        $scope.data.title = data.data.commit_title;
                    }
                });

            $scope.ok = function () {

                if ($scope.data.test_user == '') {
                    toaster.pop('error', '提示', '没有填写人员');
                    return;
                }

                if ($scope.data.title == '' || $scope.data.desc == '') {
                    toaster.pop('error', '提示', '没有测试内容');
                    return;
                }

                var urlParams = {
                    "gaea_build_id"    : $scope.data.gaea_build_id,
                    "commit_title"            : $scope.data.title,
                    "commit_desc"             : $scope.data.desc,
                    "test_user"      : $scope.data.test_user,
                    "test_user_name"      : $scope.data.test_user,
                }
                //ciProjectModel.deployALevel().get(urlParams, {})
                //ciProjectModel.deployTestReport().add(urlParams, {})
                ciProjectModel.testReport().add(urlParams, {})
                    .$promise.then(function (data) {
                        if(data.errno == 0 || data.errno == '0'){
                            toaster.pop('success', '提示', '提测成功;已通知相关人员');
                            $modalInstance.close(1);
                            return {};
                        }else{
                            toaster.pop('error', '提示', '部署失败');
                            return {};
                        }
                    });
            };

            $scope.setTestResult = function () {
                if ($scope.data.test_result == '') {
                    toaster.pop('error', '提示', '没有选择测试结果');
                    return;
                }

                var urlParams = {
                    "gaea_build_id" : $scope.data.gaea_build_id,
                    "test_result"   : $scope.data.test_result,
                    "test_content"  : $scope.data.test_content,
                }
                ciProjectModel.testReport().update(urlParams, {})
                    .$promise.then(function (data) {
                        if(data.errno == 0 || data.errno == '0'){
                            toaster.pop('success', '提示', '提交测试结果成功;已通知相关人员');
                            $modalInstance.close(1);
                            return {};
                        }else{
                            toaster.pop('error', '提示', '保存失败');
                            return {};
                        }
                    });
            };
            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            };
        }

        self.deployHandle = {
            deploy    : function (deploy_id,project_id,gaea_build_id) {
                //回滚
            //'deploy_id'     => "required",
            //'project_id'    => "required",
            //'gaea_build_id' => "required",
            //'deploy_action' => "required",
                var deployAction = 'deploy';
                var urlParams = { "deploy_id": deploy_id , "project_id": project_id, "gaea_build_id" : gaea_build_id, "deploy_action" : deployAction};
                console.log (urlParams);
                //return;
                ciProjectModel.deployAction().get(urlParams, {})
                    .$promise.then(function (data) {
                        if(data.errno == 0 || data.errno == '0'){
                            toaster.pop('success', '提示', '部署任务提交成功，部署需要一定时间');
                            //var urlData = {"deploy_id":data.data.deploy_id};
                            var urlData = {"deploy_step_id":data.data.deploy_step_id};
                            //var urlData = {"deploy_id":deploy_id};
                            $state.go('app.ci_project.deploylasttask',urlData);
                            //self.createTable();
                            return {};
                        }else{
                            toaster.pop('error', '提示', '部署失败'+data.errmsg);
                            return {};
                        }
                    });
            },
            rollBack    : function (deploy_id,project_id,gaea_build_id) {
                //回滚
                var deployAction = 'rollback';
                var urlParams = { "deploy_id": deploy_id , "project_id": project_id, "gaea_build_id" : gaea_build_id, "deploy_action" : deployAction};
                //var urlParams = { "deploy_id": deploy_id , "project_id": project_id, "gaea_build_id" : gaea_build_id};
                //ciProjectModel.rollBack().get(urlParams, {})
                ciProjectModel.deployAction().get(urlParams, {})
                    .$promise.then(function (data) {
                        if(data.errno == 0 || data.errno == '0'){
                            toaster.pop('success', '提示', '回滚任务提交成功，部署需要一定时间');
                            //var urlData = {"deploy_id":deploy_id};
                            var urlData = {"deploy_step_id":data.data.deploy_step_id};
                            //console.log(urlData);
                            //return;
                            $state.go('app.ci_project.deploylasttask',urlData);
                            self.createTable();
                            return {};
                        }else{
                            toaster.pop('error', '提示', '回滚部署失败'+data.errmsg);
                            return {};
                        }
                    });
            },
            cancel : function (deploy_id) {
                //取消发布；回滚操作
                console.log(deploy_id); 
                var urlParams = { "deploy_id": deploy_id };
                ciProjectModel.deployProjectCancel().get(urlParams, {})
                    .$promise.then(function (data) {
                        if(data.errno == 0 || data.errno == '0'){
                            toaster.pop('success', '提示', '取消部署成功');
                            self.createTable();
                            return {};
                        }else{
                            toaster.pop('error', '提示', '取消部署失败'+data.errmsg);
                            return {};
                        }
                    });
            },
            stop    : function (deploy_id) {
                //暂停发布
                console.log(deploy_id); 
            },
            isFinished : function (status) {
                //构建步骤的结束状态
                if (status == 'SUCCESS' || status== 'FAILURE' || status == 'CANCEL') {
                    return true;
                } else {
                    return false;
                }
            }
        }

        //self.formatCancelButton = {
            //success : function (status) {
                //return status == 'SUCCESS' ? true : false;
            //},
            //fail    : function (status) {
               //return status != 'SUCCESS' ? true : false;
            //}
        //}

        self.showDeployLog = function (deployLog) {

            var modalInstance = $modal.open({
                templateUrl: 'ci.deploylog.show.html',
                controller: showDeployCtrl,
                size: 'lg',
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                            "data" : deployLog,
                        }
                    }
                }
            });

            modalInstance.result.then(function () {
                console.log('ok');
            }, function () {
                console.log('cancel');
            });
        };

        var showDeployCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            $scope.data = params.data;

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }

            $scope.formatDeployStatus = {
                success : function (status) {
                    return status == 'SUCCESS' ? true : false;
                },
                running : function (status) {
                   return status == 'RUNNING' ? true : false;
                },
                waiting : function (status) {
                   return status == 'WAITING' ? true : false;
                },
                fail    : function (status) {
                   return status == 'FAILURE' ? true : false;
                }
            }
        };
    }
]);


app.controller("BuildProjectCtrl", ['$state', '$scope', '$modal', 'NgTableParams', 'ciProjectModel', '$timeout','commonModel', 'toaster',
    function ($state, $scope, $modal, NgTableParams, ciProjectModel, $timeout, commonModel, toaster) {
        var self = this;
        var api = ciProjectModel.buildProject();

        self.currentPage = 1;

        self.createTable = function() {
            var pageSize = 15;
            //self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
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
            //todo:去掉查询条件
            for(var key in fileterParams){
                //console.log(fileterParams[key]);
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
                    "buildStatus":  { "currentVal":"", "ignoreVal":"", "urlKeyName": "build_status"}
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
        self.searchParams.buildStatus.currentVal = "ALL";
        //获取project list列表
        commonModel.ciProjectList().then(function (response) {
            console.log(response.data);
            self.ciProjectList = response.data;
            //self.searchParams.projectId.currentVal = self.ciBuildStatusList[0].id;
            self.ciProjectSelect = function () {
                self.currentPage = 1;
                console.log(self.searchParams.projectId);
            };
        });

        //获取build status list列表
        commonModel.ciBuildStatusList().then(function (response) {
            console.log(response.data);
            self.ciBuildStatusList = response.data;
            //self.searchParams.buildStatus.currentVal = self.ciBuildStatusList[0].id;
            self.ciBuildStatusSelect = function () {
                self.currentPage = 1;
                console.log(self.searchParams.buildStatus);
            };
        });

        // 执行页面的定时刷新
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

        self.formatBuildStatus = {
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

//========================== 显示 build log  start==================================
        self.showBuildLog = function (row,sonar_url) {

            var modalInstance = $modal.open({
                templateUrl: 'ci.buildlog.show.html',
                controller: showBuildCtrl,
                size: 'lg',
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                            "data" : row,
                            "sonar_url" :sonar_url 
                        }
                    }
                }
            });

            modalInstance.result.then(function () {
                console.log('ok');
                //self.createTable();
            }, function () {
                console.log('cancel');
            });
        };

        var showBuildCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {

            $scope.data = params.data;
            ciProjectModel.buildProjectLog().get({"id": $scope.data.id}, {})
                .$promise.then(function (data) {
                    if(data.errno == 0 || data.errno == '0'){
                        $scope.data = data.data;
                    } else {
                        toaster.pop('error', '提示', '获取数据异常');
                        return;
                    }
                });

            $scope.sonarUrl = params.sonar_url;
            console.log($scope.data);
            console.log(params.sonar_url);

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }

            $scope.isLinkLog = function(status,jenkins_job_name) {
                if (status == 'RUNNING' || status == 'WAITING') {
                    return false;
                }
                isLinkLog = jenkins_job_name.indexOf('checkcode') >= 0;
                console.log(isLinkLog);
                return isLinkLog; 
            };

            $scope.formatBuildStatus = {
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
//========================== 显示 build log  end ==================================


//========================== 显示 project change log  start ==================================
        self.showProjectChange = function (updateId,sonar_url) {

            var modalInstance = $modal.open({
                templateUrl: 'ci.projectchange.show.html',
                controller: showProjectChangeCtrl,
                size: 'lg',
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                            "data" : updateId 
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

        var showProjectChangeCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            
            $scope.data = {};
            $scope.update_id = params.data;

            ciProjectModel.getGitlabChangeByChangeId().get({"update_id": $scope.update_id}, {})
                .$promise.then(function (data) {
                    if(data.errno == 0 || data.errno == '0'){
                        $scope.data = data.data;
                        //var commits = data.data.commits;
                        //var desc = '';
                        //for(var i=1; i<commits.length+1; i++){
                            ////desc += commits[i-1].message + '\n';
                            //desc += i + '.' + commits[i-1].message + '\n';
                        //}
                        //$scope.data.desc = desc;
                        //$scope.data.title = commits[0].message;
                    } else {
                        toaster.pop('error', '提示', '获取数据异常');
                        return;
                    }
                });

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            };
        };

//========================== 显示 project change log  end ==================================


//========================== 显示 deploy project  start ==================================

        self.deployProject = function(row) {

            var modalInstance = $modal.open({
                templateUrl: 'ci.deploy.project.create.html',
                controller: deployProjectCtrl,
                size: 'lg',
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                            "data" : row
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

        var deployProjectCtrl = function ($scope, $http, $modalInstance, $state, commonModel, toaster, params) {

            //============================= 注释掉：改为用 单选框 选择 =======================================
            ////获取列表
            //commonModel.ciHostList().then(function (response) {
                //console.log(response.data);
                //$scope.ciHostList = response.data;

                ////选择的数据
                //$scope.selected = [];
                //var updateSelected = function (action, id) {
                    //if (action == 'add' & $scope.selected.indexOf(id) == -1) $scope.selected.push(id);
                    //if (action == 'remove' && $scope.selected.indexOf(id) != -1) $scope.selected.splice($scope.selected.indexOf(id), 1);
                //}

                //$scope.updateSelection = function ($event, id) {
                    //var checkbox = $event.target;
                    //var action = (checkbox.checked ? 'add' : 'remove');
                    //updateSelected(action, id);
                //};

                //$scope.selectAll = function ($event) {
                    //var checkbox = $event.target;
                    //var action = (checkbox.checked ? 'add' : 'remove');
                    //for (var i = 0; i < $scope.ciHostList.length; i++) {
                        //var entity = $scope.ciHostList[i];
                        //updateSelected(action, entity.id);
                    //}
                //};

                //$scope.getSelectedClass = function (entity) {
                    //return $scope.isSelected(entity.id) ? 'selected' : '';
                //};

                //$scope.isSelected = function (id) {
                    //return $scope.selected.indexOf(id) >= 0;
                //};

                ////something extra I couldn't resist adding :)
                //$scope.isSelectedAll = function () {
                    //return $scope.selected.length === $scope.ciHostList.length;
                //};
            //});
            //============================= 注释掉：改为用 单选框 选择 =======================================

            deployTypeList = [{"id":1, "name":"新功能"},{"id":2, "name":"Bug修复"},{"id":3, "name":"改进优化"}];
            $scope.data = params.data;

            $scope.data.deployTypeList = deployTypeList;
            $scope.data.deployType = 1;
            $scope.data.deployStep = 0;
            //$scope.data.title = '';
            //$scope.data.desc = '';
            //$scope.data.deploy_dir = '';
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
                        console.log(data.data);
                        console.log(data.data.deploy_dir);
                        $scope.data.deploy_dir = data.data.deploy_dir;
                    }
                });

            $scope.hostset = {'test': false, 'slave' : false, 'online': false};
            ciProjectModel.checkHostSet().get({"project_id": $scope.data.project_id}, {})
                .$promise.then(function (data) {
                    if(data.errno == 0 || data.errno == '0'){
                        console.log(data.data);
                        $scope.hostset.test   = data.data.test;
                        $scope.hostset.slave  = data.data.slave;
                        $scope.hostset.online = data.data.online;
                        //console.log(data.data.deploy_dir);
                        //$scope.data.deploy_dir = data.data.deploy_dir;
                    }
                });
            //console.log($scope.data);
            //执行发布
            $scope.ok = function () {
                //var urlData = {"deploy_id":'123455555'};
                //$state.go('app.ci_project.deploylasttask',urlData);
                //return;
                //if ($scope.selected.length == 0) {
                    //toaster.pop('error', '提示', '没有选择待发布机器');
                    //return;
                //}
                //console.log($scope.selected);

                var urlParams = {
                    "gaea_build_id"    : $scope.data.gaea_build_id,
                    "title"            : $scope.data.title,
                    "desc"             : $scope.data.desc,
                    "deploy_step"      : $scope.data.deployStep,
                }
                //ciProjectModel.deployALevel().get(urlParams, {})
                ciProjectModel.deployOperate().add(urlParams, {})
                    .$promise.then(function (data) {
                        if(data.errno == 0 || data.errno == '0'){
                            toaster.pop('success', '提示', '开始部署了，需要一定时间');
                            $modalInstance.close(1);
                            //$state.go('app.ci_project.deployproject');
                            var urlData = {"deploy_id":data.data.deploy_id};
                            $state.go('app.ci_project.deploylasttask',urlData);
                            return {};
                        }else{
                            toaster.pop('error', '提示', '部署失败'+data.errmsg);
                            return {};
                        }
                    });
            };
            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            };
        };

//========================== 显示 deploy project  end  ==================================

//========================== 显示 提测  start          ==================================
        self.deployTestReport = function(row, isShowContent, isShowTestReportBtn, isShowSetResultBtn) {

            var memberTypeTester = 3; // 3为 测试人员类型ID
            ciProjectModel.fetchCiMembers().get({'project_id' : row.project_id, 'member_type' : memberTypeTester}, {})
                .$promise.then(function (data) {
                    if(data.errno == 0 || data.errno == '0'){

                        var ciTestMemberList = data.data;

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
                                        },
                                        "ci_test_member" : ciTestMemberList,
                                    }
                                }
                            }
                        });

                        modalInstance.result.then(function () {
                            console.log('ok');
                        }, function () {
                            console.log('cancel');
                        });

                    } else {
                        toaster.pop('error', '提示', '读取数据失败'+data.errmsg);
                        return {};
                    }
                });



        };

        var deployTestReportCtrl = function ($scope, $http, $modalInstance, $state, commonModel, toaster, params) {



            deployTypeList = [{"id":1, "name":"新功能"},{"id":2, "name":"Bug修复"},{"id":3, "name":"改进优化"}];
            $scope.data = params.data;
            $scope.inputStatus = params.input_status;
            $scope.ciTestMemberList = params.ci_test_member;

            //var memberTypeTester = 3; // 3为 测试人员类型ID
            //ciProjectModel.fetchCiMembers().get({'project_id' : $scope.data.project_id, 'member_type' : memberTypeTester}, {})
                //.$promise.then(function (data) {
                    //if(data.errno == 0 || data.errno == '0'){
                        //console.log(data.data);
                        //$scope.ciTestMemberList = data.data;
                        ////self.searchParams.projectId.currentVal = self.ciBuildStatusList[0].id;
                        //$scope.ciTestMemberSelect = function () {
                            //$scope.currentPage = 1;
                        //};
                    //}
                //});

            $scope.data.deployTypeList = deployTypeList;
            $scope.data.deployType = 1;
            $scope.data.deployStep = 0;
            $scope.deployTypeSelect = function () {
                console.log($scope.data.deployType);
            };

            $scope.testMemberSelect = function () {
                console.log($scope.data.test_member);
                var list = $scope.ciTestMemberList;
                for ( var item in list ) {
                    /*console.log(list[item]);*/
                    if (list[item].id.toString() == $scope.data.test_member.toString()) {
                        $scope.data.test_member_name = list[item].name;
                    }
                }
                console.log($scope.data.test_member_name);
            }

            //git 提交时，跟新信息
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
            //默认部署目录
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
                        //$scope.data.test_result = data.data.test_result;
                        $scope.data.test_result_status = data.data.test_result_status;
                        $scope.testReportData = data.data;

                        /*$scope.data.test_user = data.data.test_user;*/
                        /*$scope.data.test_user_name = data.data.test_user_name;*/
                        $scope.data.test_member = data.data.test_member;
                        $scope.data.test_member_name = data.data.test_member_name;
                        $scope.data.desc = data.data.commit_desc;
                        $scope.data.title = data.data.commit_title;
                    }
                });

            $scope.ok = function () {

                if ($scope.data.test_member == '') {
                    toaster.pop('error', '提示', '没有填写人员');
                    return;
                }

                if ($scope.data.title == '' || $scope.data.desc == '') {
                    toaster.pop('error', '提示', '没有测试内容');
                    return;
                }
                console.log($scope.data.test_member);
                /*return;*/

                var urlParams = {
                    "gaea_build_id"    : $scope.data.gaea_build_id,
                    "commit_title"     : $scope.data.title,
                    "commit_desc"      : $scope.data.desc,
                    "test_member"      : $scope.data.test_member,
                    "test_member_name" : $scope.data.test_member_name,
                }
                ciProjectModel.testReport().add(urlParams, {})
                    .$promise.then(function (data) {
                        if(data.errno == 0 || data.errno == '0'){
                            toaster.pop('success', '提示', '提测成功;已通知相关人员');
                            $modalInstance.close(1);
                            return {};
                        }else{
                            toaster.pop('error', '提示', '提测失败' + data.errmsg);
                            return {};
                        }
                    });
            };

            $scope.setTestResult = function () {
                if ($scope.data.test_result_status == '') {
                    toaster.pop('error', '提示', '没有选择测试结果');
                    return;
                }

                var urlParams = {
                    "gaea_build_id" : $scope.data.gaea_build_id,
                    "test_result_status"   : $scope.data.test_result_status,
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

        self.showStepStateLog = function (gaeaBuildId) {

            var modalInstance = $modal.open({
                templateUrl: 'ci.stepstatelog.html',
                controller: showStepStateLogCtrl,
                size: 'lg',
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                            "data" : gaeaBuildId 
                        }
                    }
                }
            });

            modalInstance.result.then(function () {
                console.log('ok');
            }, function () {
                console.log('cancel');
            });

        }

        var showStepStateLogCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {

            $scope.data = {};
            //var $scope.gaeaBuildId = params.data;
            $scope.gaeaBuildId = params.data;
            var urlParams = { 'gaea_build_id' : $scope.gaeaBuildId };
            ciProjectModel.ciStepStateLog().get(urlParams, {})
                .$promise.then(function (data) {
                    if(data.errno == 0 || data.errno == '0'){
                        $scope.data = data.data;
                        //toaster.pop('success', '提示', data.data);
                        return {};
                    }else{
                        toaster.pop('error', '错误', data.errmsg);
                        return {};
                    }
                });

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            };

        };
    }
]);


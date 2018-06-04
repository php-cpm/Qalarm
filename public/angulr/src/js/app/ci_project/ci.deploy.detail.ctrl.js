app.controller("DeployDetailCtrl", ['$state', '$scope', '$modal', 'NgTableParams', 'ciProjectModel', '$timeout','toaster','commonModel', '$stateParams',
    function ($state, $scope, $modal, NgTableParams, ciProjectModel, $timeout, toaster, commonModel, $stateParams) {

        var self = this;
        //api.deployProjectHostLogs = function(params){
        var api = ciProjectModel.deployProjectHostLogs();

        self.currentPage = 1;

        //获取url 传递的参数；deploy_id 
        self.deploy_id  = $stateParams.deploy_id;

        self.createTable = function() {
            var pageSize = 15;
            //self.tableParamsStatus = new NgTableParams({count: pageSize, page: self.currentPage}, {
            self.tableParamsHostLogs = new NgTableParams({}, {
                getData: function (params) {
                    self.currentPage  = params.page();
                    var data = getNgTableData(params);
                    return data;
                }
            });
        };

        function getNgTableData(pageParams){

            var urlParams = {'deploy_id' : self.deploy_id};
            return api.list(urlParams, {})
                .$promise.then(function (data) {
                    //后台js 返回数据判断; 0 成功,正确绑定数据; 非0 返回空
                    if(data.errno == 0 || data.errno == '0'){
                        return data.data;
                    }else{
                        return {};
                    }
                });
        }

        /* 执行页面的定时刷新*/
        $scope.onTimeout = function () {
            self.tableParamsHostLogs.reload();
            timer = $timeout($scope.onTimeout,3000);
        }
        var timer = $timeout($scope.onTimeout, 3000);
        $scope.$on('$destroy', function (event) {
            var result = $timeout.cancel(timer);
            console.log(result);
        });

        self.createTable();

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
            //console.log($scope.data);

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

        //self.deployHandle = {
            //cancel : function (deploy_id) {
                ////取消发布；回滚操作
                //console.log(deploy_id); 
                //var urlParams = { "deploy_id": deploy_id };
                //ciProjectModel.deployProjectCancel().get(urlParams, {})
                    //.$promise.then(function (data) {
                        //if(data.errno == 0 || data.errno == '0'){
                            //toaster.pop('success', '提示', '取消部署成功');
                            //self.createTable();
                            //return {};
                        //}else{
                            //toaster.pop('error', '提示', '取消部署失败');
                            //return {};
                        //}
                    //});
            //},
            //stop    : function (deploy_id) {
                ////暂停发布
                //console.log(deploy_id); 
            //},
            //rollback    : function (deploy_id) {
                ////回滚
                //toaster.pop('success', '提示', 'todo:此功能'+ deploy_id);
            //},
            //isFinished : function (status) {
                ////构建步骤的结束状态
                //if (status == 'SUCCESS' || status== 'FAILURE' || status == 'CANCEL') {
                    //return true;
                //} else {
                    //return false;
                //}
            //}
        //}
    }
]);


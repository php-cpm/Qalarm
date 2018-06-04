app.controller("CiHostCtrl", ['$state', '$scope', '$modal', '$stateParams', 'NgTableParams','toaster', 'ciProjectModel', 'OpsModel',
    function ($state, $scope, $modal, $stateParams, NgTableParams, toaster, ciProjectModel, OpsModel) {
        var self = this;

        //self.paramsData = {};
        //self.paramsData.project_id     = $stateParams.project_id;

        // 通过控件传递过来的值
        //self.project = $scope.project.ProjectRow;
        self.project = {};
        self.project = {'project_id' : $stateParams.project_id};

        self.createTable = function () {
            self.tableParams = new NgTableParams({}, {
                getData: function (params) {
                    var data = getNgTableData(params);
                    return data;
                }
            });
        };

        function getNgTableData(pageParams) {
            var pData = {
                'project_id'      : self.project.project_id
            };
            return ciProjectModel.ciProjectHosts().list({}, pData).$promise.then(function (response) {
                if (response.errno == 0) {
                    return response.data.results;
                } else {
                    return {};
                }
            });
        }

        self.createTable();

        //self.showCiHostes = function (row, size) {
        //    var modalInstance = $modal.open({
        //        templateUrl: 'ci.host.html',
        //        controller: CiHostCtrl,
        //        size: size,
        //        windowClass: 'modal-gaea',
        //        resolve: {
        //            params: function () {
        //                return {
        //                    "data": row,
        //                    "jenkins_jobs": $scope.jobs
        //                }
        //            }
        //        }
        //    });
        //
        //    modalInstance.result.then(function () {
        //    }, function () {
        //    });
        //};
        //
        //var CiHostCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
        //    $scope.ok = function () {
        //    };
        //
        //    $scope.cancel = function () {
        //        $modalInstance.dismiss('cancel');
        //    }
        //}

        self.handler = function(row, action) {
            var pData = {
                'id'        : row.id,
                'project_id': self.project.project_id,
                'opt'       : action,
                'host_name' : row.host_name
            };
            ciProjectModel.ciProjectHostUpdate().update({}, pData).$promise.then(function (response) {
                if (response.errno == 0) {
                    self.createTable();
                } else {
                    toaster.pop('error', '通知', response.errmsg);
                }
            });
        }

        self.addCiHost = function (row, size) {
            // type:1 表示在线的所有主机
            OpsModel.hostnames().get({}, {type:1}).$promise.then(function (response) {
                if (response.errno == 0) {
                    var modalInstance = $modal.open({
                        templateUrl: 'ci.host.edit.html',
                        controller: CiHostCtrl,
                        size: size,
                        windowClass: 'modal-gaea',
                        resolve: {
                            params: function () {
                                return {
                                    "data"      : row,
                                    "hostnames" : response.data
                                }
                            }
                        }
                    });
                } else {
                    toaster.pop('error', '通知', response.errmsg);
                }
            });
        };

        var CiHostCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            $scope.host = {};
            $scope.host.hostTypes    = [{'id':'1', 'name':'虚拟主机'}, {'id':'2', 'name':'docker'}];
            $scope.host.hostType     = $scope.host.hostTypes[0].id;
            $scope.host.hostEnvTypes = [{'id':'1', 'name':'测试环境'}, {'id':'2', 'name':'线上环境'}];
            $scope.host.hostEnvType  = $scope.host.hostEnvTypes[0].id;

            $scope.host.dockerQuotaTypes = [{'id':'1', 'name':'1核_2G'}, {'id':'2', 'name':'2核_4G'}, {'id':'3', 'name':'4核_8G'}];
            $scope.host.dockerQuotaType  = $scope.host.dockerQuotaTypes[0].id;


            // 集群名默认为default
            $scope.host.clusterName = 'default';

            // docker镜像名称

            ciProjectModel.ciDockerImages().list({}, {status:3}).$promise.then(function (response) {
                if (response.errno == 0) {
                    var dockerImages = new Array();
                    for (index in response.data.results) {
                        var docker = response.data.results[index];
                        dockerImages.push(docker.name)
                        $scope.host.dockerImages = dockerImages;
                        $scope.host.dockerImage  = $scope.host.dockerImages[0];
                    }
                } else {
                    toaster.pop('error', '通知', response.errmsg);
                }
            });

            // 虚拟主机列表
            var hostnames = new Array();
            for (var ip in params.hostnames) {
                hostnames.push(ip);
                hostnames.push(params.hostnames[ip]);
            }

            $scope.host.vmHosts  = hostnames;
            $scope.host.vmHost   = $scope.host.vmHosts[0];

            //$scope.$watch('host.hostEnvType', function() {
            //    if (!isEmpty($scope.host.clusterName)) {
            //        if ($scope.host.clusterName != 'test' && $scope.host.clusterName != 'production') {
            //            return;
            //        }
            //    }
            //    if ($scope.host.hostEnvType == '1') {
            //        $scope.host.clusterName  = 'test'
            //    } else {
            //        $scope.host.clusterName  = 'production'
            //    }
            //
            //}, true);

            // 去创建docker镜像
            $scope.goDockerPage = function() {
                $modalInstance.close(1)
            }

            $scope.ok = function () {

                var pData = {
                    'project_id'       : params.data.project_id,
                    'host_type'        : $scope.host.hostType,
                    'host_env_type'    : $scope.host.hostEnvType,
                    'host_cluster'     : $scope.host.clusterName,
                    'docker_quota_type': $scope.host.dockerQuotaType,
                    'docker_image'     : $scope.host.dockerImage,
                    'vm_host'          : $scope.host.vmHost,
                    'docker_replica'   : $scope.host.dockerReplica
                };

                if (isEmpty($scope.host.dockerPort)) {
                    pData.docker_port = 80;
                }
                if (isEmpty($scope.host.dockerReplica)) {
                    pData.docker_replica = 2;
                }

                ciProjectModel.ciProjectHostUpdate().add({}, pData).$promise.then(function (response) {
                    if (response.errno == 0) {
                        toaster.pop('info', '通知', '添加成功');
                        $modalInstance.close(1);
                        self.createTable();
                    } else {
                        toaster.pop('error', '通知', response.errmsg);
                    }
                });
            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };
    }
]);


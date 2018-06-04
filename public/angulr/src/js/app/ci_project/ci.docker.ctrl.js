app.controller("CiDockerCtrl", ['$state', '$scope', '$modal', 'NgTableParams','toaster', 'ciProjectModel', 'OpsModel', '$timeout', '$confirm',
    function ($state, $scope, $modal, NgTableParams, toaster, ciProjectModel, OpsModel, $timeout, $confirm) {
        var self = this;

        self.createTable = function () {
            self.tableParams = new NgTableParams({}, {
                getData: function (params) {
                    var data = getNgTableData(params);
                    return data;
                }
            });
        };

        function getNgTableData(pageParams) {
            return ciProjectModel.ciDockerImages().list({}, {}).$promise.then(function (response) {
                if (response.errno == 0) {
                    return response.data.results;
                } else {
                    return {};
                }
            });
        }

        self.createTable();
        // 执行页面的定时刷新
        $scope.onTimeout = function() {
            self.tableParams.reload();
            timer = $timeout($scope.onTimeout, 2000);
        }
        var timer =  $timeout($scope.onTimeout, 2000);
        $scope.$on('$destroy', function (event) {
            $timeout.cancel(timer);
        })

        self.handler = function(row, action) {
            var pData = {
                'id'        : row.id,
                'project_id': self.project.project_id,
                'opt'       : action
            };
            ciProjectModel.ciProjectHostUpdate().update({}, pData).$promise.then(function (response) {
                if (response.errno == 0) {
                    self.createTable();
                } else {
                    toaster.pop('error', '通知', response.errmsg);
                }
            });
        }

        self.addDocker = function (size) {
            ciProjectModel.ciDockerSegments().get({}, {}).$promise.then(function (response) {
                if (response.errno == 0) {
                    var modalInstance = $modal.open({
                        templateUrl: 'ci.docker.edit.html',
                        controller: CiDockerCtrl,
                        size: size,
                        windowClass: 'modal-gaea',
                        resolve: {
                            params: function () {
                                return {
                                    'segments': response.data
                                }
                            }
                        }
                    });
                } else {
                    toaster.pop('error', '通知', response.errmsg);
                }
            });
        };

        var CiDockerCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            $scope.docker = {};
            $scope.docker.languagesImages = params.segments.languages;
            $scope.docker.softwaresImages = params.segments.softwares;
            $scope.docker.imageSegments = {};

            $scope.ok = function () {
                if (isObjEmpty($scope.docker.imageSegments)) {
                    toaster.pop('info', '通知', '没有选择任何组件，请选择！');
                    return ;
                }

                var pData = {
                    'segments'    : JSON.stringify($scope.docker.imageSegments),
                    'name'        : $scope.docker.imageName,
                    'ports'       : $scope.docker.imagePorts
                };

                if (isEmpty($scope.docker.imagePorts)) {
                    pData.ports = 80;
                }

                ciProjectModel.ciDockerImageAssemble().add({}, pData).$promise.then(function (response) {
                    if (response.errno == 0) {
                        toaster.pop('info', '通知', '创建docker镜像需要点时间，请关注创建状态。。。');
                        $modalInstance.close(1);
                        self.createTable();
                    } else {
                        toaster.pop('error', '通知', response.errmsg);
                    }
                });
            };

            $scope.selectSegment = function(name, version) {
                $scope.docker.imageSegments[name] = version;
            }

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };

        self.showLog = function (row, size) {
            var pData = {
                'docker_name'   : row.name
            };
            ciProjectModel.ciDockerBuildLog().get({}, pData).$promise.then(function (response) {
                if (response.errno == 0) {
                    var modalInstance = $modal.open({
                        templateUrl: 'ci.dockerbuild.show.html',
                        controller: CiDockerBuildCtrl,
                        size: size,
                        windowClass: 'modal-gaea',
                        resolve: {
                            params: function () {
                                return {
                                    'buildlog' : response.data
                                }
                            }
                        }
                    });
                } else {
                    toaster.pop('error', '通知', response.errmsg);
                }
            });
        };

        self.reAssemble = function (row) {
            var pData = {
                'id'   : row.id
            };
            ciProjectModel.ciDockerImageReassemble().get({}, pData).$promise.then(function (response) {
                if (response.errno == 0) {
                    toaster.pop('info', '通知', '项目重新构建中...');
                    self.createTable();
                } else {
                    toaster.pop('error', '通知', response.errmsg);
                }
            });
        };



        var CiDockerBuildCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            $scope.build = {};
            $scope.build.buildlog = params.buildlog;

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };


        self.deleteImage = function(row) {
            $confirm({title: '确认框', ok: '确认', cancel: '取消', text: '确定删除镜像?'}).then(function () {
                var pData = {
                   'image_name'    : row.name
                };
                ciProjectModel.ciDockerImageUpdate().get({}, pData).$promise.then(function (response) {
                    if (response.errno == 0) {
                        toaster.pop('info', '通知', '成功删除');
                        self.createTable();
                    } else {
                        toaster.pop('error', '通知', response.errmsg);
                    }
                });
            });
        }

    }
]);


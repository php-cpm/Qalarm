app.controller("QalarmSubmoduleCtrl", ['$state', '$scope', '$modal', 'NgTableParams','toaster', 'QalarmModel', '$timeout', '$confirm', '$stateParams',
    function ($state, $scope, $modal, NgTableParams, toaster, QalarmModel, $timeout, $confirm, $stateParams) {
        var self = this;
        self.project_name = $stateParams.project_name;

        self.createTable = function () {
            self.tableParams = new NgTableParams({}, {
                getData: function (params) {
                    var data = getNgTableData(params);
                    return data;
                }
            });
        };

        function getNgTableData(pageParams) {
            return QalarmModel.subModule().list({project_name:$stateParams.project_name}, {}).$promise.then(function (response) {
                if (response.errno == 0) {
                    return response.data.results;
                } else {
                    return {};
                }
            });
        }

        self.createTable();

        self.editProject = function (size, action, row) {
            QalarmModel.option().get({}, {}).$promise.then(function (response) {
                if (response.errno == 0) {
                    var modalInstance = $modal.open({
                        templateUrl: 'qalarm.submodule.edit.html',
                        controller: AddProjectCtrl,
                        size: size,
                        windowClass: 'modal-gaea',
                        resolve: {
                            params: function () {
                                return {
                                    'monitors': response.data.monitors,
                                    'strategys': response.data.strategys,
                                    'action' : action,
                                    'row'    : row
                                }
                            }
                        }
                    });
                } else {
                    toaster.pop('error', '通知', response.errmsg);
                }
            });
        };

        var AddProjectCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            $scope.data = {};
            $scope.data.monitors = params.monitors;
            $scope.data.strategys = params.strategys;
            $scope.data.status = 1;

            if (params.action == 'edit') {
                $scope.data.projectName = self.project_name;
                $scope.data.projectId   = params.row.project_id;
                $scope.data.moduleName  = params.row.module;
                if (!isEmpty(params.row.monitors)) {
                    $scope.data.monitor = params.row.monitors.split(',');
                }
                $scope.data.strategy = params.row.strategy_id;
                $scope.data.status   = params.row.status;
            }

            $scope.ok = function () {
                var pData = {
                    'project_id'  : $scope.data.projectId,
                    'module'   : $scope.data.moduleName,
                    'monitor'  : $scope.data.monitor,
                    'strategy' : $scope.data.strategy,
                    'status'   : $scope.data.status,
                };

                QalarmModel.updateSubModule().save({}, pData).$promise.then(function (response) {
                    if (response.errno == 0) {
                        toaster.pop('info', '通知', '注册成功');
                        $modalInstance.close(1);
                        self.createTable();
                    } else {
                        $modalInstance.close(1);
                        toaster.pop('error', '通知', response.errmsg);
                    }
                });
            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };

        self.showSubModule = function (row) {
            $state.go('app.qalarm.project', {project_name:row.name});
        }

        self.messageHistory = function (project, row) {
            $state.go('app.qalarm.messagehistory', {project_name:project, module:row.module});
        }

        self.reback = function () {
            $state.go('app.qalarm.project');
        }


        self.alarmHistory = function (project, row) {
            var modalInstance = $modal.open({
                templateUrl: 'qalarm.alarmhistory.html',
                controller: QalarmAlarmHistoryCtrl,
                size: 'lg',
                resolve: {
                    params: function () {
                        return {
                            'project' : project,
                            'row'    : row
                        }
                    }
                }
            });
        };

        var QalarmAlarmHistoryCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            $scope.alarm = {};

            var project = params.project;
            var module  = params.row.module;

            $scope.createTable = function () {
                var pageSize = 20;
                $scope.alarm.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                    getData: function (params) {
                        var data = QalarmModel.alarmHistory().list({
                            'project': project,
                            'module' : module,
                            "page_index": params.page(),
                            "page_size": pageSize
                        }, {}).$promise.then(function (response) {
                            if (response.errno == 0) {
                                params.total(response.data.page.total);
                                params.page(response.data.page.index);
                                return response.data.results;
                            } else {
                                return {};
                            }
                        });
                        return data;
                    }
                });
            };

            $scope.createTable();

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };
    }]);


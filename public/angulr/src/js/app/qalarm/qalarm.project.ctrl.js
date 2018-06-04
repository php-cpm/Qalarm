app.controller("QalarmProjectCtrl", ['$state', '$scope', '$modal', 'NgTableParams','toaster', 'QalarmModel', '$timeout', '$confirm',
    function ($state, $scope, $modal, NgTableParams, toaster, QalarmModel, $timeout, $confirm) {
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
            return QalarmModel.projects().list({}, {}).$promise.then(function (response) {
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
                        templateUrl: 'qalarm.project.edit.html',
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
            $scope.data.testGraphStatus = 1;
            $scope.data.testAlarmStatus = 1;

            if (params.action == 'edit') {
                $scope.data.name = params.row.name;
                $scope.data.manager = params.row.manager;
                $scope.data.monitor = params.row.monitor.split(',');
                $scope.data.strategy = params.row.strategy_id;
                $scope.data.status   = params.row.status;
                $scope.data.testGraphStatus   = params.row.test_graph_status;
                $scope.data.testAlarmStatus   = params.row.test_alarm_status;
            }

            $scope.ok = function () {
                var pData = {
                    'project'     : $scope.data.name,
                    'manager'  : $scope.data.manager,
                    'monitor'  : $scope.data.monitor,
                    'strategy' : $scope.data.strategy,
                    'status'   : $scope.data.status,
                    'testGraphStatus' :$scope.data.testGraphStatus,
                    'testAlarmStatus' : $scope.data.testAlarmStatus
                };

                QalarmModel.update().save({}, pData).$promise.then(function (response) {
                    if (response.errno == 0) {
                        toaster.pop('info', '通知', '注册成功');
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

        self.showSubModule = function (row) {
            $state.go('app.qalarm.submodule', {project_name:row.name});
        }

        self.messageHistory = function (row) {
            $state.go('app.qalarm.messagehistory', {project_name:row.name});
        }


        self.alarmHistory = function (row) {
            var modalInstance = $modal.open({
                templateUrl: 'qalarm.alarmhistory.html',
                controller: QalarmAlarmHistoryCtrl,
                size: 'lg',
                resolve: {
                    params: function () {
                        return {
                            'row'    : row
                        }
                    }
                }
            });
        };

        var QalarmAlarmHistoryCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            $scope.alarm = {};

            var project = params.row.name;

            $scope.createTable = function () {
                var pageSize = 20;
                $scope.alarm.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                    getData: function (params) {
                        var data = QalarmModel.alarmHistory().list({
                            'project': project,
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


'use strict';

app.controller("CMSCtrl", ['$scope', '$modal', 'NgTableParams', 'flowModel', 'commonModel', 'toaster', '$confirm',
    function ($scope, $modal, NgTableParams, flowModel, commonModel, toaster, $confirm) {
        var self = this;
        self.isTops = [{"id": -1, "name": "全部"}, {"id": 1, "name": "是"}, {"id": 0, "name": "否"}];
        self.isTop = self.isTops[0].id;

        $scope.createTable = function () {
            var pageSize = 15;
            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = flowModel.workflows().list({
                        "page_index": params.page(),
                        "page_size": pageSize,
                        "type": "1",
                    }, {}).$promise.then(function (data) {
                            params.total(data.data.page.total);
                            params.page(data.data.page.index);
                            return data.data.results;
                        });
                    return data;
                }
            });
        };

        $scope.createTableOps = function () {
            var pageSize = 15;
            self.tableParamsOps = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = flowModel.workflows().list({
                        "page_index": params.page(),
                        "page_size": pageSize,
                        "type": "10,11,12"
                    }, {}).$promise.then(function (data) {
                            params.total(data.data.page.total);
                            params.page(data.data.page.index);
                            return data.data.results;
                        });
                    return data;
                }
            });
        };


        $scope.handler = function (workflowId, action, type, attachment) {
            if (action == 'detail') {
                var modalInstance = $modal.open({
                    templateUrl: 'service.apply.detail.html',
                    controller: applyDetailCtrl,
                    size: 'md',
                    windowClass: 'modal-gaea',
                    resolve: {
                        params: function () {
                            return {
                                'detail' : attachment
                            }
                        }
                    }
                });

                return;
            }
            var pData = {
                'id': workflowId,
                'action': action
            };

            flowModel.workflowStatusTransfer().save({}, pData).$promise.then(function (response) {
                if (response.errno == 0) {
                    toaster.pop('success', '通知', response.errmsg);
                    if (type == 1) $scope.createTable();
                    if (type == 10 || type == 11) $scope.createTableOps();
                } else {
                    toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                }
            })
        }

        var applyDetailCtrl = function ($scope, $http, $modalInstance, commonModel, toaster,  params) {
            $scope.detail = {};
            $scope.detail.content = params.detail;

            console.log(params.detail);

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };

        $scope.createTable();
        $scope.createTableOps();
    }]);

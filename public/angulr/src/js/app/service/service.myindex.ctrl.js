'use strict';

app.controller("MyServiceIndexCtrl", ['$scope', '$modal', 'NgTableParams', 'flowModel', 'commonModel', 'toaster', '$state', '$rootScope',
    function ($scope, $modal, NgTableParams, flowModel, commonModel, toaster, $state, $rootScope) {
        var self = this;

        self.doingList = {};
        self.doingListCount = 0;

        self.jobList = {};
        self.jobListCount = 0;

        $scope.createTable = function () {
            var pageSize = 15;
            var data = flowModel.workflows().list({
                "page_index": 1,
                "page_size": pageSize,
                "scope": 'one',
            }, {}).$promise.then(function (data) {
                    self.doingListCount = data.data.page.count;
                    self.doingList = data.data.results;
            });
        };

        $scope.getJobWorkflows = function () {
            var pageSize = 15;
            var data = flowModel.jobWorkflows().list({
                "page_index": 1,
                "page_size": pageSize,
            }, {}).$promise.then(function (data) {
                    self.jobListCount = data.data.page.count;
                    self.jobList = data.data.results;
                });
        };


        $scope.go = function (workflowId) {
            $state.go("app.service.apply");
        }

        $scope.handler = function (workflowId, action) {
            if (action == 'detail') {
                toaster.pop('success', '通知', '你想要多详细？?');
                return;
            }
            var pData = {
                'id': workflowId,
                'action': action
            };

            flowModel.workflowStatusTransfer().save({}, pData).$promise.then(function (response) {
                if (response.errno == 0) {
                    toaster.pop('success', '通知', response.errmsg);
                    $scope.createTable();
                } else {
                    toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                }
            })
        }

        $scope.createTable();
        $scope.getJobWorkflows();
    }]);

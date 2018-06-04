'use strict';

app.controller("OpsHostCtrl", ['$scope', '$rootScope', '$modal', '$http', '$timeout', 'NgTableParams', 'toaster', 'OpsModel', '$confirm',
    function ($scope, $rootScope, $modal, $http, $timeout, NgTableParams, toaster, OpsModel, $confirm) {
        var self = this;
        self.tabs = new Array();
        self.tabs['host'] = {'title': '主机列表', 'active': true};


        $scope.index = function (scriptType) {
            var pageSize = 100;
            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = OpsModel.hosts().list({
                        "page_index": params.page(),
                        "page_size": pageSize,
                        "keyword": self.keyword
                    }, {}).$promise.then(function (data) {
                            params.total(data.data.page.total);
                            params.page(data.data.page.index);
                            return data.data.results;
                        });
                    return data;
                }
            });
        }

        $scope.search = function() {
            $scope.index('');
        }

        // 执行动作
        $scope.doActions = function (id, action) {
            var pData = {
              'id'    : id,
              'action': action
            };

            OpsModel.updateHost().save({}, pData).$promise.then(function (response) {
                if (response.errno == 0) {
                    toaster.pop('info', '通知', '操作成功');
                    $scope.index('');
                } else {
                    toaster.pop('error', '通知', response.errmsg);
                }
                $modalInstance.close(1);
            });
        }

        $scope.index('');
    }]);

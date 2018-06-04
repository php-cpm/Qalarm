'use strict';

app.controller("ServiceAppCtrl", ['$scope', '$modal', '$http', 'NgTableParams', 'toaster', 'marketModel', 'flowModel', '$confirm',
    function ($scope, $modal, $http, NgTableParams, toaster, marketModel, flowModel, $confirm) {
        var self = this;

        $scope.index = function () {
            marketModel.apps().get({}, {}).$promise.then(function (response) {
                if (response.errno == 0) {
                    self.marketApps = response.data;
                }
            });
        }

        $scope.applyApp = function(appId) {
            $confirm({title: '确认框', ok: '确认', cancel: '取消', text: '确定操作?'}).then(function () {
                flowModel.applyApp().get({app_id:appId}, {}).$promise.then(function (response) {
                    if (response.errno == 0) {
                        toaster.pop('info', '通知', '申请已经提交，请关注邮件通知。');
                        $scope.index();
                    }
                });
            });
        }

        $scope.index();

        $scope.importUsers = function (size) {
            if ($scope.mobilesCount == 0) {
                toaster.pop('error', '通知', '请选择或手动填入用户');
                return;
            }
            var modalInstance = $modal.open({
                templateUrl: 'opertor.push.importuser.html',
                controller: pushImportUserCtrl,
                size: size,
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                            mobiles: $scope.mobiles,
                            userids: $scope.userids,
                            mobilesCount: $scope.mobilesCount,
                            refresh: $scope.noticeList
                        }
                    }
                }
            });
        };
    }]);

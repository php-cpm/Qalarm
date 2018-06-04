app.controller("WfParticipatorCtrl", ['$scope', '$modal','NgTableParams','toaster', 'adminModel', 'commonModel',
    function ($scope, $modal, NgTableParams, toaster, adminModel,commonModel) {

        var self = this;
        $scope.load = function(){
            var pageSize = 100;
            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = adminModel.wfParticipators().list({
                        "page_index": params.page(),
                        "page_size": pageSize
                    }, {}).$promise.then(function (data) {
                        params.total(data.data.page.total);
                        params.page(data.data.page.index);
                        return data.data.results;
                    });
                    return data;
                }
            });
        };

        $scope.load();

        // 解决远程获取不及时问题，并且不需要每次请求
        commonModel.usernames().then(function (response) {
            var data = response.data;
            for(var i=0; i<data.length; i++) {
                data[i].id = data[i].id + "";
                data[i].name = data[i].name + "";
            }

            self.usernames = data;
        });

        $scope.edit = function (size, row) {
            var modalInstance = $modal.open({
                templateUrl: 'admin.wfparticipator.edit.html',
                controller: WfParticipatorCtrl,
                size: size,
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                            "data"      : row,
                            "usernames" : self.usernames,
                            "refresh"   : $scope.load
                        }
                    }
                }
            });
        };

        var WfParticipatorCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            $scope.wfp = {};
            $scope.wfp.usernames = params.usernames;
            $scope.wfp.usernameSelected = params.data.participator.split(",");
            console.log(params.data.participator.split(","));

            $scope.ok = function () {

                var pData = {
                    "id"              : params.data.id,
                    "participator"    : $scope.wfp.usernameSelected.join(',')
                };

                adminModel.wfParticipatorUpdate().save(pData).$promise.then(function(response){
                    if (response.errno == 0) {
                        toaster.pop('success', '通知', '编辑成功');
                        params.refresh();
                        $modalInstance.close(1);
                    } else {
                        toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                    }
                });
            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };
    }
]);


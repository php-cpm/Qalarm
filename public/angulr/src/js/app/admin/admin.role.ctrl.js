app.controller("AdminRoleCtrl", ['$scope', '$modal', 'NgTableParams', 'toaster', 'adminModel',
    function ($scope, $modal, NgTableParams, toaster, adminModel) {
        var self = this;

        self.load = function() {
            var pageSize = 15;
            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = adminModel.role().list({
                        "page_index": params.page(),
                        "page_size": pageSize
                    }, {}).$promise.then(function (data) {
                        params.total(data.data.page.total);
                        params.page(data.data.page.index);
                        return data.data.results;
                    });
                    //console.log(data);
                    return data;
                }
            });
        };

        self.load();

        adminModel.menuPermit().get({}).$promise.then(function(response){
            //$scope.permits = response.data;
            //console.log($scope.permits);

            data = response.data;
            for(var i=0; i<data.length; i++) {
                data[i].id = data[i].id+"";
                data[i].name = data[i].sub_page_name+'-'+data[i].permit_name+"";
            }
            //$scope.citys = data;
            $scope.permits = response.data;

        });

        self.deleteData = function (id) {

            adminModel.role().delete({"id":id}).$promise.then(function(response){
                if (response.errno == 0) {
                    self.load();
                    toaster.pop('success', '通知', '编辑成功');
                    //console.log(response.data.data);
                } else {
                    toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                }
            });

        };

        self.editData = function (row) {

            //console.log(row);
            var modalInstance = $modal.open({
                templateUrl: 'admin.role.edit.html',
                controller: EditCtrl2,
                size: 'lg',
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                            "data" : row,
                            "permits": $scope.permits
                        }
                    }
                }
            });

            modalInstance.result.then(function () {
                console.log('ok');
                self.load();
            }, function () {
                console.log('cancel');
            });
        };

        var EditCtrl2 = function ($scope, $http, $modalInstance, commonModel, toaster, params) {

            $scope.data = params.data;
            $scope.permits = params.permits;

            $scope.data.permitsSelected = params.data.role_permits.split(",");

            $scope.ok = function () {

                var pData = {
                    "id" : $scope.data.id,
                    "mid"            : $scope.data.mid,
                    "sid"            : $scope.data.sid,
                    "role_name"            : $scope.data.role_name,
                    "role_permits"            : $scope.data.permitsSelected
                    //"role_permits"            : $scope.data.role_permits
                };

                adminModel.role().update(pData).$promise.then(function(response){
                    if (response.errno == 0) {
                        toaster.pop('success', '通知', '编辑成功');
                        $modalInstance.close(1);
                        //console.log(response.data.data);
                    } else {
                        toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                    }
                });
            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };

        self.addData = function (size) {

            var modalInstance = $modal.open({
                templateUrl: 'admin.role.edit.html',
                controller: EditCtrl,
                size: size,
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                            //"loadData": self.load
                            "permits": $scope.permits
                        }
                    }
                }
            });

            modalInstance.result.then(function () {
                console.log('ok');
                self.load();
            }, function () {
                console.log('cancel');
            });
        };


        var EditCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {

            $scope.permits = params.permits;

            $scope.data = {
                "mid":'',
                "sid":'',
                "role_name":'',
                "permitsSelected":''
            };
            $scope.ok = function () {

                var pData = {
                    "mid"            : $scope.data.mid,
                    "sid"            : $scope.data.sid,
                    "role_name"            : $scope.data.role_name,
                    "role_permits"            : $scope.data.permitsSelected
                };

                console.log(localStorage.getItem("username"));

                adminModel.role().add(pData).$promise.then(function(response){
                    if (response.errno == 0) {
                        //self.load();
                        toaster.pop('success', '通知', '编辑成功');
                        $modalInstance.close(1);
                        //console.log(response.data.data);
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


app.controller("AdminAuthCtrl", ['$scope', '$modal','NgTableParams','toaster', 'adminModel',
    function ($scope, $modal, NgTableParams, toaster, adminModel) {

        var self = this;
        self.load = function(){
            var pageSize = 15;
            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = adminModel.auth().list({
                        "page_index": params.page(),
                        "page_size": pageSize
                    }, {}).$promise.then(function (data) {
                        params.total(data.data.page.total);
                        params.page(data.data.page.index);
                        return data.data.results;
                    });
                    console.log(data);
                    return data;
                }
            });
        };

        self.load();

        self.deleteData = function (id) {

            adminModel.auth().delete({"id":id}).$promise.then(function(response){
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

            console.log(row);
            var modalInstance = $modal.open({
                templateUrl: 'admin.auth.edit.html',
                controller: AuthEditCtrl2,
                size: 'lg',
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                            "data" : row
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

        var AuthEditCtrl2 = function ($scope, $http, $modalInstance, commonModel, toaster, params) {

            console.log(params);
            console.log(params.data);

            $scope.data = params.data;


            $scope.ok = function () {

                var pData = {
                    "id" : $scope.data.id,
                    "mid"            : $scope.data.mid,
                    "sid"            : $scope.data.sid,
                    "auth_name"            : $scope.data.auth_name,
                    "auth_url"            : $scope.data.auth_url,
                    "icon_class"            : $scope.data.icon_class
                };


                adminModel.auth().update(pData).$promise.then(function(response){
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

        self.authEdit = function (size) {

            var modalInstance = $modal.open({
                templateUrl: 'admin.auth.edit.html',
                controller: AuthEditCtrl,
                size: size,
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                            //"loadData": self.load
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


        var AuthEditCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {

            $scope.data = {
                "mid":'',
                "sid":'',
                "auth_name":'',
                "auth_url":'',
                "icon_class":''
            };
            $scope.ok = function () {

                var pData = {
                    "mid"            : $scope.data.mid,
                    "sid"            : $scope.data.sid,
                    "auth_name"            : $scope.data.auth_name,
                    "auth_url"            : $scope.data.auth_url,
                    "icon_class"            : $scope.data.icon_class
                };

                console.log(pData);

                adminModel.auth().add(pData).$promise.then(function(response){
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


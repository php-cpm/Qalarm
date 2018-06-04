app.controller("AdminUserCtrl", ['$scope', '$modal', 'NgTableParams', 'toaster', 'adminModel', 'commonModel', function ($scope, $modal, NgTableParams, toaster, adminModel, commonModel) {
    var self = this;
    var pageSize = 300;

    self.reloadData = function() {
        self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
            getData: function (params) {
                var data = adminModel.user().list({
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

    self.reloadData();

    commonModel.citys().then(function (response) {
        data = response.data;
        for(var i=0; i<data.length; i++) {
            data[i].id = data[i].id+"";
        }
        $scope.citys = data;
    });

    //暂时用列表
    adminModel.role().get({}).$promise.then(function (response) {
        console.log(response.data.results);

        data = response.data;
        for(var i=0; i<data.length; i++) {
            data[i].id = data[i].id+"";
        }
        //$scope.citys = data;
        $scope.roles = response.data;
        //data = {};
        //for(var i=0; i<response.data.results.length; i++) {
        //    data[i].id =  response.data.results[i].id;
        //    data[i].name =  response.data.results[i].role_name;
        //}
        //
        //console.log(data);
        //console.log($scope.roles);
    });


    self.deleteData = function (id) {

        adminModel.user().delete({"id":id}).$promise.then(function(response){
            if (response.errno == 0) {
                self.reloadData();
                toaster.pop('success', '通知', '编辑成功');
                //console.log(response.data.data);
            } else {
                toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
            }
        });
    };

    self.editData = function (row) {

        var modalInstance = $modal.open({
            templateUrl: 'admin.user.edit.html',
            controller: EditCtrl2,
            size: 'lg',
            windowClass: 'modal-gaea',
            resolve: {
                params: function () {
                    return {
                        "data" : row,
                        "citys" : $scope.citys,
                        "roles":$scope.roles
                }
                }
            }
        });

        modalInstance.result.then(function () {
            console.log('ok');
            self.reloadData();
        }, function () {
            console.log('cancel');
        });
    };

    var EditCtrl2 = function ($scope, $http, $modalInstance, commonModel, toaster, params) {

        $scope.roles = params.roles;
        $scope.citys = params.citys;
        $scope.data = params.data;

        $scope.data.roleSelected = params.data.role_ids.split(",");
        $scope.data.cityidSelected = params.data.cityids.split(",");

        $scope.data.cityid = params.data.cityid+"";
        $scope.ok = function () {

            var pData = {
                "id" : $scope.data.id,
                "username"            : $scope.data.username,
                "nickname"            : $scope.data.nickname,
                "mobile"             :$scope.data.mobile,
                "cityid"            : $scope.data.cityid,
                "cityids"            : $scope.data.cityidSelected,
                "roles"            : $scope.data.roleSelected
            };

            adminModel.user().update(pData).$promise.then(function(response){
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
            templateUrl: 'admin.user.edit.html',
            controller: EditCtrl,
            size: size,
            windowClass: 'modal-gaea',
            resolve: {
                params: function () {
                    return {
                        "citys" : $scope.citys,
                        "roles":$scope.roles
                    }
                }
            }
        });

        modalInstance.result.then(function () {
            console.log('ok');
            self.reloadData();
        }, function () {
            console.log('cancel');
        });
    };


    var EditCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {

        $scope.data = {
            "username"            : '',
            "nickname"            : '',
            "mobile"                :'',
            "cityid"            : '',
            "cityidSelected"            : '',
            "roleSelected"            : ''
        };

        $scope.roles = params.roles;
        $scope.citys = params.citys;

        $scope.ok = function () {

            var pData = {
                "id" : $scope.data.id,
                "username"            : $scope.data.username,
                "nickname"            : $scope.data.nickname,
                "mobile"             :$scope.data.mobile,
                "cityid"            : $scope.data.cityid,
                "cityids"            : $scope.data.cityidSelected,
                "roles"             :$scope.data.roleSelected
            };

            console.log(pData);

            adminModel.user().add(pData).$promise.then(function(response){
                if (response.errno == 0) {
                    toaster.pop('success', '通知', '编辑成功');
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

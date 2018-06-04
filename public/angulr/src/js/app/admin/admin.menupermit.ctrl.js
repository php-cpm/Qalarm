app.controller("AdminMenuPermitCtrl", ['$scope', '$modal', 'NgTableParams','toaster', 'adminModel', function ($scope, $modal, NgTableParams,toaster, adminModel) {
    var self = this;

    self.load = function (){
        var pageSize = 15;
        self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
            getData: function (params) {
                var data = adminModel.menuPermit().list({
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

        adminModel.menuPermit().delete({"id":id}).$promise.then(function(response){
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
            templateUrl: 'admin.menupermit.edit.html',
            controller: EditCtrl2,
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

    var EditCtrl2 = function ($scope, $http, $modalInstance, commonModel, toaster, params) {

        $scope.data = params.data;
        $scope.ok = function () {


            var pData = {
                "id" : $scope.data.id,
                "menu_id"            : $scope.data.menu_id,
                "sub_page_code"            : $scope.data.sub_page_code,
                "sub_page_name"            : $scope.data.sub_page_name,
                "permit_code"            : $scope.data.permit_code,
                "permit_name"            : $scope.data.permit_name,
                "public_permit"            : $scope.data.public_permit
            };

            adminModel.menuPermit().update(pData).$promise.then(function(response){
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
            templateUrl: 'admin.menupermit.edit.html',
            controller: EditCtrl,
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


    var EditCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {

        $scope.data = {
            "menu_id"            : '',
            "sub_page_code"            : '',
            "sub_page_name"            : '',
            "permit_code"            : '',
            "permit_name"            : '',
            "public_permit"            : 0
        };

        $scope.ok = function () {

            var pData = {
                "id" : $scope.data.id,
                "menu_id"            : $scope.data.menu_id,
                "sub_page_code"            : $scope.data.sub_page_code,
                "sub_page_name"            : $scope.data.sub_page_name,
                "permit_code"            : $scope.data.permit_code,
                "permit_name"            : $scope.data.permit_name,
                "public_permit"            : $scope.data.public_permit
            };

            console.log(pData);

            adminModel.menuPermit().add(pData).$promise.then(function(response){
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


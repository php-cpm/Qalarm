app.controller("UserCtrl", ['$state', '$scope', '$modal', 'NgTableParams', 'userModel','commonModel_user','$sce',
    function ($state, $scope, $modal, NgTableParams, userModel, commonModel_user,$sce) {
        var self = this;
        $scope.createTable = function() {
            var pageSize = 15;
            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = userModel.list({
                        "page_index": params.page(),
                        "page_size": pageSize,
                        "mobile": $scope.userMobile,
                        "name":$scope.userName,
                        "cartype":typeof($scope.carType) == 'undefined' ? 0 : $scope.carType,
                        "usertype":typeof($scope.userType) == 'undefined' ? 0 : $scope.userType
                    }, {}).$promise.then(function (data) {
                            params.total(100);
                            params.page(10);
                            //console.log(data.data.basic);
                            return data.data;
                        });
                    console.log(data);
                    return data;
                }
            });
        };

        commonModel_user.userTypes().then(function(response){
            $scope.userTypes = response.data;
            $scope.userType = $scope.userTypes[0].id;
            $scope.userTypeSelect = function () {
                console.log($scope.userType);
            };
        });

        commonModel_user.carTypes().then(function(response){
            $scope.carTypes = response.data;
            $scope.carType = $scope.carTypes[0].id;
            $scope.carTypeSelect = function () {
                console.log($scope.carType);
            };
        });

        $scope.open = function (size) {
            console.log('this is open ');
        };

        $scope.userName = '';
        $scope.userMobile = '';

        $scope.search = function() {
            console.log('this is search ');
            console.log($scope.carType); 
            console.log($scope.userType);
            $scope.createTable();
        };

        $scope.createTable();

        $scope.formatRowData = {
            userName: function (href_url) {
                //var href='#app/user/detail?userid='+href_url;
                //var html = "<a class='text-info' href='" + href_url + "'>" + href_name + "</a>";
                //return $sce.trustAsHtml(html);
            },
            headImg: function(img_src){
                //var html = "<img class='text-info' href='" + img_src + "'>" + img_src + "</a>";
                //return $sce.trustAsHtml(html);
            }

        };

    }]);

app.factory('userModel', ['$resource', function ($resource) {
    return $resource('/api/v1/users', {},
        {
            'query': {method: 'GET', isArray: false},
            'get': {method: 'POST', params: {'action': 'get'}},
            'list': {method: 'POST', params: {'action': 'list'}},
        }
    );
}]);

app.factory('commonModel_user', ['$resource', function ($resource) {
    var  utils = {};
    utils.userTypes = function(params) {
        var userTypes = $resource('/api/v1/util/usertypes');
        return userTypes.get({},{}).$promise;
    };

    utils.carTypes = function(params) {
        var carTypes = $resource('/api/v1/util/cartypes');
        return carTypes.get({},{}).$promise;
    };

    return utils;
}]);


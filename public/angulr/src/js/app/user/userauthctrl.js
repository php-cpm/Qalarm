/**
 * Created by weichen on 15/10/14.
 */
app.controller("UserAuthCtrl", ['$state', '$scope', '$modal', 'NgTableParams', 'userAuthModel','authUserTypesModel','$sce',
    function ($state, $scope, $modal, NgTableParams, userAuthModel,authUserTypesModel,$sce) {
        var self = this;
        $scope.createTable = function() {
            var pageSize = 15;
            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = userAuthModel.list({
                        "page_index": params.page(),
                        "page_size": pageSize,
                        "mobile": $scope.userMobile,
                        "name":$scope.userName,
                        "authState":typeof($scope.authState) == 'undefined' ? 0 : $scope.authState,
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

        authUserTypesModel.userTypes().then(function(response){
            $scope.userTypes = response.data;
            $scope.userType = $scope.userTypes[0].id;
            $scope.userTypeSelect = function () {
                console.log($scope.userType);
            };
        });

        authUserTypesModel.authStates().then(function(response){
            $scope.authStates = response.data;
            $scope.authState = $scope.authStates[0].id;
            $scope.authStateSelect = function () {
                console.log($scope.authState);
            };
        });

        $scope.userName = '';
        $scope.userMobile = '';

        $scope.search = function() {
            console.log('this is search ');
            console.log($scope.authState);
            console.log($scope.userType);
            $scope.createTable();
        };

        $scope.createTable();

        $scope.formatRowData = {
            userName: function (sex) {
                showUserName = sex == '男' ? '先生' : sex == '女' ? '女士' : '未设置';
                return showUserName;
            },
            authUserStates: function(authstate){
                css_class = authstate == 0 ? 'bg-warning' : authstate == 1 ? 'bg-primary' : authstate == 2 ? 'bg-success' : authstate == 3 ? 'bg-danger' : 'bg-danger';
                return 'label ' + css_class;
            }
        };

    }]);

app.factory('userAuthModel', ['$resource', function ($resource) {
    return $resource('/api/v1/user/auth', {},
        {
            //'query': {method: 'GET', isArray: false},
            //'get': {method: 'POST', params: {'action': 'get'}},
            'list': {method: 'POST', params: {'action': 'list'}},
        }
    );
}]);

app.factory('authUserTypesModel', ['$resource', function ($resource) {
    var  utils = {};
    utils.userTypes = function(params) {
        var userTypes = $resource('/api/v1/util/usertypes');
        return userTypes.get({},{}).$promise;
    };

    utils.authStates = function(params) {
        var authStates = $resource('/api/v1/util/user/auth/states');
        return authStates.get({},{}).$promise;
    };

    return utils;
}]);


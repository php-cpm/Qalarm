/* Controllers */
// signin controller
app.controller('SigninFormController', ['$scope', '$rootScope', '$http', '$state', 'adminModel', '$cookies', '$cookieStore',
    function ($scope, $rootScope, $http, $state, adminModel, $cookies, $cookieStore) {
        $scope.user = {};
        $scope.authError = null;
        $scope.login = function () {
            $scope.authError = null;
            // Try to login
            //adminUser.get({username: $scope.user.username, password: hex_sha1($scope.user.password)}, {}).$promise
            //    .then(function (response) {
            //        if (response.errno != 0) {
            //            $scope.authError = '邮箱或密码不正确';
            //        } else {
            //            $rootScope.user = response.data.user;
            //            // record to cookies
            //            $cookieStore.put('user', $rootScope.user);
            //            $scope.app.user = $rootScope.user;
            //            $state.go('app.myjob');
            //        }
            //    })
            //    .catch(function (exception) {
            //        $scope.authError = exception.errmsg;
            //    });
        };
    }]);


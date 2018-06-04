app.controller("UserDetailCtrl", ['$scope', '$modal', 'NgTableParams', 'userModelDetail','$stateParams',
    function ($scope, $modal, NgTableParams, userModelDetail ,$stateParams) {
        var self = this;

        $scope.user_id = $stateParams.user_id;

        $scope.getUserDetail = function() {
            userModelDetail.query({user_id:$scope.user_id},{"user_id":$scope.user_id}).$promise.then(function(data){
                //console.log(data.data);
                $scope.data = data.data;

            });
            //console.log($scope.data);
        };

        $scope.getUserDetail();
    }]);

app.factory('userModelDetail', ['$resource', function ($resource) {
    return $resource('/api/v1/user/detail', {},
        {
            'query': {method: 'GET', params: {'action': 'query'}},
            'get': {method: 'POST', params: {'action': 'get'}},
            'list': {method: 'POST', params: {'action': 'list'}},
        }
    );
}]);


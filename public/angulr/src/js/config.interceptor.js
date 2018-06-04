'use strict';

/**
 * Config for the router
 */
angular.module('app')
    .run(
    ['$rootScope', '$state', '$stateParams', '$cookieStore', '$window', 'adminModel',
        function ($rootScope, $state, $stateParams, $cookieStore, $window, adminModel) {
            $rootScope.$state = $state;
            $rootScope.$stateParams = $stateParams;
            $rootScope.$state.isLogin = false;

            $rootScope.$on('$stateChangeStart', function (event, toState, toParams, fromState, fromParams) {
                // Try to login
                //adminModel.index().get({}, {}).$promise
                //    .then(function (response) {
                //        if (response.errno != 0) {
                //        } else {
                //            $state.go('app.myjob');
                //        }
                //    })
            });
        }
    ]
)
// 把$http的header Content-Type替换成x-www-form-urlencode
    .config(
    ['$httpProvider',
        function ($httpProvider) {
            //$httpProvider.defaults.headers.put['Content-Type'] = 'application/x-www-form-urlencoded';
            //$httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
            $httpProvider.defaults.headers.post['Content-Type'] = 'application/json; charset=utf-8';
            $httpProvider.defaults.headers.common['Content-Type'] = 'application/json; charset=utf-8';

            //// Override $http service's default transformRequest
            //$httpProvider.defaults.transformRequest = [function (data) {
            //    /**
            //     * The workhorse; converts an object to x-www-form-urlencoded serialization.
            //     * @param {Object} obj
            //     * @return {String}
            //     */
            //    var param = function (obj) {
            //        var query = '';
            //        var name, value, fullSubName, subName, subValue, innerObj, i;
            //
            //        for (name in obj) {
            //            value = obj[name];
            //
            //            if (value instanceof Array) {
            //                for (i = 0; i < value.length; ++i) {
            //                    subValue = value[i];
            //                    fullSubName = name + '[' + i + ']';
            //                    innerObj = {};
            //                    innerObj[fullSubName] = subValue;
            //                    query += param(innerObj) + '&';
            //                }
            //            } else if (value instanceof Object) {
            //                for (subName in value) {
            //                    subValue = value[subName];
            //                    fullSubName = name + '[' + subName + ']';
            //                    innerObj = {};
            //                    innerObj[fullSubName] = subValue;
            //                    query += param(innerObj) + '&';
            //                }
            //            } else if (value !== undefined && value !== null) {
            //                query += encodeURIComponent(name) + '='
            //                    + encodeURIComponent(value) + '&';
            //            }
            //        }
            //
            //        return query.length ? query.substr(0, query.length - 1) : query;
            //    };
            //
            //    return angular.isObject(data) && String(data) !== '[object File]'
            //        ? param(data)
            //        : data;
            //}];
        }])
    .config(
    ['$httpProvider',
        function ($httpProvider) {
            $httpProvider.interceptors.push('myInterceptor');
        }
    ]
);


'use strict';

/* Controllers */

angular.module('app')
    .controller('AppCtrl', ['$scope', '$translate', '$localStorage', '$window', '$rootScope', '$cookieStore', 'commonModel', 'adminModel', '$state', 'marketModel', '$cookies', 'toaster',
        function ($scope, $translate, $localStorage, $window, $rootScope, $cookieStore, commonModel, adminModel, $state, marketModel, $cookies, toaster) {
            // add 'ie' classes to html
            var isIE = !!navigator.userAgent.match(/MSIE/i);
            isIE && angular.element($window.document.body).addClass('ie');
            isSmartDevice($window) && angular.element($window.document.body).addClass('smart');

            // config
            $scope.app = {
                name: '飞凡| Qalarm',
                version: '1.0.0',
                company: '飞凡信息科技有限公司',
                // for chart colors
                color: {
                    primary: '#7266ba',
                    info: '#23b7e5',
                    success: '#27c24c',
                    warning: '#fad733',
                    danger: '#f05050',
                    light: '#e8eff0',
                    dark: '#3a3f51',
                    black: '#1c2b36'
                },
                settings: {
                    themeID: 1,
                    navbarHeaderColor: 'bg-black',
                    navbarCollapseColor: 'bg-white-only',
                    asideColor: 'bg-black',
                    headerFixed: true,
                    asideFixed: false,
                    asideFolded: false,
                    asideDock: false,
                    container: false
                },
                // refresh
                user: {}
            }

            //头像写入rootScope中;有默认值;在ui-nav中重置用户头像
            //在用户修改头像之后重置用户头像
            //$rootScope.user = {};//注释：无法获取权限验证Bug

            //$scope.$watch(function() {
            //    return $rootScope.user_head_img;
            //}, function() {
            //    $scope.user_head_img = $rootScope.user_head_img;
            //}, true);
            $scope.$watch($rootScope.user_head_img, function() {
                $scope.user_head_img = $rootScope.user_head_img;
            }, true);

            // save settings to local storage
            if (angular.isDefined($localStorage.settings)) {
                $scope.app.settings = $localStorage.settings;
            } else {
                $localStorage.settings = $scope.app.settings;
            }
            $scope.$watch('app.settings', function () {
                if ($scope.app.settings.asideDock && $scope.app.settings.asideFixed) {
                    // aside dock and fixed must set the header fixed.
                    $scope.app.settings.headerFixed = true;
                }
                // save to local storage
                $localStorage.settings = $scope.app.settings;
            }, true);

            $rootScope.$watch('user', function() {
                $scope.app.user = $rootScope.user;
            }, true);

            $rootScope.$watch('user.head_img', function() {
                $scope.app.user = $rootScope.user;
            }, true);

            // angular translate
            $scope.lang = {isopen: false};
            $scope.langs = {en: 'English', cn: '简体中文'};
            $scope.selectLang = $scope.langs[$translate.proposedLanguage()] || "简体中文";
            $scope.setLang = function (langKey, $event) {
                // set the current lang
                $scope.selectLang = $scope.langs[langKey];
                // You can change the language during runtime
                $translate.use(langKey);
                $scope.lang.isopen = !$scope.lang.isopen;
            };

            // 退出登录
            $scope.logout = function () {
                adminModel.logout().get({}, {}).$promise.then(function (response) {
                    // $cookieStore.put('user', {});
                    $rootScope.user = {};
                    $window.location.href = response.ref;
                });
            };

            $scope.clickMe = function() {
                toaster.pop('info', '通知', '就知道你要戳我！！！想要就等等吧...');
            }

            // 实时消息服务地址
            //commonModel.realtimeServiceAddr().then(function (response) {
            //    if (response.errno == 0) {
            //        $cookieStore.put('realtime', response.data);
            //    }
            //});

            // 我的应用
            // marketModel.myapp().get({}, {}).$promise.then(function (response) {
            //     if (response.errno == 0) {
            //         $scope.myapps = response.data;
            //         // $scope.app.user = $cookieStore.get('user');
            //     }
            // });

            function isSmartDevice($window) {
                // Adapted from http://www.detectmobilebrowsers.com
                var ua = $window['navigator']['userAgent'] || $window['navigator']['vendor'] || $window['opera'];
                // Checks for iOs, Android, Blackberry, Opera Mini, and Windows mobile devices
                return (/iPhone|iPod|iPad|Silk|Android|BlackBerry|Opera Mini|IEMobile/).test(ua);
            }

        }]);

app.factory('myInterceptor', ['$q', '$cookieStore', 'toaster', '$window', '$rootScope', function ($q, $cookieStore, toaster, $window, $rootScope) {
    return {
        request: function (config) {
            config.headers = config.headers || {};
            var user = $rootScope.user || {};
            config.headers['TICKET'] = user.ticket;
            return config;
        },
        response: function (response) {
            // 没有授权返回1101
            if (response.data.errno === 1101) {
                $rootScope.user = {};
                toaster.pop('warning', '通知', '您没有权限访问此页');
            }

            //if (response.status === 302) { // server not responding
            //    $window.location.href = response.data.ref;
            //    return;
            //}

            return response || $q.when(response);
        },
        responseError: function (rejection) {
            //// 302
            //if (rejection.status === 0) { // server not responding
            //    $window.location.href = rejection.data.ref;
            //    return;
            //}
            if (rejection.status >= 500) {
                toaster.pop('error', '通知', '服务器错误,错误码：' + rejection.data.errno + ', 错误信息: ' + rejection.data.errmsg);
            }

             return $q.reject(rejection);
        }
    };
}]);

//gaea 权限模块;用户登陆之后,页面哪权限;用此获取页面权限
app.factory('Permits', ['$cookieStore','$rootScope', function($cookieStore,$rootScope) {

    return {
        pagePermits : function (pageRoute){
            //console.log($cookieStore.get('user'));
            var user = $rootScope.user || null;
            if (user == null) return;

            var permits = eval('(' + user.permits + ')');

            if (permits == undefined) return;
            var page_route = pageRoute;

            var pagePermits =  {}; //页面权限

            //console.log(permits);
            for(var i = 0; i < permits.length; i++){
                var item = permits[i];
                //当前页面的权限
                if(item.route == page_route){
                    //pagePermits = permits[i].data;
                    //当前页面Tab权限
                    for(var j = 0; j< item.data.length; j++){
                        var subItem = item.data[j];
                        pagePermits[subItem.subpage] = subItem.permit;
                    }
                    break;
                }
            }
            return pagePermits;
        }
    }

}]);

'use strict';

app.controller("ServiceFlowCtrl", ['$scope', '$modal', '$http', 'NgTableParams', 'toaster', 'flowModel',
    function ($scope, $modal, $http, NgTableParams, toaster, flowModel) {
        var self = this;

        $scope.index = function () {
            //marketModel.apps().get({}, {}).$promise.then(function (response) {
            //    if (response.errno == 0) {
            //        self.marketApps = response.data;
            //    }
            //});
        }


        // VPN 流程
        $scope.applyVPN = function(size) {
            var modalInstance = $modal.open({
                templateUrl: 'service.flows.common.vpn.html',
                controller: flowsVPNCtrl,
                size: size,
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                            mobiles: $scope.mobiles,
                            userids: $scope.userids,
                            mobilesCount: $scope.mobilesCount,
                            refresh: $scope.noticeList
                        }
                    }
                }
            });
        }

        var flowsVPNCtrl = function ($scope, $http, $modalInstance, commonModel, toaster,  params) {
            $scope.vpn = {};
            $scope.vpn.btnDisabled = false;

            $scope.ok = function () {
                var pData = {
                    'remark' : $scope.vpn.remark
                };

                $scope.vpn.btnDisabled = true;
                flowModel.vpn().save({}, pData).$promise.then(function (response) {
                    if (response.errno == 0) {
                        $modalInstance.close(1);
                        toaster.pop('info', '通知', '申请已经提交，请关注通知。');
                    } else {
                        toaster.pop('error', '通知', response.errmsg);
                    }
                    $scope.vpn.btnDisabled = false;
                });
            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };


        // 一键回收权限 流程
        $scope.recoverPermission = function(size) {
            var modalInstance = $modal.open({
                templateUrl: 'service.flows.common.recoverPerm.html',
                controller: flowsRecoverPermCtrl,
                size: size,
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                        }
                    }
                }
            });
        }

        var flowsRecoverPermCtrl = function ($scope, $http, $modalInstance, commonModel, toaster,  params) {
            $scope.recoverPerm = {};
            $scope.recoverPerm.btnDisabled = false;

            $scope.ok = function () {
                var pData = {
                    'recover_username' : $scope.recoverPerm.recoverUsername
                };

                $scope.recoverPerm.btnDisabled = true;
                flowModel.recoverPermission().save({}, pData).$promise.then(function (response) {
                    if (response.errno == 0) {
                        toaster.pop('info', '通知', '申请已经提交，请关注通知。');
                    } else {
                        toaster.pop('error', '通知', response.errmsg);
                    }
                    $modalInstance.close(1);
                });
            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };

        // 服务器权限流程
        $scope.applyServerPerm = function(size) {
            var modalInstance = $modal.open({
                templateUrl: 'service.flows.ops.serverperm.html',
                controller: flowsServerPermCtrl,
                size: size,
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                        }
                    }
                }
            });
        }

        var flowsServerPermCtrl = function ($scope, $http, $modalInstance, commonModel, toaster,  params) {
            $scope.serverPerm = {};
            $scope.serverPerm.groups = [{"id":"normal", "name":"普通账号"},{"id":"ttyc", "name":"ttyc"},{"id":"root", "name":"root"}];
            $scope.serverPerm.group = "normal";
            $scope.serverPerm.btnDisabled = false;

            $scope.ok = function () {
                var pData = {
                    'group'  : $scope.serverPerm.group,
                    'ips'    : $scope.serverPerm.ips,
                    'remark' : $scope.serverPerm.remark
                };

                $scope.serverPerm.btnDisabled = true;
                flowModel.serverPerm().save({}, pData).$promise.then(function (response) {
                    if (response.errno == 0) {
                        $modalInstance.close(1);
                        toaster.pop('info', '通知', '申请已经提交，请关注通知。');
                    } else {
                        toaster.pop('error', '通知', response.errmsg);
                    }
                    $scope.serverPerm.btnDisabled = false;
                });

            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };

        // 服务器修改密码流程
        $scope.applyServerChangePassword = function(size) {
            var modalInstance = $modal.open({
                templateUrl: 'service.flows.ops.serverchangepassword.html',
                controller: flowsServerChangePasswordCtrl,
                size: size,
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                        }
                    }
                }
            });
        }

        var flowsServerChangePasswordCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, $timeout, $cookieStore, params) {
            $scope.passwd = {};
            $scope.passwd.randPassword = randomWord(false, 18);
			$scope.passwd.count = '获取';
            $scope.passwd.btnDisabled = false;

			var timer = function() {
				$scope.passwd.count --;

				if ($scope.passwd.count > 0) {
					$timeout(timer, 1000);
				} else {
					$timeout.cancel(timer);
					$scope.passwd.count = '获取';
					$scope.passwd.showCaptcha = false;
				}
			};

			var cookieKey = 'captcha_expire_time';
			var timeout   = 60;
			var expire = $cookieStore.get(cookieKey) || 0;
			var myDate = new Date();
			var now = myDate.getTime();
			var diff = Math.round((expire - now) / 1000);

			// can get
			if (diff <= 0) {
				$scope.passwd.showCaptcha = false;
				$scope.passwd.count = '获取';
			} else {
                $scope.passwd.showCaptcha = true;
				$scope.passwd.count = diff;
				$timeout(timer, 1000);
			}


            $scope.getCaptcha = function() {
                $scope.passwd.showCaptcha = true;
				
				var myDate = new Date();
				var now = myDate.getTime();
				$cookieStore.put(cookieKey, now + timeout*1000);
				$scope.passwd.count = timeout;
                $timeout(timer, 1000);

                commonModel.captcha().then(function(response) {
                    if (response.errno == 0) {

                    }
                })
            }

            $scope.ok = function () {
                if ($scope.passwd.password1 != $scope.passwd.password2) {
                    toaster.pop('warning', '通知', '两次输入的密码不一致');
                    return;
                }

                if ($scope.passwd.password1.length < 12) {
                    toaster.pop('warning', '通知', '密码长度必须大于等于12位');
                    return;
                }

                if (!checkpassWord($scope.passwd.password1)) {
                    toaster.pop('warning', '通知', '包括大小写字母、数字、特殊字符');
                    return;
                }

                var slice = $scope.passwd.password1.substr(0, 3);
                slice += '|';
                slice += $scope.passwd.password1.substr(-3, 3);

                var pData = {
                    'captcha'     : $scope.passwd.captcha,
                    'new_shadow'  : hex_sha1($scope.passwd.password1),
                    'new_slice'   : slice,
                    'passwd'      : binb2b64(str2binb($scope.passwd.password1))
                };

                $scope.passwd.btnDisabled = true;
                flowModel.changePasswd().save({}, pData).$promise.then(function (response) {
                    if (response.errno == 0) {
                        toaster.pop('info', '通知', '线上密码已修改，请稍候使用');
                    } else {
                        toaster.pop('error', '通知', response.errmsg);
                    }
                    $modalInstance.close(1);
                });
            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };


        $scope.applyApp = function(appId) {
            flowModel.applyApp().get({app_id:appId}, {}).$promise.then(function (response) {
                if (response.errno == 0) {
                    $scope.myapps = response.data;
                    toaster.pop('info', '通知', '申请已经提交，请关注通知。');
                }
            });

            $scope.marketApps = {};
            $scope.index();
        }

        $scope.index();
    }]);

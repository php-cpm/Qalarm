'use strict';

app.controller("PushCtrl", ['$scope', '$modal', '$http', 'NgTableParams', 'userModel', 'toaster', 'commonModel', 'NoticeModel', '$timeout', 'FileUploader',
    function ($scope, $modal, $http, NgTableParams, userModel, toaster, commonModel, NoticeModel, $timeout, FileUploader) {
        var self = this;

        $scope.identitys = [{"id": 0, "name": "不限"}, {"id": 1, "name": "车主"}, {"id": 2, "name": "乘客"}];
        $scope.sexs = [{"id": 0, "name": "不限"}, {"id": 1, "name": "男"}, {"id": 2, "name": "女"}];
        $scope.authStatus = [{"id": 0, "name": "不限"}, {"id": 2, "name": "通过"}, {"id": 3, "name": "不通过"}];
        $scope.useStatus = [{"id": 0, "name": "不限"}, {"id": 1, "name": "是"}, {"id": 2, "name": "否"}];

        $scope.identity = $scope.identitys[0].id;
        $scope.sex = $scope.sexs[0].id;
        $scope.headAuth = $scope.authStatus[0].id;
        $scope.carAuth = $scope.authStatus[0].id;
        $scope.isUseApp = $scope.useStatus[0].id;   //是否打开过APP
        $scope.isTakeOrder = $scope.useStatus[0].id;   //是否接单
        $scope.isCallCar = $scope.useStatus[0].id;   //是否叫车
        $scope.isBeenTake = $scope.useStatus[0].id;   //是否被接

        $scope.validTimeRange = {
            startDate: moment(),
            endDate : moment().add(1,'days')
        };


        $scope.validTimeOptions = {
            format: "YYYY-MM-DD",
        };

        // FIXME
        //$scope.mobiles = '13658364971';
        $scope.mobiles = '';

        commonModel.citys().then(function (response) {
            $scope.citys = response.data;
            $scope.city = $scope.citys[0].id;
        });

        commonModel.carBrands().then(function (response) {
            $scope.cars = response.data;
            $scope.car = $scope.cars[0].id;
        });

        $scope.openPushDo = function (size) {
            if ($scope.mobilesCount == 0) {
                toaster.pop('error', '通知', '请选择或手动填入用户');
                return;
            }
            var modalInstance = $modal.open({
                templateUrl: 'opertor.push.go.html',
                controller: pushGoCtrl,
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
        };

        $scope.getPushResult = function (msgid, size) {
            if ($scope.mobilesCount == 0) {
                var modalInstance = $modal.open({
                    templateUrl: 'opertor.push.result.html',
                    controller: pushGoCtrl,
                    size: size,
                    windowClass: 'modal-gaea',
                    resolve: {
                        params: function () {
                            return {}
                        }
                    }
                });
            };
        }

        $scope.importUsers = function (size) {
            if ($scope.mobilesCount == 0) {
                toaster.pop('error', '通知', '请选择或手动填入用户');
                return;
            }
            var modalInstance = $modal.open({
                templateUrl: 'opertor.push.importuser.html',
                controller: pushImportUserCtrl,
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
        };

        var pushGoCtrl = function ($scope, $http, $modalInstance, commonModel, toaster,  params) {
            $scope.dopush = {};
            $scope.dopush.pushTypes = [{"id": 1, "name": "普通通知"}, {"id": 2, "name": "APP内跳转通知"}, {"id": 3, "name": "活动通知"}];
            $scope.dopush.pushType = $scope.dopush.pushTypes[0].id;
            $scope.dopush.smsChannels = [{"id": 1, "name": "生产渠道"}, {"id": 2, "name": "营销渠道"}];
            $scope.dopush.smsChannel = $scope.dopush.smsChannels[1].id;  //默认显示营销渠道
            $scope.dopush.noticeWay = '1';
            $scope.dopush.noticeTime = '';
            $scope.dopush.pushCount = params.mobilesCount;

            commonModel.pushJumpTypes().then(function (response) {
                $scope.dopush.pushJumpTypes = response.data;
                $scope.dopush.pushJumpType = $scope.dopush.pushJumpTypes[0].id;
            });

            $scope.ok = function () {
                // 文案长度判断
                //var length = $scope.dopush.pushContent.length;
                //if ($scope.dopush.noticeWay == 2 && length > 64) {
                //    toaster.pop('warning', '通知', '文案字数:' + length + ', 请把文案压缩到64个字以内, 如果不能压缩请联系管理员,谢谢。');
                //    return;
                //}

                var pData = {
                    'push_type': $scope.dopush.pushType,
                    'jump_url': $scope.dopush.pushJumpType,
                    'active_id': $scope.dopush.active,
                    'users': params.mobiles,
                    'userids': params.userids,
                    'user_count': params.mobilesCount,
                    'push_way': $scope.dopush.noticeWay,
                    'push_content': $scope.dopush.pushContent,
                    'push_remark': $scope.dopush.pushRemark,
                    'sms_channel': $scope.dopush.smsChannel
                };

                // 组合帖子链接
                if (pData.push_type == 2 && pData.jump_url == 'ttyongche:///sns/news/detail?id=') {
                    if (typeof($scope.dopush.cardId) == 'undefined') {
                        toaster.pop('error', '通知', '请输入帖子id');
                        return;
                    }
                    pData.jump_url = $scope.dopush.pushJumpType + $scope.dopush.cardId;
                }

                var timestamp = Date.parse($scope.dopush.noticeTime.replace(/\-/g, "/"));
                if (isNaN(timestamp)) {
                    timestamp = new Date().getTime();
                }
                pData.push_time = timestamp / 1000;    //转换成秒

                // post 数据
                $http({method: 'POST', url: '/api/v1/notices/add', data: pData}).success(function (response) {
                    if (response.errno == 0) {
                        toaster.pop('success', '通知', '推送成功');
                        $modalInstance.close(1);
                        params.refresh();
                    } else {
                        toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                    }
                });

            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };

        var pushImportUserCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, FileUploader, params) {
            $scope.import = {};
            var uploader = $scope.import.uploader = new FileUploader({
                url: '/api/admin/auth'
            });
            $scope.ok = function () {

            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };

        // 通知列表函数
        $scope.noticeList = function () {
            var pageSize = 15;

            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = NoticeModel.list({
                        "page_index": params.page(),
                        "page_size": pageSize,
                    }, {}).$promise.then(function (data) {
                            params.total(data.data.page.total);
                            params.page(data.data.page.index);
                            return data.data.results;
                        });
                    return data;
                }
            });

            //var myTimer = $timeout(function () {
            //}, 2000);
            //myTimer.then(function () {
            //    $scope.noticeList();
            //});
            //
            //$scope.stop = function () {
            //    console.log('xxxxxxxx');
            //    $timeout.cancel(myTimer);
            //}
        };


        $scope.searchUsers = function () {
            $scope.searchBtnDisabled = true;
            // 没有选择是为全部，替换成数组
            var citys = $scope.city == 0 ? [0] : $scope.city;
            var cars = $scope.car == 0 ? [0] : $scope.car;

            var pData = {
                'cityids': citys.join(','),
                'identity': $scope.identity,
                'sex': $scope.sex,
                'headAuth': $scope.headAuth,
                'carAuth': $scope.carAuth,
                'carmodelids': cars.join(','),
            };
            userModel.get(pData, {}).$promise.then(function (response) {
                if (response.errno == 0) {
                    var mobiles = response.data.mobiles;
                    var count = response.data.count;
                    var arr = mobiles.match(/1[34578]\d{9}(?=\n|$)/g);
                    if (arr != null) {
                        if (arr.length != count) {
                            var validArr = minus(mobiles.split('\n'), arr);
                            $scope.invalidMobiles = validArr.join('\n');
                            $scope.mobiles = arr.join('\n');
                            $scope.userids = response.data.userids;
                        } else {
                            $scope.invalidMobiles = '';
                            $scope.mobiles = mobiles;
                        }
                    } else {     //无合法的手机号
                        $scope.invalidMobiles = '';
                        $scope.mobiles = '';
                        $scope.userids = '';
                    }

                    if (count == 0) {
                        toaster.pop('info', '通知', '没有符合条件的用户');
                    }
                }
                $scope.searchBtnDisabled = false;
                console.log($scope.car);
            });
            var minus = function (arr1, arr2) {
                var arr3 = new Array();
                for (var i = 0; i < arr1.length; i++) {
                    var flag = true;
                    for (var j = 0; j < arr2.length; j++) {
                        if (arr1[i] == arr2[j])
                            flag = false;
                    }
                    if (flag)
                        arr3.push(arr1[i]);
                }
                return arr3;
            }

            console.log($scope.city);
        };

        $scope.$watch('mobiles', function (newValue, oldValue) {
            if (newValue != undefined && newValue != '') {
                var mobiles = newValue.split('\n');
                var count = mobiles.length;
                var arr = newValue.match(/1[34578]\d{9}(?=\n|$)/g);
                // 对测试账号一次只能支持一个发送
                if (arr == null) {
                    var arr = newValue.match(/999\d{8}(?=\n|$)/g);
                    if (arr != null) {
                        $scope.mobilesCount = arr.length;
                    }
                    return;
                }
                if (arr.length != count) {
                    toaster.pop('error', '通知', '电话号码错误');
                }
                $scope.mobilesCount = count;
            } else {
                $scope.mobilesCount = 0;
            }
        });

        $scope.noticeList();

        //// 导入用户
        //$scope.importUsers = function () {
        //
        //    socket.emit('message',{'user': 'chenfei', 'text': 'hahah', 'from':'ni'}, function(data) {
        //        console.log(data);
        //    });
        //
        //};
        //socket.on('realtime.opertor.notice.add', function (message) {
        //    $scope.noticeList();
        //    console.log(message);
        //});
        //
        //$scope.$on('$destroy', function (event) {
        //    console.log('exit lllll');
        //
        //    socket.removeAllListeners();
        //});
    }]);

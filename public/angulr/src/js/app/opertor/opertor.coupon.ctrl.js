'use strict';

app.controller("CouponCtrl", ['$scope', '$modal', 'NgTableParams', 'couponModel', 'manualCouponModel', 'commonModel',
    function ($scope, $modal, NgTableParams, couponModel, manualCouponModel, commonModel) {
        var self = this;
        $scope.createTable = function () {
            var pageSize = 15;
            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = couponModel.list({
                        "page_index": params.page(),
                        "page_size": pageSize,
                        "city_id": typeof($scope.city) == 'undefined' ? 0 : $scope.city,
                        "coupon_type": typeof($scope.couponType) == 'undefined' ? 0 : $scope.couponType,
                        "buss_type": typeof($scope.bussType) == 'undefined' ? 0 : $scope.bussType
                    }, {}).$promise.then(function (data) {
                            params.total(data.data.page.total);
                            params.page(data.data.page.index);
                            return data.data.results;
                        });
                    return data;
                }
            });
        };
        $scope.createManualCouponTable = function () {
            var pageSize = 15;
            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = manualCouponModel.list({
                        "page_index": params.page(),
                        "page_size": pageSize,
                        "city_id": typeof($scope.manualCity) == 'undefined' ? 0 : $scope.manualCity,
                        "send_reason_id": typeof($scope.manualCouponType) == 'undefined' ? 0 : $scope.manualCouponType,
                        "buss_type": typeof($scope.manualBussType) == 'undefined' ? 0 : $scope.manualBussType,
                        "mobile": typeof($scope.mobile) == 'undefined' ? '' : $scope.mobile
                    }, {}).$promise.then(function (data) {
                            params.total(data.data.page.total);
                            params.page(data.data.page.index);
                            return data.data.results;
                        });
                    return data;
                }
            });
        };

        commonModel.couponTypes().then(function (response) {
            $scope.couponTypes = response.data;
            $scope.couponType = $scope.couponTypes[0].id;
            $scope.couponTypeSelect = function () {
                console.log($scope.couponType);
            };
        });

        commonModel.manualCouponTypes().then(function (response) {
            $scope.manualCouponTypes = response.data;
            $scope.manualCouponType = $scope.manualCouponTypes[0].id;
        });

        commonModel.bussTypes().then(function (response) {
            $scope.bussTypes = response.data;
            $scope.bussType = $scope.bussTypes[0].id;
            $scope.manualBussType = $scope.bussTypes[0].id;
        });

        commonModel.citys().then(function (response) {
            $scope.citys = response.data;
            $scope.city = $scope.citys[0].id;
            $scope.manualCity = $scope.citys[0].id;
        });

        $scope.openCoupon = function (size) {
            var modalInstance = $modal.open({
                templateUrl: 'opertor.coupon.edit.html',
                controller: ModalInstanceCtrl,
                size: size,
                resolve: {
                    items: function () {
                        return $scope.items;
                    }
                }
            });

            modalInstance.result.then(function () {
                console.log('ok');

            }, function () {
                console.log('cancel');
            });
        };
        var ModalInstanceCtrl = function ($scope, $modalInstance) {
            $scope.ok = function () {
                $modalInstance.close(1);
            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            };
        };

        $scope.openManualCoupon = function (size) {
            var modalInstance = $modal.open({
                templateUrl: 'opertor.coupon.manual.edit.html',
                controller: couponManualCtrl,
                size: size,
                windowClass: 'modal-gaea'
                //resolve: {
                //    params: function () { return {
                //        bussTypes : $scope.bussTypes,
                //        coupopTypes : $scope.couponTypes
                //    }}
                //}
            });
        };

        var couponManualCtrl =  function ($scope, $http, $modalInstance, commonModel, toaster) {
            $scope.manual = {};

            $scope.manual.validTimeRange = {
                startDate: moment(),
                endDate : moment().add(1,'days')
            };


            $scope.manual.validTimeOptions = {
                format: "YYYY-MM-DD",
            };

            commonModel.bussTypes({"type": "add"}).then(function (response) {
                $scope.manual.bussTypes = response.data;
                $scope.manual.bussType = $scope.manual.bussTypes[0].id;
            });

            commonModel.manualCouponTypes({"type": "add"}).then(function (response) {
                $scope.manual.couponTypes = response.data;
                $scope.manual.couponType = $scope.manual.couponTypes[0].id;
            });

            $scope.manual.noticeSms = '1';

            $scope.ok = function () {
                // 验证mobiles
                var mobiles = $scope.manual.mobile.split('\n');
                var count = mobiles.length;
                var arr = $scope.manual.mobile.match(/1[34578]\d{9}(?=\n|$)/g);
                // 检查是否为测试账号
                if (arr == null) {
                    arr = $scope.manual.mobile.match(/999\d{8}(?=\n|$)/g);
                }
                if (arr == null || arr.length != count) {
                    toaster.pop('error', '通知', '电话号码错误,每行一个号码');
                    return;
                }

                if ($scope.manual.money < 1) {
                    toaster.pop('error', '通知', '优惠金额大于等于1元');
                    return;
                }

                var pData = {
                    'money': $scope.manual.money,
                    'start_date': $scope.manual.validTimeRange.startDate.format("YYYY-MM-DD"),
                    'coupon_type':$scope.manual.couponType,
                    'end_date': $scope.manual.validTimeRange.endDate.format("YYYY-MM-DD"),
                    'buss_type': $scope.manual.bussType,
                    'reason_id': $scope.manual.couponType,
                    'reason': $scope.manual.reason,
                    'notice_sms': $scope.manual.noticeSms,
                    'mobile': $scope.manual.mobile
                };

                $http({method: 'GET', url: '/api/v1/coupons/gaeadispense', params: pData}).success(function (response) {
                    if (response.errno == 0) {
                        toaster.pop('success', '通知', '发放成功');
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


        $scope.searchCoupon = function () {
            $scope.createTable();
        };

        $scope.searchManualCoupon = function () {
            $scope.createManualCouponTable();
        };

        $scope.createTable();
    }]);

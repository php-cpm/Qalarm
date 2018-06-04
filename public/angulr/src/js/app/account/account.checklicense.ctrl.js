/**
 * Created by weichen on 15/10/14.
 */
app.controller("AccountCheckLicenseCtrl", ['$state', '$scope', '$modal','$http', 'NgTableParams', 'toaster', 'accountModel','commonModel', 'Permits',
    function ($state, $scope, $modal, $http, NgTableParams, toaster, accountModel,commonModel, Permits) {
        var self = this;
        self.searchBtnDisabled = true;
        var api = accountModel.accountLicenseCheckList();
        self.createTable = function() {
            self.searchBtnDisabled = true;
            var pageSize = 15;
            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = getNgTableData(params);
                    console.log(data);
                    return data;
                }
            });
        };

        pageRoute = 'app.account.check';
        self.permits = Permits.pagePermits(pageRoute);
        console.log(self.permits);
        console.log("aa vv");


        function getNgTableData(pageParams){

            return api.list(setNgTableParams(pageParams,self.searchParams), {})
                .$promise.then(function (data) {
                    self.searchBtnDisabled = false;
                    //后台js 返回数据判断; 0 成功,正确绑定数据; 非0 返回空
                    if(data.errno == 0 || data.errno == '0'){
                        pageParams.total(data.data.page.total);
                        pageParams.page(data.data.page.index);
                        return data.data.result;
                    }else{
                        return {};
                    }

                });
        }

        function setNgTableParams(pageParams,fileterParams){

            var result = {};
            for(var key in fileterParams){
                if(fileterParams[key].currentVal != fileterParams[key].ignoreVal){
                    result[fileterParams[key].urlKeyName] = fileterParams[key].currentVal;
                }
            }
            result.page_index = pageParams.page();
            result.page_size = 15;
            return result;

        }

        //初始化查询条件://默认查找为审核数据 其他无特殊处理
        function initSearchParams(){
            return {
                filters: {
                    "accountName":  { "currentVal": "", "ignoreVal": "", "urlKeyName": "name"},
                    "accountMobile":{ "currentVal": "", "ignoreVal": "", "urlKeyName": "mobile"},
                    "accountType":  { "currentVal":  0, "ignoreVal":  0, "urlKeyName": "user_type"},
                    "accountIdCard":{ "currentVal": "", "ignoreVal": "", "urlKeyName": "idcard"},
                    "sex":          { "currentVal": -1, "ignoreVal": -1, "urlKeyName": "sex"},
                    "carBrand":     { "currentVal": -1, "ignoreVal": -1, "urlKeyName": "car_brand_id"},

                    "accountAuthState": { "currentVal": -1, "ignoreVal": -1, "urlKeyName": "account_auth_state"},
                    "accountCheckState":{ "currentVal": -1, "ignoreVal": -1, "urlKeyName": "account_check_state"},
                    "licenseCheckState":{ "currentVal": -1, "ignoreVal": -1, "urlKeyName": "car_license_state"},
                    "carImgCheckState": { "currentVal": -1, "ignoreVal": -1, "urlKeyName": "car_image_state"},
                    "carImgLicenseCheckState":{ "currentVal": 1, "ignoreVal": -1, "urlKeyName": "car_license_image_state"},

                    //"headImgCheckState":{ "currentVal":"", "defaultVal": -1, "ignoreVal": -1, "urlKeyName": "headimg_check_state"},
                    //"pageIndex":{ "name":"accountMobile",   "currentVal":"", "defaultVal": 1, "urlKeyName": "page_index"},
                    //"pageSize":{ "name":"accountMobile",   "currentVal":"", "defaultVal": 15, "urlKeyName": "page_size"}
                }
            };
        }

        self.searchParams = initSearchParams().filters;

        accountModel.accountTypes().then(function(response){
            self.accountTypes = response.data;
            //self.searchParams.accountType.currentVal = self.accountTypes[0].id;
            self.accountTypeSelect = function () {
                console.log(self.searchParams.accountType.currentVal);
            };
        });

        accountModel.accountAuthStates().then(function(response){
            self.accountAuthStates = response.data;
            //self.searchParams.accountAuthState.currentVal = self.accountAuthStates[0].id;
            self.accountAuthStateSelect = function () {
                console.log(self.searchParams.accountAuthState.currentVal);
            };
        });

        accountModel.accountCheckStates().then(function(response){
            self.accountCheckStates = response.data;
            //self.searchParams.accountCheckState.currentVal = self.accountCheckStates[0].id;
            self.accountCheckStateSelect = function () {
                console.log(self.searchParams.accountCheckState.currentVal);
            };
        });

        accountModel.licenseCheckStates().then(function(response){
            self.licenseCheckStates = response.data;
            //self.searchParams.licenseCheckState.currentVal = self.licenseCheckStates[0].id;
            self.licenseCheckStateSelect = function () {
                console.log(self.searchParams.licenseCheckState.currentVal);
            };
        });

        accountModel.carImgCheckStates().then(function(response){
            self.carImgCheckStates = response.data;
            //self.searchParams.carImgCheckState.currentVal = self.carImgCheckStates[0].id;
            self.carImgCheckStateSelect = function () {
                console.log(self.searchParams.carImgCheckState.currentVal);
            };
        });

        accountModel.accountSex().then(function(response){
            self.accountSex = response.data;
            //self.searchParams.sex.currentVal = self.accountSex[0].id;
            self.sexSelect = function () {
                console.log(self.searchParams.sex.currentVal);
            };
        });

        accountModel.carBrands().then(function(response) {
            self.carBrands = response.data;
        });


        //默认查找为审核数据
        accountModel.carImgCheckStates().then(function(response){
            self.carImgLicenseCheckStates = response.data;
            //self.searchParams.carImgLicenseCheckState.currentVal = 1;
            self.carImgLicenseCheckStateSelect = function () {
                console.log(self.searchParams.carImgLicenseCheckState.currentVal);
            };
        });


        self.search = function() {
            self.createTable();
        };

        self.createTable();

        self.formatRowData = {
            accountName: function (sex) {
                showAccountName = sex == '男' ? '先生' : sex == '女' ? '女士' : '未设置';
                return showAccountName;
            },
            carImgState: function(authState){
                cssClass = authState == 0 ? 'bg-warning' : authState == 1 ? 'bg-primary' : authState == 2 ? 'bg-success' : authState == 3 ? 'bg-danger' : 'bg-danger';
                return 'badge badge-sm up pull-right-xs ' + cssClass;
            },

            licenseState: function(authState){
                cssClass = authState == 0 ? 'bg-warning' : authState == 1 ? 'bg-primary' : authState == 2 ? 'bg-success' : authState == 3 ? 'bg-danger' : 'bg-danger';
                return 'badge badge-sm up pull-right-xs ' + cssClass;
            },

            accountHeadImgState: function(authState){
                cssClass = authState == 0 ? 'bg-warning' : authState == 1 ? 'bg-primary' : authState == 2 ? 'bg-success' : authState == 3 ? 'bg-danger' : 'bg-danger';
                //return 'label ' + cssClass;
                return 'badge badge-sm up pull-right-xs ' + cssClass;
            }
        };

        //======================= start
        //弹出对话框
        self.openImgBox = function (accountId,imgUrl,size,typeId) {
            //console.log(imgUrl);
            var modalInstance = $modal.open({
                templateUrl : 'account.showimg.html',  //指向上面创建的视图
                controller : ModalInstanceCtrl,// 初始化模态范围
                size : size, //大小配置
                resolve : {
                    data : function(){
                        return {"imgUrl":imgUrl, "accountId": accountId, "typeId":typeId};
                    }
                }
            });
            modalInstance.result.then(function(){
                //$scope.selected = selectedItem;
                console.log('ok');
                self.createTable();
            },function(){
                //$log.info('Modal dismissed at: ' + new Date())
                console.log('cancel');
            })

        };

        var ModalInstanceCtrl = function ($scope, $modal, $http, $modalInstance, data) {

            $scope.imageUrl = data.imgUrl;
            $scope.accountId = data.accountId;
            $scope.typeId = data.typeId;

            //关闭
            $scope.close = function () {
                $modalInstance.dismiss('close');
            };

            $scope.imgeAction = {
                rotate:function (domId,rotate) {
                    imgRotate(domId,rotate);
                },
                reset :function (domId) {
                    reset(domId);
                },
                save : function (domId,typeId) {

                    //查找图片对象的 URL
                    imgurl = $('#' + domId).attr('src');

                    var apiSave;
                    var pData = {};

                    if( typeId == 1){
                        //头像旋转
                        pData = {"user_id": $scope.accountId, "headimg": imgurl };
                        apiSave = accountModel.accountBasicUpdate();
                    }else if(typeId == 2){
                        //车照旋转
                        pData = {"user_id": $scope.accountId, "carimg": imgurl};
                        apiSave =  accountModel.accountCarUpdate();
                    }else if(typeId == 3){
                        //驾驶本证件旋转
                        pData = {"user_id": $scope.accountId, "licenseimg": imgurl};
                        apiSave = accountModel.accountDriverUpdate();
                    }else if(typeId == 4){
                        //car  行驶本旋转
                        pData = {"user_id": $scope.accountId, "license": imgurl};
                        apiSave = accountModel.accountCarUpdate();
                    }

                    apiSave.save(pData).$promise.then(function(response){
                        if (response.errno == 0) {
                            toaster.pop('success', '通知', '编辑成功');
                            $modalInstance.close(1); //回调函数
                        } else {
                            toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                        }
                    });

                }
            };
        };

        //======================= end


        //self.accountCheck = function (accountId,isCheckOk) {
        //
        //    accountStates = { "ok" : 2, "refuse" : 3 };
        //
        //    stateCode = accountStates.refuse;
        //    if(isCheckOk == true){
        //        stateCode = accountStates.ok;
        //    }
        //
        //    pData = {
        //        "user_id"            : accountId,
        //        "headimg_state" : stateCode
        //    };
        //
        //    $http({method: 'POST', url: '/api/v1/account/headimgsateupdate', params: pData}).success(function (response) {
        //        if (response.errno == 0) {
        //            toaster.pop('success', '通知', '操作成功');
        //            $scope.createTable();
        //        } else {
        //            toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
        //        }
        //    });
        //};


        self.isnodata = false;
        //== 开始审核页面获取数据
        self.loadCheckAccountInfo = function(){

            //查找未审核用户包含:证件审核未通过,或是车照审核未通过
            var notCheckAccounts = accountModel.startCheckLicense().get({car_license_image_state: 1}, {}).$promise.then(function (data) {
                //后台js 返回数据判断; 0 成功,正确绑定数据; 非0 返回空
                //console.log(data.data.result);
                if(data.errno == 0 || data.errno == '0'){
                    //console.log(data.data);
                    if(data.data.length == 0){
                        //没有可以审核的数据
                        //$("#quickcheck").html('<div>没有审核数据!或是其他审核人正在审核中</div>');
                        self.isnodata = true;
                    }else{
                        self.accountData = data.data.result[0];
                        self.isnodata = false;
                    }
                }else{
                    self.isnodata = true;
                    return {};
                }
            });
        };

        //开始审核按钮
        self.showListTable = true;
        self.toggleStartCheckLicense = function(){
            self.showListTable = !self.showListTable;

            //如果列表要显示,需要从新加载一下最新数据
            if(self.showListTable){
                self.createTable();
            }else{
                //todo:需要加载数据;加载一条未审核;未跳过的数据
                self.loadCheckAccountInfo();
            }
        };

        //self.isNextData = {
        //    'isCheckLicense'    :false,
        //    'isCheckCarImg'     :false
        //};

        //self.checkLicense = function (accountId, isCheckOk) {
        //
        //    console.log(accountId);
        //
        //    accountStates = {"ok": 2, "refuse": 3};
        //
        //    stateCode = accountStates.refuse;
        //    if (isCheckOk == true) {
        //        stateCode = accountStates.ok;
        //    }
        //
        //    pData = {
        //        "user_id": accountId,
        //        "license_state": stateCode,
        //        "license_reason": self.licenseReason
        //    };

        //    //$http({
        //    //    method: 'POST',
        //    //    url: '/api/v1/account/driverupdate',
        //    //    params: pData
        //    //}).success(function (response) {
        //    accountModel.accountDriverUpdate().save(pData).$promise.then(function(response){
        //        if (response.errno == 0) {
        //            //self.createTable();
        //            self.isNextData.isCheckLicense = true;
        //
        //            //从置 数据状态
        //            self.accountData.car.license_state = stateCode;
        //
        //            if( self.isNextData.isCheckLicense &&  self.isNextData.isCheckCarImg){
        //                self.loadCheckAccountInfo ();
        //                console.log(self.isNextData);
        //
        //                self.isNextData = {
        //                    'isCheckLicense' : false,
        //                    'isCheckCarImg' : false
        //                }
        //            }
        //            toaster.pop('success', '通知', '操作成功');
        //        } else {
        //            toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
        //        }
        //    });
        //
        //    accountModel.accountCarUpdate().save(pData).$promise.then(function(response){
        //        if (response.errno == 0) {
        //            //self.createTable();
        //            console.log(self.accountData);
        //            self.isNextData.isCheckLicense = true;
        //            self.accountData.driver.license_state = stateCode;
        //
        //            if(self.accountData.driver.license_state != 1 && self.accountData.car.license_state != 1 && self.accountData.car.image_state){
        //                //if( self.isNextData.isCheckLicense &&  self.isNextData.isCheckCarImg){
        //                self.loadCheckAccountInfo ();
        //                console.log(self.isNextData);
        //
        //                self.isNextData = {
        //                    'isCheckLicense' : false,
        //                    'isCheckCarImg' : false
        //                }
        //            }
        //            toaster.pop('success', '通知', '操作成功');
        //        } else {
        //            toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
        //        }
        //    });
        //};
        //
        //self.checkCarImg = function (accountId, isCheckOk) {
        //
        //    console.log(accountId);
        //    accountStates = {"ok": 2, "refuse": 3};
        //
        //    stateCode = accountStates.refuse;
        //    if (isCheckOk == true) {
        //        stateCode = accountStates.ok;
        //    }
        //
        //    pData = {
        //        "user_id": accountId,
        //        "image_state": stateCode,
        //        "image_reason": self.carImageReason
        //    };
        //
        //    accountModel.accountCarUpdate().save(pData).$promise.then(function(response){
        //        if (response.errno == 0) {
        //
        //            console.log(self.accountData);
        //
        //            self.isNextData.isCheckCarImg = true;
        //
        //            self.accountData.car.image_state = stateCode;
        //            //if( self.isNextData.isCheckLicense &&  self.isNextData.isCheckCarImg){
        //            if(self.accountData.driver.license_state != 1 && self.accountData.car.license_state != 1 && self.accountData.car.image_state){
        //
        //                self.loadCheckAccountInfo ();
        //                console.log(self.isNextData);
        //                self.isNextData = {
        //                    'isCheckLicense' : false,
        //                    'isCheckCarImg' : false
        //                };
        //            }
        //            toaster.pop('success', '通知', '操作成功');
        //
        //        } else {
        //            toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
        //        }
        //    });
        //
        //};

        //self.ignoreCheck = function (accountId, isCheckOk) {
        //    self.loadCheckAccountInfo ();
        //};

        //self.carImageReason = '';
        self.licenseReason = '';

        self.setReasonText = function ($event,reasonType,textReason) {

            checkbox = $event.target;
            if(reasonType == 1 && checkbox.checked){
                self.licenseReason += textReason;
            }

            //if(reasonType == 2 && checkbox.checked){
            //    self.carImageReason += textReason;
            //}
        };

        self.quickReturns1 = [{"val":"驾驶证与车主身份不符;"},{"val":"行驶证与车辆信息不符;"},{"val":"驾驶证照片不合规范;"},{"val":"行驶证照片不合规范;"}];
        self.quickReturns2 = [{"val":"车照模糊;"},{"val":"需上传车辆正面照含车牌号;"},{"val":"xx收拾不符合规范;"},{"val":"xx收拾不符合规范;"}];


        //跳过
        self.checkSkip = function(){

            console.log(self.accountData);

            if(self.accountData != 'null'){
                var pData = {
                    'account_id':self.accountData.basic.id,
                    'account_name':self.accountData.basic.name,
                    "account_mobile": self.accountData.basic.mobile,
                    "check_state": 8,
                    "user_id": 123123 , //todo:登陆gaea用户ID
                    "user_name": 'gaea' //todo:登陆gaea用户名称
                };

                console.log(pData);
                accountModel.accountCheckSkip().save(pData).$promise.then(function(response){
                    //accountModel.checkAcc().save(pData).$promise.then(function(response){
                    if (response.errno == 0) {
                        self.loadCheckAccountInfo ();
                        toaster.pop('success', '通知', '编辑成功');
                    } else {
                        toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                    }
                });
            }
        };

        //单独一个按钮审核数据了
        self.checkImgData = function(accountId,mobile,isCheckOk){
            console.log(accountId);

            accountStates = {"ok": 2, "refuse": 3};
            stateCode = accountStates.refuse;
            if (isCheckOk == true) {
                stateCode = accountStates.ok;
            }

            updateDataStates = {
                "carimg":         {"serverstate": -1, "errmsg": '', "errcode": -1},
                "carlicense":     {"serverstate": -1, "errmsg": '', "errcode": -1},
                "driverlicense":  {"serverstate": -1, "errmsg": '', "errcode": -1}
            };

            //汽车照片参数;理由统一
            pDataCarImg = {
                "user_id": accountId,
                "image_state": stateCode,
                "image_reason": self.licenseReason
            };
            var isOk = true;
            accountModel.accountCarUpdate().save(pDataCarImg).$promise.then(function(response){
                if (response.errno == 0) {
                    updateDataStates.carimg = {"serverstate": 0, "errmsg": "车照操作成功", "errcode": 0};
                } else {
                    if(isOk == true){isOk = false}
                    var errmsg = response.errmsg;
                    updateDataStates.carimg = {"serverstate": 0, "errmsg": errmsg, "errcode": response.errno};
                }

                return updateDataStates.carimg;
            });

            //汽车行驶本
            pDataCarLicense = {
                "user_id": accountId,
                "image_state": stateCode,
                "image_reason": self.licenseReason
            };

            accountModel.accountCarUpdate().save(pDataCarLicense).$promise.then(function(response){
                if (response.errno == 0) {
                    updateDataStates.carlicense = {"serverstate": 0, "errmsg": "行驶本操作成功", "errcode": 0};
                } else {
                    if(isOk == true){isOk = false}
                    var errmsg = response.errmsg;
                    updateDataStates.carlicense = {"serverstate": 0, "errmsg": errmsg, "errcode": response.errno};
                }
            });

            //汽车驾驶本
            pData_driver = {
                "user_id": accountId,
                "license_state": stateCode,
                "license_reason": self.licenseReason
            };
            accountModel.accountDriverUpdate().save(pData_driver).$promise.then(function(response){
                if (response.errno == 0) {
                    updateDataStates.driverlicense = {"serverstate": 0, "errmsg": "驾驶本操作成功", "errcode": 0};
                } else {
                    if(isOk == true){isOk = false}
                    var errmsg = response.errmsg;
                    updateDataStates.driverlicense = {"serverstate": 0, "errmsg": errmsg, "errcode": response.errno};
                }
            });

            //console.log(updateDataStates);
            //console.log(updateDataStates.carimg.errcode);
            //console.log(updateDataStates.carlicense.errcode);
            //console.log(updateDataStates.driverlicense.errcode);
            //console.log('kkkkkkkkkk');

            if(isOk){
                //todo:删除本地数据
                pDataLocal = {
                    "account_id": accountId,
                    "account_mobile" :mobile,
                    "account_name":'',
                    "check_state" : stateCode

                };
                accountModel.checkAcc().save(pDataLocal).$promise.then(function(response){
                    console.log('delete local data ');
                    console.log(response);
                    if (response.errno == 0) {
                        //toaster.pop('success', '通知', '操作成功1111');
                    } else {
                        //toaster.pop('error', '通知', '服务端错误,错误信息222：');
                    }
                });

                toaster.pop('success', '通知', '操作成功');
            }else{
                errmsg = updateDataStates.carimg.errmsg + updateDataStates.carlicense.errmsg + updateDataStates.driverlicense.errmsg;
                toaster.pop('error', '通知', '服务端错误,错误信息：' + errmsg);
            }

            //if(updateDataStates.carimg.errcode == 0 && updateDataStates.carlicense.errcode == 0 && updateDataStates.driverlicense.errcode == 0){
            //    toaster.pop('success', '通知', '操作成功');
            //}else{
            //    errmsg = updateDataStates.carimg.errmsg + updateDataStates.carlicense.errmsg + updateDataStates.driverlicense.errmsg;
            //    toaster.pop('error', '通知', '服务端错误,错误信息：' + errmsg);
            //}

            self.loadCheckAccountInfo ();

        };

        self.extractData = function (userid, imgUrl1, imgUrl2, prefix ,suffix, size) {
            //console.log(imgUrl);
            var modalInstance = $modal.open({
                templateUrl : 'account.check.extractdata.html',  //指向上面创建的视图
                controller : ExtractDataModalInstanceCtrl,// 初始化模态范围
                size : size, //大小配置
                resolve : {
                    imgUrl : function(){
                        return {
                            'userid' : userid,
                            'imgUrl1': imgUrl1,
                            'imgUrl2': imgUrl2,
                            'car_prefix' : prefix,
                            'car_suffix' : suffix
                        };
                    }
                }
            });
        };

        var ExtractDataModalInstanceCtrl = function ($scope, $modal, accountModel, $modalInstance, imgUrl) {

            $scope.vm = {};

            $scope.imgUrl1 = imgUrl.imgUrl1;
            $scope.imgUrl2 = imgUrl.imgUrl2;
            $scope.userid  = imgUrl.userid;
            $scope.vm.carIdentityNumberLength = 0;

            console.log(imgUrl);


            accountModel.accountCarNumbers().get({userid: $scope.userid, aciton: 'get'}, {}).$promise.then(function(response) {
                if (response.errno == 0) {
                    if (response.data.car_number == '' || response.data.car_number == undefined) {
                        $scope.vm.carNumber = imgUrl.car_prefix + imgUrl.car_suffix;
                    } else {
                        $scope.vm.carNumber = response.data.car_number;
                    }
                    $scope.vm.carIdentityNumber = response.data.car_identity_number;
                    $scope.vm.engine_number   = response.data.engine_number;
                    $scope.vm.registered_at = response.data.registered_at;
                }
            });

            $scope.$watch('vm.carIdentityNumber', function(newValue, oldValue) {
                if (newValue != undefined && newValue != '') {
                    console.log(newValue)
                    console.log(oldValue)
                    if (newValue.length > 17) {
                        $scope.vm.carIdentityNumber = oldValue;
                    }
                    $scope.vm.carIdentityNumberLength = newValue.length;
                }
            });

            $scope.ok = function () {
                var pData = {
                    action: 'update',
                    userid:  $scope.userid,
                    car_number: $scope.vm.carNumber,
                    car_identity_number: $scope.vm.carIdentityNumber,
                    engine_number: $scope.vm.engine_number,
                    registered_at: $scope.vm.registered_at
                }

                console.log($scope.vm.registered_at);
                accountModel.accountCarNumbers().save(pData, {}).$promise.then(function(response) {
                    if (response.errno == 0) {
                        toaster.pop('success', '通知', '操作成功');
                        $modalInstance.close(1);
                    } else {
                        toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                    }
                });
            }

            $scope.cancel = function () {
                $modalInstance.dismiss('close');
            };
        };
    }]);


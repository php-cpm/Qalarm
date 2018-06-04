/**
 * Created by weichen on 15/10/14.
 */
app.controller("AccountCheckHeadImgCtrl", ['$state', '$scope', '$modal', '$http', 'NgTableParams', 'ngTableDefaults', 'toaster', 'accountModel', 'commonModel','Permits',
    function ($state, $scope, $modal, $http, NgTableParams, ngTableDefaults, toaster, accountModel, commonModel,Permits) {
        var self = this;
        var api = accountModel.accountHeadImgCheckList();

        self.selectedIds = [];
        self.createTable = function () {
            var pageSize = 15;
            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function ($defer, params) {
                    var data = getNgTableData(params);
                    return data;
                }
            });

            //清空监听对象
            self.checkboxes = {
                checked: false,
                items: {}
            };
        };

        pageRoute = 'app.account.check';
        self.permits = Permits.pagePermits(pageRoute);

        /**
         * 调用resource 查询数据
         * @param pageParams
         * @returns {*}
         */
        function getNgTableData(pageParams) {

            return api.list(setNgTableParams(pageParams, self.searchParams), {})
                .$promise.then(function (data) {
                    //后台js 返回数据判断; 0 成功,正确绑定数据; 非0 返回空
                    if (data.errno == 0 || data.errno == '0') {
                        pageParams.total(data.data.page.total);
                        pageParams.page(data.data.page.index);
                        //在此处取得查询的数据,后续监听
                        self.selectedIds = data.data.result;
                        return data.data.result;
                    } else {
                        return {};
                    }
                });
        }

        /**
         * 设置查询条件
         * @param pageParams
         * @param fileterParams
         * @returns {{}}
         */
        function setNgTableParams(pageParams, fileterParams) {

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

        //初始化查询条件
        function initSearchParams(){
            return {
                filters: {
                    "accountName":      { "currentVal": "", "ignoreVal":"", "urlKeyName": "name"},
                    "accountMobile":    { "currentVal": "", "ignoreVal":"", "urlKeyName": "mobile"},
                    "accountType":      { "currentVal": 0,  "ignoreVal":0,  "urlKeyName": "user_type"},
                    "sex":              { "currentVal": -1, "ignoreVal": -1,"urlKeyName": "sex"},
                    "headImgCheckState":{ "currentVal": 1 , "ignoreVal":-1, "urlKeyName": "headimg_state"}
                    //"pageIndex":{ "name":"accountMobile",   "currentVal":"", "defaultVal": 1, "urlKeyName": "page_index"},
                    //"pageSize":{ "name":"accountMobile",   "currentVal":"", "defaultVal": 15, "urlKeyName": "page_size"}
                }
            };
        }

        self.searchParams = initSearchParams().filters;

        //设定用户头像审核状态为: 审核中 ;性别:全部; 用户类型:全部
        //self.searchParams.accountType.currentVal = self.searchParams.accountType.defaultVal;
        //self.searchParams.sex.currentVal = self.searchParams.sex.defaultVal;
        //self.searchParams.headImgCheckState.currentVal = self.searchParams.headImgCheckState.defaultVal;

        self.checkboxes = {
            checked: false,
            items: {}
        };

        // 监听全选
        $scope.$watch(function () {
                return self.checkboxes.checked;
            }
            , function (value) {
                angular.forEach(self.selectedIds, function (item) {
                    //console.log(item);
                    self.checkboxes.items[item.basic.id] = value;
                    console.log(item)
                });
            });

        // watch for data checkboxes
        $scope.$watch(function () {
            return self.checkboxes.items;
        }, function (values) {
            var checked = 0, unchecked = 0;
            angular.forEach(self.selectedIds, function (item) {
                checked += (self.checkboxes.items[item.basic.id]) || 0;
                unchecked += (!self.checkboxes.items[item.basic.id]) || 0;
            });

            //console.log(self.selectedIds);
            console.log(values);

        }, true);

        accountModel.accountTypes().then(function (response) {
            self.accountTypes = response.data;
            //self.searchParams.accountType.currentVal = self.accountTypes[0].id;
            self.accountTypeSelect = function () {
                console.log(self.searchParams.accountType.currentVal);
            };
        });



        accountModel.headImgCheckStates().then(function (response) {
            self.headImgCheckStates = response.data;
            //self.searchParams.headImgCheckState.currentVal = self.headImgCheckStates[0].id;
            self.headImgCheckStateSelect = function () {
                console.log(self.searchParams.headImgCheckState.currentVal);
            };
        });



        accountModel.accountSex().then(function (response) {
            self.accountSex = response.data;
            //self.searchParams.sex.currentVal = self.accountSex[0].id;
            self.sexSelect = function () {
                console.log(self.searchParams.sex.currentVal);
            };
        });

        self.search = function () {
            console.log(self.searchParams);
            self.createTable();
        };

        self.createTable();

        $scope.formatRowData = {
            accountName: function (sex) {
                showAccountName = sex == '男' ? '先生' : sex == '女' ? '女士' : '未设置';
                return showAccountName;
            },

            accountHeadImgState: function (authState) {
                cssClass = authState == 0 ? 'bg-warning' : authState == 1 ? 'bg-primary' : authState == 2 ? 'bg-success' : authState == 3 ? 'bg-danger' : 'bg-danger';
                return 'label ' + cssClass;
            }
        };


        self.accountCheck = function (accountId, isCheckOk) {

            accountStates = {"ok": 2, "refuse": 3};

            stateCode = accountStates.refuse;
            if (isCheckOk == true) {
                stateCode = accountStates.ok;
            }

            pData = {
                "user_id": accountId,
                "headimg_state": stateCode
            };

            accountModel.accountBasicUpdate().save(pData).$promise.then(function(response){
                if (response.errno == 0) {
                    self.createTable();
                    toaster.pop('success', '通知', '编辑成功');
                } else {
                    toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                }
            });

            //$http({
            //    method: 'POST',
            //    url: '/api/v1/account/basicupdate',
            //    params: pData
            //}).success(function (response) {
            //    if (response.errno == 0) {
            //        self.createTable();
            //        toaster.pop('success', '通知', '操作成功');
            //    } else {
            //        toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
            //    }
            //});

        };

        self.checkAgree = function () {

            console.log('checkagree');
            for (var id in self.checkboxes.items)
            {
                if(self.checkboxes.items[id] == true){
                    self.accountCheck(id,true);
                }
            }
        };

        self.checkRefuse = function () {

            for (var id in self.checkboxes.items)
            {
                //console.log(id + ' --------- ' + self.checkboxes.items[id]);
                if(self.checkboxes.items[id] == true){
                    self.accountCheck(id,false);
                }
            }
        };

        //=======================
        //弹出对话框
        self.openImgBox = function (accountId,imgUrl,size,typeId) {
            //console.log(imgUrl);
            var modalInstance = $modal.open({
                templateUrl : 'account.showimg.html',  //指向上面创建的视图
                controller : ModalInstanceCtrl,// 初始化模态范围
                size : size, //大小配置
                resolve : {
                    //imgUrl : function(){
                    //    return imgUrl;
                    //}
                    data : function(){
                        return {"imgUrl":imgUrl, "accountId": accountId, "typeId":typeId};
                    }
                }
            });
            modalInstance.result.then(function(){
                //$scope.selected = selectedItem;
                self.createTable(); //刷新数据
            },function(){
                //$log.info('Modal dismissed at: ' + new Date())
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
                        //证件旋转
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

            //$scope.ok = function () {
            //
            //};
            //
            //$scope.close = function () {
            //    $modalInstance.dismiss('close');
            //};
        };

    }]);


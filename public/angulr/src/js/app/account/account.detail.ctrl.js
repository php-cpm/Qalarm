app.controller("AccountDetailCtrl", ['$scope', '$modal', '$state', 'NgTableParams', 'accountModel','$stateParams', 'toaster', 'commonModel', 'Permits',
    function ($scope, $modal, $state, NgTableParams, accountModel ,$stateParams, toaster, commonModel, Permits) {
        var self = this;

        $scope.user_id = $stateParams.user_id;

        $scope.load = function() {
            accountModel.accountDetail().query({user_id:$scope.user_id},{"user_id":$scope.user_id}).$promise.then(function(data){
                console.log(data.data);
                $scope.data = data.data;

                if($scope.data.basic.voice_intro != undefined){
                    $scope.audio = document.getElementById('voice_intro');
                    if($scope.data.basic.voice_intro.url != ''){
                        $scope.showaudio = true;
                        $scope.audio.src = $scope.data.basic.voice_intro.url;
                        $scope.audio.style.display = "";
                    }else{
                        console.log('no data');
                        $scope.showaudio = false;
                        $scope.audio.style.display = "none";
                    }
                }

            });

            //图像删除log
            accountModel.photosDelLogs().get({"user_id":$scope.user_id}).$promise.then(function(data) {
                //console.log(data.data);
                $scope.photosDelLogs = data.data.data;
            });

            //音频删除log
            accountModel.voiceDelLogs().get({"user_id":$scope.user_id}).$promise.then(function(data) {
                //console.log(data.data);
                $scope.voiceDelLogs = data.data.data;
            });

        };

        $scope.load();

        pageRoute = 'app.account.list';
        $scope.permits = Permits.pagePermits(pageRoute);
        //console.log($scope.permit);

        //弹出修改窗口
        $scope.openAccountEdit = function (size) {

            var modalInstance = $modal.open({
                templateUrl: 'account.edit.html',
                controller: ModalInstanceCtrl,
                size: size,
                resolve: {
                    subData : function () {
                        return {
                            "refresh"   : $scope.load,
                            "data"      : $scope.data,
                            "permits"   : $scope.permits
                        };
                    }
                }
            });

            modalInstance.result.then(function () {
                console.log('ok');
            }, function () {
                console.log('cancel');
            });
        };


        var ModalInstanceCtrl = function ($scope, $modal, $http, $modalInstance, commonModel, toaster, subData) {

            //读取车型
            commonModel.carBrands().then(function(response){
                $scope.carBrands = response.data;
                //console.log(response.data);
                $scope.carBrandSelect = function () {
                    console.log($scope.data.car.model_id);
                };
            });

            commonModel.carPrefix().then(function(response){
                $scope.carNumberPrefixs = response.data;

                //等待网络数据返回之后,下拉框执行绑定事件
                $scope.data = subData.data;
                $scope.permits = subData.permits;

                //console.log(subData.permits);

                $scope.carNumberPrefixSelect = function () {
                    console.log($scope.data.car.number_prefix);
                };
            });



            //$scope.carBrands = editData.carBrands;
            $scope.okAuthUpdate = function () {

                var pData = {
                    "user_id"       : $scope.data.basic.id,
                    "realname"      : $scope.data.auth.realname,
                    "idcard"        : $scope.data.auth.idcard
                };

                console.log($scope.pData);

                accountModel.accountAuthUpdate().save(pData).$promise.then(function(response){
                    if (response.errno == 0) {
                        toaster.pop('success', '通知', '编辑成功');
                        //$modalInstance.close(1);
                        //再次加载父数据
                        subData.refresh();
                    } else {
                        toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                    }
                });
                //$http({method: 'POST', url: '/api/v1/account/accountauthupdate', params: pData}).success(function (response) {
                //    if (response.errno == 0) {
                //        toaster.pop('success', '通知', '编辑成功');
                //        //$modalInstance.close(1);
                //        //再次加载父数据
                //        subData.refresh();
                //    } else {
                //        toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                //    }
                //});
            };

            $scope.okCarUpdate = function () {

                var pData = {
                    "user_id"       : $scope.data.basic.id,
                    "number_prefix" : $scope.data.car.number_prefix,
                    "number_suffix" : $scope.data.car.number_suffix,
                    "model_id"      : $scope.data.car.model_id
                };

                console.log(pData);

                accountModel.accountCarUpdate().save(pData).$promise.then(function(response){
                    console.log(response);
                    if (response.errno == 0) {
                        toaster.pop('success', '通知', '编辑成功');
                        //$modalInstance.close(1);
                        //再次加载父数据
                        subData.refresh();
                    } else {
                        toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                    }
                });

                //$http({method: 'POST', url: '/api/v1/account/carupdate', params: pData}).success(function (response) {
                //    if (response.errno == 0) {
                //        toaster.pop('success', '通知', '编辑成功');
                //        //$modalInstance.close(1);
                //        //再次加载父数据
                //        subData.refresh();
                //    } else {
                //        toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                //    }
                //});
            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            };
        };

        //=======================
        //弹出对话框
        $scope.openImgBox = function (accountId,imgUrl,size,typeId) {
            var modalInstance = $modal.open({
                templateUrl : 'account.showimg.html',  //指向上面创建的视图
                controller : ModalInstanceCtrl2,// 初始化模态范围
                size : size, //大小配置
                resolve : {
                    data : function(){
                        return {"imgUrl":imgUrl, "accountId": accountId, "typeId":typeId};
                    }
                }
            });
            //对话框回调: 第一个回调:成功;第二个取消
            modalInstance.result.then(function(){
                console.log('ok');
                $scope.load(); //刷新数据
            },function(){
                //$log.info('Modal dismissed at: ' + new Date())
                console.log('cancel');
            });

        };

        var ModalInstanceCtrl2 = function ($scope, $modal, $http, $modalInstance, data) {

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
                        pData = {"user_id": $scope.accountId, "image": imgurl};
                        apiSave =  accountModel.accountCarUpdate();
                    }else if(typeId == 3){
                        //driver 驾驶本旋转
                        pData = {"user_id": $scope.accountId, "license": imgurl};
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

        //=======================
        //弹出对话框
        $scope.showphotoBox = function (accountId,imgId,imgUrl,size) {
            var modalInstance = $modal.open({
                templateUrl : 'account.showphoto.html',  //指向上面创建的视图
                controller : ModalInstanceCtrl3,// 初始化模态范围
                size : size, //大小配置
                resolve : {
                    data : function(){
                        return {"accountId": accountId, "imgId":imgId, "imgUrl":imgUrl, "reason":""};
                    }
                }
            });
            //对话框回调: 第一个回调:成功;第二个取消
            modalInstance.result.then(function(){
                console.log('ok');
                $scope.load(); //刷新数据
            },function(){
                console.log('cancel');
            });

        };


        var ModalInstanceCtrl3 = function ($scope, $modal, $http, $modalInstance, data) {

            $scope.imgId = data.imgId;
            $scope.imageUrl = data.imgUrl;
            $scope.accountId = data.accountId;
            //$scope.reason = data.reason;
            $scope.reason = "不符合规定!";

            //关闭
            $scope.close = function () {
                $modalInstance.dismiss('close');
            };

            $scope.deletePhoto = function () {
                console.log('delete');
                console.log($scope.reason);
                if($scope.reason == ''){
                    toaster.pop('error', '通知', '删除原因未填写,请补充');
                    return;
                }

                pData = {
                    "user_id":$scope.accountId,
                    "photos":$scope.imgId,
                    "reason":$scope.reason
                };
                accountModel.photosDel().delete(pData).$promise.then(function(response){
                    if (response.errno == 0) {
                        toaster.pop('success', '通知', '编辑成功');
                        $modalInstance.close(1); //回调函数
                    } else {
                        toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                    }
                });
            };
        };


        $scope.delVoiceReason = '';
        $scope.voiceDele = function(accountId){

            if($scope.delVoiceReason.trim() == ''){
                toaster.pop('error', '通知', '删除原因未填写,请补充');
                return;
            }

            var pData = {
                "user_id":accountId,
                "voice_intro":"",
                "voice_del_reason":$scope.delVoiceReason
            };

            accountModel.accountBasicUpdate().save(pData).$promise.then(function(response){
                if (response.errno == 0) {

                    if($scope.delVoiceReason.trim() == ''){
                        toaster.pop('error', '通知', '删除原因未填写,请补充');
                        return;
                    }

                    toaster.pop('success', '通知', '编辑成功');
                    $scope.load();// 刷新数据
                    $scope.delVoiceReason = '';
                } else {
                    toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                }
            });
        }

    }]);

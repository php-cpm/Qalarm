app.controller("AdminUserProfileCtrl", ['$scope', '$rootScope', '$modal','toaster', 'adminModel','$cookies',
    function ($scope,$rootScope, $modal, toaster,adminModel,$cookies) {
        var self = this;

        $scope.user = $rootScope.user;

        $scope.load = function(){
            if ($scope.user.admin_id == undefined) {
                return;
            }
            adminModel.user().get({"id" : $scope.user.admin_id}).$promise.then(function (response) {
                if (response.errno == 0) {
                    //console.log(response.data);
                    $scope.userData = response.data.result;
                    //console.log($scope.userData);

                    //配置头像;在头像不为空;重新设定头像;
                    if(response.data.result.head_img != ''){
                        $rootScope.user_head_img = response.data.result.head_img;
                    }
                } else {
                    toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                }
            });
        };
        $scope.load();

        $scope.updateHeadImg = function () {

            var modalInstance = $modal.open({
                templateUrl: 'admin.headimg.html',
                controller: EditCtrl,
                size: 'lg',
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                            "data": $scope.userData
                        }
                    }
                }
            });

            modalInstance.result.then(function () {
                console.log('ok');
                $scope.load();
            }, function () {
                console.log('cancel');
            });
        };


        var EditCtrl = function ($scope,$rootScope, $http, $modalInstance, commonModel, toaster, params) {

            $scope.data = params.data;

            $scope.myImage='';
            $scope.myCroppedImage='';
            $scope.cropType="circle";
            $scope.isSelect = false;

            $scope.setFile = function(element) {
                $scope.$apply(function($scope) {
                    $scope.isSelect = true;
                    var file=element.files[0];
                    var reader = new FileReader();
                    reader.onload = function (evt) {
                        $scope.$apply(function($scope){
                            $scope.myImage=evt.target.result;
                        });
                    };
                    reader.readAsDataURL(file);
                });
            };

            $scope.saveHeadImg = function(){
                //console.log(file);
                if($scope.isSelect){
                    console.log('this is save');
                    var pData = {"user_head": $scope.myCroppedImage};
                    var data = adminModel.user().update(pData).$promise.then(function (response) {
                        if (response.errno == 0) {
                            toaster.pop('success', '通知', '编辑成功');
                            $rootScope.user.head_img = response.data.head_img;
                            $modalInstance.close(1);
                            console.log($rootScope.user_head_img);
                        } else {
                            toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                        }
                    });
                }else {
                    toaster.pop('error', '提醒', '您没有选择头像!!');
                }
            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };
    }
]);

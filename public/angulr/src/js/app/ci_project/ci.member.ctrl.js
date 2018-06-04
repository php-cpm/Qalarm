app.controller("CiMemberCtrl", ['$state', '$scope', '$modal', '$stateParams', 'NgTableParams','toaster', 'ciProjectModel', 
    function ($state, $scope, $modal, $stateParams, NgTableParams, toaster, ciProjectModel) {
        var self = this;

        // 通过控件传递过来的值
        //self.project = $scope.project.ProjectRow;
        self.project = {};
        self.project = {'project_id' : $stateParams.project_id};

        self.createTable = function () {
            self.tableParams = new NgTableParams({}, {
                getData: function (params) {
                    var data = getNgTableData(params);
                    return data;
                }
            });
        };

        function getNgTableData(pageParams) {
            var pData = {
                'project_id'      : self.project.project_id
            };
            return ciProjectModel.projectMemberManager().list({}, pData).$promise.then(function (response) {
                if (response.errno == 0) {
                    return response.data;
                } else {
                    return {};
                }
            });
        }

        self.createTable();

        self.handler = {
            delete : function(dataId) {
                var pData = {
                    'data_id' : dataId,
                };
                console.log('id is' +dataId);
                ciProjectModel.projectMemberManager().delete(pData, {}).$promise.then(function (response) {
                //ciProjectModel.projectMemberManager().delete({}, pData).$promise.then(function (response) {
                    if (response.errno == 0) {
                        self.createTable();
                        toaster.pop('success', '成功');
                    } else {
                        toaster.pop('error', '通知', response.errmsg);
                    }
                });
            },
            updateState : function(dataId, opt) {
                //opt 必须是 enable,disable,
                var pData = {
                    'data_id' : dataId,
                    'opt' : opt 
                };
                ciProjectModel.projectMemberManager().update(pData, {}).$promise.then(function (response) {
                    if (response.errno == 0) {
                        self.createTable();
                        toaster.pop('success', '成功');
                    } else {
                        toaster.pop('error', '通知', response.errmsg);
                    }
                });
            }
        };

        self.addProjectMember = function (row, size) {
            //查找当前需要添加的用户信息
            ciProjectModel.projectMemberManager().get({}, {}).$promise.then(function (response) {
                if (response.errno == 0) {

                    var modalInstance = $modal.open({
                        templateUrl: 'ci.member.edit.html',
                        controller: CiMemberEditCtrl,
                        size: size,
                        windowClass: 'modal-gaea',
                        resolve: {
                            params: function () {
                                return {
                                    "data"      : row,
                                    "user_list" : response.data
                                }
                            }
                        }
                    });
                    modalInstance.result.then(function () {
                        console.log('ok');
                    }, function () {
                        console.log('cancel');
                    });

                } else {
                    toaster.pop('error', '通知', response.errmsg);
                }
            });
        };

        var CiMemberEditCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {

            $scope.data = {};
            $scope.data.memberTypes = [{'id':'1', 'name':'管理员'}, {'id':'2', 'name':'开发人员'}, {'id':'3', 'name':'测试人员'},{'id':'4', 'name':'关注人员'}];
            $scope.data.memberType  = $scope.data.memberTypes[0].id;
            $scope.data.projectId  = params.data.project_id;
            
            //用户列表信息
            $scope.data.userList  = params.user_list;

            $scope.ok = function () {

                var pData = {
                    "project_id"       : $scope.data.projectId,
                    "selected_user_id" : $scope.data.selected_user_id,
                    "member_type"      : $scope.data.memberType,
                };
                ciProjectModel.projectMemberManager().add(pData, {}).$promise.then(function (response) {
                    if (response.errno == 0) {
                        self.createTable();
                        toaster.pop('success', '成功');
                        $modalInstance.close(1);
                    } else {
                        toaster.pop('error', '通知', response.errmsg);
                    }
                });

            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };
    }
]);


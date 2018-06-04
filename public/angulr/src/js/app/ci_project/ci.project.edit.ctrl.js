app.controller("CiProjectEditCtrl", ['$state', '$scope', '$modal', '$stateParams', 'NgTableParams','toaster', 'ciProjectModel',
    function ($state, $scope, $modal, $stateParams, NgTableParams, toaster, ciProjectModel) {

        var self = this;
        var api = ciProjectModel.gitLabProject();

        //get url params : project_id 
        self.paramsData = {};
        self.paramsData.project_id     = $stateParams.project_id;

        //read data by project_id
        $scope.data = {};
        $scope.selected =[];
        api.get({'project_id':self.paramsData.project_id},{}).$promise.then(function(response){
            if (response.errno == 0) {

                $scope.data = response.data; 

                //查找下拉框；应绑定什么数据
                buildSteps = $scope.data.ci_build_steps;
                for(objJob in $scope.jobList) {
                    for(obj in buildSteps) {
                        if ($scope.jobList[objJob].name == buildSteps[obj].job_name_pre) {
                            $scope.selected.push($scope.jobList[objJob].id);
                        }
                    }
                }
                $scope.data.jobsSelected = $scope.selected;

            } else {
                toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
            }
        });

        //job 类别选择复选框
        $scope.jobList = [ 
                { "id": 1, "name": "checkcode", "sname": "代码规范检查", "weight": "111", "job_type": "1" },
                { "id": 2, "name": "build",     "sname": "项目构建",     "weight": "115", "job_type": "2" }
            ];

        var updateSelected = function(action,id,name){
            if(action == 'add' && $scope.selected.indexOf(id) == -1){
                $scope.selected.push(id);
            }
            if(action == 'remove' && $scope.selected.indexOf(id)!=-1){
                var idx = $scope.selected.indexOf(id);
                $scope.selected.splice(idx,1);
            }
        }

        $scope.updateSelection = function($event, id){
            var checkbox = $event.target;
            var action = (checkbox.checked?'add':'remove');
            //updateSelected(action,id,checkbox.name);
            updateSelected(action,Number(checkbox.value),checkbox.name);
            console.log($scope.selected);
        }

        $scope.isSelected = function(id){
            return $scope.selected.indexOf(id)>=0;
        }

        //project language
        $scope.languages = ['php','java'];
        $scope.$watch('data.language', function(newVal) {
            if (newVal == 'php' ) $scope.languageVersions = ['5.3', '5.4','5.5','5.6','5.7'];
            if (newVal == 'java') $scope.languageVersions = ['jdk 1.6', 'jdk 1.7', 'jdk 1.8'];
        });

        //todo:bug chosen 临时写法
        //$scope.selectedBranchs = getSelectedBranchs($scope.data.listener_branchs); 
        //function getSelectedBranchs (data){
            //var branchs = [];
            //for(var i=0; i<data.length; i++) {
                //branchs.push(data[i].name);
            //}
            //return branchs.join('|');
        //}

        $scope.ok = function () {

            if ($scope.data.language_version == '' || $scope.data.language == '') {
                toaster.pop('error', '提示', '项目语言必须选择');
                return;
            }

            //将选中的复选框信息；保存到data 中
            $scope.data.jobsSelected = $scope.selected;
            var pData = {
                "id"                  : $scope.data.id,
                //"project_id"        : $scope.data.project_id,
                //"project_name"      : $scope.data.project_name,
                "listener_branchs"    : $scope.data.listener_branchs,
                "ssh_user"            : $scope.data.ssh_user,
                "build_before_sh"     : $scope.data.build_before_sh,
                "deploy_after_sh"     : $scope.data.deploy_after_sh,
                "language"            : $scope.data.language,
                "language_version"    : $scope.data.language_version,
                "build_steps"         : $scope.data.jobsSelected,
                "deploy_dir"          : $scope.data.deploy_dir,
                "checkcode_dir"       : $scope.data.checkcode_dir,
                //"is_scm_open"       : $scope.data.is_scm_open,
                //"is_shell_open"     : $scope.data.is_shell_open,
                //"is_blacklist_open" : $scope.data.is_blacklist_open
                "deploy_files"        : $scope.data.deploy_files,
                "deploy_black_files"  : $scope.data.deploy_black_files,
                "deploy_dir"          : $scope.data.deploy_dir
            };

            //ciProjectModel.gitLabProject().update(pData).$promise.then(function(response){
            api.update(pData).$promise.then(function(response){
                if (response.errno == 0) {
                    toaster.pop('success', '通知', '编辑成功');
                } else {
                    toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                }
            });
        };

        $scope.cancel = function () {
            console.log('cancel');
        }

    }
]);


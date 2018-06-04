//app.controller("DiffFilesCtrl", ['$state', '$scope', '$modal', 'NgTableParams','toaster', 'ciProjectModel','$stateParams', '$sce',
app.controller("DeployProCtrl", ['$state', '$scope', '$modal', 'NgTableParams','toaster', 'ciProjectModel','$stateParams', '$sce',

    function ($state, $scope, $modal, NgTableParams, toaster, ciProjectModel, $stateParams, $sce) {

        var self = this;
        //get url params : gaea_build_id, project_id, update_id 
        self.paramsData = {};
        self.paramsData.gaea_build_id  = $stateParams.gaea_build_id;
        self.paramsData.project_id     = $stateParams.project_id;
        self.paramsData.update_id      = $stateParams.update_id;

        //==== get data by gaea_build_id  start ====  
        self.diffFilesList = {};
        function getDiffFilesList() {
            ciProjectModel.diffFiles().list({}, {'gaea_build_id' : self.paramsData.gaea_build_id})
                .$promise.then(function (data) {
                    if (data.errno == 0 || data.errno == '0') {
                        self.diffFilesList = data.data;
                    } else {
                        return {};
                    }
            });
        }
        getDiffFilesList();

        //get data
        deployTypeList = [{"id":1, "name":"新功能"},{"id":2, "name":"Bug修复"},{"id":3, "name":"改进优化"}];
        //$scope.data = params.data;
        $scope.data = {};
        $scope.data = self.paramsData;
        $scope.data.deployTypeList = deployTypeList;
        $scope.data.deployType = 1;
        //$scope.data.deploy_step = 0;
        $scope.data.deploy_step = 'beta';
        $scope.data.project_name = '';

        $scope.deployTypeSelect = function () {
            console.log($scope.data.deployType);
        };

        ciProjectModel.getGitlabChangeByChangeId().get({"update_id": $scope.data.update_id}, {})
            .$promise.then(function (data) {
                if (data.errno == 0 || data.errno == '0') {
                    var commits = data.data.commits;
                    var desc = '';
                    for (var i=1; i<commits.length+1; i++) {
                        //desc += commits[i-1].message + '\n';
                        desc += i + '.' + commits[i-1].message + '';
                    }
                    $scope.data.desc = desc;
                    $scope.data.title = commits[0].message;
                }
            });

        ciProjectModel.gitLabProject().get({"project_id": $scope.data.project_id}, {})
            .$promise.then(function (data) {
                if (data.errno == 0 || data.errno == '0') {
                    //console.log(data.data);
                    //console.log(data.data.deploy_dir);
                    $scope.data.project_name = data.data.project_name;
                    $scope.data.deploy_dir = data.data.deploy_dir;
                }
            });

        $scope.hostset = {'test': false, 'slave' : false, 'online': false};
        ciProjectModel.checkHostSet().get({"project_id": $scope.data.project_id}, {})
            .$promise.then(function (data) {
                if(data.errno == 0 || data.errno == '0'){
                    console.log(data.data);
                    $scope.hostset.test   = data.data.test;
                    $scope.hostset.slave  = data.data.slave;
                    $scope.hostset.online = data.data.online;
                }
            });
            //console.log($scope.data);
            //执行发布
            $scope.ok = function () {
                //if ($scope.selected.length == 0) {
                    //toaster.pop('error', '提示', '没有选择待发布机器');
                    //return;
                //}
                //console.log($scope.selected);

                var urlParams = {
                    "gaea_build_id"    : $scope.data.gaea_build_id,
                    "title"            : $scope.data.title,
                    "desc"             : $scope.data.desc,
                    "deploy_step"      : $scope.data.deploy_step,
                }
                //ciProjectModel.deployALevel().get(urlParams, {})
                ciProjectModel.deployOperate().add(urlParams, {})
                    .$promise.then(function (data) {
                        if(data.errno == 0 || data.errno == '0'){
                            toaster.pop('success', '提示', '开始部署了，需要一定时间');
                            //var urlData = {"deploy_id":data.data.deploy_id};
                            //$state.go('app.ci_project.deploylasttask',urlData);
                            var urlData = {"deploy_step_id":data.data.deploy_step_id};
                            $state.go('app.ci_project.deploylasttask',urlData);
                            return {};
                        }else{
                            toaster.pop('error', '提示', '部署失败'+data.errmsg);
                            return {};
                        }
                    });
            };
            //$scope.cancel = function () {
                //$modalInstance.dismiss('cancel');
            //};
            


            //========================================================



        /*$scope.$watch('diffFilesList', function() {*/
                //console.log(' 变化了');
        //});

        self.getDiffFilesOne = function (projectId,gaeaBuildId,diffFileName) {
            var paramsUrl = {'project_id': projectId, 'gaea_build_id': gaeaBuildId, 'diff_file_name' : diffFileName};
            ciProjectModel.diffFiles().get(paramsUrl, {}).$promise.then(function (data) {
                //后台js 返回数据判断; 0 成功,正确绑定数据; 非0 返回空
                if(data.errno == 0 || data.errno == '0'){
                    //formatDiffFilesList(data.data);
                    formatDiffFile(data.data);
                }else{
                    return {};
                }
            });
        }

        function formatDiffFile(data) {
            var dataNew = data.new;
            var dataOld = data.old;
            var intra = data.intra;
            var diffVisible = data.visible;
            var len = Math.max(data.new.length, data.old.length);
            var html=[];
            var tpl = '<tr><td>11122121</td></tr>'
            //var tpl = new Ext.XTemplate(
                        //'<tr>',
                            //'<td>{oldLineNo}</td>',
                            //'<td class="cl {oldCls}">{oldText}</td>',
                            //'<td>{newLineNo}</td>',
                            //'<td class="cr {newCls}">{newText}</td>',
                        //'</tr>'
                    //);
            //console.log('长度'+len);
            var isVisible = true;
            for(var i= 0;i<len;i++){
                if(!diffVisible[i]){
                    if(isVisible){
                        isVisible = false;
                        html.push('<tr class="unvisible"><td colspan="4">...</td></tr>');
                    }
                    continue;
                };
                isVisible = true;

                var itemNew = dataNew[i],
                    itemOld = dataOld[i],
                    data = {};

                if(!itemNew){
                    data.newLineNo = '';
                    data.newCls = 'diff-null';
                    data.newText = '';
                }
                else{
                    var type = itemNew.type,
                        text = itemNew.text;
                    //if(!Ext.isString(text))
                        //text = '';
                    if(intra[i]){
                        var hls = intra[i][1];
                        if(hls.length==1 && hls[0][0]==0){
                            if(!(hls[0][1]==0))
                                data.newCls = 'full-new';
                                //text = Ext.String.htmlEncode(text);
                                text = $sce.trustAsHtml(text);
                        }else{

                            var newText = '',
                                pos = 0;
                            for(var j= 0,jLen=hls.length;j<jLen;j++){
                                if(hls[j][0]==1){
                                    //newText += '<span class="highlight">'+Ext.String.htmlEncode(text.substr(pos,hls[j][1]))+'</span>';
                                    //newText += '<span class="highlight">'+text.substr(pos,hls[j][1])+'</span>';
                                    newText += '<span class="highlight">'+$sce.trustAsHtml(text.substr(pos,hls[j][1]))+'</span>';
                                }else{
                                    //newText += Ext.String.htmlEncode(text.substr(pos,hls[j][1]));
                                    //newText += text.substr(pos,hls[j][1]);
                                    newText += $sce.trustAsHtml(text.substr(pos,hls[j][1]));
                                }
                                pos += hls[j][1];
                            }
                            text = newText;
                        }
                    }else{
                        //text = Ext.String.htmlEncode(text);
                        //text = text;
                        text = $sce.trustAsHtml(text);
                    }

                    data.newLineNo = itemNew.line;
                    data.newCls += ' '+((type=="+") ? 'diff-add' : (type=='-') ? 'diff-minus' : '');
                    data.newText = text;
                }

                if(!itemOld){
                    data.oldLineNo = '';
                    data.oldCls = 'diff-null';
                    data.oldText = '';
                }
                else{
                    var type = itemOld.type;
                    var text = itemOld.text;
                    //if(!Ext.isString(text))
                        //text = '';

                    if(intra[i]){
                        var hls = intra[i][0];
                        if(hls.length==1 && hls[0][0]==0){
                            if(!(hls[0][1]==0))
                                data.oldCls = 'full-old';
                                //text = Ext.String.htmlEncode(text);
                                //text = text;
                                text = $sce.trustAsHtml(text);
                        }else{

                            var newText = '',
                                pos = 0;
                            for(var j= 0,jLen=hls.length;j<jLen;j++){
                                if(hls[j][0]==1){
                                    //newText += '<span class="highlight">'+Ext.String.htmlEncode(text.substr(pos,hls[j][1]))+'</span>';
                                    //newText += '<span class="highlight">'+text.substr(pos,hls[j][1])+'</span>';
                                    newText += '<span class="highlight">'+$sce.trustAsHtml(text.substr(pos,hls[j][1]))+'</span>';
                                }else{
                                    //newText += Ext.String.htmlEncode(text.substr(pos,hls[j][1]));
                                    //newText += text.substr(pos,hls[j][1]);
                                    newText += $sce.trustAsHtml(text.substr(pos,hls[j][1]));
                                }
                                pos += hls[j][1];
                            }
                            text = newText;
                        }
                    }else{
                        //text = Ext.String.htmlEncode(text);
                        //text = text;
                        text = $sce.trustAsHtml(text);
                    }

                    data.oldLineNo = itemOld.line;
                    data.oldCls += ' ' + ((type=="+") ? 'diff-add' : (type=='-') ? 'diff-minus' : '');
                    data.oldText = text;
                }
                //html.push(tpl.apply(data));
                //html.push(data);
                
                  var tr_html = '<tr>';
                  tr_html += '<td>'+data.oldLineNo+'</td>';
                  tr_html += '<td class="cl '+data.oldCls+'">'+data.oldText+'</td>';
                  tr_html += '<td>'+data.newLineNo+'</td>';
                  tr_html += '<td class="cl '+data.newCls+'">'+data.newText+'</td>';
                  tr_html += '</tr>';
                  html.push(tr_html);
            }

            var diffContentDiv = angular.element("#diff_content");
            var row = '<tr><td></td><td class="cl"><span class="title">线上(旧)</span></td><td></td><td class="cr"><span class="title">线下(新)</span></td></tr>';
            var html_content = '<table class="diff-table" border="0" cellpadding="0" cellspacing="0" font-size="12px">'+row+html.join('')+'</table>';
            //diffContentDiv.append(html_content);
            diffContentDiv.html(html_content);
        }



        //==================================================
            //deployTypeList = [{"id":1, "name":"新功能"},{"id":2, "name":"Bug修复"},{"id":3, "name":"改进优化"}];
            ////$scope.data = params.data;
            //$scope.data = self.paramsData;
            //$scope.data.deployTypeList = deployTypeList;
            //$scope.data.deployType = 1;
            //$scope.data.deployStep = 0;

            //$scope.deployTypeSelect = function () {
                //console.log($scope.data.deployType);
            //};

            //ciProjectModel.getGitlabChangeByChangeId().get({"update_id": $scope.data.update_id}, {})
                //.$promise.then(function (data) {
                    //if(data.errno == 0 || data.errno == '0'){
                        //var commits = data.data.commits;
                        //var desc = '';
                        //for(var i=1; i<commits.length+1; i++){
                            ////desc += commits[i-1].message + '\n';
                            //desc += i + '.' + commits[i-1].message + '';
                        //}
                        //$scope.data.desc = desc;
                        //$scope.data.title = commits[0].message;
                    //}
                //});
            //ciProjectModel.gitLabProject().get({"project_id": $scope.data.project_id}, {})
                //.$promise.then(function (data) {
                    //if(data.errno == 0 || data.errno == '0'){
                        //console.log(data.data);
                        //console.log(data.data.deploy_dir);
                        //$scope.data.deploy_dir = data.data.deploy_dir;
                    //}
                //});

            //$scope.hostset = {'test': false, 'slave' : false, 'online': false};
            //ciProjectModel.checkHostSet().get({"project_id": $scope.data.project_id}, {})
                //.$promise.then(function (data) {
                    //if(data.errno == 0 || data.errno == '0'){
                        //console.log(data.data);
                        //$scope.hostset.test   = data.data.test;
                        //$scope.hostset.slave  = data.data.slave;
                        //$scope.hostset.online = data.data.online;
                        ////console.log(data.data.deploy_dir);
                        ////$scope.data.deploy_dir = data.data.deploy_dir;
                    //}
                //});
            ////console.log($scope.data);
            ////执行发布
            //$scope.ok = function () {
                ////var urlData = {"deploy_id":'123455555'};
                ////$state.go('app.ci_project.deploylasttask',urlData);
                ////return;
                ////if ($scope.selected.length == 0) {
                    ////toaster.pop('error', '提示', '没有选择待发布机器');
                    ////return;
                ////}
                ////console.log($scope.selected);

                //var urlParams = {
                    //"gaea_build_id"    : $scope.data.gaea_build_id,
                    //"title"            : $scope.data.title,
                    //"desc"             : $scope.data.desc,
                    //"deploy_step"      : $scope.data.deployStep,
                //}
                ////ciProjectModel.deployALevel().get(urlParams, {})
                //ciProjectModel.deployOperate().add(urlParams, {})
                    //.$promise.then(function (data) {
                        //if(data.errno == 0 || data.errno == '0'){
                            //toaster.pop('success', '提示', '开始部署了，需要一定时间');
                            //$modalInstance.close(1);
                            ////$state.go('app.ci_project.deployproject');
                            //var urlData = {"deploy_id":data.data.deploy_id};
                            //$state.go('app.ci_project.deploylasttask',urlData);
                            //return {};
                        //}else{
                            //toaster.pop('error', '提示', '部署失败'+data.errmsg);
                            //return {};
                        //}
                    //});
            //};
            //$scope.cancel = function () {
                //$modalInstance.dismiss('cancel');
            //};
 
    }
]);

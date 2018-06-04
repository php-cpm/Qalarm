app.controller("DiffFilesCtrl", ['$state', '$scope', '$modal', 'NgTableParams','toaster', 'ciProjectModel','$stateParams', '$sce',

    function ($state, $scope, $modal, NgTableParams, toaster, ciProjectModel, $stateParams, $sce) {

        var self = this;
        //获取 href 跳转；传递的参数
        self.paramsData = {};
        self.paramsData.gaea_build_id  = $stateParams.gaea_build_id;
        self.paramsData.project_id     = $stateParams.project_id;
        self.paramsData.update_id      = $stateParams.update_id;

        //更具  update_id 获取变化信息
        ciProjectModel.getGitlabChangeByChangeId().get({"update_id" : self.paramsData.update_id}, {})
            .$promise.then(function (data) {

                if(data.errno == 0 || data.errno == '0'){
                    self.projectChange = data.data;
                } else {
                    toaster.pop('error', '提示', '获取数据异常'+data.errmsg);
                    return;
                }

            });
        //切换现实隐藏commit 信息 
        self.showCommit = function () {
            console.log('wowo');
            self.isShowCommit = !self.isShowCommit;
        };

        //self.filesDiffList = [{"name":'/home/t/src/acc.php'}, {"name":"/home/t/xxx/ccc.php"}];
        //获取url 传递的参数；deploy_id 
        //self.gaeaBuildId  = $stateParams.gaea_build_id;
        self.diffFilesList = {};
        function getDiffFilesList() {
            //ciProjectModel.diffFiles().list({'gaea_build_id' : self.gaeaBuildId}, {}).$promise.then(function (data) {
            ciProjectModel.diffFiles().list({'gaea_build_id' : self.paramsData.gaea_build_id}, {})
                .$promise.then(function (data) {
                    if(data.errno == 0 || data.errno == '0'){
                        self.diffFilesList = data.data;
                    }else{
                        return {};
                    }
                });
        }
        //获取数据
        getDiffFilesList();
        //$scope.$watch('diffFilesList', function() {
                //console.log(' 变化了');
        //});

        ////todo:暂时用watch;取消注册dom 方式
        //function formatDiffFilesList(data) {
            //var listData = data;
            //var listDataLength = data.length;
            //var html = [];
            //var tr_html = '';
            //for (var i = 0; i < listDataLength; i++) {
                //tr_html = '';
                //tr_html += "<tr style='vertical-align: middle'>";
                //tr_html += '<td><p ng-click="vm.getDiffFilesOne(123,123)" href="" title="" class="text-info">'+listData[i].data+'</p></td>';
                //tr_html += "</tr>";
                //html.push(tr_html);
            //}
            //var listHtml = "<table>"+html.join('')+"</table>"
            //angular.element("#diff_files_list").html(listHtml);
        //}

        self.getDiffFilesOne = function (projectId,gaeaBuildId,diffFileName) {
            var paramsUrl = {'project_id': projectId, 'gaea_build_id': gaeaBuildId, 'diff_file_name' : diffFileName};
            //var paramsUrl = {'project_id': 'ccc', 'gaea_build_id': 'aaaa', 'diff_file_name' : 'bbbbbbb'};
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
    }
]);

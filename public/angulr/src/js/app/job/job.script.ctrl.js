'use strict';

app.controller("JobScriptCtrl", ['$scope', '$rootScope', '$modal', '$http', '$timeout', 'NgTableParams', 'toaster', 'jobDoneModel', '$confirm',
    function ($scope, $rootScope, $modal, $http, $timeout, NgTableParams, toaster, jobDoneModel, $confirm) {
        var self = this;
        self.scriptName = '';
        self.tabs = new Array();
        self.tabs['history'] = {'title': '执行历史', 'active': true}
        self.tabs['manager'] = {'title': '脚本管理', 'active': false}


        $scope.setTabShow = function (name) {
            for (var key in self.tabs) {
                if (key == name) {
                    self.tabs[key].active = true;
                } else {
                    self.tabs[key].active = false;
                }
            }
        }

        jobDoneModel.scriptTyps().get({}, {}).$promise.then(function (response) {
            self.scritpTypes = response.data;
        });

        $scope.index = function (scriptType) {
            var pageSize = 15;
            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = jobDoneModel.scripts().list({
                        "page_index": params.page(),
                        "page_size": pageSize,
                        "type": scriptType,
                        "name": self.scriptName
                    }, {}).$promise.then(function (data) {
                            params.total(data.data.page.total);
                            params.page(data.data.page.index);
                            return data.data.results;
                        });
                    return data;
                }
            });
        }

        $rootScope.$watch('user', function () {
            if (!isEmpty($rootScope.user)) {
                self.execUsername = $rootScope.user.admin_name;
            }
        }, true);

        $scope.fetchExecHistory = function () {
            var pageSize = 15;
            self.execTableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function ($defer, params) {
                    var data = jobDoneModel.scriptExecs().list({
                        "page_index": params.page(),
                        "page_size": pageSize,
                        "exec_name": self.execUsername
                    }, {}).$promise.then(function (data) {
                            params.total(data.data.page.total);
                            params.page(data.data.page.index);
                            $defer.resolve(data.data.results)
                            // return data.data.results;
                        });
                    // return data;
                }
            });
        }



        // 通过type过滤脚本
        $scope.filter = function (type) {
            $scope.index(type);
        }

        // 通过脚本名模糊搜索脚本
        $scope.search = function () {
            $scope.index('');
        }

        // 通过脚本名模糊搜索
        $scope.execSearch = function () {
            self.execTableParams.reload();
        }

        // 执行脚本
        $scope.execute = function (size, id, script) {
            var modalInstance = $modal.open({
                templateUrl: 'job.script.exec.html',
                controller: execScriptCtrl,
                size: size,
                windowClass: 'modal-gaea',
                backdrop: 'static',
                keyboard: false,
                resolve: {
                    params: function () {
                        return {
                            scriptId: id,
                            script: script,
                            rootScope: $scope
                        }
                    }
                }
            });
        }


        // 删除脚本
        $scope.delete = function (id) {
            $confirm({title: '确认框', ok: '确认', cancel: '取消', text: '确定删除?'}).then(function () {
                var pData = {
                    'id': id
                };

                jobDoneModel.updateScript().del({}, pData).$promise.then(function (response) {
                    if (response.errno == 0) {
                        toaster.pop('info', '通知', '删除成功');
                        $scope.index(self.scritpType);
                    } else {
                        toaster.pop('error', '通知', response.errmsg);
                    }
                });
            });
        };

        // 添加脚本
        $scope.addScript = function (size, id) {
            // 先请求数据，再弹窗，防止下拉菜单绑定失败
            jobDoneModel.scriptTyps().get({}, {}).$promise.then(function (response) {
                var scriptTypes = response.data;
                var modalInstance = $modal.open({
                    templateUrl: 'job.script.edit.html',
                    controller: addScriptCtrl,
                    size: size,
                    windowClass: 'modal-gaea',
                    backdrop: 'static',
                    keyboard: false,
                    resolve: {
                        params: function () {
                            return {
                                scriptId: id,
                                scriptTypes : scriptTypes,
                                refresh: $scope.index
                            }
                        }
                    }
                });
            });
        }

        // 机器列表
        $scope.showExecHostnames = function (size, hostnames) {
            var modalInstance = $modal.open({
                templateUrl: 'job.script.exec.hostnames.html',
                controller: showExecHostnamesCtrl,
                size: size,
                windowClass: 'modal-gaea',
                backdrop: 'static',
                keyboard: false,
                resolve: {
                    params: function () {
                        return {
                            hostnames: hostnames
                        }
                    }
                }
            });
        }
        var showExecHostnamesCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            $scope.execResult = {};
            $scope.execResult.editorOptions = {
                lineNumbers: true,
                mode: 'shell',
                fixedGutter: true,
                keyMap: 'vim',
                indentWithTabs: true,
                readOnly: 'nocursor',
            };
            $scope.execResult.hostnames = params.hostnames.replace(/,/g, '\n');

            $scope.ok = function () {
                $modalInstance.dismiss('cancel');
            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };

        // 机器列表
        $scope.showExecResult = function (size, id, type) {
            var modalInstance = $modal.open({
                templateUrl: 'job.script.exec.results.html',
                controller: showExecResultCtrl,
                size: size,
                windowClass: 'modal-gaea',
                resolve: {
                    params: function () {
                        return {
                            id: id,
                            type: type
                        }
                    }
                }
            });
        }
        var showExecResultCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            $scope.execResult = {};
            $scope.execResult.editorOptions = {
                lineNumbers: true,
                mode: 'shell',
                fixedGutter: true,
                indentWithTabs: true,
                readOnly: 'nocursor',
                lineWrapping: true,
                viewportMargin: Infinity,
                onLoad: function (_cm) {
                    // HACK to have the codemirror instance in the scope...
                    _cm.setSize(null, 'auto');
                }
            };

            var pData = {
                'id': params.id,
                'type': params.type
            }

            var pageSize = 100;
            $scope.execResult.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = jobDoneModel.execResult().get(pData, {}).$promise.then(function (data) {
                        return data.data;
                    });
                    return data;
                }
            });

            $scope.ok = function () {
                $modalInstance.dismiss('cancel');
            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };

        // 历史页面的执行操作
        $scope.doActions = function (id, action) {
            $confirm({title: '确认框', ok: '确认', cancel: '取消', text: '确定操作?'}).then(function () {
                var pData = {
                    'exec_id': id,
                    'action': action
                };

                jobDoneModel.goonScriptExec().save({}, pData).$promise.then(function (response) {
                    if (response.errno == 0) {
                        toaster.pop('info', '通知', '操作成功');
                        self.execTableParams.reload();
                    } else {
                        toaster.pop('error', '通知', response.errmsg);
                    }
                });
            });
        };
        var addScriptCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            $scope.script = {};
            $scope.script.isShow = false;
            $scope.script.scritpOwners = [{"id": 1, "name": "Gaea"}, {"id": 2, "name": "OPS"}, {
                "id": 3,
                "name": "DBA"
            }];
            $scope.script.scritpTypes = '';
            $scope.script.scritpOwner = '';
            $scope.script.btnDisabled = false;

            $scope.script.editorOptions = {
                lineNumbers: true,
                mode: 'shell',
                fixedGutter: true,
                keyMap: 'vim',
                indentWithTabs: true,
            };

            // 防止返回时机导致被重写
            $scope.script.scritpTypes = params.scriptTypes;
            if (isEmpty($scope.script.scritpOwner)) {
                $scope.script.scritpOwner = $scope.script.scritpOwners[0].id;
            }

            if (isEmpty($scope.script.scriptType)) {
                $scope.script.scriptType = $scope.script.scritpTypes[0].id;
            }

            // 编辑
            if (params.scriptId != '') {
                // 不能编辑脚本名和脚本类型
                $scope.script.isShow = true;

                var pData = {
                    'id': params.scriptId
                };
                jobDoneModel.script().get(pData, {}).$promise.then(function (response) {
                    if (response.errno == 0) {
                        var script = response.data;
                        $scope.script.scriptName = script.scriptname;
                        $scope.script.scriptFunction = script.remark;
                        $scope.script.scriptParams = script.params;
                        $scope.script.scritpOwner = script.owner;
                        $scope.script.scriptDir = script.scriptdir;
                        if (script.type != '' && script.type != null && $scope.script.scritpTypes != '') {
                            $scope.script.scriptType = $scope.script.scritpTypes[script.type - 1].id;
                        }
                        $scope.script.scriptContent = script.script;
                    }
                });
            }

            $scope.ok = function () {
                var pData = {
                    'name': $scope.script.scriptName,
                    'type': $scope.script.scriptType,
                    'function': $scope.script.scriptFunction,
                    'params': $scope.script.scriptParams,
                    'content': $scope.script.scriptContent,
                    'owner': $scope.script.scritpOwner,
                    'dir': $scope.script.scriptDir
                };

                $scope.script.btnDisabled = true;
                jobDoneModel.updateScript().save({}, pData).$promise.then(function (response) {
                    if (response.errno == 0) {
                        $modalInstance.close(1);
                        if (params.scriptId != '') {
                            toaster.pop('info', '通知', '修改成功');
                        } else {
                            toaster.pop('info', '通知', '添加成功');
                        }
                        params.refresh('');
                    } else {
                        toaster.pop('error', '通知', response.errmsg);
                        $scope.script.btnDisabled = false;
                    }
                });
            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };

        var execScriptCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            $scope.exec = {};
            $scope.exec.scriptName = params.script.scriptname;
            $scope.exec.scriptId = params.scriptId;
            $scope.exec.btnDisabled = false;

            $scope.exec.params = [];
            $scope.exec.values = {};

            console.log(params.script.params)
            if (!isEmpty(params.script.params)) {
                var local = params.script.params.split(',');
                for (var p in local) {
                    $scope.exec.params.push({"label": local[p]})
                    $scope.exec.values[local[p]] = '';
                }
            }

            $scope.exec.isShowInvalid = false;
            $scope.exec.editorOptions = {
                lineNumbers: true,
                mode: 'shell',
                fixedGutter: true,
                indentWithTabs: true,
            };

            // 执行页面显示详细的脚本代码
            $scope.showCode = function (size, id) {
                var modalInstance = $modal.open({
                    templateUrl: 'job.script.detail.html',
                    controller: detailScriptCtrl,
                    size: size,
                    windowClass: 'modal-gaea',
                    backdrop: 'static',
                    keyboard: false,
                    resolve: {
                        params: function () {
                            return {
                                scriptId: id
                            }
                        }
                    }
                });
            }

            var detailScriptCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
                $scope.detail = {};
                $scope.detail.scripts = '';
                $scope.detail.editorOptions = {
                    lineNumbers: true,
                    mode: 'shell',
                    fixedGutter: true,
                    keyMap: 'vim',
                    indentWithTabs: true,
                };

                // 加载数据
                jobDoneModel.script().get({id: params.scriptId}, {}).$promise.then(function (response) {
                    if (response.errno == 0) {
                        $scope.detail.scripts = response.data.script;
                    }
                });

                $scope.ok = function () {
                    $modalInstance.dismiss('cancel');
                };

                $scope.cancel = function () {
                    $modalInstance.dismiss('cancel');
                }
            };

            $scope.ok = function () {
                if ($scope.exec.hostnames == '' || $scope.exec.hostnames.length == 0) {
                    toaster.pop('info', '通知', '请输入主机名');
                    return;
                }
                var pData = {
                    'script_id': $scope.exec.scriptId,
                    'params': '',
                    'hostnames': $scope.exec.hostnames
                };

                if (!isEmpty(params.script.params)) {
                    var values = '';
                    var local = params.script.params.split(',');
                    for (var p in local) {
                        var one = local[p] + '=' + $scope.exec.values[local[p]];
                        values += one
                        values += '|'
                    }

                    pData['params'] = values;
                }

                $scope.exec.btnDisabled = true;
                jobDoneModel.updateScriptExec().save({}, pData).$promise.then(function (response) {
                    if (response.errno == 0) {
                        var data = response.data;
                        // 主机名检查失败
                        if (data.check == false) {
                            $scope.exec.isShowInvalid = true;
                            $scope.exec.hostnames = data.valid;
                            $scope.exec.invalidHostnames = data.invalid;
                            // toaster.pop('info', '通知', '存在失效的主机名');
                            return;
                        }
                        $modalInstance.close(1);
                        params.rootScope.setTabShow('history');
                        params.rootScope.execTableParams.reload();
                    } else {
                        toaster.pop('error', '通知', response.errmsg);
                        $scope.exec.btnDisabled = false;
                    }
                });
            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };

        $scope.index('');
        $scope.fetchExecHistory();

        // 执行页面的定时刷新
        $scope.onTimeout = function() {
            self.execTableParams.reload();
            timer = $timeout($scope.onTimeout, 2000);
        }
        var timer =  $timeout($scope.onTimeout, 2000);
        $scope.$on('$destroy', function (event) {
            $timeout.cancel(timer);
        })
    }]);

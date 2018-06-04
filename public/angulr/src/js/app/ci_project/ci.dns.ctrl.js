app.controller("CiDnsCtrl", ['$state', '$scope', '$modal', '$stateParams', 'NgTableParams','toaster', 'ciProjectModel', '$confirm',
    function ($state, $scope, $modal, $stateParams, NgTableParams, toaster, ciProjectModel, $confirm) {
        var self = this;

        // 通过控件传递过来的值
        //self.project = $scope.project.ProjectRow;
        self.project = {};
        self.project = {'project_id' : $stateParams.project_id, 'project_name' : $stateParams.project_name};

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
            return ciProjectModel.ciProjectDnses().list({}, pData).$promise.then(function (response) {
                if (response.errno == 0) {
                    return response.data.results;
                } else {
                    return {};
                }
            });
        }

        self.createTable();

        self.handler = {
            delete : function(name) {
                var pData = {
                    'dns_name' : name,
                };
                $confirm({title: '确认框', ok: '删除', cancel: '取消', text: '确定删除选中域名?'}).then(function () {
                    ciProjectModel.ciProjectDnsUpdate().delete(pData, {}).$promise.then(function (response) {
                        if (response.errno == 0) {
                            self.createTable();
                            toaster.pop('success', '成功');
                            $modalInstance.close(1);
                        } else {
                            toaster.pop('error', '通知', response.errmsg);
                        }
                    });
                });
            },
            online: function(name, hostname) {
                var pData = {
                    'dns_name' : name,
                    'action'   : 'online',
                    'host_name': hostname
                };
                ciProjectModel.ciProjectDnsUpdate().update(pData, {}).$promise.then(function (response) {
                    if (response.errno == 0) {
                        self.createTable();
                        toaster.pop('success', '成功');
                        $modalInstance.close(1);
                    } else {
                        toaster.pop('error', '通知', response.errmsg);
                    }
                });
            },
            offline: function(name, hostname) {
                var pData = {
                    'dns_name' : name,
                    'action'   : 'offline',
                    'host_name': hostname
                };
                ciProjectModel.ciProjectDnsUpdate().update(pData, {}).$promise.then(function (response) {
                    if (response.errno == 0) {
                        self.createTable();
                        toaster.pop('success', '成功');
                        $modalInstance.close(1);
                    } else {
                        toaster.pop('error', '通知', response.errmsg);
                    }
                });
            }
        };

        self.AddOrUpdateProjectDns = function (size, page, dnsRow) {
            var pData = {
                'project_id' : self.project.project_id
            }
            ciProjectModel.ciProjectHosts().get(pData, {}).$promise.then(function (response) {
                if (response.errno == 0) {
                    // 处理数据，按照环境放入不同的数组中
                    var result = response.data.results;
                    var hostList = {};
                    var host_env_type_test = 1;
                    var host_env_type_prod = 2;

                    var dns_env_type_test  = 1;
                    var dns_env_type_slave = 2;
                    var dns_env_type_prod  = 3;

                    var host_enable  = 1;
                    var host_disable = 2;

                    hostList[dns_env_type_test]   = new Array();
                    hostList[dns_env_type_slave] = new Array();
                    hostList[dns_env_type_prod]   = new Array();


                    for (idx in result) {
                        console.log(result[idx]);
                        var row = result[idx];
                        if (row.enable == host_disable) {
                            continue;
                        }
                        if (row.host_env_type == 1) {
                            hostList[dns_env_type_test].push(row.host_name)
                        } else {
                            if (row.host_is_slave == '是') {
                                hostList[dns_env_type_slave].push(row.host_name);
                            } else {
                                hostList[dns_env_type_prod].push(row.host_name);
                            }
                        }
                    }

                    if (page == 'add') {
                        var modalInstance = $modal.open({
                            templateUrl: 'ci.dns.add.html',
                            controller: CiDnsAddCtrl,
                            size: size,
                            windowClass: 'modal-gaea',
                            resolve: {
                                params: function () {
                                    return {
                                        "hostList": hostList,
                                        "project" : self.project
                                    }
                                }
                            }
                        });
                    } else {
                        var modalInstance = $modal.open({
                            templateUrl: 'ci.dns.edit.html',
                            controller: CiDnsEditCtrl,
                            size: size,
                            windowClass: 'modal-gaea',
                            resolve: {
                                params: function () {
                                    return {
                                        "hostList": hostList,
                                        "project" : self.project,
                                        "dnsRow"  : dnsRow
                                    }
                                }
                            }
                        });
                    }
                } else {
                    toaster.pop('error', '通知', response.errmsg);
                }
            });
        };

        var CiDnsAddCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            $scope.data = {};
            $scope.data.dnsTypes = [{'id':'1', 'name':'测试', 'desc':'test'}, {'id':'2', 'name':'回归', 'desc':'slave'}, {'id':'3', 'name':'生产', 'desc':''}];
            $scope.data.dnsType  = $scope.data.dnsTypes[0].id;


            $scope.data.dnsBackupHosts = params.hostList[$scope.data.dnsType];
            $scope.data.dnsSuffix = '.ttyongche.com';
            $scope.data.dnsName     = $scope.data.dnsTypes[$scope.data.dnsType - 1].desc + "." + params.project.project_name + $scope.data.dnsSuffix;

            $scope.data.dnsBackupHostSelected = [];
            
            $scope.ok = function () {
                if (isEmpty($scope.data.dnsPort))  {
                    toaster.pop('error', '通知', '请输入服务端口号');
                    return;
                }

                if (($scope.data.dnsBackupHostSelected.length == 0))  {
                    toaster.pop('error', '通知', '请至少选择一台主机');
                    return;
                }

                var pData = {
                    "project_id"       : params.project.project_id,
                    "dns_type"         : $scope.data.dnsType,
                    "dns_name"         : $scope.data.dnsName,
                    "dns_port"         : $scope.data.dnsPort,
                };

                pData.hosts = $scope.data.dnsBackupHostSelected.join('|');

                ciProjectModel.ciProjectDnsUpdate().add(pData, {}).$promise.then(function (response) {
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

            $scope.dnsTypeSelect = function(type) {
                $scope.data.dnsBackupHosts = params.hostList[$scope.data.dnsType];
                if (isEmpty($scope.data.dnsTypes[$scope.data.dnsType - 1].desc)) {
                    $scope.data.dnsName = params.project.project_name + $scope.data.dnsSuffix;
                } else {
                    $scope.data.dnsName = $scope.data.dnsTypes[$scope.data.dnsType - 1].desc + "." + params.project.project_name + $scope.data.dnsSuffix;
                }
                $scope.data.dnsBackupHostSelected = [];
            }

            $scope.selectHost = function(host, dnsBackupHostSelected) {
                var idx = dnsBackupHostSelected.indexOf(host);
                if (idx > -1) {
                    dnsBackupHostSelected.splice(idx, 1);
                }
                else {
                    dnsBackupHostSelected.push(host);
                }
            }

            $scope.existHost = function(host, dnsBackupHostSelected) {
                return dnsBackupHostSelected.indexOf(host) > -1;
            }
        };

        var CiDnsEditCtrl = function ($scope, $http, $modalInstance, commonModel, toaster, params) {
            $scope.data = {};
            $scope.data.dnsTypes = [{'id':'1', 'name':'测试', 'desc':'test'}, {'id':'2', 'name':'回归', 'desc':'slave'}, {'id':'3', 'name':'生产', 'desc':''}];
            $scope.data.dnsType  = params.dnsRow.type;

            $scope.data.dnsBackupHosts = params.hostList[$scope.data.dnsType];


            $scope.data.dnsBackupHostSelected = params.dnsRow.selected_host;

            $scope.data.editorOptions = {
                lineNumbers: true,
                mode: 'shell',
                fixedGutter: true,
                keyMap: 'vim',
                indentWithTabs: true,
            };

            $scope.data.dnsConf = params.dnsRow.conf;
            $scope.data.dnsPort = params.dnsRow.port;
            $scope.data.dnsName = params.dnsRow.name;

            $scope.ok = function () {
                if (isEmpty($scope.data.dnsPort))  {
                    toaster.pop('error', '通知', '请输入服务端口号');
                    return;
                }

                if (($scope.data.dnsBackupHostSelected.length == 0))  {
                    toaster.pop('error', '通知', '请至少选择一台主机');
                    return;
                }

                var pData = {
                    "project_id"       : params.project.project_id,
                    "dns_type"         : $scope.data.dnsType,
                    "dns_name"         : $scope.data.dnsName,
                    "dns_port"         : $scope.data.dnsPort,
                    "dns_conf"         : $scope.data.dnsConf,
                };

                pData.hosts = $scope.data.dnsBackupHostSelected.join('|');

                ciProjectModel.ciProjectDnsUpdate().update(pData, {}).$promise.then(function (response) {
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

            $scope.selectHost = function(host, dnsBackupHostSelected) {
                var idx = dnsBackupHostSelected.indexOf(host);
                if (idx > -1) {
                    dnsBackupHostSelected.splice(idx, 1);
                }
                else {
                    dnsBackupHostSelected.push(host);
                }
            }

            $scope.existHost = function(host, dnsBackupHostSelected) {
                return dnsBackupHostSelected.indexOf(host) > -1;
            }
        };
    }
]);


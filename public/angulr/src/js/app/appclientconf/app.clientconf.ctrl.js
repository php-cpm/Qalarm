app.controller("AppClientConfCtrl", ['$state', '$scope', '$modal', 'NgTableParams', 'toaster', 'appClientConf',
    function ($state, $scope, $modal, NgTableParams, toaster, appClientConf) {
        var self = this;
        var api = appClientConf;

        self.createTable = function() {

            self.tableParams = new NgTableParams({}, {
                getData: function () {
                    var data = getNgTableData();
                    return data;
                }
            });
        };

        function getNgTableData(){

            return api.index().list(setNgTableParams(self.filterParams), {})
                .$promise.then(function (data) {
                    //后台js 返回数据判断; 0 成功,正确绑定数据; 非0 返回空
                    if(data.errno == 0 || data.errno == '0'){
                        return data.data.data;
                    }else{
                        return {};
                    }
                });

        }

        //设置 查询参数
        function setNgTableParams(filterParams){

            var result = {};
            for(var key in filterParams){
                if(filterParams[key].currentVal != filterParams[key].ignoreVal){
                    result[filterParams[key].urlKeyName] = filterParams[key].currentVal;
                }
            }
            return result;

        }

        self.apps = [{"id": "", "name": "--请选择--"}, {"id": "pinche", "name": "顺风车"}, {"id": "magic", "name": "车管家"}, {"id": "advisor", "name": "小哥端"}];
        self.clientOs = [{"id": "", "name": "--请选择--"}, {"id": "android", "name": "ANDROID"}, {"id": "ios", "name": "IOS"}];
        //self.clientVersion = [{"id": '', "name": "--请选择--"}, {"id": '1.0.0', "name": "1.0.0"}, {"id": '2.0.0', "name": "2.0.0"}, {"id": '3.0.0', "name": "3.0.0"}];
        self.clientVerType = [{"id": "", "name": "--请选择--"}, {"id": "normal", "name": "正式版本"}, {"id": "test", "name": "测试版本"},{ "id": "beta", "name": "灰度版本"}];
        self.functionType = [{"id": "", "name": "--请选择--"}, {"id": "config", "name": "正常配置"}, {"id": "patch", "name": "补丁配置"},{ "id": "update", "name": "强制更新配置"}];

        self.appsSelect = function () {
            console.log(self.filterParams.apps.currentVal);
        };
        self.clientOsSelect = function () {
            console.log(self.filterParams.clientOs.currentVal);
        };
        self.clientVersionSelect = function () {
            console.log(self.filterParams.clientVersion.currentVal);
        };
        self.clientVerTypeSelect = function () {
            //self.obj.data = {};
            console.log(self.filterParams.clientVerType.currentVal);
        };
        self.functionTypeSelect = function () {
            console.log(self.filterParams.functionType.currentVal);
        };

        //初始化筛选条件
        function initSearchParams(){
            return {
                filters: {
                    "apps":             { "currentVal": "", "ignoreVal": "", "urlKeyName": "app_id"},
                    "clientOs":         { "currentVal": "", "ignoreVal": "", "urlKeyName": "client_os"},
                    "clientVerType":    { "currentVal": "", "ignoreVal": "", "urlKeyName": "client_ver_type"},
                    "clientVersion":    { "currentVal": "", "ignoreVal": "", "urlKeyName": "version"},
                    "functionType" :    { "currentVal": "", "ignoreVal": "", "urlKeyName": "function_type"}
                }
            };
        }


        self.filterParams = initSearchParams().filters;
        self.createTable();

        //复制一条数据
        self.copyData = function(row){

            var pData = {
                "app_id"        : row.app_id,
                "app_name"      : row.app_name,
                "app_os"        : row.app_os,
                "version"       : row.version,
                "app_type"      : row.app_type,
                "app_type_name" : row.app_type_name,
                "function_type" : row.function_type,
                "function_type_name" : row.function_type_name,
                "conf_key"      : row.conf_key,
                "conf_value"    : row.conf_value
            };

            api.insert().save(pData).$promise.then(function(response){
                if (response.errno == 0) {
                    //self.obj.data = {};
                    //设置当前插入的数据值
                    self.selectRow = response.data.data;
                    self.obj.data = eval('(' + response.data.data.conf_value + ')');

                    self.createTable(); //从新加载数据

                    //设置颜色
                    setTableRowColor(response.data.data.id);

                    toaster.pop('success', '通知', '编辑成功');
                } else {
                    toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                }
            });

        };


        //选中的数据
        self.selectRow = {
            "app_id"        : "",
            "app_name"      : "",
            "app_os"        : "",
            "version"       : "",
            "app_type"      : "",
            "app_type_name" : "",
            "function_type" : "",
            "function_type_name" : "",
            "conf_key"      : "",
            "conf_value"    : "{}"
        };

        self.isEdit = false;
        self.setJsonData = function(rowId,rowData){
            self.selectRow = rowData;
            self.obj.data = eval('(' + rowData.conf_value + ')');
            setTableRowColor(rowId);

            (self.selectRow.conf_state == 0) ? self.isEdit = true : self.isEdit = false;
        };

        //设置颜色
        function setTableRowColor(rowId){
            self.bg = [];
            self.bg[rowId] = 'bg-primary';
        }

        self.update = function(){
            console.log('this is update');

            if(self.selectRow.app_id == '' || self.selectRow.app_id == undefined){
                toaster.pop('warn', '提示', 'APP名称没有选择!请从新选择!');
                return;
            }
            if(self.selectRow.app_os == '' || self.selectRow.app_os == undefined){
                toaster.pop('warn', '提示', '客户端OS没有选择!请从新选择!');
                return;
            }
            if(self.selectRow.app_type == '' || self.selectRow.app_type == undefined){
                toaster.pop('warn', '提示', '版本状态没有选择!请从新选择!');
                return;
            }

            if(self.selectRow.version == '' || self.selectRow.version == undefined){
                toaster.pop('warn', '提示', '版本号未设置,请从新设置!');
                return;
            }

            if(self.selectRow.function_type == '' || self.selectRow.function_type == undefined){
                toaster.pop('warn', '提示', '功能没有选择!,请从新设置!');
                return;
            }

            self.selectRow.app_name = getJsonNameById(self.selectRow.app_id,self.apps);
            self.selectRow.app_type_name = getJsonNameById(self.selectRow.app_type,self.clientVerType);

            var pData = {
                "id"            : self.selectRow.id,
                "app_id"        : self.selectRow.app_id,
                "app_name"      : self.selectRow.app_name,
                "app_os"        : self.selectRow.app_os,
                "version"       : self.selectRow.version,
                "app_type"      : self.selectRow.app_type,
                "app_type_name" : self.selectRow.app_type_name,
                "function_type" : self.selectRow.function_type,
                "function_type_name" : self.selectRow.function_type_name,
                "conf_key"      : self.selectRow.conf_key,
                "conf_value"    : angular.toJson(self.obj.data),
                "remark"        : ""
            };
            //console.log(pData);

            api.update().save(pData).$promise.then(function(response){
                if (response.errno == 0) {
                    self.createTable(); //从新加载数据
                    toaster.pop('success', '通知', '编辑成功');
                    //console.log(response.data.data);
                } else {
                    toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                }
            });

        };

        self.releaseData = function(id){
            console.log('this is releaseData');
            var pData = {
                "id"            : id,
                "conf_state"    : 1
            };

            api.releasedata().save(pData).$promise.then(function(response){
                if (response.errno == 0) {
                    self.createTable(); //从新加载数据
                    toaster.pop('success', '通知', '编辑成功');
                   // openWin(response.data);
                } else {
                    toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                }
            });
        }

        ////=======================
        //function openWin(data) {
        //      console.log(data);
        //    var modalInstance = $modal.open({
        //        templateUrl : 'show.redis.html',  //指向上面创建的视图
        //        controller : ModalInstanceCtrl,// 初始化模态范围
        //        size : '', //大小配置
        //        resolve : {
        //            data : function(){
        //                return {"redis_key":data.redis_key, "redis_val":data.redis_val };
        //            }
        //        }
        //    });
        //}
        //
        //var ModalInstanceCtrl = function ($scope,$modalInstance,data) {
        //    $scope.data = {
        //        "redis_key" : data.redis_key,
        //        "redis_val" : data.redis_val
        //    }
        //};

        //=====================
        var jsonData = {

        };

        self.obj = {data: jsonData, options: { 'mode': 'tree'}};

        self.onLoad = function (instance) {
            self.obj.options.mode = self.obj.options.mode == 'tree' ? 'code' : 'tree';
        };


        //=== 编辑下拉框
        self.appsEditSelect = function () {
            setRedisKey();
            //self.selectRow.conf_key = self.selectRow.app_id + '_'+ self.selectRow.app_os + '_'+ self.selectRow.app_type;
            (self.selectRow.app_id !='' ) ? self.selectRow.app_name = getJsonNameById(self.selectRow.app_id, self.apps) : '';
        };

        self.clientOsEditSelect = function () {
            setRedisKey();
            //self.selectRow.conf_key = self.selectRow.app_id + '_'+ self.selectRow.app_os + '_'+ self.selectRow.app_type;
        };

        self.clientVerTypeEditSelect = function () {
            setRedisKey();
            //self.selectRow.conf_key = self.selectRow.app_id + '_'+ self.selectRow.app_os + '_'+ self.selectRow.app_type;
            (self.selectRow.app_type !='' ) ? self.selectRow.app_type_name = getJsonNameById(self.selectRow.app_type, self.clientVerType) : '';
        };

        self.functionTypeEditSelect = function () {
            setRedisKey();
            //self.selectRow.conf_key = self.selectRow.app_id + '_'+ self.selectRow.app_os + '_'+ self.selectRow.app_type;
            (self.selectRow.function_type !='' ) ? self.selectRow.function_type_name = getJsonNameById(self.selectRow.function_type, self.functionType) : '';
        };

        self.versionEdit = function(val){
            setRedisKey();
        };
        //ng-change="textChange(val)"

        //根据json的Id获取json 第一层值
        function getJsonNameById(jsonId,jsonObj){
            var result = '';
            for(var i=0; i< jsonObj.length; i++)
            {
                if(jsonId == jsonObj[i].id){
                    result = jsonObj[i].name;
                    break;
                }
            }
            return result;
        }

        //设置rediskey
        function setRedisKey(){
            self.selectRow.conf_key = self.selectRow.function_type
                + '_' + self.selectRow.app_id
                + '_'+ self.selectRow.app_os
                + '_' + self.selectRow.version
                + '_'+ self.selectRow.app_type;
        }

    }]);


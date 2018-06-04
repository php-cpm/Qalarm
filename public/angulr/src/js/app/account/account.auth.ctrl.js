/**
 * Created by weichen on 15/10/14.
 */
app.controller("AccountAuthCtrl", ['$state', '$scope', '$modal', 'NgTableParams', 'accountModel','commonModel','Permits',
    function ($state, $scope, $modal, NgTableParams, accountModel,commonModel, Permits) {
        var self = this;
        var api = accountModel.accountAuth();
        $scope.createTable = function() {
            var pageSize = 15;
            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = getNgTableData(params);
                    console.log(data);
                    return data;
                }
            });
        };

        pageRoute = 'app.account.list';
        $scope.permits = Permits.pagePermits(pageRoute);

        function getNgTableData(pageParams){

            return api.list(setNgTableParams(pageParams,$scope.searchParams), {})
                .$promise.then(function (data) {
                    //后台js 返回数据判断; 0 成功,正确绑定数据; 非0 返回空
                    if(data.errno == 0 || data.errno == '0'){
                        pageParams.total(data.data.page.total);
                        pageParams.page(data.data.page.index);
                        return data.data.result;
                    }else{
                        return {};
                    }
                });
        }

        function setNgTableParams(pageParams,fileterParams){
            var result = {};
            for(var key in fileterParams){

                console.log(fileterParams[key].currentVal + ' -- ' + fileterParams[key].ignoreVal);
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
                    "accountName":      { "currentVal": "", "ignoreVal": "", "urlKeyName": "name"},
                    "accountMobile":    { "currentVal": "", "ignoreVal": "", "urlKeyName": "mobile"},
                    "accountIdCard":    { "currentVal": "", "ignoreVal": "", "urlKeyName": "idcard"},
                    "accountAuthState": { "currentVal": -1, "ignoreVal": -1, "urlKeyName": "auth_state"}
                    //"accountType":  { "currentVal":"", "defaultVal": "", "urlKeyName": "user_type"},
                    //"pageIndex":{ "name":"accountMobile",   "currentVal":"", "defaultVal": 1, "urlKeyName": "page_index"},
                    //"pageSize":{ "name":"accountMobile",   "currentVal":"", "defaultVal": 15, "urlKeyName": "page_size"}
                }
            };
        }

        //调用初始化函数
        $scope.searchParams = initSearchParams().filters;

        //commonModel.accountTypes().then(function(response){
        //    $scope.accountTypes = response.data;
        //    $scope.searchParams.accountType = $scope.accountTypes[0].id;
        //    $scope.accountTypeSelect = function () {
        //        console.log($scope.searchParams.accountType);
        //    };
        //});

        commonModel.accountAuthStates().then(function(response){
            $scope.accountAuthStates = response.data;
            //$scope.searchParams.accountAuthState.currentVal = $scope.accountAuthStates[0].id;
            $scope.accountAuthStateSelect = function () {
                console.log($scope.searchParams.accountAuthState.currentVal);
            };
        });

        $scope.search = function() {
            console.log('this is search ');
            $scope.createTable();
        };

        $scope.createTable();

        $scope.formatRowData = {
            accountAuthState: function(authState){
                cssClass = authState == 0 ? 'bg-warning' : authState == 1 ? 'bg-primary' : authState == 2 ? 'bg-success' : authState == 3 ? 'bg-danger' : 'bg-danger';
                return 'label ' + cssClass;
            }
        };

    }]);


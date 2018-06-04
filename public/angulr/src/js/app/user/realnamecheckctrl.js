app.controller("RealNameCheckCtrl", ['$scope', '$modal', 'NgTableParams', 'realNameModel','commonModel_realname',"$interpolate", "$sce",
    function ($scope, $modal, NgTableParams, realNameModel, commonModel_realname,$interpolate, $sce) {
        var self = this;

        commonModel_realname.userTypes().then(function(response){
            $scope.userTypes = response.data;
            $scope.userType = $scope.userTypes[0].id;
            $scope.userTypeSelect = function () {
                console.log($scope.userType);
            };
        });

        commonModel_realname.checkStateTypes().then(function(response){
            $scope.checkStateTypes = response.data;
            $scope.checkStateType = $scope.checkStateTypes[0].id;
            $scope.checkStateTypeSelect = function () {
                console.log($scope.checkStateType);
            };
        });

        $scope.open = function (size) {
            console.log('this is open ');
        };


        $scope.search = function() {
            // $scope.createTable();
        };


        self.cols = getColumns();

        $scope.createTable = function() {
            var pageSize = 15;
            self.tableParams2 = new NgTableParams({count: 10, page: 1}, {
                getData: function (params) {
                    //console.log($scope.usertype);
                    var data = realNameModel.list({
                        "page_index": params.page(),
                        "page_size": pageSize
                    }, {}).$promise.then(function (data) {
                            params.total(10);
                            params.page(1);
                            return data.data;
                        });
                    console.log(data);
                    return data;
                }
            });
        };

        $scope.createTable();

        //
        //var simpleList = [
        //    {realname:'lili', idcard: '12321', state:'12', reason: 'ssss'},
        //    {realname:'lili', idcard: '12321', state:'12', reason: 'ssss'},
        //    {realname:'lili', idcard: '12321', state:'12', reason: 'ssss'},
        //    {realname:'lili', idcard: '12321', state:'12', reason: 'ssss'}
        //
        //];
        //self.tableParams2 = new NgTableParams({count:10,page:1}, {
        //    data: simpleList
        //});

        function noFormatData($scope, row){
            var value = row[this.field];
            return value;
        }

        function htmlValue($scope, row) {
            var value = row[this.field];
            var html = "<a class='text-info' href='#app/user/detail?userid=" + value + "'>" + value + "</a>";
            return $sce.trustAsHtml(html);
        }

        function formatCheckStateHtml ($scope, row){
            var value = row[this.field];

            //实名认证状态 0:未认证 1:认证中 2:成功 3:失败
            var text = '';
            var css_class = '';

            switch (value) {
                case 0:
                    text = '未认证';
                    css_class = 'abel bg-warning';
                    break;
                case 1:
                    text = '认证中';
                    css_class = 'abel bg-primary';
                    break;
                case 2:
                    text = '认证通过';
                    css_class = 'abel bg-success';
                    break;
                case 3:
                    text = '认证失败';
                    css_class = 'abel bg-danger';
                    break;
                default :
                    text = '未知状态';
                    css_class = 'abel bg-danger';
                    break;
            };

            var html = "<span class='"+ css_class +"' title='"+ text +"'>"+ text+"</span>";
            return $sce.trustAsHtml(html);
        }

        function getColumns (){
            return [{
                field: "realname",
                title: "真实姓名",
                show: true,
                getValue: htmlValue,
                sortable: "realname"

            }, {
                field: "idcard",
                title: "验证信息",
                show: true,
                getValue: noFormatData,
                sortable: "idcard"

            }, {
                field: "state",
                title: "认证状态",
                show: true,
                getValue: formatCheckStateHtml,
                sortable: "state"

            },{
                field:"reason",
                title:"认证返回",
                show:true,
                getValue : noFormatData,
                sortable: "reason"
            }]
        };

    }]);

app.factory('realNameModel', ['$resource', function ($resource) {
    return $resource('/api/v1/userauth', {},
        {
            'query': {method: 'GET', isArray: false},
            'get': {method: 'POST', params: {'action': 'get'}},
            'list': {method: 'POST', params: {'action': 'list'}},
        }
    );
}]);

app.factory('commonModel_realname', ['$resource', function ($resource) {
    var  utils = {};
    utils.userTypes = function(params) {
        var userTypes = $resource('/api/v1/util/usertypes');
        return userTypes.get({},{}).$promise;
    };

    utils.checkStateTypes = function(params) {
        var checkStateTypes = $resource('/api/v1/util/checkstatetypes');
        return checkStateTypes.get({},{}).$promise;
    };

    return utils;
}]);


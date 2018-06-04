'use strict';

app.controller("PageSpeed", ['$scope', '$state', '$rootScope', '$modal', '$http', '$timeout', 'NgTableParams', 'toaster', 'PageModel', '$confirm',
    function ($scope, $state, $rootScope, $modal, $http, $timeout, NgTableParams, toaster, PageModel, $confirm) {
        var self = this;
        self.tableParams = null;

        self.showDetail = function (row) {
            var modalInstance = $modal.open({
                templateUrl: 'page.waterfall.html',
                controller: AddProjectCtrl,
                size: 'lg',
                keyboard : true,
                resolve: {
                    params: function () {
                        return {
                            row : row
                        }
                    }
                }
            });
        }

        var AddProjectCtrl = function ($scope, $http, $modalInstance, toaster, params) {
            $scope.data = {};
            $scope.data.url = '/harview?id=' + params.row.waterfall_id;
            $scope.ok = function () {
                $modalInstance.close(1);
            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            }
        };
        PageModel.getSpeeds().get({}, {}).$promise.then(function (response) {

            var points = response.data.points;
            $scope.chartConfig = {
                options: {
                    series: [],
                    colorAxis: {
                        dataClasses: [{
                            from: 0,
                            to: 1,
                            name: '100ms',
                            color: '#33ff00'
                        }, {
                            from: 1,
                            to: 2,
                            name: '200ms',
                            color: '#66ff00'
                        }, {
                            from: 2,
                            to: 3,
                            name: '300ms',
                            color: '#99ff00'
                        }, {
                            from: 3,
                            to: 6,
                            name: '600ms',
                            color: '#ccff00'
                        }, {
                            from: 6,
                            to: 10,
                            name: '1s',
                            color: '#ffff00'
                        }, {
                            from: 10,
                            to: 20,
                            name: '2s',
                            color: '#ffcc00'
                        }, {
                            from: 20,
                            to: 30,
                            name: '3s',
                            color: '#ff9900'
                        }, {
                            from: 30,
                            to: 40,
                            name: '4s',
                            color: '#ff6600'
                        }, {
                            from: 40,
                            to: 60,
                            name: '6s',
                            color: '#ff3300'
                        }, {
                            from: 60,
                            to: 80,
                            name: '8s',
                            color: '#ff2000'
                        }]
                    },
                    title: {
                        text: '页面加载速度map',
                        style: {
                            display: 'none'
                        }
                    },
                    tooltip: {
                        pointFormatter: function(){ //鼠标滑过后的tooltip展示
                            if (!this.parent) {
                                // return '<b>名称 : '+ this.name + '</b> ' +
                                //     '<br/><b>请求数 : ' + this.value + '</b> <br/>' +
                                //     '<b>拦截率 : ' + this.rate + '</b><br/>'
                            }else {
                                return '<b>资源名称 : '+ this.name + '</b> ' +
                                    '<br/><b>加载时间: ' + this.value2 + 'ms</b> <br/>';
                            }

                        }
                    }
                },
                series :[{
                    type: "treemap",
                    alternateStartingDirection: true,
                    events: {
                        click: function(event) {
                            var pageSize = 15;
                            var pData = {
                                "page_size": pageSize,
                                'project' : event.point.parent,
                                'module' : event.point.name
                            };
                            PageModel.getPageList().list(pData, {}).$promise.then(function (response) {
                                self.tableParams = new NgTableParams({count: pageSize, page: 1}, {data:response.data.results});
                            });
                        }
                    },
                    levels: [{
                        level: 1,
                        layoutAlgorithm: 'sliceAndDice',
                        dataLabels: {
                            enabled: true,
                            align: 'left',
                            verticalAlign: 'top',
                            borderWidth: 3,
                            style: {
                                fontSize: '22px',
                                fontWeight: 'bold'
                            }
                        }
                    },{
                        level: 2,
                        layoutAlgorithm: 'stripes',

                        dataLabels: {
                            enabled: true,
                            align: 'center',
                            verticalAlign: 'center',
                            borderWidth: 1,
                            style: {
                                fontSize: '10px',
                            },
                            formatter: function () {
                                return '<b>' + this.point.name + '</b> '
                            }

                        }
                    }],
                    data: points
                }]

            };
        });

    }]);
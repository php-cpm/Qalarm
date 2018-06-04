'use strict';

app.controller("QalarmGraphDetail", ['$scope', '$rootScope', '$stateParams',  '$modal', '$http', '$timeout', 'NgTableParams', 'toaster', 'QalarmModel', '$confirm',
    function ($scope, $rootScope, $stateParams, $modal, $http, $timeout, NgTableParams, toaster, QalarmModel, $confirm) {
        var self = this;
        var errChart,
            submoduleIdxs = {},
            fullscreenChart,
            fullSubmoduleIdxs = {},
            clicks = 0,
            ignore = [],
            startTime = (new Date()).getTime();
        var colors = ["#DDDF0D", "#55BF3B", "#DF5353", "#7798BF", "#aaeeee", "#ff0066", "#eeaaee", "#55BF3B", "#DF5353", "#7798BF", "#aaeeee"];
        var colorMap = {};
        var curColorIndex = 0;
        var _renderer,_svgNormalG,_svgFullG,_svgImg,_svgFullImg,_svgNormalImg;
        var pageData = {pname:$stateParams.project_name};
        self.tableParams = null;
        var series = {};
        var hideAll = false;

        $scope.showAllSeries = function (){
            if(hideAll){
                for(var i=0,len=series.length;i<len;i++){
                    series[i].show();
                }
                _svgNormalImg.attr({'href':'/img/eye.png'})
                hideAll = false;
            }else{
                for(var i=0,len=series.length;i<len;i++){
                    series[i].hide();
                }
                _svgNormalImg.attr({'href':'/img/eye-close.png'})
                hideAll = true;
            }
        }

        $scope.color = function (md5) {
            var r = md5.substr(0,2);
            var g = md5.substr(2,2);
            var b = md5.substr(4,2);
            r = parseInt(r,16);
            g = parseInt(g,16);
            b = parseInt(b,16);
            if (r < 127) {
                r = 255 - r;
                r = parseInt(r, 10).toString(16);
            } else {
                r = parseInt(r, 10).toString(16);
            }
            if (g < 127) {
                g = 255 - g;
                g = parseInt(g, 10).toString(16);
            } else {
                g = parseInt(g, 10).toString(16);
            }
            if (b < 127) {
                b = 255 - b;
                b = parseInt(b, 10).toString(16);
            } else {
                b = parseInt(b, 10).toString(16);
            }

            if (r.length < 2) {
                r = '0' + r;
            }
            if (g.length < 2) {
                g = '0' + g;
            }
            if (b.length < 2) {
                b = '0' + b;
            }
            var color = '#' + r + g + b;
            return color;
        }

        $scope.onload = function (){
            errChart = this;
            var pData = {
                'project_name' : pageData.pname
            }
            QalarmModel.getGraphDetails().get(pData, {}).$promise.then(function (response) {
                var points = response.data.points;
                pageData.points = points;

                var counter = 0;
                series = errChart.series;
                for (var t in pageData.points) {
                    var point = pageData.points[t];
                    for (var sm in point) {
                        if (sm == 'count') {
                            continue;
                        }
                        var lastPoint = {x:parseInt(t,10)||0, y:parseInt(point[sm],10)||0}
                        if (undefined === submoduleIdxs[sm]) {
                            if (t < startTime) {
                                startTime = t;
                            }
                            submoduleIdxs[sm] = errChart.series.length;
                            var md5 = hex_md5(sm);
                            errChart.addSeries({
                                id: sm,
                                name: sm,
                                data: [lastPoint],
                                color: $scope.color(md5),
                                events: {
                                    click: function(e) {
                                        var pageSize = 15;
                                        var pData = {
                                            "page_size": pageSize,
                                            'project' : pageData.pname,
                                            'module' : this.options.name
                                        };
                                        QalarmModel.getMessages().list(pData, {}).$promise.then(function (response) {
                                            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {data:response.data.items});
                                        });
                                    }
                                }
                            }, false);
                            errChart.series[submoduleIdxs[sm]].lastPoint = lastPoint;
                            //getNames(sm);
                        } else {
                            errChart.series[submoduleIdxs[sm]].lastPoint = lastPoint;
                            errChart.series[submoduleIdxs[sm]].addPoint(lastPoint, false);
                        }
                    }
                    if (++counter % 50 == 0) {
                        counter=0;
                        errChart.redraw();
                    }
                }

                var renderer = errChart.renderer;

                _svgNormalG = renderer.g('series-all')
                    .attr({
                        zIndex:100,
                        cursor: 'pointer',
                        transform:'translate(-1000,-1000)'
                    })
                    .add();
                renderer
                    .rect(0.5, 0.5, 85, 30, 5)
                    .attr({
                        fill:"none",
                        stroke:"#909090",
                        "stroke-width":1,
                        visibility:"visible"
                    }).add(_svgNormalG);
                _svgNormalImg = renderer.image('/img/eye.png', 8, 8, 16,16).add(_svgNormalG);
                renderer.text('show all', 27, 19).attr('fill','#A0A0A0').add(_svgNormalG);
               _svgNormalG.on('click', $scope.showAllSeries);

                // var socket = io.connect("http://localhost:10090");
                var socket = io.connect("http://qalarm.long.intra.ffan.com");
                socket.on("handle_error", $scope.handleError);
            });
        }

        $scope.handleError = function (data) {
            var t = (new Date()).getTime();
            if (data != null) {
                $.each(data, function(pname, detail) {
                    if(pname != pageData.pname) return;
                    for (var sm in detail) {
                        if(sm == 'count') continue;
                        var thisX = parseInt(t, 10)||0;
                        var lastPoint = {x:thisX, y:parseInt(detail[sm],10)||0};
                        if (undefined === submoduleIdxs[sm]) {
                            submoduleIdxs[sm] = errChart.series.length;
                            var md5 = hex_md5(sm);
                            var color = colorMap[md5];
                            if(!color){
                                color = colors[curColorIndex];
                                colorMap[md5]=color;
                                curColorIndex = (curColorIndex+1)%colors.length;
                            }
                            errChart.addSeries({
                                id: sm,
                                name: sm,
                                data: [lastPoint],
                                color: color,
                                events: {
                                    click: function(e) {
                                        var pageSize = 15;
                                        var pData = {
                                            "page_size": pageSize,
                                            'project' : pageData.pname,
                                            'module' : this.options.name
                                        };
                                        QalarmModel.getMessages().list(pData, {}).$promise.then(function (response) {
                                            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {data:response.data.items});
                                        });
                                    }
                                }
                            }, false);
                        } else {
                            try{
                                if (errChart.series === undefined ) {
                                    return;
                                }

                                var series = errChart.series[submoduleIdxs[sm]];
                                if (undefined === series.lastPoint) {
                                    if(series.points && series.points.length>0)
                                        series.lastPoint = series.points[series.points.length-1];
                                }
                                if (thisX - series.lastPoint.x > 20000){
                                    series.addPoint({x:series.lastPoint.x+3000, y:0}, false);
                                    series.addPoint({x:thisX-3000, y:0}, false);
                                }
                                series.lastPoint = lastPoint;
                                series.addPoint(lastPoint, false);
                            }catch(e){
                                console.log(e);
                                location.reload();
                            }
                        }

                    }
                });
                if (errChart.xAxis != undefined) {
                    if (t-startTime > 600000) {
                        errChart.xAxis[0].setExtremes(t-600000, t);
                    } else {
                        errChart.redraw();
                    }
                }
            }

        }

        Highcharts.setOptions({
            global: {
                timezoneOffset: -8 * 60  // +8 时区修正方法
            }
        });
        Highcharts.setOptions(Highcharts.theme);
        $scope.chartConfig = {
            options: {
                title: {
                    text: pageData.pname+'模块监控图',
                },
                chart: {
                    renderTo: "chart_container",
                    type: "spline",
                    events: {
                        load: $scope.onload,
                        redraw: function(){
                            var svg = this.renderer.box;

                            /translate\((\d*),(\d*)\)/.test($('.highcharts-legend').attr('transform'));
                            var tx = parseInt(RegExp.$1 || 0, 10);
                            var ty = parseInt(RegExp.$2 || 0, 10);

                            if(_svgNormalG){
                                _svgNormalG.attr({transform:'translate('+(tx-100)+','+ty+')'})
                            }
                        }
                    }
                },
                xAxis: {
                    useHTML:true,
                    type: "datetime",
                    tickInterval: 60*1000
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: '错误数'
                    },
                    labels: {
                        overflow: 'justify'
                    },
                    useHTML: true
                },
                legend: {
                    useHTML:true,
                },
                tooltip: {
                    formatter: function() {
                        var s = '<b>'+ (new Date(this.x)).toTimeString().replace(/.*(\d{2}:\d{2}:\d{2}).*/, "$1"); +'</b>';
                        $.each(this.points, function(i, point) {
                            s += '<br/>' + point.series.name + ': ' + point.y;
                        });
                        return s;
                    },
                    crosshairs: true,
                    shared: true
                },
                plotOptions: {
                    spline: {
                        lineWidth: 2,
                        marker: {
                            enabled: false
                        },
                        events: {
                            legendItemClick: function(e) {
                                clicks++;
                                var show = false;
                                var that = this;
                                var sm = this.options.id;
                                var form = document.createElement("form");
                                if (clicks == 1) {
                                    setTimeout(function(e) {
                                        if (clicks == 1) {
                                            //click 隐藏/显示曲线
                                            that.setVisible(!that.visible);
                                        } else {
                                            // $("#searchContainer").load('/graph/messages?module='+sm+' #searchContainer');
                                        }
                                        clicks = 0;
                                    }, 300);
                                }
                                return false;
                            }
                        }
                    }
                },
                reflow: true,
                series: []
            },
            series :[]

        };
    }]);
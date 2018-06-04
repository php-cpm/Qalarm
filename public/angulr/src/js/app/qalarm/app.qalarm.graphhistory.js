'use strict';

app.controller("QalarmGraphHistory", ['$scope', '$state', '$rootScope', '$modal', '$http', '$timeout', 'NgTableParams', 'toaster', 'QalarmModel', '$confirm',
    function ($scope, $state, $rootScope, $modal, $http, $timeout, NgTableParams, toaster, QalarmModel, $confirm) {

        var errChart,
            fullscreenChart,
            projectIdxs = {},
            fullProjectIdxs = {},
            projects,
            fullProjects,
            submodules = {},
            clicks = 0;

        var isMobile = 0;
        var isFullscreen = false;


        var _renderer,_svgNormalG,_svgFullG,_svgImg,_svgFullImg,_svgNormalImg;
        var hideAll = false;
        var startTime = (new Date()).getTime();

        $scope._color = function (md5) {
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

        $scope.onLoad = function () {
            errChart = this;
            QalarmModel.getGraphHistorys().get({}, {}).$promise.then(function (response) {
                var points = response.data.points;
                projects = errChart.series;
                for (var pname in points) {
                    var projectPoints = points[pname];
                    for (var t in projectPoints) {
                        // var lastPoint = {x:parseInt(t), y:parseInt(projectPoints[t]['count']), marker:{symbol: 'url(/images/alarm.png)'}, detail:projectPoints}
                        var lastPoint = {x:parseInt(t), y:parseInt(projectPoints[t]['count']), detail:projectPoints[t]}
                        if (undefined === projectIdxs[pname]) {
                            if (t < startTime) {
                                startTime = t;
                            }
                            projectIdxs[pname] = {id:projects.length, authorization:true};

                            var md5 = hex_md5(pname);
                            var color = $scope._color(md5);
                            errChart.addSeries({
                                id:projectIdxs[pname]['id'],
                                name:pname,
                                data:[lastPoint],
                                color: color,
                                events: {
                                    click: function(e) {
                                        // $state.go('app.qalarm.graphdetail', {project_name:this.options.name});
                                    }
                                }
                            });
                            projects[projectIdxs[pname]['id']].lastPoint = lastPoint;
                        } else {
                            projects[projectIdxs[pname]['id']].addPoint(lastPoint, false);
                            projects[projectIdxs[pname]['id']].lastPoint = lastPoint;
                        }
                    }
                }
                errChart.redraw();

                var renderer = errChart.renderer;
                _svgNormalG = renderer.g('series-all')
                    .attr({
                        zIndex:7,
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

            });
        }

        $scope.showAllSeries = function (){
            if(hideAll){
                for(var i=0,len=projects.length;i<len;i++){
                    projects[i].show();
                }
                _svgNormalImg.attr({'href':'/img/eye.png'})
                hideAll = false;
            }else{
                for(var i=0,len=projects.length;i<len;i++){
                    projects[i].hide();
                }
                _svgNormalImg.attr({'href':'/img/eye-close.png'})
                hideAll = true;
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
                    text: "7日监控汇总图"
                },
                chart: {
                    renderTo: "chart_container",
                    type: "spline",
                    events: {
                        load: $scope.onLoad,
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
                    type: "datetime",
                    tickInterval: 60*60*1000
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: '错误数'
                    },
                    labels: {
                        overflow: 'justify'
                    }
                },
                tooltip: {
                    useHTML: true,
                    formatter: function() {
                        return '<div>'
                            +'<p>项目:'+this.series.name+'</p>'
                            +'<p>错误数:'+this.point.y+'</p>'
                            +'<p>date:'+Highcharts.dateFormat('%Y-%m-%d %H:%M:%S',this.point.x)+'</p>'
                            +'</div>'
                    }
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
                                var that = this;
                                if (clicks == 1) {
                                    setTimeout(function(e) {
                                        if (clicks == 1) {
                                            //click 隐藏/显示曲线
                                            that.setVisible(!that.visible);
                                        } else {
                                            // $state.go('app.qalarm.graphdetail', {project_name:pname});
                                        }
                                        clicks = 0;
                                    }, 300);
                                }
                                return false;
                            }
                        }
                    }
                },
                series: []
            },
            series :[]

        };
    }]);
var GD = (function(){
	var errChart,
        submoduleIdxs = {},
        fullscreenChart,
        fullSubmoduleIdxs = {},
	    clicks = 0,
	    ignore = [],
	    startTime = (new Date()).getTime();
	var colors = ["#DDDF0D", "#55BF3B", "#DF5353", "#7798BF", "#aaeeee", "#ff0066", "#eeaaee",
      "#55BF3B", "#DF5353", "#7798BF", "#aaeeee"];
    var colorMap = {};
    var curColorIndex = 0;
    function getNames(ids) {
	    var test = [];
            $.ajax({
                url: "/?c=submodule&a=get_graphe_detail&format=json",
                type: "get",
                data: {"ids": Object.keys(submoduleIdxs).join(",")},
                success: function(ret) {
                    for (var key in ret.data) {
                        var rsm = ret.data[key];
                        test[submoduleIdxs[rsm.id]] = rsm.name;
                        //errChart.series[submoduleIdxs[rsm.id]].update({name: rsm.name});

                    }
                    for(var i=0,len = test.length;i<len;i++){
                        errChart.series[i].update({name:test[i]});
                    }
                    errChart.redraw();
                }
            });
    };

    //数据加载
    function loadFun(){
        var counter = 0;
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
                    submoduleIdxs[sm] = this.series.length;
                    var md5 = hex_md5(sm);
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
                    this.addSeries({
                        id: sm, 
                        name: sm,
                        data: [lastPoint],
                        color: color,
                        events: {
                            click: function(e) {
                                        $.get('/graph/messages?module='+this.options.name+'&project='+pageData.pname, function(result) {
                                            $result = $(result);
                                            $('#searchContainer').empty();
                                            $result.find('#detail_content').appendTo('#searchContainer');
                                            $result.find('script').appendTo('#searchContainer');
                                        });
                               // $("#searchContainer").load('/graph/messages?module='+this.options.name+'&project='+pageData.pname+' #searchContainer');
                            }
                        }
                    }, false);
                    this.series[submoduleIdxs[sm]].lastPoint = lastPoint;
                    //getNames(sm);
                } else {
                    this.series[submoduleIdxs[sm]].lastPoint = lastPoint;
                    this.series[submoduleIdxs[sm]].addPoint(lastPoint, false);
                }
            }
            if (++counter % 50 == 0) {
                counter=0;
                this.redraw();
            }

        }
        // getNames(Object.keys(submoduleIdxs).join(","));

        var socket = io.connect();
        var that = this;
        var detailArr = new Array();
        socket.on("handle_error", function (data) {
            var t = (new Date()).getTime();
            if (data != null) {
                $.each(data, function(pname, detail) {
                    if(pname != pageData.pname) return;
                    for (var sm in detail) {
                        if(sm == 'count') continue;
                        var thisX = parseInt(t, 10)||0;
                        var lastPoint = {x:thisX, y:parseInt(detail[sm],10)||0};
                        if (undefined === submoduleIdxs[sm]) {
                            submoduleIdxs[sm] = that.series.length;
                            var md5 = hex_md5(sm);
                            var color = colorMap[md5];
                            if(!color){
                                color = colors[curColorIndex];
                                colorMap[md5]=color;
                                curColorIndex = (curColorIndex+1)%colors.length;
                            }
                            that.addSeries({
                                id: sm, 
                                name: sm,
                                data: [lastPoint],
                                color: color,
                                events: {
                                    click: function(e) {
                                        $.get('/graph/messages?module='+this.options.name+'&project='+pageData.pname, function(result) {
                                            $result = $(result);
                                            $('#searchContainer').empty();
                                            $result.find('#detail_content').appendTo('#searchContainer');
                                            $result.find('script').appendTo('#searchContainer');
                                        });
                                        // $("#searchContainer").load('/graph/messages?module='+this.options.name+'&project='+pageData.pname+' #searchContainer');
                                    }
                                }
                            }, false);
                            //that.series[submoduleIdxs[sm]].lastPoint = lastPoint;
                            // getNames(sm);
                        } else {
                            try{
                                var series = that.series[submoduleIdxs[sm]];
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
                                location.reload();
                            }
                        }
                        
                    }
                });
                if (t-startTime > 600000) {
                    that.xAxis[0].setExtremes(t-600000, t);
                } else {
                    that.redraw();
                }
            }
        });
    }

    function fullscreenGetNames(ids) {
        var test = [];
            $.ajax({
                url: "/?c=submodule&a=get_graphe_detail&format=json",
                type: "get",
                data: {"ids": Object.keys(fullSubmoduleIdxs).join(",")},
                success: function(ret) {
                    for (var key in ret.data) {
                        var rsm = ret.data[key];
                        test[fullSubmoduleIdxs[rsm.id]] = rsm.name;
                        //errChart.series[submoduleIdxs[rsm.id]].update({name: rsm.name});
                    }
                    for(var i=0,len = test.length;i<len;i++){
                        fullscreenChart.series[i].update({name:test[i]});
                    }
                    fullscreenChart.redraw();
                }
            });
    };

    function fullscreenLoadFun(){
        var counter = 0;
        for (var t in pageData.points) {
            var point = pageData.points[t];
            for (var sm in point) {
                if (sm == 'count') {
                    continue;
                }
                var fullLastPoint = {x:parseInt(t,10)||0, y:parseInt(point[sm],10)||0}
                if (undefined === fullSubmoduleIdxs[sm]) {
                    if (t < startTime) {
                        startTime = t;
                    }
                    fullSubmoduleIdxs[sm] = this.series.length;
                    var md5 = hex_md5(sm);
                    var color = '#' + md5.substr(0,6);
                    this.addSeries({
                        id: sm, 
                       // name: "unknow_error",
                        data: [fullLastPoint],
                        color: color,
                        events: {
                            click: function(e) {
                                //window.open('/?c=submodule&a=get_detail&sub_mid='+this.options.id, '_blank');
                                $("#searchContainer").load('/?c=submodule&a=get_detail&sub_mid='+this.options.id+' #searchContainer');
                            }
                        }
                    }, false);
                    this.series[fullSubmoduleIdxs[sm]].fullLastPoint = fullLastPoint;
                    //getNames(sm);
                } else {
                    this.series[fullSubmoduleIdxs[sm]].fullLastPoint = fullLastPoint;
                    this.series[fullSubmoduleIdxs[sm]].addPoint(fullLastPoint, false);
                }
            }
            if (++counter % 50 == 0) {
                counter=0;
                this.redraw();
            }

        }
        fullscreenGetNames(Object.keys(fullSubmoduleIdxs).join(","));

        var socket = io.connect();
        var that = this;
        var detailArr = new Array();
        socket.on("handle_error", function (data) {
            var t = (new Date()).getTime();
            if (data != null) {
                $.each(data, function(pname, detail) {
                    if(pname != pageData.pname) return;
                    for (var sm in detail) {
                        if(sm == 'count') continue;
                        
                        var thisX = parseInt(t, 10)||0;
                        var fullLastPoint = {x:thisX, y:parseInt(detail[sm],10)||0};
                        if (undefined === fullSubmoduleIdxs[sm]) {
                            fullSubmoduleIdxs[sm] = that.series.length;
                            var md5 = hex_md5(sm);
                            var color = colorMap[md5];
                            if(!color){
                                color = colors[curColorIndex];
                                colorMap[md5]=color;
                                curColorIndex = (curColorIndex+1)%colors.length;
                            }
                            that.addSeries({
                                id: sm, 
                               // name: "unknow_error",
                                data: [fullLastPoint],
                                color: color,
                                events: {
                                    click: function(e) {
                                        //window.open('/?c=submodule&a=get_detail&sub_mid='+this.options.id, '_blank');
                                        $("#searchContainer").load('/?c=submodule&a=get_detail&sub_mid='+this.options.id+' #searchContainer');
                                    }
                                }
                            }, false);
                            //that.series[submoduleIdxs[sm]].lastPoint = lastPoint;
                            fullscreenGetNames(sm);
                        } else {
                            try{
                                var series = that.series[fullSubmoduleIdxs[sm]];
                                if (undefined === series.fullLastPoint) {
                                    if(series.points && series.points.length>0)
                                        series.fullLastPoint = series.points[series.points.length-1];
                                } 
                                if (thisX - series.fullLastPoint.x > 20000){
                                    series.addPoint({x:series.fullLastPoint.x+3000, y:0}, false);
                                    series.addPoint({x:thisX-3000, y:0}, false);
                                }
                                series.fullLastPoint = fullLastPoint;
                                series.addPoint(fullLastPoint, false);
                            }catch(e){
                                location.reload();
                            }
                        }
                        
                    }
                    
                });
                if (t-startTime > 600000) {
                    that.xAxis[0].setExtremes(t-600000, t);
                } else {
                    that.redraw();
                }
            }
        });
    }

	function initEvent(){
        //触发全屏模式
		$('#fs').click(function(){
            $('#fullscreenchart_container').show();
			var elem = $('#fullscreenchart_container')[0];
			if (elem.requestFullscreen) {
			  elem.requestFullscreen();
			} else if (elem.webkitRequestFullscreen) {
			  elem.webkitRequestFullscreen();
			}else if (elem.mozRequestFullScreen) {
			  elem.mozRequestFullScreen();
			}
	    });

	    $(document).on('fullscreenchange webkitfullscreenchange mozfullscreenchange', function(){
        	if (!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement){
                errChart.setSize(null,400);
                $('#fullscreenchart_container').hide();
	    	}
	    });
	}
    
	function init(){
        Highcharts.setOptions({
            global: {
                timezoneOffset: -8 * 60  // +8 时区修正方法
            }
        });
        Highcharts.setOptions(Highcharts.theme);
        errChart = new Highcharts.Chart({
            title: {
                text: pageData.pname+'错误监控图',
            },
            chart: {
                renderTo: "chart_container",
                type: "spline",
                events: {
                    load: loadFun
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
            series: [],
        });

        return;
        //全屏模式下的图表
        Highcharts.setOptions(Highcharts.tcountheme_fullscreen);
        fullscreenChart = new Highcharts.Chart({
            title: {
                text: pageData.pname+'错误监控图',
            },
            chart: {
                renderTo: "fullscreenchart_container",
                type: "spline",
                events: {
                    load: fullscreenLoadFun
                }
            },
            xAxis: {
                type: "datetime",
                tickInterval: 60*1000,
                useHTML:true
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
                    lineWidth: 3,
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
                                        $("#searchContainer").load('/?c=submodule&a=get_detail&sub_mid='+that.options.id+' #searchContainer');
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
            series: [],
        })

		initEvent();
	}
	return {
		init: init
	}
})();

$(document).ready(function(){
	GD.init();
});

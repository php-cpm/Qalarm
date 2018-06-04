var Monitor = (function(){
	var errChart,
		fullscreenChart,
		projectIdxs = {},
		fullProjectIdxs = {},
		projects,
		fullProjects,
		submodules = {},
		clicks = 0;

	var isFullscreen = false;

	var _renderer,_svgNormalG,_svgFullG,_svgImg,_svgFullImg;
	var hideAll = false;
    var startTime = (new Date()).getTime();

    function _color(md5) {
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

    function handleError(data){
    	var t = (new Date()).getTime();
		if (data != null) {
			$.each(data, function(pname, detail) {
				var thisX = parseInt(t);
				// var lastPoint = {x:thisX, y:parseInt(detail.count),marker:{symbol: 'url(/images/alarm.png)'},  detail:detail};
				var lastPoint = {x:thisX, y:parseInt(detail.count), detail:detail};
				if (undefined === projectIdxs[pname]){
					//add data and series
					projectIdxs[pname] = {id:projects.length, auth:false};
					$.ajax({
						url: "/graph/checkauth",
						type: "get",
						dataType: "json",
						data: {"type":"project", "format":"json", "pname":pname},
                        success: function(data) {
                            if (data.data.authorization > 0) {
                                projectIdxs[pname] = {id:projects.length, authorization:true};
                                md5 = hex_md5(pname);
                                var color = _color(md5);
                                errChart.addSeries({
                                    id:projectIdxs[pname]['id'], 
									name:pname, 
									data:[lastPoint],
                                    color: color,
									events: {
										click: function(e) {
                                                   if(isMobile)
                                    window.location.href='/graph/detail?project_name='+this.name;
                                                   else
                                    window.open('/graph/detail?project_name='+this.name, '_blank');
										}
									}
								});
								projects[projectIdxs[pname]['id']].lastPoint = lastPoint;
							}
						}
					});
				} else if (projectIdxs[pname]['authorization']) {
					//add data
					if (thisX - projects[projectIdxs[pname]['id']].lastPoint.x > 20000) {
						projects[projectIdxs[pname]['id']].addPoint({x:projects[projectIdxs[pname]['id']].lastPoint.x+3000, y:0}, false);
						projects[projectIdxs[pname]['id']].addPoint({x:thisX-3000, y:0}, false);
					}
					projects[projectIdxs[pname]['id']].lastPoint = lastPoint;
					projects[projectIdxs[pname]['id']].addPoint(lastPoint, false);
				}
			});
		}

		if (t-startTime > 600000) {
			errChart.xAxis[0].setExtremes(t-600000, t);
		} else {
			errChart.redraw();
		}
    }
	function onLoad(){
        projects = this.series;
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

                    md5 = hex_md5(pname);
                    var color = _color(md5);
                    this.addSeries({
                        id:projectIdxs[pname]['id'], 
                        name:pname, 
                        data:[lastPoint],
                        color: color,
                        events: {
                            click: function(e) {
                                 if(isMobile)
                                      window.location.href='/graph/detail?project_name='+this.name;
                                 else
                                      window.open('/graph/detail?project_name='+this.name, '_blank');
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
        this.redraw();

		renderer = this.renderer;
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
        _svgNormalImg = renderer.image('/images/eye.png', 8, 8, 16,16).add(_svgNormalG);
        renderer.text('show all', 27, 19).attr('fill','#A0A0A0').add(_svgNormalG);
        _svgNormalG.on('click', showAllSeries);

		var socket = io.connect();
		socket.on("handle_error", handleError);
	}
	function showAllSeries(){
		if(hideAll){
			for(var i=0,len=projects.length;i<len;i++){
				projects[i].show();
			}
			_svgNormalImg.attr({'href':'/images/eye.png'})
			hideAll = false;
		}else{
			for(var i=0,len=projects.length;i<len;i++){
				projects[i].hide();
			}
			_svgNormalImg.attr({'href':'/images/eye-close.png'})
			hideAll = true;
		}
	}
	function fullHandleError(data){
    	var t = (new Date()).getTime();
		if (data != null) {
			$.each(data, function(pname, detail) {
				var thisX = parseInt(t);
				var lastPoint = {x:thisX, y:parseInt(detail.count), detail:detail};
				if (undefined === fullProjectIdxs[pname]){
					//add data and series
					fullProjectIdxs[pname] = {id:fullProjects.length, auth:false};
					$.ajax({
						url: "/?c=default&a=check_auth",
						type: "get",
						dataType: "json",
						data: {"type":"project", "format":"json", "pname":pname},
						success: function(data) {
							if (data.data.auth > 0) {
								fullProjectIdxs[pname] = {id:fullProjects.length, auth:true};
                                md5 = hex_md5(pname);
                                var color = _color(md5);
								fullscreenChart.addSeries({
									id:fullProjectIdxs[pname]['id'], 
									name:pname, 
									data:[lastPoint],
                                    color: color,
									events: {
										click: function(e) {
                                            if(isMobile)
                                                window.location.href='/?c=project&a=graphe_detail&project_name='+this.name;
                                            else
                                                window.open('/?c=project&a=graphe_detail&project_name='+this.name, '_blank');
                                            
										}
									}
								});
								fullProjects[fullProjectIdxs[pname]['id']].lastPoint = lastPoint;
							}
						}
					});
				} else if (fullProjectIdxs[pname]['auth']) {
					//add data
					if (thisX - fullProjects[fullProjectIdxs[pname]['id']].lastPoint.x > 20000) {
						fullProjects[fullProjectIdxs[pname]['id']].addPoint({x:fullProjects[fullProjectIdxs[pname]['id']].lastPoint.x+3000, y:0}, false);
						fullProjects[fullProjectIdxs[pname]['id']].addPoint({x:thisX-3000, y:0}, false);
					}
					fullProjects[fullProjectIdxs[pname]['id']].lastPoint = lastPoint;
					fullProjects[fullProjectIdxs[pname]['id']].addPoint(lastPoint, false);
				}
			});
		}

		if (t-startTime > 600000) {
			fullscreenChart.xAxis[0].setExtremes(t-600000, t);
		} else {
			fullscreenChart.redraw();
		}
    }

	function fullOnLoad(){
        fullProjects = this.series;
		for (var pname in points) {
            var point = points[pname];
			for (var sm in point) {
				var lastPoint = {x:parseInt(sm), y:parseInt(point[sm]['count']), detail:point}
				if (undefined === fullProjectIdxs[pname]) {
                    if (sm < startTime) {
                        startTime = sm;
                    }
                    fullProjectIdxs[pname] = {id:fullProjects.length, auth:true};
                    md5 = hex_md5(pname);
                    var color = _color(md5);
                    this.addSeries({
                        id:fullProjectIdxs[pname]['id'], 
                        name:pname, 
                        data:[lastPoint],
                        color: color,
                        events: {
                            click: function(e) {
                                if(isMobile)
                                    window.location.href='/?c=project&a=graphe_detail&project_name='+this.name;
                                else
                                    window.open('/?c=project&a=graphe_detail&project_name='+this.name, '_blank');
                                
                            }
                        }
                    });
                    fullProjects[fullProjectIdxs[pname]['id']].lastPoint = lastPoint;
				} else {
					fullProjects[fullProjectIdxs[pname]['id']].addPoint(lastPoint, false);
                    fullProjects[fullProjectIdxs[pname]['id']].lastPoint = lastPoint;
				}
			}
		}

		var socket = io.connect();
		socket.on("handle_error", fullHandleError);
	}

	function addFullShowAllBtn(renderer){
		_svgFullG = renderer.g('full-series-all')
			.attr({
        		zIndex:7,
        		cursor: 'pointer',
        		transform:'translate(-1000,-1000)'
        	})
        	.add();
        renderer
        	.rect(0.5, 0.5, 85, 35, 5)
            .attr({
                fill:"none",
                stroke:"#909090",
                "stroke-width":1,
                visibility:"visible"
            }).add(_svgFullG);
        _svgFullImg = renderer.image('/images/eye.png', 8, 5, 16,22).add(_svgFullG);
        renderer.text('show all', 27, 21).attr('fill','#A0A0A0').add(_svgFullG);
        _svgFullG.on('click', fullShowAllSeries);
	}
	
	function fullShowAllSeries(){
		if(hideAll){
			for(var i=0,len=fullProjects.length;i<len;i++){
				fullProjects[i].show();
			}
			_svgFullImg.attr({'href':'/images/eye.png'})
			hideAll = false;
		}else{
			for(var i=0,len=fullProjects.length;i<len;i++){
				fullProjects[i].hide();
			}
			_svgFullImg.attr({'href':'/images/eye-close.png'})
			hideAll = true;
		}
	}
	
	function initEvent(){
        //触发全屏模式
		$('#fs').click(function(){
            $('#fullscreenchart_container').show();
			var elem = $('#fullscreenchart_container')[0];
			if (elem.requestFullscreen) {
			  elem.requestFullscreen();

			  isFullscreen = true;
			} else if (elem.webkitRequestFullscreen) {
			  elem.webkitRequestFullscreen();
			  $('#fullscreenchart_container .highcharts-legend').attr('class', 'full-highcharts-legend');
			  isFullscreen = true;
			}else if (elem.mozRequestFullScreen) {
			  elem.mozRequestFullScreen();
			  isFullscreen = true;
			}
	    });

	    $(document).on('fullscreenchange webkitfullscreenchange mozfullscreenchange', function(){
        	if (!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement){
                errChart.setSize(null,550);
                $('#fullscreenchart_container').hide();
                isFullscreen = false;
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
				text: "错误监控图"
			},
			chart: {
				renderTo: "chart_container",
				type: "spline",
				events: {
					load: onLoad,
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
				tickInterval: 60*1000
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
                                        if(isMobile)
                                            window.location.href='/graph/detail?project_name='+that.name;
                                        else
                                            window.open('/graph/detail?project_name='+that.name, '_blank');
                                        
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
		});

        return;

		//全屏模式下的图表
        Highcharts.setOptions(Highcharts.theme_fullscreen);
		fullscreenChart = new Highcharts.Chart({
			title: {
				text: "错误监控图"
			},
			chart: {
				renderTo: "fullscreenchart_container",
				type: "spline",
				events: {
					load: fullOnLoad,
					redraw: function(){
						if(!_svgFullG){
							addFullShowAllBtn(this.renderer);
						}
						var svg = this.renderer.box;
						
						/translate\((\d*),(\d*)\)/.test($('.full-highcharts-legend').attr('transform'));
						var tx = parseInt(RegExp.$1 || 0, 10);
						var ty = parseInt(RegExp.$2 || 0, 10);

						if(_svgFullG){
							_svgFullG.attr({transform:'translate('+(tx-100)+','+ty+')'})
						}
					}	
				}
			},
			xAxis: {
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
				}
			},
			tooltip: {
				useHTML: true,
				formatter: function() {
					return '<div>'
						+'<p>项目:'+this.series.name+'</p>'
						+'<p>错误数:'+this.point.y+'</p>'
						+'</div>'
				}
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
                            var that = this;
                            if (clicks == 1) {
                                setTimeout(function(e) {
                                    if (clicks == 1) {
                                        //click 隐藏/显示曲线
                                        that.setVisible(!that.visible);
                                    } else {
                                        if(isMobile)
                                            window.location.href='/?c=project&a=graphe_detail&project_name='+that.name;
                                        else
                                            window.open('/?c=project&a=graphe_detail&project_name='+that.name, '_blank');
                                        
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
		});
		initEvent();
	}

	return{
		init: init,
		hideAll: hideAll
	}
})();
$(Monitor.init);

Highcharts.theme_fullscreen = {
	chart: {
	  backgroundColor: {
	     linearGradient: { x1: 0, y1: 0, x2: 1, y2: 1 },
	     stops: [
	        [0, 'rgb(48, 48, 96)'],
	        [1, 'rgb(0, 0, 0)']
	     ]
	  },
	  className: 'dark-container',
	  plotBackgroundColor: 'rgba(255, 255, 255, .1)',
	  plotBorderColor: '#CCCCCC',
	  plotBorderWidth: 1,
	  marginBottom: 140
	},
	title: {
	  style: {
	     color: '#A0A0A0',
	     fontFamily:'MicroSoft YaHei',
	     fontWeight: 'bold',
	     fontSize: '36px'
	  },
	  y: 37
	},
	subtitle: {
	  style: {
	     color: '#666666',
	     fontFamily: 'MicroSoft YaHei'
	  }
	},
	xAxis: {
	  gridLineColor: '#999',
	  gridLineWidth: 1,
	  labels: {
	     style: {
	        color: '#fff',
	        fontSize: '18px'
	     },
	     y: 20
	  },
	  lineColor: '#A0A0A0',
	  tickColor: '#A0A0A0',
	  title: {
	     style: {
	        color: '#CCC',
	        fontSize: '18px',
	        fontFamily: 'MicroSoft YaHei'

	     }
	  }
	},
	yAxis: {
	  gridLineColor: '#333333',
	  labels: {
	     style: {
	        color: '#fff',
	        fontSize: '18px'
	     }
	  },
	  lineColor: '#A0A0A0',
	  minorTickInterval: null,
	  tickColor: '#A0A0A0',
	  tickWidth: 5,
	  title: {
	     style: {
	        color: '#CCC',
	        fontSize: '24px',
	        fontFamily: 'MicroSoft YaHei',
            fontWeight: 400
	     }
	  }
	},
	tooltip: {
	  backgroundColor: 'rgba(0, 0, 0, 0.75)',
	  style: {
	     color: '#F0F0F0',
	     fontSize: '16px'
	  }
	},
	toolbar: {
	  itemStyle: {
	     color: 'silver'
	  }
	},
	plotOptions: {
	  spline: {
	    marker: {
	        lineColor: '#333'
	    },

	    lineWidth: 2
	  }
	},
	legend: {
	  itemStyle: {
	     fontFamily: 'MicroSoft YaHei',
	     color: '#A0A0A0',
	     fontSize: '18px'
	  },
	  itemHoverStyle: {
	     color: '#FFF'
	  },
	  itemHiddenStyle: {
	     color: '#444'
	  },
	  itemDistance: 5
	},
	credits: {
	  style: {
	     color: '#666'
	  }
	},
	labels: {
	  style: {
	     color: '#CCC'
	  }
	},
	navigation: {
	  buttonOptions: {
	     symbolStroke: '#DDDDDD',
	     hoverSymbolStroke: '#FFFFFF',
	     theme: {
	        fill: {
	           linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
	           stops: [
	              [0.4, '#606060'],
	              [0.6, '#333333']
	           ]
	        },
	        stroke: '#000000'
	     }
	  }
	},
	// scroll charts
	rangeSelector: {
	  buttonTheme: {
	     fill: {
	        linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
	        stops: [
	           [0.4, '#888'],
	           [0.6, '#555']
	        ]
	     },
	     stroke: '#000000',
	     style: {
	        color: '#CCC',
	     },
	     states: {
	        hover: {
	           fill: {
	              linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
	              stops: [
	                 [0.4, '#BBB'],
	                 [0.6, '#888']
	              ]
	           },
	           stroke: '#000000',
	           style: {
	              color: 'white'
	           }
	        },
	        select: {
	           fill: {
	              linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
	              stops: [
	                 [0.1, '#000'],
	                 [0.3, '#333']
	              ]
	           },
	           stroke: '#000000',
	           style: {
	              color: 'yellow'
	           }
	        }
	     }
	  },
	  inputStyle: {
	     backgroundColor: '#333',
	     color: 'silver'
	  },
	  labelStyle: {
	     color: 'silver'
	  }
	},
	navigator: {
	  handles: {
	     backgroundColor: '#666',
	     borderColor: '#AAA'
	  },
	  outlineColor: '#CCC',
	  maskFill: 'rgba(16, 16, 16, 0.5)',
	  series: {
	     color: '#7798BF',
	     lineColor: '#A6C7ED'
	  }
	},
	scrollbar: {
	  barBackgroundColor: {
	        linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
	        stops: [
	           [0.4, '#888'],
	           [0.6, '#555']
	        ]
	     },
	  barBorderColor: '#CCC',
	  buttonArrowColor: '#CCC',
	  buttonBackgroundColor: {
	        linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
	        stops: [
	           [0.4, '#888'],
	           [0.6, '#555']
	        ]
	     },
	  buttonBorderColor: '#CCC',
	  rifleColor: '#FFF',
	  trackBackgroundColor: {
	     linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
	     stops: [
	        [0, '#000'],
	        [1, '#333']
	     ]
	  },
	  trackBorderColor: '#666'
	},

	// special colors for some of the
	legendBackgroundColor: 'rgba(0, 0, 0, 0.5)',
	legendBackgroundColorSolid: 'rgb(35, 35, 70)',
	dataLabelsColor: '#444',
	textColor: '#C0C0C0',
	maskColor: 'rgba(255,255,255,0.3)',
	global: { 
	    useUTC: false 
	} 
};

Highcharts.theme = {
	chart: {
	  backgroundColor: {
	     linearGradient: { x1: 0, y1: 0, x2: 1, y2: 1 },
	     stops: [
	        [0, 'rgb(48, 48, 96)'],
	        [1, 'rgb(0, 0, 0)']
	     ]
	  },
	  className: 'dark-container',
	  plotBackgroundColor: 'rgba(255, 255, 255, .1)',
	  plotBorderColor: '#CCCCCC',
	  plotBorderWidth: 1,
	  spacingBottom: 30
	},
	title: {
	  style: {
	     color: '#C0C0C0',
	     fontFamily:'MicroSoft YaHei',
	     fontSize: '18px'
	  },
	  y: 15
	},
	subtitle: {
	  style: {
	     color: '#666666',
	     fontFamily: 'MicroSoft YaHei'
	  }
	},
	xAxis: {
	  gridLineColor: '#999',
	  gridLineWidth: 1,
	  labels: {
	     style: {
	        color: '#fff',
	        fontSize: '12px',
	        marginTop: '5px'
	     },
	     y: 20
	  },
	  lineColor: '#A0A0A0',
	  tickColor: '#A0A0A0',
	  title: {
	     style: {
	        color: '#CCC',
	        fontSize: '18px',
	        fontFamily: 'MicroSoft YaHei'

	     }
	  }
	},
	yAxis: {
	  gridLineColor: '#333333',
	  labels: {
	     style: {
	        color: '#fff',
	        fontSize: '12px'
	     }
	  },
	  lineColor: '#A0A0A0',
	  minorTickInterval: null,
	  tickColor: '#A0A0A0',
	  tickWidth: 5,
	  title: {
	     style: {
	        color: '#CCC',
	        fontSize: '14px',
	        fontFamily: 'MicroSoft YaHei',
            fontWeight: 400
	     }
	  }
	},
	tooltip: {
	  backgroundColor: 'rgba(0, 0, 0, 0.75)',
	  style: {
	     color: '#F0F0F0'
	  }
	},
	toolbar: {
	  itemStyle: {
	     color: 'silver'
	  }
	},
	plotOptions: {
	  spline: {
	    marker: {
	        lineColor: '#333'
	    },
	    lineWidth: 2
	  }
	},
	legend: {
	  itemStyle: {
	     fontFamily: 'MicroSoft YaHei',
	     color: '#A0A0A0',
	     fontSize: '14px'
	  },
	  itemHoverStyle: {
	     color: '#FFF'
	  },
	  itemHiddenStyle: {
	     color: '#444'
	  },
	  itemDistance: 5,
	  style: {
	  	height: '50px',
	  	fontSize: '15px'
	  }
	},
	credits: {
	  style: {
	     color: '#666'
	  }
	},
	labels: {
	  style: {
	     color: '#CCC'
	  }
	},
	navigation: {
	  buttonOptions: {
	     symbolStroke: '#DDDDDD',
	     hoverSymbolStroke: '#FFFFFF',
	     theme: {
	        fill: {
	           linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
	           stops: [
	              [0.4, '#606060'],
	              [0.6, '#333333']
	           ]
	        },
	        stroke: '#000000'
	     }
	  }
	},
	// scroll charts
	rangeSelector: {
	  buttonTheme: {
	     fill: {
	        linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
	        stops: [
	           [0.4, '#888'],
	           [0.6, '#555']
	        ]
	     },
	     stroke: '#000000',
	     style: {
	        color: '#CCC',
	     },
	     states: {
	        hover: {
	           fill: {
	              linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
	              stops: [
	                 [0.4, '#BBB'],
	                 [0.6, '#888']
	              ]
	           },
	           stroke: '#000000',
	           style: {
	              color: 'white'
	           }
	        },
	        select: {
	           fill: {
	              linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
	              stops: [
	                 [0.1, '#000'],
	                 [0.3, '#333']
	              ]
	           },
	           stroke: '#000000',
	           style: {
	              color: 'yellow'
	           }
	        }
	     }
	  },
	  inputStyle: {
	     backgroundColor: '#333',
	     color: 'silver'
	  },
	  labelStyle: {
	     color: 'silver'
	  }
	},
	navigator: {
	  handles: {
	     backgroundColor: '#666',
	     borderColor: '#AAA'
	  },
	  outlineColor: '#CCC',
	  maskFill: 'rgba(16, 16, 16, 0.5)',
	  series: {
	     color: '#7798BF',
	     lineColor: '#A6C7ED'
	  }
	},
	scrollbar: {
	  barBackgroundColor: {
	        linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
	        stops: [
	           [0.4, '#888'],
	           [0.6, '#555']
	        ]
	     },
	  barBorderColor: '#CCC',
	  buttonArrowColor: '#CCC',
	  buttonBackgroundColor: {
	        linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
	        stops: [
	           [0.4, '#888'],
	           [0.6, '#555']
	        ]
	     },
	  buttonBorderColor: '#CCC',
	  rifleColor: '#FFF',
	  trackBackgroundColor: {
	     linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
	     stops: [
	        [0, '#000'],
	        [1, '#333']
	     ]
	  },
	  trackBorderColor: '#666'
	},
	// special colors for some of the
	legendBackgroundColor: 'rgba(0, 0, 0, 0.5)',
	legendBackgroundColorSolid: 'rgb(35, 35, 70)',
	dataLabelsColor: '#444',
	textColor: '#C0C0C0',
	maskColor: 'rgba(255,255,255,0.3)',
	global: { 
	    useUTC: true
	} 
};
//Highcharts.setOptions(Highcharts.theme);

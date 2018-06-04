module.exports = {
	less: {
        files: {
          'src/css/app.css': [
            'src/css/less/app.less'
          ]
        },
        options: {
          compile: true
        }
    },
    angular: {
        files: {
            'angular/css/app.min.css': [
                'bower_components/bootstrap/dist/css/bootstrap.css',
                'bower_components/animate.css/animate.css',
                'bower_components/font-awesome/css/font-awesome.css',
                'bower_components/simple-line-icons/css/simple-line-icons.css',
                'bower_components/ng-table/dist/ng-table.min.css',
                'bower_components/bootstrap-daterangepicker/daterangepicker-bs3.css',
                'bower_components/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css',
                'bower_components/chosen/chosen.css',
                'bower_components/angularjs-toaster/toaster.css',
                'bower_components/jsoneditor/dist/jsoneditor.min.css',
                'bower_components/ngImgCrop/compile/minified/ng-img-crop.css',
                'bower_components/codemirror/lib/codemirror.css',
                'bower_components/codemirror/theme/monokai.css',
                'src/css/*.css'
            ]
        },
        options: {
            compress: true
        }
    },
    html: {
        files: {
            'html/css/app.min.css': [
                'bower_components/bootstrap/dist/css/bootstrap.css',
                'bower_components/animate.css/animate.css',
                'bower_components/font-awesome/css/font-awesome.css',
                'bower_components/simple-line-icons/css/simple-line-icons.css',
                'bower_components/ng-grid/ng-grid.bootstrap.css',
                'bower_components/ng-grid/ng-grid.min.css',
                'src/css/*.css'
            ]
        },
        options: {
            compress: true
        }
    }
}

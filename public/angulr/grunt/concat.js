module.exports = {
  angular:{
    src:[
      'bower_components/jquery/dist/jquery.js',

      'bower_components/angular/angular.js',
      
      'bower_components/angular-animate/angular-animate.js',
      'bower_components/angular-cookies/angular-cookies.js',
      'bower_components/angular-resource/angular-resource.js',
      'bower_components/angular-sanitize/angular-sanitize.js',
      'bower_components/angular-touch/angular-touch.js',

      'bower_components/angular-ui-router/release/angular-ui-router.js', 
      'bower_components/ngstorage/ngStorage.js',
      'bower_components/angular-ui-utils/ui-utils.js',

      'bower_components/angular-bootstrap/ui-bootstrap-tpls.js',
     
      'bower_components/oclazyload/dist/ocLazyLoad.js',
     
      'bower_components/angular-translate/angular-translate.js',
      'bower_components/angular-translate-loader-static-files/angular-translate-loader-static-files.js',
      'bower_components/angular-translate-storage-cookie/angular-translate-storage-cookie.js',
      'bower_components/angular-translate-storage-local/angular-translate-storage-local.js',

      'bower_components/ng-table/dist/ng-table.js',
      'bower_components/ngImgCrop/compile/minified/ng-img-crop.js',

      'bower_components/moment/moment.js',
      'bower_components/bootstrap-daterangepicker/daterangepicker.js',
      'bower_components/angular-daterangepicker/js/angular-daterangepicker.js',
      'bower_components/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js',
      'bower_components/angular-bootstrap-datetimepicker-directive/angular-bootstrap-datetimepicker-directive.js',
      'bower_components/chosen/chosen.jquery.js',
      'bower_components/angularjs-toaster/toaster.js',
      'bower_components/angular-ueditor/dist/angular-ueditor.js',
      'bower_components/jsoneditor/dist/jsoneditor.js',
      'bower_components/ng-jsoneditor/ng-jsoneditor.js',

      'bower_components/codemirror/lib/codemirror.js',
      'bower_components/angular-ui-codemirror/ui-codemirror.js',
      'bower_components/codemirror/keymap/vim.js',
      'bower_components/codemirror/mode/shell/shell.js',
      'bower_components/angular-confirm-modal/angular-confirm.min.js',
      'bower_components/highcharts/highcharts.js',
      // 'bower_components/highcharts/modules/exporting.js',
      'bower_components/highcharts/modules/heatmap.js',
      'bower_components/highcharts/modules/treemap.js',
      'bower_components/highcharts/themes/dark-blue.js',
      'bower_components/highcharts-ng/dist/highcharts-ng.min.js',

      'src/js/*.js',
      'src/js/directives/*.js',
      'src/js/services/*.js',
      'src/js/filters/*.js',
      'src/js/controllers/bootstrap.js',
      'src/js/app/account/img.qiniu.js'
    ],
    dest:'angular/js/app.src.js'
  },
  html:{
    src:[
      'bower_components/jquery/dist/jquery.min.js',
      'bower_components/bootstrap/dist/js/bootstrap.js',
      'html/js/*.js'
    ],
    dest:'html/js/app.src.js'
  }
}

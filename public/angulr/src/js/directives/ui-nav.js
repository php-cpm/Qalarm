angular.module('app')
  .directive('uiNav', ['$timeout','$compile', function($timeout, $compile) {
    return {
      restrict: 'AC',
      link: function(scope, el, attr) {
        var DUMMY_SCOPE = {
              $destroy: angular.noop
            },
            root = el,
            childScope,
            destroyChildScope = function() {
              (childScope || DUMMY_SCOPE).$destroy();
            };

        // 观察html的变化，如果有变，则重新compile
        attr.$observe("html", function(html) {
          if (html) {
            destroyChildScope();
            childScope = scope.$new(false);
            var content = $compile(html)(childScope);
            root.replaceWith(content);
            root = content;
            el = root;
            scope = childScope;

            var _window = $(window),
                _mb = 768,
                wrap = $('.app-aside'),
                next,
                backdrop = '.dropdown-backdrop';
            // unfolded
            el.on('click', 'a', function(e) {
              next && next.trigger('mouseleave.nav');
              var _this = $(this);
              _this.parent().siblings( ".active" ).toggleClass('active');
              _this.next().is('ul') &&  _this.parent().toggleClass('active') &&  e.preventDefault();
              // mobile

              _this.next().is('ul') || ( ( _window.width() < _mb ) && $('.app-aside').removeClass('show off-screen') );
            });

            // folded & fixed
            el.on('mouseenter', 'a', function(e){
              next && next.trigger('mouseleave.nav');
              $('> .nav', wrap).remove();
              if ( !$('.app-aside-fixed.app-aside-folded').length || ( _window.width() < _mb ) || $('.app-aside-dock').length) return;
              var _this = $(e.target)
                  , top
                  , w_h = $(window).height()
                  , offset = 50
                  , min = 150;

              !_this.is('a') && (_this = _this.closest('a'));
              if( _this.next().is('ul') ){
                next = _this.next();
              }else{
                return;
              }

              _this.parent().addClass('active');
              top = _this.parent().position().top + offset;
              next.css('top', top);
              if( top + next.height() > w_h ){
                next.css('bottom', 0);
              }
              if(top + min > w_h){
                next.css('bottom', w_h - top - offset).css('top', 'auto');
              }
              next.appendTo(wrap);

              next.on('mouseleave.nav', function(e){
                $(backdrop).remove();
                next.appendTo(_this.parent());
                next.off('mouseleave.nav').css('top', 'auto').css('bottom', 'auto');
                _this.parent().removeClass('active');
              });

              $('.smart').length && $('<div class="dropdown-backdrop"/>').insertAfter('.app-aside').on('click', function(next){
                next && next.trigger('mouseleave.nav');
              });
            });

            wrap.on('mouseleave', function(e){
              next && next.trigger('mouseleave.nav');
              $('> .nav', wrap).remove();
            });
          } else {
            scope.$on("$destroy", destroyChildScope);
          }
        });
      }
    };
  }])
    .controller('NavController', ['navModel','$rootScope', '$state', '$stateParams','$cookieStore', function(navModel, $rootScope, $state, $stateParams,$cookieStore){
      $rootScope.$state = $state;
      $rootScope.$stateParams = $stateParams;
      var vm = this;
      navModel.get({}, {}, function(response){
        vm.html = response.data.nav;

        if (response.errno != 0) {
          //$scope.authError = '邮箱或密码不正确';
        } else {
          $rootScope.user = response.data.user;
          // record to cookies
          $cookieStore.put('user', $rootScope.user);


          if ($rootScope.user.head_img == undefined) {
            $rootScope.user.head_img = 'img/a0.jpg';
          }
        }

      });
    }]);

app.factory('navModel', ['$resource', function($resource) {
  return $resource('/api/admin/nav', {},
      {
        'query': { method: 'GET', isArray: false},
        'get'  : { method: 'POST',params:{'action':'get'}},
        'add'  : { method: 'GET',params:{'action':'add'}},
        'update'  : { method: 'GET',params:{'action':'update'}}
      }
  );
}]);


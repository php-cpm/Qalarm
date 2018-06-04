'use strict';

/* Filters */
// need load the moment.js to use this filter. 
angular.module('app')
  .filter('fromNow', function() {
    return function(date) {
      return moment(date).fromNow();
    }
  });

angular.module('app')
    .filter('replaceEnter', function () {
        return function (item) {
            return item.replace(/\n/g, "</br>");
        }
    });

angular.module('app')
    .filter('explode', function() {
        return function(item, seq) {
            var arr = item.split(seq);
            var output = '';
            for (var a in arr) {
                output += a + '\n';
            }

            return output;
        }
    });

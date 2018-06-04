angular.module('app')
    .directive('uiFieldset', [function() {
        return {
            restrict: 'AC',
            compile: function(el, attrs) {
                var sectionName = attrs.uiFieldset;
                var isSelectedParamName = attrs.isSelected;

                isSelectedParamName = isSelectedParamName.replace('{{', '');
                isSelectedParamName = isSelectedParamName.replace('}}', '');

                var contentDiv = el.find('div')[0];
                var legend = el.find('legend');

                el.addClass('beautiful-fieldset');
                legend.html('<div style="display:inline;"> <input type="checkbox" id="ckb_id" >\
                             <span style="padding: 1px 0; margin-left:2px; font: 14px bold tahoma,arial,verdana,sans-serif ;color: #15428b;">'+sectionName+'</span></div>');



                //attrs.$observe('isSelected', function(value) {
                //    console.log('attrDd has changed value to ' + value); //my little dada
                //});

                var linkFunction = function (scope, el, attrs) {
                    var checkbox = el.find('input#ckb_id');
                    var selected    = attrs.isSelected;

                    if (isEmpty(selected) == true) {
                        toggle(false);
                    } else {
                        if (selected == 'true') {
                            $(checkbox).attr("checked", 'true');
                            toggle(true);
                        } else {
                            toggle(false);
                        }
                    }

                    // 点击事件
                    $(checkbox).on('click', function() {
                        toggle($(checkbox).is(":checked"));
                    });

                    function toggle(isShow) {
                        if(isShow) {
                            $(contentDiv).show();
                            el.removeClass('beautiful-fieldset-collapsed');
                        } else {
                            $(contentDiv).hide();
                            el.addClass('beautiful-fieldset-collapsed');
                        }
                    }
                }
                return linkFunction;
            }
        };
    }]);
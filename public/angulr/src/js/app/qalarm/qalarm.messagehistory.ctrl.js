app.controller("QalarmMessageHistoryCtrl", ['$state', '$scope', '$stateParams', '$modal', 'NgTableParams','toaster', 'QalarmModel', '$timeout', '$confirm',
    function ($state, $scope, $stateParams, $modal, NgTableParams, toaster, QalarmModel, $timeout, $confirm) {
        var self = this;

        self.createTable = function () {
            var pageSize = 20;
            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = QalarmModel.messageHistory().list({
                        'project': $stateParams.project_name,
                        'module' : $stateParams.module,
                        "page_index": params.page(),
                        "page_size": pageSize
                    }, {}).$promise.then(function (response) {
                        if (response.errno == 0) {
                            params.total(response.data.page.total);
                            params.page(response.data.page.index);
                            return response.data.results;
                        } else {
                            return {};
                        }
                    });
                    return data;
                }
            });

        };

        self.goback = function () {
            window.history.back();
        }

        self.createTable();
    }]);


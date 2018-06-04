'use strict';

app.controller("CMSCtrl", ['$scope', '$modal', 'NgTableParams', 'contentModel', 'manualCouponModel', 'commonModel', 'toaster',
    function ($scope, $modal, NgTableParams, contentModel, manualCouponModel, commonModel, toaster) {
        var self = this;
        self.isTops = [{"id": -1, "name": "全部"}, {"id": 1, "name": "是"}, {"id": 0, "name": "否"}];
        self.isTop = self.isTops[0].id;

        $scope.createTable = function (contentType) {
            var pageSize = 15;
            self.tableParams = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = contentModel.contents().list({
                        "page_index": params.page(),
                        "page_size": pageSize,
                        "content_type": contentType,
                        "is_top" : self.isTop,
                    }, {}).$promise.then(function (data) {
                            params.total(data.data.page.total);
                            params.page(data.data.page.index);
                            return data.data.results;
                        });
                    return data;
                }
            });
        };

        $scope.loadContentTypeTree = function (reloadAllSelect) {
            contentModel.contentTypesTree().list({}, {}).$promise.then(function (response) {
                self.tree = response.data.tree;
                self.types = response.data.types;

                self.mainContentTypes = new Array();
                self.mainContentTypes.push({"id":0, "name":"全部"})

                var levelOne = Object.keys(self.tree);
                for (var i in levelOne) {
                    var one = new Object();
                    one.id = levelOne[i];
                    one.name = self.types[one.id].name;
                    self.mainContentTypes.push(one);
                }

                if (reloadAllSelect) {
                    if (self.mainContentTypes.length > 0) {
                        self.mainContentType = self.mainContentTypes[0].id;
                        $scope.mainContentSelect();
                    }
                }
            });
        }

        $scope.mainContentSelect = function () {
            var subContentTypes = self.tree[self.mainContentType];
            console.log(self.subContentTypes);

            self.secondContentTypes = new Array();
            self.secondContentTypes.push({"id":0, "name":"全部"})
            var levelSecond = Object;
            if (subContentTypes != undefined) {
                levelSecond = Object.keys(subContentTypes);
            }
            for (var i in levelSecond) {
                var one = new Object();
                one.id = levelSecond[i];
                one.name = self.types[one.id].name;
                self.secondContentTypes.push(one);
            }

            if (self.secondContentTypes.length > 0) {
                self.secondContentType = self.secondContentTypes[0].id;
                $scope.secondContentSelect();
            }

            //self.secondContentType = '';
            //self.thridContentType = '';
            self.contentTypeName = '';
            self.contentType = '';
            self.showEditTab = false;
        }




        $scope.secondContentSelect = function () {
            var subContentTypes = Object;
            if (self.tree[self.mainContentType] != undefined) {
                subContentTypes = self.tree[self.mainContentType][self.secondContentType];
            }
            console.log(self.secondContentType);
            self.thridContentTypes = new Array();
            self.thridContentTypes.push({"id":0, "name":"全部"})
            var levelThrid = Object;
            if (subContentTypes != undefined) {
                levelThrid = Object.keys(subContentTypes);
            }
            for (var i in levelThrid) {
                var one = new Object();
                one.id = levelThrid[i];
                one.name = self.types[one.id].name;
                self.thridContentTypes.push(one);
            }
            if (self.thridContentTypes.length > 0) {
                self.thridContentType = self.thridContentTypes[0].id;
            }

            //self.thridContentType = '';
            self.contentTypeName = '';
            self.contentType = '';
            self.showEditTab = false;
        }




        $scope.searchContents = function () {
            var contentType = self.thridContentType;
            if (contentType == 0) {
                contentType = self.secondContentType;
            }
            if (contentType == 0) {
                contentType = self.mainContentType;
            }
            $scope.createTable(contentType);
        }

        $scope.showEditTabFunction = function () {
            var name = '';
            var typeId = '';
            var contentType = self.mainContentType;
            console.log(contentType);
            if (contentType != 0) {
                typeId = contentType;
                name = self.types[contentType].name;
                contentType = self.secondContentType;

            }
            if (contentType != 0) {
                typeId = contentType;
                name += '/';
                name += self.types[contentType].name;
                contentType = self.thridContentType;
            }
            if (contentType != 0) {
                typeId = contentType;
                name += '/';
                name += self.types[contentType].name;
            }

            if (name.length == 0) {
                toaster.pop('warning', '通知', '请选择要创建的文章类型');
                return;
            }
            self.showEditTab = !self.showEditTab
            self.showSaveBtn = !self.showSaveBtn;

            self.contentTypeName = name;
            self.contentType = typeId;
            self.published_at = moment().format();

            // 重置文章id
            self.contentId = undefined;
        }


        $scope.saveContent = function () {
            var pData = {
                'content_id': self.contentId,
                'content_type': self.contentType,
                'published_at': self.published_at,
                'keywords': self.keywords,
                'title': self.title,
                'summary': self.summary,
                'content': self.content
            };

            contentModel.contentUpdate().save({}, pData).$promise.then(function (response) {
                if (response.errno == 0) {
                    toaster.pop('success', '通知', '保存成功');
                    $scope.createTable(self.contentType);

                    self.contentType = '';
                    self.published_at = '';
                    self.keywords = '';
                    self.title = '';
                    self.summary = '';
                    self.content = '';
                    self.showEditTab = false;
                    self.showSaveBtn = false;
                    self.contentId = undefined;
                } else {
                    toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                }
            })
        }

        $scope.editContent = function(contentId) {
            self.showEditTab = !self.showEditTab
            self.showSaveBtn = !self.showSaveBtn;

            var pDate = {
                'content_id': contentId
            }
            contentModel.content().get(pDate, {}).$promise.then(function(response) {
                if (response.errno == 0) {
                    var data = response.data;
                    self.content = data.content;
                    self.title   = data.title;
                    self.summary = data.summary;
                    self.published_at = data.published_at;
                    if (self.published_at == '') {
                        self.published_at = moment().format();
                    }
                    self.keywords = data.keywords;

                    self.mainContentType = data.parent_type.a_id;
                    self.secondContentType = data.parent_type.b_id;
                    self.thridContentType = data.parent_type.c_id;

                    self.contentTypeName = data.parent_type.name;
                    self.contentType = data.type_id;

                    // 区别创建和编辑
                    self.contentId = contentId;
                }

            })
        }

        $scope.publishContent = function(contentId) {
            var pDate = {
                'content_id': contentId,
                'action': 'publish'
            }
            contentModel.contentStatus().save({}, pDate).$promise.then(function(response) {
                if (response.errno == 0) {
                    toaster.pop('success', '通知', '发布成功, 网站已可见');
                    $scope.searchContents();
                } else {
                    toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                }
            })
        }

        $scope.topContent = function(contentId) {
            var pDate = {
                'content_id': contentId,
                'action': 'top'
            }
            contentModel.contentStatus().save({}, pDate).$promise.then(function(response) {
                if (response.errno == 0) {
                    toaster.pop('success', '通知', '置顶成功, 首页已可见');
                    $scope.searchContents();
                } else {
                    toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                }
            })
        }

        $scope.unTopContent = function(contentId) {
            var pDate = {
                'content_id': contentId,
                'action': 'untop'
            }
            contentModel.contentStatus().save({}, pDate).$promise.then(function(response) {
                if (response.errno == 0) {
                    toaster.pop('success', '通知', '撤顶成功, 首页已不可见');
                    $scope.searchContents();
                } else {
                    toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                }
            })
        }

        $scope.previweContent = function(contentId) {

        }

        // 内容类型管理
        $scope.createContentTypeTable = function (contentType) {
            var pageSize = 15;
            self.tableParamsType = new NgTableParams({count: pageSize, page: 1}, {
                getData: function (params) {
                    var data = contentModel.contentTypes().list({
                        "page_index": params.page(),
                        "page_size": pageSize,
                        "content_type": contentType,
                    }, {}).$promise.then(function (data) {
                            params.total(data.data.page.total);
                            params.page(data.data.page.index);
                            return data.data.results;
                        });
                    return data;
                }
            });
        };

        /*contentType 最终为0表示所有类型*/
        $scope.searchContentTypes = function () {
            var contentType = self.secondContentType;
            if (contentType == 0) {
                contentType = self.mainContentType;
            }
            $scope.createContentTypeTable(contentType);
        }

        $scope.openTypeCtrl = function (size) {
            var contentType = self.secondContentType;
            if (contentType == 0) {
                contentType = self.mainContentType;
            }
            if (contentType == 0) {
                toaster.pop('warning', '通知', '请慎重创建一级类型');
            }

            var modalInstance = $modal.open({
                templateUrl: 'opertor.cms.type.edit.html',
                controller: ModalInstanceCtrl,
                size: size,
                resolve: {
                    params: function () {
                        return {
                            'contentType': contentType,
                            'contentTypeName': (self.types[contentType] != undefined) ? self.types[contentType].name : '首页'
                        }
                    }
                }
            });

            modalInstance.result.then(function () {
                $scope.searchContentTypes();
                $scope.loadContentTypeTree(false);
            }, function () {
                console.log('cancel');
            });
        };

        var ModalInstanceCtrl = function ($scope, $modalInstance, params) {
            $scope.vm = {};
            $scope.vm.contentTypeName = params.contentTypeName;

            $scope.ok = function () {
                var pData = {
                    'name': $scope.vm.name,
                    'parent_id': params.contentType
                };
                contentModel.contentTypeUpdate().save({}, pData).$promise.then(function (response) {
                    if (response.errno == 0) {
                        toaster.pop('success', '通知', '添加成功');
                        $modalInstance.close(1);
                    } else {
                        toaster.pop('error', '通知', '服务端错误,错误信息：' + response.errmsg + '错误码：' + response.errno);
                    }
                })

            };

            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            };
        };

        $scope.loadContentTypeTree(true);
        self.showEditTab = false;
        self.showSaveBtn = false;
        $scope.searchContents();
    }]);

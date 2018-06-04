'use strict';

/**
 * Config for the router
 */
angular.module('app')
    .run(
    ['$rootScope', '$state', '$stateParams',
        function ($rootScope, $state, $stateParams) {
            $rootScope.$state = $state;
            $rootScope.$stateParams = $stateParams;
        }
    ]
)
    .config(
    ['$stateProvider', '$urlRouterProvider', 'JQ_CONFIG',
        function ($stateProvider, $urlRouterProvider, JQ_CONFIG) {
            //$urlRouterProvider
            //    .otherwise('/app/myjob');
            $stateProvider
                .state('app', {
                    abstract: true,
                    url: '/app',
                    templateUrl: 'tpl/app.html',
                    resolve: {
                        //deps: ['$ocLazyLoad',
                        //    function ($ocLazyLoad) {
                        //        return $ocLazyLoad.load('toaster').then(function () {
                        //
                        //        })
                        //    }]
                    }
                })
                .state('app.myjob', {
                    url: '/myjob',
                    templateUrl: 'tpl/blank.html',


                })
                .state('app.admin', {
                    url: '/admin',
                    template: '<div ui-view class="fade-in-up"></div>',
                })
                .state('app.admin.auth', {
                    url: '/admin/auth',
                    templateUrl: 'tpl/admin/admin.auth.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/admin/admin.auth.ctrl.js']);
                            }]
                    }
                })
                .state('app.admin.role', {
                    url: '/admin/role',
                    templateUrl: 'tpl/admin/admin.role.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/admin/admin.role.ctrl.js']);
                            }]
                    }
                })
                .state('app.admin.user', {
                    url: '/admin/user',
                    templateUrl: 'tpl/admin/admin.user.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/admin/admin.user.ctrl.js']);
                            }]
                    }
                })
                //== llw update start
                .state('app.admin.menupermit', {
                    url: '/admin/menupermit',
                    templateUrl: 'tpl/admin/admin.menupermit.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/admin/admin.menupermit.ctrl.js']);
                            }]
                    }
                })
                .state('app.admin.profile', {
                    url: '/admin/profile',
                    templateUrl: 'tpl/admin/admin.profile.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/admin/admin.profile.ctrl.js']);
                            }]
                    }
                })
                .state('app.admin.wfparticipator', {
                    url: '/admin/wfparticipator',
                    templateUrl: 'tpl/admin/admin.wfparticipator.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/admin/admin.wfparticipator.ctrl.js']);
                            }]
                    }
                })
                //== llw uodate end
                .state('app.opertor', {
                    abstract: true,
                    url: '/opertor',
                    template: '<div ui-view class="fade-in-up"></div>'

                })
                .state('app.opertor.coupon', {
                    url: '/opertor/coupon',
                    templateUrl: 'tpl/opertor/opertor.coupon.html',
                    resolve: {
                        deps: ['uiLoad', '$ocLazyLoad',
                            function (uiLoad, $ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'js/app/opertor/opertor.coupon.ctrl.js',
                                ]).then(
                                );
                            }]
                    }
                })
                .state('app.opertor.active', {
                    url: '/opertor/active',
                    templateUrl: 'tpl/opertor/opertor.active.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/opertor/opertor.active.ctrl.js']);
                            }]
                    }
                })
                .state('app.opertor.push', {
                    url: '/opertor/push',
                    templateUrl: 'tpl/opertor/opertor.push.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load('angularFileUpload').then(
                                    function () {
                                        return $ocLazyLoad.load([
                                            'js/app/opertor/opertor.push.ctrl.js'
                                        ]);
                                    });
                            }]
                    }
                })
                .state('app.opertor.cms', {
                    url: '/opertor/cms',
                    templateUrl: 'tpl/opertor/opertor.cms.html',
                    resolve: {
                        deps: ['$ocLazyLoad', 'uiLoad',
                            function ($ocLazyLoad, uiLoad) {
                                return uiLoad.load([
                                    'js/app/opertor/opertor.cms.ctrl.js',
                                    '../ueditor/ueditor.config.js',
                                    '../ueditor/ueditor.all.min.js',
                                ]);
                            }]
                    }
                })
                .state('app.account', {
                    url: '/account',
                    template: '<div ui-view class="fade-in-up"></div>'

                })
                .state('app.account.list', {
                    url: '/list',
                    //templateUrl: 'tpl/user/users.html',
                    templateUrl: 'tpl/account/account.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                //return $ocLazyLoad.load('ngTable').then(function () {
                                return $ocLazyLoad.load(['js/app/account/account.ctrl.js','js/app/account/account.auth.ctrl.js']);
                                //})
                            }]
                    }
                })
                .state('app.account.detail', {
                    //url: '/detail/:user_id',
                    url: '/detail?user_id',
                    //templateUrl: 'tpl/user/user_detail.html',
                    templateUrl: 'tpl/account/account.detail.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                //return $ocLazyLoad.load('ngTable').then(function () {
                                return $ocLazyLoad.load(['js/app/account/account.detail.ctrl.js']);
                                //})
                            }]
                    }
                })
                .state('app.service', {
                    url: '/service',
                    template: '<div ui-view class="fade-in-up"></div>'

                })
                .state('app.service.app', {
                    //url: '/detail/:user_id',
                    url: '/app',
                    templateUrl: 'tpl/service/service.apps.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/service/service.app.ctrl.js']);
                            }]
                    }
                })
                .state('app.service.apply', {
                    //url: '/detail/:user_id',
                    url: '/apply',
                    templateUrl: 'tpl/service/service.apply.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/service/service.apply.ctrl.js']);
                            }]
                    }
                })
                .state('app.service.myindex', {
                    //url: '/detail/:user_id',
                    url: '/myindex',
                    templateUrl: 'tpl/service/service.myindex.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/service/service.myindex.ctrl.js']);
                            }]
                    }
                })
                .state('app.service.flow', {
                    //url: '/detail/:user_id',
                    url: '/flow',
                    templateUrl: 'tpl/service/service.flows.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/service/service.flow.ctrl.js']);
                            }]
                    }
                })
                .state('app.service.docs', {
                    //url: '/detail/:user_id',
                    url: '/docs',
                    templateUrl: 'tpl/docs.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                //return $ocLazyLoad.load(['js/app/service/service.myindex.ctrl.js']);
                            }]
                    }
                })
                .state('app.job', {
                    url: '/job',
                    template: '<div ui-view class="fade-in-up"></div>'

                })
                .state('app.job.script', {
                    url: '/script',
                    templateUrl: 'tpl/job/job.script.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/job/job.script.ctrl.js']);
                            }]
                    }
                })
                .state('app.ops', {
                    url: '/ops',
                    template: '<div ui-view class="fade-in-up"></div>'

                })
                .state('app.ops.host', {
                    url: '/host',
                    templateUrl: 'tpl/ops/ops.host.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/ops/ops.host.ctrl.js']);
                            }]
                    }
                })
                .state('app.account.check', {
                    //url: '/detail/:user_id',
                    url: '/check',
                    //templateUrl: 'tpl/user/user_detail.html',
                    templateUrl: 'tpl/account/account.check.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                //return $ocLazyLoad.load('ngTable').then(function () {
                                return $ocLazyLoad.load([
                                    'js/app/account/account.check.ctrl.js',
                                    'js/app/account/account.checklicense.ctrl.js']);
                                //})
                            }]
                    }
                })
                .state('app.clientconf', {
                    url: '/clientconf',
                    template: '<div ui-view class="fade-in-up"></div>'
                })
                .state('app.clientconf.index', {
                    url: '/index',
                    templateUrl: 'tpl/appclientconf/app.clientconf.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/appclientconf/app.clientconf.ctrl.js']);
                            }
                        ]
                    }
                })
                .state('app.ci_project', {
                    url: '/ci_project',
                    template: '<div ui-view class="fade-in-up"></div>'
                })
                .state('app.ci_project.gitlab', {
                    url: '/gitlab',
                    templateUrl: 'tpl/ci_project/ci.gitlab.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'js/app/ci_project/ci.gitlab.ctrl.js',
                                    //'js/app/ci_project/ci.host.ctrl.js',
                                    //'js/app/ci_project/ci.member.ctrl.js',
                                    //'js/app/ci_project/ci.dns.ctrl.js',
                                    //'js/directives/ui-Fieldset.js'
                                ]);
                            }
                        ]
                    }
                })
                .state('app.ci_project.project_edit', {
                    url: '/project_edit?project_id&project_name',
                    templateUrl: 'tpl/ci_project/ci.project.edit.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'js/app/ci_project/ci.project.edit.ctrl.js',
                                    //'js/app/ci_project/ci.gitlab.ctrl.js',
                                    'js/app/ci_project/ci.host.ctrl.js',
                                    'js/app/ci_project/ci.member.ctrl.js',
                                    'js/app/ci_project/ci.dns.ctrl.js',
                                    'js/directives/ui-Fieldset.js'
                                ]);
                            }
                        ]
                    }
                })
                .state('app.ci_project.buildproject', {
                    url: '/buildproject',
                    templateUrl: 'tpl/ci_project/ci.build.project.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/ci_project/ci.build.project.ctrl.js']);
                            }
                        ]
                    }
                })
                .state('app.ci_project.deployproject', {
                    url: '/deployproject',
                    templateUrl: 'tpl/ci_project/ci.deploy.project.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/ci_project/ci.deploy.project.ctrl.js']);
                            }
                        ]
                    }
                })
                .state('app.ci_project.deploylasttask', {
                    //url: '/deployproject/lasttask?deploy_id',
                    url: '/deployproject/lasttask?deploy_step_id',
                    templateUrl: 'tpl/ci_project/ci.deploy.project.lasttask.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/ci_project/ci.deploy.project.lasttask.ctrl.js']);
                            }
                        ]
                    }
                })
                .state('app.ci_project.deploydetail', {
                    url: '/deploy/detail?deploy_id',
                    templateUrl: 'tpl/ci_project/ci.deploy.detail.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/ci_project/ci.deploy.detail.ctrl.js']);
                            }
                        ]
                    }
                })
                .state('app.ci_project.difffiles', {
                    //url: '/difffiles?gaea_build_id',
                    url: '/difffiles?project_id&gaea_build_id&update_id',
                    templateUrl: 'tpl/ci_project/ci.difffiles.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/ci_project/ci.difffiles.ctrl.js']);
                            }
                        ]
                    }
                })
                .state('app.ci_project.docker', {
                    url: '/docker',
                    templateUrl: 'tpl/ci_project/ci.docker.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/ci_project/ci.docker.ctrl.js']);
                            }
                        ]
                    }
                })
                .state('app.ci_project.deploypro', {
                    url: '/deploypro?project_id&gaea_build_id&update_id',
                    templateUrl: 'tpl/ci_project/ci.deploy.pro.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(['js/app/ci_project/ci.deploy.pro.ctrl.js']);
                            }
                        ]
                    }
                })
                .state('app.qalarm', {
                    url: '/qalarm',
                    template: '<div ui-view class="fade-in-up"></div>'
                })
                .state('app.qalarm.project', {
                    url: '/project',
                    templateUrl: 'tpl/qalarm/qalarm.project.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'js/app/qalarm/qalarm.project.ctrl.js'
                                ]);
                            }
                        ]
                    }
                })
                .state('app.qalarm.graph', {
                    url: '/graph',
                    templateUrl: 'tpl/qalarm/qalarm.graph.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'js/services/v3202.js',
                                    'js/app/qalarm/app.qalarm.graph.js'
                                ]);
                            }
                        ]
                    }
                })
                .state('app.qalarm.graphhistory', {
                    url: '/graphhistory',
                    templateUrl: 'tpl/qalarm/qalarm.graphhistory.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'js/services/v3202.js',
                                    'js/app/qalarm/app.qalarm.graphhistory.js'
                                ]);
                            }
                        ]
                    }
                })
                .state('app.qalarm.graphdetail', {
                    url: '/graphdetail?project_name',
                    templateUrl: 'tpl/qalarm/qalarm.graphdetail.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'js/services/v3202.js',
                                    'js/app/qalarm/app.qalarm.graphdetail.js'
                                ]);
                            }
                        ]
                    }
                })
                .state('app.qalarm.messagehistory', {
                    url: '/messagehistory?project_name&module',
                    templateUrl: 'tpl/qalarm/qalarm.messagehistory.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'js/app/qalarm/qalarm.messagehistory.ctrl.js'
                                ]);
                            }
                        ]
                    }
                })
                .state('app.qalarm.submodule', {
                    url: '/submodule?project_name',
                    templateUrl: 'tpl/qalarm/qalarm.submodule.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'js/app/qalarm/qalarm.submodule.ctrl.js'
                                ]);
                            }
                        ]
                    }
                })
                .state('app.page', {
                    url: '/page',
                    template: '<div ui-view class="fade-in-up"></div>'
                })
                .state('app.page.speed', {
                    url: '/speed',
                    templateUrl: 'tpl/page/page.speed.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'js/app/page/page.speed.ctrl.js'
                                ]);
                            }
                        ]
                    }
                })
                // others
                .state('access', {
                    url: '/access',
                    template: '<div ui-view class="fade-in-right-big smooth"></div>'
                })
                .state('access.signin', {
                    url: '/signin',
                    templateUrl: 'tpl/page.signin.html',
                    resolve: {
                        deps: ['$ocLazyLoad',
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([
                                    'js/controllers/signin.js',
                                    'js/services/encrypt.js'
                                ]);
                            }]
                    }
                })
                .state('access.forgotpwd', {
                    url: '/forgotpwd',
                    templateUrl: 'tpl/page.forgotpwd.html'
                })
                .state('access.404', {
                    url: '/404',
                    templateUrl: 'tpl/page.404.html'
                })
                .state("otherwise", {
                    url: "*head",
                    template: "",
                    controller: [
                        '$state',
                        function ($state) {
                            // $state.go('app.service.myindex');
                            $state.go('app.qalarm.graph');
                        }]
                })
        }
    ]
)
;

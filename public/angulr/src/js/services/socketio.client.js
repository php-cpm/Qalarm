/**
 * Created by admin on 15/10/15.
 */

'use strict';

app.factory('socket', ['$rootScope', '$cookies', '$cookieStore', 'toaster', function ($rootScope, $cookies, $cookieStore, toaster) {
    // 如果node server不可用，则降级服务
    var server = $cookieStore.get('realtime');
    server = 'http://' + server;

    try {
        var socket = io.connect(server);

        // user token
        var user = JSON.parse($cookies.TDD) || {};
        console.log(user);
        socket.on('connect', function () {
            socket.emit('authentication', user);
            socket.on('authenticated', function (data) {
               console.log(data)
            });
            // 认证失败
            socket.on('unauthorized', function (err) {
                toaster.pop('warning', '认证失败', '消息提示功能认证失败，您可能不能使用此服务');
            });
        });

        return {
            on: function (eventName, callback) {
                socket.on(eventName, function () {
                    var args = arguments;
                    $rootScope.$apply(function () {
                        callback.apply(socket, args);
                    });
                });
            },
            emit: function (eventName, data, callback) {
                socket.emit(eventName, data, function () {
                    var args = arguments;
                    $rootScope.$apply(function () {
                        if (callback) {
                            callback.apply(socket, args);
                        }
                    });
                })
            },
            removeAllListeners: function (eventName, callback) {
                socket.removeAllListeners(eventName, function () {
                    var args = arguments;
                    $rootScope.$apply(function () {
                        callback.apply(socket, args);
                    });
                });
            }
        };
    } catch (err) {
        toaster.pop('warning', '通知', '获取socket.io sdk 失败，您将不能使用消息提醒功能');
        return {
            on: function (eventName, callback) {
            },
            emit: function (eventName, data, callback) {
            },
            removeAllListeners: function (eventName, callback) {
            }
        }
    }
}]);

/**
 * Created by admin on 15/10/15.
 */
// Keep track of which names are used so that there are no duplicates
var userNames = (function () {
    var names = {};

    var claim = function (name) {
        if (!name || names[name]) {
            return false;
        } else {
            names[name] = true;
            return true;
        }
    };

    // serialize claimed names as an array
    var getAll = function () {
        var res = [];
        for (user in names) {
            res.push(user);
        }

        return res;
    };

    var getCollection = function() {
        return names;
    }

    var put = function(name, socket) {
        names[name] = socket;
        return true;
    }

    var get = function(name) {
        if (names[name]) {
            return names[name];
        }
        return null;
    }

    var free = function (name) {
        if (names[name]) {
            delete names[name];
        }
    };

    return {
        claim: claim,
        free: free,
        get: get,
        put: put,
        getAll: getAll,
        getCollection: getCollection,
    };
}());

module.exports = function (io) {
    'use strict';
    io.on('connection', function (socket) {
        socket.on('message', function (data, fn) {

            console.log('recieved message from',
                fn, 'msg', JSON.stringify(data));

            console.log('payload is', data);
            io.sockets.emit('message', {
                'data': data
            });
            console.log('broadcast complete');
        });
    });

    // clean up when a user leaves, and broadcast it to other users
    io.on('disconnect', function () {
        // userNames.free(name);
    });

    return {
        userCollection : userNames
    };
};
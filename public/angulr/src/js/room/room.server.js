var config=require('./config');
var io = require('socket.io').listen(config.server.port);
var globalSocket = require('./room.session.js')(io);
io.set("origins", "*");

var fs = require('fs');
var path = require('path');

// usage: https://github.com/facundoolano/socketio-auth
require('socketio-auth')(io, {
    authenticate: function (socket, data, callback) {
       /** data format:
        *{
        *    admin_id: 10,
        *    admin_name: '陈飞',
        * }
        **/

        // find ticket from db
        var valid = true;
        if (!valid) {
            // err.message will be "User not found"
            return callback(new Error("User not found"));
        }
        globalSocket.userCollection.put(data.admin_id, socket);

        return callback(null, true);
    }
});

var redis = require('redis'),
    RDS_PORT = config.redis.port;
    RDS_HOST = config.redis.host;
    RDS_OPTS = {};
    client = redis.createClient(RDS_PORT, RDS_HOST, RDS_OPTS),
    sub    = redis.createClient(RDS_PORT, RDS_HOST, RDS_OPTS);

var redisStoreChannel     = 'TTYC_GEAE_NODE_SERVER_STORE';
var redisPushsubChannel   = 'TTYC_GEAE_NODE_SERVER_PUBSUB';

// 把开放的地址写入redis中
// var key  = 'gaea_realtime_service_addr';
// var addr = config.server.host+':'+config.server.port;
// client.set(key, addr);

sub.subscribe(redisPushsubChannel);
sub.on("message", function(pattern, key) {
    //client.hget(redisStoreChannel, key, function(e, obj) {
    client.hgetall(redisStoreChannel, function(e, obj) {
        console.log(obj);
        client.del(redisStoreChannel);
        for (notice in obj) {
            var job = JSON.parse(obj[notice]);
            var targetSocket = globalSocket.userCollection.get(job.admin_id);

            if (targetSocket == null) {
                continue;
            }
            targetSocket.emit(job.module_name, {
                'data': job
            });
        }
    })
})

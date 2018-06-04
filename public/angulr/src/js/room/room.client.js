///**
// *
// * Created by admin on 15/10/15.
// */
//var sleep = require('sleep');
//var uuid = require('node-uuid');
//var redis = require('redis'),
//    RDS_PORT = 6479,
//    RDS_HOST = '10.6.12.178',
//    RDS_OPTS = {},
//    client = redis.createClient(RDS_PORT, RDS_HOST, RDS_OPTS),
//    pub = redis.createClient(RDS_PORT, RDS_HOST, RDS_OPTS);
//
//var redisStoreChannel = 'TTYC_GEAE_NODE_SERVER_STORE';
//var redisPushsubChannel = 'TTYC_GEAE_NODE_SERVER_PUBSUB';
//
//var send = function () {
//    var id = 'message:' + uuid.v1();
//    var job = {
//        'userid': 10,
//        'module': 'realtime.opertor.notice.add',
//        'data': {
//            'id': "aaa",
//            'action': "flush"
//        }
//    };
//
//    console.log(id);
//
//    client.hset(redisStoreChannel, id, JSON.stringify(job), function (e, r) {
//        console.log(e);
//        console.log(r);
//        pub.publish(redisPushsubChannel, id);
//    })
//
//    //sleep.sleep(1);
//    console.log('ssssssss');
//}
//send();
//

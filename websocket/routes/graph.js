var express = require('express');
var router = express.Router();
var config = require('../config');
var redis = require("redis");

router.get('/detail', function(req, res, next) {
    try {
        var client = redis.createClient(config.redis.port, config.redis.host, {});
        var pname = req.query.project_name;
        var key = 'history_' + pname;
        
        var data = '{';
        client.lrange(key, 0, 200, function(err, reply) {
            reply.sort();
            for (var idx in reply) {
                var now = (new Date()).getTime();
                var time = reply[idx].split(':')[0];
                if ((time > now) || (time < (now - 10*60*1000))) {
                    continue;
                }
                data += reply[idx] + ','
            }

            if (data !== '{') {
                data = data.slice(0, -1);
            }
            data += '}';
            res.render('GrapheDetail', { 'pname': pname , 'points': data});
       });



    } catch (e) {
        res.render('GrapheDetail', { 'pname': pname , 'points': '{}'});
    }

});

router.get('/checkauth', function(req, res, next) {
    try {
        var pname = req.query.project_name;
        var data = {'errno':0, 'data':{ 'authorization': 1}};
        res.send(data);
    } catch (e) {
        console.log(e);
    }

});

router.get('/messages', function(req, res, next) {
    var PHPUnserialize = require('php-unserialize');
    var moment = require('moment');
    try {
        var project = req.query.project;
        var module  = req.query.module;
        var limit = 20;
        var key = ['module', project, module].join('|');
        var currentPage = req.query.page || 1;
        var client = redis.createClient(config.redis.port, config.redis.host, {});
          
        client.llen(key, function(error, count){
            var totalPages  = count / limit + 1;
            var data = new Array();
            client.lrange(key, (currentPage-1)*limit, currentPage*limit, function(err, reply) {
                for (var idx in reply) {
                    var raw = PHPUnserialize.unserialize(reply[idx]);
                    data.push({
                        'code'      : raw['code'],
                        'name'      : raw['module'],
                        'time'      : moment(raw['time'] * 1000).format('YYYY/MM/DD HH:mm'),
                        'server_ip' : raw['server_ip'],
                        'client_ip' : raw['client_ip'],
                        'env'       : raw['env'],
                        'script'    : raw['script'],
                        'message'   : raw['message'],

                    });
                }
                res.render('messages', {'items' : data, 'current_page':currentPage, 'total_pages':totalPages, 'module':module, 'project':project});
            });
        });
    } catch(e) {
        console.log(e);
    }
});

module.exports = router;

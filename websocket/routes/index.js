var express = require('express');
var partials = require('express-partials');
var router = express.Router();
var config = require('../config');
var redis = require("redis");

/* GET home page. */
router.get('/', function(req, res, next) {
    try {
        var data = '{}';
        var client = redis.createClient(config.redis.port, config.redis.host, {});
        var projects = new Array();

        client.hgetall('wanda_qalarm_history_projects', function(err,result) {
            var now = (new Date()).getTime();
            for (var pname in result) {
                if ((now - result[pname]) <= 10*60*1000) {
                    projects.push(pname);
                }
            }

            if (projects.length == 0) {
                res.render('Graphe', {'points': '{}'});
            }

            var projectCount = projects.length;
            var all = [];

            projects.forEach(function(pname) {
                var key = 'history_' + pname;
                var projectName = pname;
                client.lrange(key, 0, 200, function(err, reply) {
                    var data = '{';
                    reply.sort();
                    for (var idx in reply) {
                        var now = (new Date()).getTime();
                        var time = reply[idx].split(':')[0];
                        if ((time > now) || (time < (now - 10*60*1000))) {
                            continue;
                        }
                        data += reply[idx] + ','
                    }

                    // no suitable data
                    if (data !== '{') {
                        data = data.slice(0, -1);
                    }
                    data += '}';

                    all[projectName] = data;

                    if ( --projectCount === 0) {
                        var points = '{';
                        for (var pname in all) {
                            if (all[pname] == '{}') continue;
                            points += pname + ':' + all[pname] + ',';
                        }
                        // no suitable data
                        if (points !== '{') {
                            points = points.slice(0, -1);
                        }
                        points += '}';

                        res.render('Graphe', {'points': points});
                    }
                });
            });
        });

    } catch (e) {
        console.log(e);
    }
});

module.exports = router;

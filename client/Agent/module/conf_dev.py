from os import path

import module.lua
import module.php
import module.qconf


# Base path
path = path.dirname(path.realpath(__file__)) + '/..'
path_qalarm = path + '/storage'

# File name
record_file = path + '/storage/records.json'
qalarm_file = path_qalarm + '/alarm.log'
heartbeat_file = path_qalarm + '/heartbeat.log'

# Check list
check_list = {
    'php': {
        'action': module.php.check_error,
        'path': '/var/wd/log/php/php_error.log',
    },
    'php2': {
        'action': module.php.check_error,
        'path': '/var/wd/log/php/php_error.log2',
    },
    'qconf': {
        'action': module.qconf.check_error,
        'path': '/usr/local/qconf/logs/zoo.err.log',
    },
    'flashsale': {
        'action': module.lua.check_resty,
        'path': '/var/wd/log/nginx/flashsale_err.log',
    },
    'shake': {
        'action': module.lua.check_resty,
        'path': '/var/wd/log/nginx/error.lua_xapi_activity.log',
    },


    'dev_php_error': {
        'action': module.php.check_error,
        'path': 'log/php_error.log',
        'ignore_levels': set(['notice']),
    },
    'dev_lumen': {
        'action': module.php.check_lumen,
        'path': 'log/lumen.log',
        'ignore_levels': set(['info']),
    },
    'dev_openresty': {
        'action': module.lua.check_resty,
        'path': 'log/lua_error.log',
        'only_levels': set(['error']),
    },
    'dev_qconf': {
        'action': module.qconf.check_error,
        'path': 'log/qconf.log',
        'ignore_levels': set(['error']),
    },
}

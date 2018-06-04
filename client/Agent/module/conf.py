import module.lua
import module.php
import module.qconf


# Base path
path = '/var/wd/wrs/release/stare'
path_qalarm = '/var/wd/wrs/logs/alarm'

# File name
record_file = path + '/records.json'
qalarm_file = path_qalarm + '/alarm.log'
heartbeat_file = path_qalarm + '/heartbeat.log'

# Check list
check_list = {
    # Common
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

    # Project
    'proj_shake': {
        'action': module.lua.check_resty,
        'path': '/var/wd/log/nginx/error.lua_xapi_activity.log',
        'ignore_levels': set(['notice']),
    },

    'proj_flashsale': {
        'action': module.lua.check_resty,
        'path': '/var/wd/log/nginx/flashsale_err.log',
        'ignore_levels': set(['notice']),
    },

    'proj_mop': {
        'action': module.php.check_lumen,
        'path': '/var/wd/log/logs/lumen.log',
        'ignore_levels': set(['info']),
    },
}

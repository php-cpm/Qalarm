import re
import time


def check_error(fp, module, report):
    '''
    Example line
    2016-10-24 14:36:18,004:20098(0x7ff54d6ba700):ZOO_WARN@zookeeper_interest@1557: Exceeded deadline by 11ms
    '''

    for line in fp:
        match = re.match('^(.+?)\,(.+)$', line)
        if match is None:
            continue

        buf = {
            'timestr': match.group(1),
            'message': match.group(2),
            'file': None,
            'lines': [line],
            'level': 'error',
        }

        res = time.strptime(buf['timestr'][:20], '%Y-%m-%d %H:%M:%S')
        buf['timestamp'] = int(time.mktime(res))

        report(buf, module)

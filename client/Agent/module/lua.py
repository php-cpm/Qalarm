import re
import time


def check_resty(fp, module, report):
    '''
    Example line
    2016/09/18 16:13:15 [error] 19875#0: *20607461 lua tcp socket read timed out, client: 10.209.37.76, server: , request: "POST /shake/v2/lottery/luck?puid=78176F4B526D448EB75AFF2A1D4BA15A&__v=v2&_realip=117.136.7.13&__trace_id=10.209.230.194-1474186395.856-61231-1056 HTTP/1.1", host: "xapi.intra.ffan.com"
    '''

    buf = None
    for line in fp:
        match = re.match('^(.+?) \[(\w+)\] (.+)$', line)

        # Title line
        if match:
            if buf is not None:
                report(buf, module)

            # Init
            buf = {
                'timestr': match.group(1),
                'message': unicode(match.group(3), errors='ignore'),
                'file': None,
                'lines': [unicode(line, errors='ignore')],
                'level': 'error',
            }

            # Time
            try:
                res = time.strptime(buf['timestr'][:19], '%Y/%m/%d %H:%M:%S')
            except ValueError as err:
                buf = None
                continue
            buf['timestamp'] = int(time.mktime(res))

            # Level
            buf['level'] = match.group(2)

    if buf is not None:
        report(buf, module)

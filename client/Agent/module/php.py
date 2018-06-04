import re
import time


def check_error(fp, module, report):
    '''
    Example line
    [01-Aug-2016 18:14:33 Asia/Chongqing] PHP Fatal error:  Call to undefined function asdf() in Command line code on line 1
    '''

    buf = None
    for line in fp:
        match = re.match('^\[(.+?)\]\s*(.+?):\s*(.+) in (.+) on line (\d+)', line)

        # Title line
        if match:
            if buf is not None:
                report(buf, module)

            # Init
            buf = {
                'timestr': match.group(1),
                'message': match.group(2) + ': ' + match.group(3),
                'file': match.group(4),
                'lineno': match.group(5),
                'lines': [line],
                'level': 'error',
            }

            # Time "01-Aug-2016 18:14:33 Asia/Chongqing"
            res = time.strptime(buf['timestr'][:20], '%d-%b-%Y %H:%M:%S')
            buf['timestamp'] = int(time.mktime(res))

            # Level
            match = re.match('^(PHP )?(\w+)$', match.group(2))
            if match:
                buf['level'] = match.group(2).lower()

        # Body lines
        elif buf:
            buf['lines'].append(line)

    if buf is not None:
        report(buf, module)


def check_lumen(fp, module, report):
    '''
    Example line
    [2016-10-24 23:25:57] lumen.INFO: post cost: 23ms param {"puid":"DE4EB169C9F24727B2CC3ACEF30CC137","ploginToken":"fbe00b5555aadb9daad629a394371042","itemId":4,"siedc":"831ba21b08ce8b4a722f7fb3fe5dab6109c9615497e901d3a2e7e4984712e1a1","devInfo":"{\"locationY\":\"119.963105\",\"network_desc\":\"WIFI\",\"locationCity\":\"\u5e38\u5dde\u5e02\",\"IP\":\"192.168.16.101\",\"size\":\"320*568\",\"locationProvince\":\"\u6c5f\u82cf\u7701\",\"wifi\":\"B-LINK_888888\",\"device_desc\":\"iPhone5,2\",\"sourceFrom\":\"APP\",\"screenSize\":\"320*568\",\"locationAddress\":\"\u4e2d\u56fd \u6c5f\u82cf\u7701 \u5e38\u5dde\u5e02 \u5929\u5b81\u533a \u4e2d\u5434\u5927\u9053 \u5929\u5b81\u533a\",\"os_version\":\"iOS 10.0.2\",\"mac\":\"f9081e39f8296d3eb0bc76f7439a44d4dff39ee8\",\"gmtTime\":\"2016-10-24 23:24:29\",\"locationX\":\"31.750984\",\"wifiMac\":\"ac:a2:13:80:fa:de\",\"GPS\":\"31.750984,119.963105\",\"Os_type\":\"IOS\",\"locationDistrict\":\"\u5929\u5b81\u533a\",\"network\":\"WIFI\",\"device_id\":\"f9081e39f8296d3eb0bc76f7439a44d4dff39ee8\"}","__trace_id":"10.209.240.141-1477322757.743-78294-687"} url: http://api.ffan.com/ffan/v1/member/whalecoin/earn?app_key=6be55f3281c0f9bf7d3e313318d8381f&sign=f0213aed42fc0aabf1670ba738673876&method=POST&ts=1477322757&__uni_source=4.2.2 data:{"status":4012,"message":"\u9cb8\u5e01\u53d1\u653e\u4efb\u52a1ID(4)\u5b8c\u6210\u6b21\u6570\u8d85\u9650(1)"}
    '''

    buf = None
    for line in fp:
        match = re.match('^\[(.+?)\]\s*(.+?)lumen.ERROR:\s*(.+)$', line)

        # Title line
        if match:
            if buf is not None:
                report(buf, module)

            # Init
            buf = {
                'timestr': match.group(1),
                'file': None,
                'lines': [line],
                'level': 'error',
            }

            # Time "2016-10-24 23:25:57"
            try:
                res = time.strptime(buf['timestr'][:20], '%Y-%m-%d %H:%M:%S')
            except ValueError as err:
                buf = None
                continue

            buf['timestamp'] = int(time.mktime(res))

            # Level
            parts = match.group(2).split('.')
            if len(parts) >= 2:
                buf['level'] = parts[1]

        # Body lines
        # elif buf:
        #    buf['lines'].append(line)

    if buf is not None:
        report(buf, module)

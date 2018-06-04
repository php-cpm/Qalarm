from os import stat
import json
import signal
import socket
import time

from module import conf


# Report
def report(error, module):
    if report.fp is None:
        report.fp = open(conf.qalarm_file, 'a')

    # Level
    if 'level' not in error:
        error['level'] = 'default'
    else:
        error['level'] = error['level'].lower()

    # Only mode
    if ('only_levels' in conf.check_list[module] and
            error['level'] not in conf.check_list[module]['only_levels']):
        return

    # Ignore mode
    if ('ignore_levels' in conf.check_list[module] and
            error['level'] in conf.check_list[module]['ignore_levels']):
        return

    data = {
        'project': 'stare',
        'module': module,
        'code': None,
        'env': 'prod',
        'time': error['timestamp'],
        'level': error['level'],
        'server_ip': socket.gethostname(),
        'client_ip': None,
        'script': error['file'],
        'message': '\t'.join(error['lines']),
    }

    report.fp.write(json.dumps(data, report.fp) + '\n')
    report.fp.flush()

# init report file handler
report.fp = None


# Config & record
def load_records():
    try:
        data = json.load(open(conf.record_file, 'r'))
    except (IOError, ValueError):
        data = {}

    for module, item in conf.check_list.items():
        if item['path'] not in data:
            data[item['path']] = {'inode': 0, 'offset': 0}

        # Always update module name
        data[item['path']]['module'] = module

    return data


def save_records(data):
    return json.dump(data, open(conf.record_file, 'w'), indent=2)


# Heartbeat
def heartbeat():
    fp = open(conf.heartbeat_file, 'a')

    data = {
        'k': 'qalarm|heartbeat|201|prod',
        'i': 'localhost',
        't': int(time.time()),
        'type': 'h'
    }

    fp.write(json.dumps(data) + '\n')
    fp.close()


def main():
    print 'begin staring...'

    # Load records
    records = load_records()
    for path in records:
        print path, records[path]

    last_heartbeat_time = None

    # Run as daemon
    while not main.stop:
        # Heartbeat per 5 minute
        now = time.time()
        if last_heartbeat_time is None or (now - last_heartbeat_time) > 300:
            last_heartbeat_time = now
            heartbeat()

        for path, record in records.items():
            # Check existence, inode
            try:
                res = stat(path)
            except OSError:
                continue

            # New file, jump to the end
            if record['inode'] == 0 and record['offset'] == 0:
                print 'New file added, jump to the end %s@%d:%d' % (path, res.st_ino, res.st_size)
                record['inode'] = res.st_ino
                record['offset'] = res.st_size
                continue

            # No new data
            elif res.st_ino == record['inode'] and res.st_size == record['offset']:
                continue

            # Another file
            elif res.st_ino != record['inode'] or res.st_size < record['offset']:
                print 'Open new file %s@%d:%d' % (path, res.st_ino, record['offset'])
                record['inode'] = res.st_ino
                record['offset'] = 0

            # Open then set offset
            print 'Log file processed %s@%d:%d' % (path, res.st_ino, record['offset'])
            fp = open(path, 'r')
            fp.seek(record['offset'])

            # Process
            module = record['module']
            action = conf.check_list[module]['action']
            action(fp, module, report)

            # Save offset
            record['offset'] = fp.tell()
            fp.close()

        save_records(records)
        time.sleep(1)

    print 'finish staring'

# set stop flag
main.stop = False


def sig_handler(signum, frame):
    print 'Signal handler called with signal', signum
    main.stop = True


if __name__ == '__main__':

    signal.signal(signal.SIGTERM, sig_handler)

    main()

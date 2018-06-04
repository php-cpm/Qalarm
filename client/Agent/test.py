from os import path, stat
from pprint import pprint
import json
import re
import socket
import time
import sys
import copy


message='2016-06-03 00:20:17,008:14309(0x7fbeab4ff700):ZOO_WARN@zookeeper_interest@1557: Exceeded deadline by 11ms'

match = re.match('^(.+?)\,(.+)', message)

print match.group(2)

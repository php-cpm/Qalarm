#!/bin/bash

mkdir -p logs
chmod 777 logs 

#pm2 delete Qalarm >> ./logs/start.log 2>&1
#pm2 startOrGracefulReload config/env.json >> ./logs/start.log 2>&1
#nohup pm2 logs >> ./logs/start.log 2>&1 &

pm2 restart config/env.json 

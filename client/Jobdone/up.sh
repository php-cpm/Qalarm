if [ "$1" == "" ];then
    param="-n"
fi

env="test"

if [ "$2" != "" ];then
    env="production"
fi

if [ "$env" == "test" ];then
    #rsync -altv  ./* --exclude="logs" --exclude="*.swo" --exclude="*.swp" --exclude="tags" ttyc@172.16.10.20::GAEA_CLIENT/ $param
    rsync -altv  ./* --exclude="logs" --exclude="*.swo" --exclude="*.swp" --exclude="tags" ttyc@172.16.10.60:/srv/salt/gaea/gaea-client $param
    exit 0
fi

if [ "$env" == "production" ];then
    rsync -altv -e 'ssh -p 52110'  ./* --exclude="up.sh" --exclude="config.ini" --exclude="logs" --exclude="*.swo" --exclude="*.swp" --exclude="tags" ttyc@10.10.161.60:/srv/salt/gaea/gaea-client/ $param
    exit 0
fi


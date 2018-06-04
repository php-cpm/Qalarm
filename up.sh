if [ "$1" == "" ];then
    param="-n"
fi

#rsync -altv  ./* ttyc@172.16.10.10:/home/t/system/ttyc-gaea $param
rsync -altv  ./* --exclude="public/angulr/bower_components" --exclude="public/angulr/node_modules" --exclude="public/bower_components" --exclude="public/node_modules" --exclude="config" --exclude="tests" --exclude="bootstrap/cache" --exclude="storage" --exclude="*.swo" --exclude="*.swp" --exclude="tags" --exclude="vendor" ttyc@172.16.10.10:/home/t/system/ttyc-gaea $param

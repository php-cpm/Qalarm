# 初始化api-v3项目
#
# @author Yuchen Wang
# @date 2015-10-19

# 检查用户
if [ `id -u` != "0" ]; then
    echo "need run by root"
    exit 1
fi

BUILD_SPACE="$HOME/buildspace-`date +%F`"
BUILD_TARGET="/home/t/system/ttyc-api"

# 目录
mkdir -p $BUILD_SPACE $BUILD_TARGET
chown ttyc:ttyc $BUILD_TARGET

# 手动操作
if [ ! -f $BUILD_TARGET/.env ]; then
    echo -e "请先确认已进行下列操作：\n1.初始化发布\n2.配置.env文件\n3.通知陈飞配置QBus Agent"
    exit 2
fi

# 目录及权限
cd $BUILD_TARGET
storage_dirs="bootstrap/cache storage storage/app storage/framework storage/framework/cache storage/framework/sessions storage/framework/views /data/log/ttyc-api"
mkdir -p $storage_dirs
chmod 777 $storage_dirs
chown ttyc:ttyc $storage_dirs
rm -f storage/logs && ln -sf /data/log/ttyc-api storage/logs

# Crontab
cd $BUILD_SPACE
crontab -u ttyc -l | grep -v '/home/t/system/ttyc-api/artisan schedule:run' > crontab.conf
echo "* * * * * \$PHP /home/t/system/ttyc-api/artisan schedule:run >> /dev/null 2>&1" >> crontab.conf
crontab -u ttyc crontab.conf

# Supervisor
cd $BUILD_TARGET
if ! grep -F `grep 'program:' deploy/supervisor.conf` /etc/supervisord.conf > /dev/null; then
    cat deploy/supervisor.conf >> /etc/supervisord.conf
    supervisorctl reload
fi

# Nginx
cp $BUILD_TARGET/deploy/com.ttyongche.api.conf /usr/local/nginx/conf/host.d/

# self-test
/usr/local/php/bin/php $BUILD_TARGET/artisan ttyc:monitor_infra_service

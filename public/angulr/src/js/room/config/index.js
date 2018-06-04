/**
 * Created by admin on 15/10/31.
 */

var _ = require('underscore');
var path = require('path');
var default_config = require('./default.json'); //加载默认共享配置

//自启动匿名函数
module.exports = (function () {
    var version = (process.env.NODE_ENV||'develop').toLowerCase(), ver_config, config;
    //首先读取EVN 环境变量 NODE_ENV ,如果此变量未定义,赋值 develop (加载 develop.json )
    try {
        ver_config = require('./' + version);
        // 加载环境变量指定的配置,如果 YIJIEBUYI_VERSION=test  此处加载 test.json
    } catch (err) {
        console.log("Cannot find version " + version + " config file!!!");
        process.exit(-1);
    }

    config = _.extend({'version':version}, default_config, ver_config);
    // extend 是underscore 的一个api ,作用就是 把 default_config(默认配置),自定义配置(ver_config) 复制全部属性到 {'version':version}此对象上.如果有重复属性(后面的会覆盖前面的属性值)

    //下面可以给 config 对象添加一些属性,方便程序使用
    config.is_production = config.version === 'production';
    config.is_test = config.version === 'test';

    return config;
})();
<?php

if (! function_exists('parse_qconf_redis_url')) {
    function parse_qconf_redis_url($uri, $value) {
        $arr = explode(':', $uri);
        if (empty($value)) {
            return array('host' => $arr[0], 'port' => $arr[1], 'timeout' => 1, 'read_write_timeout' => 0.1);
        }
        return array_add(array('host' => $arr[0], 'port' => $arr[1]), 'password', $value);
    }
}

return [

    /*
    |--------------------------------------------------------------------------
    | PDO Fetch Style
    |--------------------------------------------------------------------------
    |
    | By default, database results will be returned as instances of the PHP
    | stdClass object; however, you may desire to retrieve records in an
    | array format for simplicity. Here you can tweak the fetch style.
    |
    */

    'fetch' => PDO::FETCH_CLASS,

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'gaea'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => storage_path('database.sqlite'),
            'prefix'   => '',
        ],

        'gaea' => [
            'driver'    => 'mysql',
            'database'  => env('GAEA_MYSQL_DATABASE'),
            'write'     => [
                // 'host'      => Qconf::getHost(env('PINCHE_MYSQL_MASTER_QCONF')),
                'host'      => env('GAEA_MYSQL_MASTER_HOST'),
                'username'  => env('GAEA_MYSQL_MASTER_USERNAME', ''),
                'password'  => env('GAEA_MYSQL_MASTER_PASSWORD', ''),
             ],
            'read'      => [
                // 'host'      => Qconf::getHost(env('PINCHE_MYSQL_SLAVE_QCONF')),
                'host'      => env('GAEA_MYSQL_SLAVE_HOST'),
                'username'  => env('GAEA_MYSQL_SLAVE_USERNAME', ''),
                'password'  => env('GAEA_MYSQL_SLAVE_PASSWORD', ''),
             ],

            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
            'options'       => [
                PDO::ATTR_PERSISTENT => env('GAEA_MYSQL_OPT_PERSISTENT', false),
            ],
        ],
        'qalarm' => [
            'driver'    => 'mysql',
            'database'  => env('QALARM_MYSQL_DATABASE'),
            'write'     => [
                // 'host'      => Qconf::getHost(env('GAEA_MYSQL_MASTER_QCONF')),
                'host'      => env('QALARM_MYSQL_MASTER_HOST'),
                'username'  => env('QALARM_MYSQL_MASTER_USERNAME', ''),
                'password'  => env('QALARM_MYSQL_MASTER_PASSWORD', ''),
             ],
            'read'      => [
                // 'host'      => Qconf::getHost(env('GAEA_MYSQL_SLAVE_QCONF')),
                'host'      => env('QALARM_MYSQL_SLAVE_HOST'),
                'username'  => env('QALARM_MYSQL_SLAVE_USERNAME', ''),
                'password'  => env('QALARM_MYSQL_SLAVE_PASSWORD', ''),
             ],

            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
            'options'       => [
                PDO::ATTR_PERSISTENT => env('QALARM_MYSQL_OPT_PERSISTENT', false),
            ],
        ],


        'pgsql' => [
            'driver'   => 'pgsql',
            'host'     => env('DB_HOST', 'localhost'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
        ],

        'sqlsrv' => [
            'driver'   => 'sqlsrv',
            'host'     => env('DB_HOST', 'localhost'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8',
            'prefix'   => '',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'gaea_migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [
        'cluster' => false,

        // 'default' => parse_qconf_redis_url(env('REDIS_DEFAULT_HOST'), env('REDIS_DEFAULT_PASSWD')),
        // 'queue' => parse_qconf_redis_url(env('REDIS_QUEUE_HOST'), env('REDIS_QUEUE_PASSWD')),
        //
        'default' => parse_qconf_redis_url(Qconf::getHost(env('REDIS_DEFAULT_QCONF')), env('REDIS_DEFAULT_PASSWD')),
        'queue' => parse_qconf_redis_url(Qconf::getHost(env('REDIS_QUEUE_QCONF')), env('REDIS_QUEUE_PASSWD')),
    ],

];

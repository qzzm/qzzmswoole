<?php

return [
    // 运行模式 [开发模式=0 生产模式=1]
    'MODE' => 0,
    // mysql配置
    'MYSQL' => [
        [
            'pool' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'db' => 'tukebbs',
            'user' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
            'prefix' => 'qzzm_',
        ],
        [
            'pool' => 'mysql1',
            'host' => 'localhost',
            'port' => 3306,
            'db' => 'erp1',
            'user' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
            'prefix' => 'qzzm_',
        ],
    ],
    // 缓存方式 数组方式,可同时注册多个 [1=Swoole内存表 , 2=Redis]
    'CACHE_TYPE' => [1],
    // redis配置
    'REDIS' => [
        'REDIS1' => [],
        'REDIS2' => [],
    ],
    //跨域及请求处理
    'CROSS' => [
        // 允许域名
        'domains' => ['*'],
        // 允许方法
        'methods' => [
            'GET',
            'POST',
            'OPTIONS',
            'PUT'
        ],
        // 允许请求头
        'headers' => [
            'Qzzm-Token',
            'Qzzm-Domain',
            'Qzzm-Action',
            'Content-Type',
            'Authorization',
            'X-Requested-With',
            'XMLHttpRequestUpload'
        ],
    ],
    // 服务器端配置
    'SERVER' => [
        'IS_WEBSOCKET' => 1,        //是否开启websocket服务
        'IS_HTTP' => 1,             //是否开启http服务        IS_HTTP 和 IS_WEBSOCKET 同时等于1时 表示同时开启两种服务
        'IS_SSL' => 0,              //是否开启wss/https服务 (目前暂时未开发此功能)
        'LISTEN_ADDRESS' => '127.0.0.1',
        'LISTEN_PORT' => 10080,
        'SETTING' => [
            'reactor_num' => 1, //与  一般为CPU的1~4倍的数量最好
            'worker_num' => 4,  //与  一般为CPU的1~4倍的数量最好
            'reload_async' => true,
            'max_wait_time' => 5,
//            'document_root'         => QZZM_ROOT . '/Static',
            'enable_static_handler' => true,
            //上传文件大小
//            'package_max_length' => 10 * 1024 * 1024,
        ],
        'TASK' => [
            'workerNum' => 8,
            'maxRunningNum' => 128,
            'timeout' => 15
        ],
        'HOT_RELOAD_DIR' => '/test', //开发模式下热重启监听的目录 留空为项目内全部文件(仅限服务启动后加载的文件有效)
    ],
    // 客户端配置
    'CLIENT' => [
        'LISTEN_ADDRESS' => '0.0.0.0',
        'LISTEN_PORT' => 10080,
//        'LISTEN_ADDRESS' => 'spider.server.qzus.cn',
//        'LISTEN_PORT' => 80,
        'HEART_CHECK_TIME' => 1,      //心跳检查时间 单位:秒
        'TOKEN' => 'hdaqzzm',
        'SETTING' => [
            'worker_num' => 1   //进程数 window下目前建议设置为1
        ],
        'HOT_RELOAD_DIR' => '/test/client', //开发模式下热重启监听的目录 留空为项目内全部文件(仅限服务启动后加载的文件有效)
    ],
];
<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

define('THINK_VERSION', '5.0.21');
define('THINK_START_TIME', microtime(true));
define('THINK_START_MEM', memory_get_usage());
define('EXT', '.php');
define('DS', DIRECTORY_SEPARATOR);
defined('THINK_PATH') or define('THINK_PATH', __DIR__ . DS);
define('LIB_PATH', THINK_PATH . 'library' . DS);
define('CORE_PATH', LIB_PATH . 'think' . DS);
define('TRAIT_PATH', LIB_PATH . 'traits' . DS);
defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . DS);
defined('ROOT_PATH') or define('ROOT_PATH', dirname(realpath(APP_PATH)) . DS);
defined('EXTEND_PATH') or define('EXTEND_PATH', ROOT_PATH . 'extend' . DS);
defined('VENDOR_PATH') or define('VENDOR_PATH', ROOT_PATH . 'vendor' . DS);
defined('RUNTIME_PATH') or define('RUNTIME_PATH', ROOT_PATH . 'runtime' . DS);
defined('LOG_PATH') or define('LOG_PATH', RUNTIME_PATH . 'log' . DS);
defined('CACHE_PATH') or define('CACHE_PATH', RUNTIME_PATH . 'cache' . DS);
defined('TEMP_PATH') or define('TEMP_PATH', RUNTIME_PATH . 'temp' . DS);
defined('CONF_PATH') or define('CONF_PATH', APP_PATH); // 配置文件目录
defined('CONF_EXT') or define('CONF_EXT', EXT); // 配置文件后缀
defined('ENV_PREFIX') or define('ENV_PREFIX', 'PHP_'); // 环境变量的配置前缀
defined('COMMON_PATH') or define('COMMON_PATH', APP_PATH . 'common' . DS);

// 环境常量
define('IS_CLI', PHP_SAPI == 'cli' ? true : false);
define('IS_WIN', strpos(PHP_OS, 'WIN') !== false);

// 载入Loader类
require CORE_PATH . 'Loader.php';

// 加载环境变量配置文件
if (is_file(ROOT_PATH . '.env')) {
    $env = parse_ini_file(ROOT_PATH . '.env', true);

    foreach ($env as $key => $val) {
        $name = ENV_PREFIX . strtoupper($key);

        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $item = $name . '_' . strtoupper($k);
                putenv("$item=$v");
            }
        } else {
            putenv("$name=$val");
        }
    }
}

/**
 * 设置服务端环境
 * @author chinayin <whereismoney@qq.com>
 */
$APP_STATUS = getenv(ENV_PREFIX . strtoupper(str_replace('.', '_', 'app_status')));
empty($APP_STATUS) && $APP_STATUS = '';
define('APP_STATUS', strtolower($APP_STATUS));
if (!in_array(APP_STATUS, ['dev', 'testing', 'production'], true)) {
//    die('Failed, APP_STATUS_ERROR');
}
define('IS_PRODUCTION', APP_STATUS === 'production');

/**
 * 生成并设置 request_id.
 * @author chinayin <whereismoney@qq.com>
 */
function gen_request_id() {
    $REQ_ARRS = gettimeofday();
    $pool = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    return $REQ_ARRS['sec'] . '-' . $REQ_ARRS['usec'] . '-' . substr(str_shuffle(str_repeat($pool, 6)), 0, 6);
}

/**
 * 如存在nginx跟踪ID,直接带过来
 * 如没有直接生成一个
 * @author chinayin <whereismoney@qq.com>
 */
if (!IS_CLI && isset($_SERVER['TRACE_PHP_ID'])) {
    define('TRACE_PHP_ID', $_SERVER['TRACE_PHP_ID']);
    define('REQUEST_ID', TRACE_PHP_ID);
}
defined('REQUEST_ID') || define('REQUEST_ID', gen_request_id());
if (!IS_CLI) {
    header('X-Request-Id: ' . REQUEST_ID);
}

// 注册自动加载
\think\Loader::register();

// 注册错误和异常处理机制
\think\Error::register();

// 加载惯例配置文件
\think\Config::set(include THINK_PATH . 'convention' . EXT);

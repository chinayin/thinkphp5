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

define('THINK_VERSION', '5.0.24');
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
/**
 * docker中定义需要配置runtime目录
 */
define('DEPLOY_IS_DOCKER', is_true(getenv('DEPLOY_POD_NAME') ? 1 : 0));
if (DEPLOY_IS_DOCKER && !defined('RUNTIME_PATH')) {
    // 2020-11-03 老配置兼容
    if (!empty(getenv('TP_RUNTIME_PATH'))) {
        define('RUNTIME_PATH', rtrim(getenv('TP_RUNTIME_PATH'), '/') . DS);
    } else {
        $DEPLOY_RUNTIME_ROOT_PATH = getenv('DEPLOY_RUNTIME_ROOT_PATH');
        $DEPLOY_APP_NAME = getenv('DEPLOY_APP_NAME');
        $DEPLOY_POD_NAME = getenv('DEPLOY_POD_NAME');
        if ($DEPLOY_RUNTIME_ROOT_PATH && $DEPLOY_APP_NAME && $DEPLOY_POD_NAME) {
            define('RUNTIME_PATH',
                rtrim($DEPLOY_RUNTIME_ROOT_PATH, '/') . DS .
                trim($DEPLOY_APP_NAME) . DS .
                trim($DEPLOY_POD_NAME) . DS .
                'runtime' . DS
            );
            define('RUNTIME_SCHEMA_PATH', ROOT_PATH . 'runtime' . DS . 'schema' . DS);
        }
    }
}
defined('CONF_PATH') or define('CONF_PATH', APP_PATH); // 配置文件目录
defined('CONF_EXT') or define('CONF_EXT', EXT); // 配置文件后缀
defined('ENV_PREFIX') or define('ENV_PREFIX', 'PHP_'); // 环境变量的配置前缀
defined('COMMON_PATH') or define('COMMON_PATH', APP_PATH . 'common' . DS);
//
defined('RUNTIME_PATH') or define('RUNTIME_PATH', ROOT_PATH . 'runtime' . DS);
defined('LOG_PATH') or define('LOG_PATH', RUNTIME_PATH . 'log' . DS);
defined('CACHE_PATH') or define('CACHE_PATH', RUNTIME_PATH . 'cache' . DS);
defined('TEMP_PATH') or define('TEMP_PATH', RUNTIME_PATH . 'temp' . DS);
defined('RUNTIME_SCHEMA_PATH') or define('RUNTIME_SCHEMA_PATH', RUNTIME_PATH . 'schema' . DS);

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
function is_true($val)
{
    $boolval = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return ($boolval === null ? false : $boolval);
}

/**
 * 设置服务端环境
 *
 * @author chinayin <whereismoney@qq.com>
 */
$APP_STATUS = getenv(ENV_PREFIX . strtoupper(str_replace('.', '_', 'app_status')));
empty($APP_STATUS) && $APP_STATUS = '';
define('APP_STATUS', strtolower($APP_STATUS));
if (!in_array(APP_STATUS, ['dev', 'testing', 'uat', 'production'], true)) {
//    die('Failed, APP_STATUS_ERROR');
}
/**
 * 是否正式环境
 */
define('IS_PRODUCTION', APP_STATUS === 'production');
/**
 * 是否预上线环境
 */
define('IS_UAT', APP_STATUS === 'uat');
/**
 * 是否内网vpc环境
 */
define('DEPLOY_IS_VPC_ZONE', is_true(getenv('DEPLOY_IS_VPC_ZONE')));
// 2022-06-01 在k8s环境下已废弃
define('IS_PRIVATE_ZONE_SERVER', getenv('IS_PRIVATE_ZONE_SERVER') === false ? DEPLOY_IS_VPC_ZONE : is_true(getenv('IS_PRIVATE_ZONE_SERVER')));
/**
 * 是否是海外服务器部署
 */
define('DEPLOY_IS_ABROAD_ZONE', is_true(getenv('DEPLOY_IS_ABROAD_ZONE')));
/**
 * 服务器部署地域
 */
define('DEPLOY_REGION_A2CODE', getenv('DEPLOY_REGION_A2CODE') ?: '');
define('DEPLOY_REGION_ID', getenv('DEPLOY_REGION_ID') ?: '');
define('DEPLOY_ZONE_ID', getenv('DEPLOY_ZONE_ID') ?: '');
/**
 * 是否docker镜像部署
 */
define('DEPLOY_POD_NAME', getenv('DEPLOY_POD_NAME') ?: '');
define('DEPLOY_POD_IP', getenv('DEPLOY_POD_IP') ?: '');
define('DEPLOY_APP_NAME', getenv('DEPLOY_APP_NAME') ?: '');
/**
 * 生成并设置 request_id.
 */
function gen_request_id()
{
    // 2020-01-14 新唯一id,取16位为了区分正式测试生成
    return substr(md5(uniqid(rand(), true)), 8, 16);
}

/**
 * 如存在nginx跟踪ID,直接带过来
 * 如没有直接生成一个
 *
 * @author chinayin <whereismoney@qq.com>
 */
if (!IS_CLI && isset($_SERVER['TRACE_PHP_ID']) && !empty($_SERVER['TRACE_PHP_ID'])) {
    define('TRACE_PHP_ID', $_SERVER['TRACE_PHP_ID']);
    define('REQUEST_ID', TRACE_PHP_ID);
}else{
    define('REQUEST_ID', gen_request_id());
    header('x-request-id: ' . REQUEST_ID);
}

/**
 * trace_id
 * 2020-10-26 阿里云arms trace_id
 */
$TRACE_ID = function_exists('obtain_arms_trace_id') ? (obtain_arms_trace_id() ?: '-') : '-';
defined('TRACE_ID') || define('TRACE_ID', $TRACE_ID);
IS_CLI || header('x-trace-id: ' . TRACE_ID);

// 注册自动加载
\think\Loader::register();

// 注册错误和异常处理机制
\think\Error::register();

// 加载惯例配置文件
\think\Config::set(include THINK_PATH . 'convention' . EXT);

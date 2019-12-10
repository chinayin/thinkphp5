<?php

namespace think\behavior\logger;

use think\Config;
use think\Debug;
use think\Lang;
use think\Request;

/**
 * 记录每次request时 提交的表单信息
 *
 * @author  lei.tian
 * @version 2019-12-10
 */
class LoggerRequest
{
    /** @var array 配置 */
    protected $options = [
        /** 是否禁用 */
        'disable' => false,
        /** 数据版本 */
        'version' => 1,
        /** 保存路径 */
        'path' => RUNTIME_PATH . 'logger',
        /** 忽略Controller */
        'excepted_controller' => [],
    ];
    /** @var request */
    private $request;

    public function run(&$params)
    {
        // 初始化
        $this->_initialize();
        if ($this->disable()) {
            return;
        }
        // 打点内容
        $s = $this->buildLoggerData();
        // 写
        $this->write($s);
    }

    /**
     * 初始化
     */
    private function _initialize()
    {
        $this->request = request();
        $configs = Config::get('logger_request');
        if (!empty($configs)) {
            $this->options = array_merge($this->options, $configs);
        }
    }

    /**
     * 获取日志路径
     *
     * @return string
     */
    private function getLogFile()
    {
        $filename = date('Y') . DIRECTORY_SEPARATOR . date('Ymd') . DIRECTORY_SEPARATOR . date('H') . '.log';
        $destination = rtrim($this->options['path'], '/') . '/' . $filename;
        return $destination;
    }

    /**
     * 写日志
     *
     * @param $message
     *
     * @return bool
     */
    private function write($message)
    {
        $filename = $this->getLogFile();
        $dir = dirname($filename);
        is_dir($dir) || mkdir($dir, 0755, true);
        return error_log($message . "\r\n", 3, $filename);
    }

    /**
     * 生成日志内容
     *
     * @return string
     */
    private function buildLoggerData(): string
    {
        // v1   version|reqid|time|use_time|use_peak_mem|ip|method|host|uri|lang|pid|hostname
        $version = $this->options['version'];
        $data = [];
        $data[] = $version;
        $data[] = REQUEST_ID;
        $data[] = microtime_float();
        $data[] = Debug::getUseTime(3);
        $data[] = round((memory_get_peak_usage() - THINK_START_MEM) / 1024, 3);
        $data[] = $this->request->ip();
        $data[] = $this->request->method();
        $data[] = $this->request->host();
        $data[] = $this->request->url();
        $data[] = Lang::range();
        $data[] = getmypid();
        $data[] = gethostname();
        $s = implode('|', $data);
        unset($data);
        return $s;
    }

    /**
     * 是否跳出
     */
    private function disable()
    {
        if ($this->options['disable']) {
            return true;
        }
        if ($this->checkExceptedController()) {
            return true;
        }

        return false;
    }

    /**
     * 判断组.
     */
    private function checkExceptedController()
    {
        if (empty($this->options['excepted_controller'])) {
            return false;
        }
        return in_array_case($this->request->controller(), $this->options['excepted_controller']);
    }

}

<?php
namespace Logger;

class Logger
{
    const L_ALL = 0;

    const L_DEBUG = 1;

    const L_TRACE = 2;

    const L_INFO = 3;

    const L_NOTICE = 4;

    const L_WARNING = 5;

    const L_FATAL = 6;

    public static $arrDesc = array(
            0 => 'ALL',
            1 => 'DEBUG',
            2 => 'TRACE',
            3 => 'INFO',
            4 => 'NOTICE',
            5 => 'WARNING',
            6 => 'FATAL',
        );

    private static $logLevel = self::L_DEBUG;

    private static $arrBasic = array();

    private static $files = array();

    private static $forceFlush = false;

    public static function flush()
    {
        foreach (self::$files as $file) {
            fflush($file);
        }
    }

    public static function addBasic($key, $value = '')
    {
        self::$arrBasic [$key] = $value;
    }

    /**
     * @param      $filename
     * @param      $level
     * @param null $arrBasic
     */
    public static function init($filename, $level, $arrBasic = null)
    {
        if (!isset(self::$arrDesc [$level])) {
            trigger_error("invalid level:$level");

            return;
        }
        self::$logLevel = $level;
        $dir = dirname($filename);
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0755, true)) {
                trigger_error(
                    "create log file $filename failed, no permmission"
                );

                return;
            }
        }
        self::$files [0] = fopen($filename, 'a+');
        if (empty(self::$files [0])) {
            trigger_error(
                "create log file $filename failed, no disk space for permission"
            );
            self::$files = array();

            return;
        }

        self::$files [1] = fopen($filename . '.wf', 'a+');
        if (empty(self::$files [1])) {
            trigger_error(
                "create log file $filename.wf failed, no disk space for permission"
            );
            self::$files = array();

            return;
        }

        if (!empty($arrBasic)) {
            self::$arrBasic = $arrBasic;
        }

        self::addBasic('logid', self::getLogId());
    }

    public static function debug()
    {
        $arrArg = func_get_args();
        self::log(self::L_DEBUG, $arrArg);
    }

    private static function log($level, $arrArg)
    {
        if ($level < self::$logLevel || empty(self::$files) || empty($arrArg)) {
            return;
        }

        $content = '[' . date('Ymd H:i:s');
        $content .= '][';
        $content .= self::$arrDesc [$level];
        $content .= "]";
        foreach (self::$arrBasic as $key => $value) {
            $content .= empty($value) ? "[$key]" : "[$key:$value]";
        }

        $arrTrace = debug_backtrace();
        if (isset($arrTrace [1])) {
            $line = $arrTrace [1] ['line'];
            $file = $arrTrace [1] ['file'];
            $file = basename($file);
            $content .= "[$file:$line] ";
        }

        foreach ($arrArg as $idx => $arg) {
            if ($arg instanceof BtstoreElement) {
                $arg = $arg->toArray();
            }

            if (is_array($arg)) {
                array_walk_recursive(
                    $arg,
                    array(__CLASS__, 'checkPrintable')
                );

                $data = json_encode($arg);

                $arrArg [$idx] = $data;
            }
        }
        $content .= call_user_func_array('sprintf', $arrArg);
        $content .= "\n";

        $file = self::$files [0];
        fputs($file, $content);
        if (self::$forceFlush) {
            fflush($file);
        }

        if ($level <= self::L_NOTICE) {
            return;
        }

        $file = self::$files [1];
        fputs($file, $content);
        if (self::$forceFlush) {
            fflush($file);
        }
    }

    public static function trace()
    {
        $arrArg = func_get_args();
        self::log(self::L_TRACE, $arrArg);
    }

    public static function info()
    {
        $arrArg = func_get_args();
        self::log(self::L_INFO, $arrArg);
    }

    public static function notice()
    {
        $arrArg = func_get_args();
        self::log(self::L_NOTICE, $arrArg);
    }

    public static function warning()
    {
        $arrArg = func_get_args();
        self::log(self::L_WARNING, $arrArg);
    }

    public static function fatal()
    {
        $arrArg = func_get_args();
        self::log(self::L_FATAL, $arrArg);
    }

    public static function getLogLevel()
    {
        return self::$arrDesc;
    }

    private static function checkPrintable(&$data)
    {
        if (!is_string($data)) {
            return;
        }

        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\xFF]/', $data)) {
            $data = base64_encode($data);
        }
    }

    /**
     * 获取logid
     * @return string
     */
    public static function getLogId()
    {
        return mt_rand(1000000000, 9999999999);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */

<?php

class Util_Cron
{

    private static $sLogName = 'cronlog';

    public static function log ($sMsg)
    {
        Yaf_Logger::debug(date('[Y-m-d H:i:s]') . $sMsg, self::$sLogName);
        echo date('[Y-m-d H:i:s]') . $sMsg;

        $aRow = array(
            'sCmd' => $sMsg,
            'sRunTime' => date('Y-m-d H:i:s'),
            'iStatus' => 1,
            'iCreateTime' => time(),
            'iUpdateTime' => time()
        );
        $oDbh = Util_Common::getMySQLDB('db_decision_global');
        $oDbh->insert('t_cron_log', $aRow, true);
    }

    /**
     * 杀死子任务
     */
    public static function stopSub ()
    {
        self::log("kill sub task:\n");
        $tasks = self::getTasks();
        foreach ($tasks as $task) {
            self::killProcess($task['path']);
        }
        self::log("kill sub done.\n");
    }

    /**
     * 杀死主进程
     *
     * @param unknown $main_file
     */
    public static function stopMain ($main_file)
    {
        self::killProcess($main_file);
    }

    /**
     * 启动主进程
     *
     * @param unknown $main_file
     */
    public static function startMain ($main_file)
    {
        $sLogPath = Util_Common::getConf('sBaseDir', 'logger') . '/' . self::$sLogName;
        if (! file_exists($sLogPath)) {
            umask(0000);
            mkdir($sLogPath, 0755);
        }
        self::newProcess($main_file);
    }

    /**
     * 启动子进程
     */
    public static function startSub ()
    {
        while (true) {
            $tasks = self::getTasks();
            $tasks = empty($tasks) ? array() : $tasks;
            $last_time = microtime(true);
            $now = explode(':', date('i:H:d:m:w'));
            $second = $now[0];
            $mutine = $now[1];
            foreach ($tasks as $task) {
                try {
                    // 每个小时的23分钟，将常驻进程重启
                    if ($second == 0 && $mutine == 23 && $task['cron'] == '* * * * *') {
                        self::killProcess($task['path']);
                    }

                    // 如果进程数大于设定数，则进程重启
                    $task_count = self::countProcess($task['path']);
                    if ($task_count > $task['num']) {
                        self::killProcess($task['path']);
                    }

                    // 检查是否符合执行时间
                    $run_flag = true;
                    $cron = explode(' ', $task['cron']);
                    for ($i = 0; $i < 5; $i ++) {
                        if ($cron[$i] == '*') {
                            continue;
                        } elseif (is_numeric($cron[$i]) && (int) $now[$i] == (int) $cron[$i]) {
                            continue;
                        } elseif (strpos($cron[$i], '/') !== false) {
                            $tmp = explode('/', $cron[$i]);
                            $by = max(1, isset($tmp[1]) ? $tmp[1] : 1);
                            if ((int) $now[$i] % $by == 0) {
                                continue;
                            }
                        }

                        $run_flag = false;
                        break;
                    }

                    if ($run_flag) {
                        for ($pid = 0; $pid < $task['num']; $pid ++) {
                            // 检查当前任务的进程个数
                            $exec_num = self::countProcess($task['path'], $pid);
                            if ($exec_num == 0) {
                                self::newProcess($task['path'], $pid);
                            }
                        }
                    }
                } catch (Exception $excp_obj) {
                    self::log($excp_obj->getMessage());
                }
            }

            // 休眠时间
            sleep(61 - date('s'));
        }
    }

    /**
     * 取得任务列表
     *
     * @return Ambigous <NULL, multitype:>
     */
    public static function getTasks ()
    {
        return include Yaf_G::getConfPath() . '/cron.php';
    }

    /**
     * 取得该任务执行文件的完整
     *
     * @param string $route
     *            任务文件名
     * @param int $pid
     *            任务PID
     * @return int
     */
    public static function getRunCmd ($route, $pid = -1)
    {
        $cmd = $_SERVER['_'] . ' ';
        $cmd .= ENV_CMD_MAIN . ' ';
        if (substr($route, 0, 7) == 'http://') {
            $cmd .= $route;
        } else {
            $cmd .= 'http://' . ENV_CMD_HOST . $route;
        }
        if ($pid != -1) {
            $sChar = strstr($route, '?') ? '&' : '?';
            $cmd =rtrim($cmd, '/? ') . $sChar . 'pid=' . $pid;
        }
        $cmd .= " ";
        $cmd .= ENV_SCENE;
        return $cmd;
    }

    /**
     * 取得该任务在运行的进程数
     *
     * @param string $route
     *            任务文件名
     * @param int $pid
     *            任务PID
     * @return int
     */
    public static function countProcess ($route, $pid = -1)
    {
        $cmd = 'ps -efww | grep "' . self::getRunCmd($route, $pid) . '"|grep -v grep|wc -l';
        $out = '';
        exec($cmd, $out);
        $exec_num = isset($out[0]) ? $out[0] : 0;
        // echo $cmd . ' run num: ' . $exec_num . "\n";
        return $exec_num;
    }

    /**
     * 杀死某个任务
     *
     * @param string $route
     *            任务文件名
     * @param int $pid
     *            任务PID
     * @return void
     */
    public static function killProcess ($route, $pid = -1)
    {
        self::log("stop $route ... ");
        $cmd = 'ps -efww | grep "' . self::getRunCmd($route, $pid) . '"|grep -v grep|awk \'{ print $2 }\'|xargs --no-run-if-empty kill -9';
        //print_r($cmd);die;
        // echo "$cmd\n";
        exec($cmd);
        self::log("ok\n");
    }

    /**
     * 开始一个新的任务进程
     *
     * @param string $route
     *            任务文件名
     * @param int $pid
     *            任务PID
     * @return void
     */
    public static function newProcess ($route, $pid = -1)
    {
        self::log("start $route ... \n");
        $cmd = self::getRunCmd($route, $pid);
        $cmd .= ' >> ';
        $sPath = Util_Common::getConf('sBaseDir', 'logger') . '/' . self::$sLogName . '/';
        if (! is_dir($sPath)) {
            umask(0000);
            if (false === mkdir($sPath, 0775, true)) {
                throw new Exception(__CLASS__ . ': can not create path(' . $sPath . ').');
                return false;
            }
        }

        $cmd .= $sPath . str_replace('/', '_', trim($route, '/')) . '.log ';
        $cmd .= '2>&1 &';
        self::log("$cmd\n");
        exec($cmd);
        self::log("ok\n");
    }

    /**
     * 清理7天前的日志
     */
    public static function clearLog ()
    {
        $iLastTime = time() - 7 * 86400;
        $sPath = Util_Common::getConf('sBaseDir', 'logger') . '/' . self::$sLogName . '/';
        foreach (glob($sPath . '*') as $sFile) {
            if (filectime($sFile) < $iLastTime) {
                unlink($sFile);
                echo $sFile . "\n";
            }
        }

        $oDbh = Util_Common::getMySQLDB('db_decision_global');
        $oDbh->query("DELETE FROM t_cron_log WHERE iCreateTime<$iLastTime");
    }
}
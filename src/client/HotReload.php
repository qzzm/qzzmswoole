<?php
/**
 * QZZM [ 广西启米科技有限公司 ]
 * Copyright (c) 2005~2019 http://www.qzzm.cn All rights reserved.
 * QQ:5478890
 * Email: 5478890@qq.com
 * Telephone: +86 18607775565
 * Address: 中国广西钦州市钦南区文峰街道龙墩路6号
 * Date: 2019-08-03 23:46:06
 * Author: hda
 * Version: 0.1
 * Progress: yaopifa
 */

namespace qzzm\client;

use qzzm\utlis\Singleton;
use Swoole\Table;
use Swoole\Timer;

/**
 * 暴力热重载
 * Class HotReload
 * @package App\Process
 */
class HotReload
{
    use Singleton;
    /** @var \swoole_table $table */
    protected $table;
    protected $isReady = false;

    protected $monitorDir; // 需要监控的目录
    protected $monitorExt; // 需要监控的后缀

    /**
     * 启动定时器进行循环扫描
     */
    public function run($arg)
    {
        $pid = $arg['pid'] ?? 0;

        $config = require QZZM_ROOT . '/config.php';
        // 此处指定需要监视的目录 建议只监视App目录下的文件变更
//        $this->monitorDir = !empty($arg['monitorDir']) ? $arg['monitorDir'] : QZZM_ROOT . '/app';
        $dir = $config['CLIENT']['HOT_RELOAD_DIR'] ?? '';
        $this->monitorDir = !empty($arg['monitorDir']) ? $arg['monitorDir'] : QZZM_ROOT . $dir;

        // 指定需要监控的扩展名 不属于指定类型的的文件 无视变更 不重启
        $this->monitorExt = !empty($arg['monitorExt']) && is_array($arg['monitorExt']) ? $arg['monitorExt'] : ['php'];
        //进行暴力扫描
        $this->table = new Table(512);
        $this->table->column('mtime', Table::TYPE_INT, 4);
        $this->table->create();
        $this->runComparison($pid);
        Timer::tick(1000, function () use ($pid) {
            $this->runComparison($pid);
        });
    }

    /**
     * 扫描文件变更
     */
    private function runComparison(int $pid)
    {
        $startTime = microtime(true);
        $doReload = false;

        $dirIterator = new \RecursiveDirectoryIterator($this->monitorDir);
        $iterator = new \RecursiveIteratorIterator($dirIterator);
        $inodeList = array();

        // 迭代目录全部文件进行检查
        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $ext = $file->getExtension();
            if (!in_array($ext, $this->monitorExt)) {
                continue; // 只检查指定类型
            } else {
                // 由于修改文件名称 并不需要重新载入 可以基于inode进行监控
                $inode = $file->getInode();
                $mtime = $file->getMTime();
                array_push($inodeList, $inode);
                if (!$this->table->exist($inode)) {
                    // 新建文件或修改文件 变更了inode
                    $this->table->set($inode, ['mtime' => $mtime]);
                    $doReload = true;
                } else {
                    // 修改文件 但未发生inode变更
                    $oldTime = $this->table->get($inode)['mtime'];
                    if ($oldTime != $mtime) {
                        $this->table->set($inode, ['mtime' => $mtime]);
                        $doReload = true;
                    }
                }
            }
        }

        foreach ($this->table as $inode => $value) {
            // 迭代table寻找需要删除的inode
            if (!in_array(intval($inode), $inodeList)) {
                $this->table->del($inode);
                $doReload = true;
            }
        }

        if ($doReload) {
            $count = $this->table->count();
            $time = date('Y-m-d H:i:s');
            $usage = round(microtime(true) - $startTime, 3);
            $arr = [31, 32, 33, 35, 36];
            $color = $arr[array_rand($arr, 1)];
            if (!$this->isReady == false) {
                // 监测到需要进行热重启
                echo "\e[0;{$color}msever reload at {$time} use : {$usage} s total: {$count} files\e[0m\n";
                if ($pid > 0) {
                    //SIGTERM 信号：中止服务，向所有工作进程发送SIGTERM关闭进程
                    //SIGUSR1 信号：重启工作进程，管理器会逐个重启工作进程
                    \Swoole\Process::kill($pid, SIGUSR1);
                }
            } else {
                // 首次扫描不需要进行重启操作
                echo "\e[0;{$color}m-----------------------------------------------------------------------\nhot reload ready at {$time} use : {$usage} s total: {$count} files\e[0m\n";
                $this->isReady = true;
            }
        }
    }

}

<?php

declare(strict_types=1);

namespace nova\plugin\login\db\Dao;

use nova\framework\core\Context;
use nova\framework\core\Logger;
use nova\plugin\ip\IpLocation;
use nova\plugin\login\db\Model\LogModel;
use nova\plugin\device\UserAgent;
use nova\plugin\orm\object\Dao;
use function nova\framework\throttle;

class LogDao extends Dao
{
    /**
     * 获取模型类名
     * @return void
     */
    public function __construct(string $model = null, string $child = null, $user_key = null)
    {
        parent::__construct(LogModel::class, $child, $user_key);
        throttle("log_cleanup", 300, function () {
            LogDao::getInstance()->cleanOldLogs();
        });
    }

    /**
     * 记录系统操作日志
     *
     * @param  string $action      操作类型
     * @param  string $description 操作描述
     * @param  array  $data        相关数据
     * @return int    插入的日志ID
     */
    public function logAction(
        int $user_id,
        string $action,
        string $description = '',
        array $data = [],
    ): int {
        $model = new LogModel();
        $model->user_id = $user_id;
        $model->action = $action;
        $model->description = $description;
        $model->data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $model->ip = Context::instance()->request()->getClientIP();
        $model->address = join(" ", IpLocation::getLocation($model->ip));
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        [ $OsName  ,  $OsImg, $BrowserName, $BrowserImg ] = UserAgent::parse($ua);
        $model->os = "$OsImg $OsName";
        $model->browser = "$BrowserImg $BrowserName";
        $model->create_time = time();

        return $this->insertModel($model);
    }

    /**
     * 清理90天前的日志
     *
     * @return int 删除的记录数
     */
    public function cleanOldLogs(): int
    {
        $ninetyDaysAgo = time() - (90 * 24 * 60 * 60); // 90天前的时间戳

        try {
            $result = $this->delete()
                ->where(["create_time < " => $ninetyDaysAgo])
                ->commit();

            $count = is_int($result) ? $result : 0;
            Logger::info("已清理 {$count} 条90天前的日志记录");
            return $count;
        } catch (\Exception $e) {
            Logger::error("清理日志失败: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 获取指定用户的操作日志
     *
     * @param  string $userId 用户ID
     * @param  int    $page   页码
     * @param  int    $size   每页记录数
     * @return array
     */
    public function getUserLogs(string $userId, int $page = 1, int $size = 20): array
    {
        return $this->getAll(
            null,
            ['user_id' => $userId],
            ($page - 1) * $size,
            $size,
            true,
            ['create_time' => 'DESC']
        );
    }

    /**
     * 获取指定操作类型的日志
     *
     * @param  string $action 操作类型
     * @param  int    $page   页码
     * @param  int    $size   每页记录数
     * @return array
     */
    public function getActionLogs(string $action, int $page = 1, int $size = 20): array
    {
        return $this->getAll(
            null,
            ['action' => $action],
            ($page - 1) * $size,
            $size,
            true,
            ['create_time' => 'DESC']
        );
    }

    /**
     * 搜索日志
     *
     * @param  string $keyword 关键词
     * @param  int    $page    页码
     * @param  int    $size    每页记录数
     * @return array
     */
    public function searchLogs(string $keyword, int $page = 1, int $size = 20): array
    {
        return $this->getAll(
            null,
            ["description LIKE " => "%{$keyword}%"],
            ($page - 1) * $size,
            $size,
            true,
            ['create_time' => 'DESC']
        );
    }

    /**
     * 当表被创建时执行
     */
    public function onCreateTable(): void
    {
        Logger::info("日志表已创建，初始化清理任务");
        // 表创建时可以执行一些初始化操作
    }
}

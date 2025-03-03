<?php
declare(strict_types=1);

namespace app\db\Model;

use nova\plugin\orm\object\Model;
use nova\plugin\orm\object\SqlKey;

class LogModel extends Model
{
    public int $user_id;
    /**
     * @var string 操作类型
     */
    public string $action = '';
    
    /**
     * @var string 操作描述
     */
    public string $description = '';
    
    /**
     * @var string 操作IP地址
     */
    public string $ip = '';
    /**
     * @var string
     */
    public string $browser = '';

    public string $os = '';
    /**
     *
     */
    public string $address = '';
    /**
     * @var string 操作相关的数据，JSON格式
     */
    public string $data = '{}';
    
    /**
     * @var int 操作时间戳
     */
    public int $create_time = 0;

    /**
     * 获取格式化的时间
     * 
     * @return string 格式化的时间
     */
    public function getFormattedTime(): string
    {
        return date('Y-m-d H:i:s', $this->create_time);
    }

    /**
     * 获取解析后的数据
     * 
     * @return array 解析后的JSON数据
     */
    public function getParsedData(): array
    {
        return json_decode($this->data, true) ?: [];
    }
}
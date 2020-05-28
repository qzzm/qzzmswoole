<?php


namespace qzzm\cache;


use qzzm\lib\Singleton;
use Swoole\Table;

class RAM
{
    use Singleton;

    private $table;

    public function create(int $size = 1024): RAM
    {
        //建立内存数据表
        $this->table = new Table($size);
        $this->table->column('content', Table::TYPE_STRING, 2 * 1024 * 1024);      //缓存内容
        $this->table->column('data_type', Table::TYPE_STRING, 10);
        $this->table->column('expire_time', Table::TYPE_INT, 4);  //缓存设置时间
        $this->table->create();
//        $server->table = $this->table;//\Swoole\WebSocket\Server $server,
        return $this;
    }

    private function getTable(): Table
    {
        return $this->table;
    }

    public function set(string $key, $content, int $cache_time = 0): bool
    {
        $dataType = strtoupper(gettype($content));
        switch ($dataType) {
            case 'STRING':
                $value = $content;
                break;
            case 'BOOLEAN':
                $value = $content ? 1 : 0;
                break;
            case 'INTEGER':
                $value = strval($content);
                break;
            case 'DOUBLE':
                $value = strval($content);
                break;
            case 'ARRAY':
                $value = json_encode($content);
                break;
            case 'OBJECT':
                $value = json_encode($content);
                break;
            default :
                $value = $content;
                break;
        }

        $data = [
            'content' => $value,
            'data_type' => $dataType,
            'expire_time' => $cache_time ?? time() + $cache_time
        ];
        return $this->getTable()->set($key, $data);
    }

    public function get(string $key)
    {
        $data = $this->getTable()->get($key);
        if ($data) {
            if ($data['expire_time'] === 0 || $data['expire_time'] >= time()) {
                $dataType = $data['data_type'];
                switch ($dataType) {
                    case 'STRING':
                        return $data['content'];
                    case 'BOOLEAN':
                        return $data['content'] == 1;
                    case 'INTEGER':
                        return intval($data['content']);
                    case 'DOUBLE':
                        return floatval($data['content']);
                    case 'ARRAY':
                        return json_decode($data['content'], true);
                    case 'OBJECT':
                        return json_decode($data['content']);
                    default :
                        return $data['content'];
                }
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    public function remove(string $key): bool
    {
        return $this->getTable()->del($key);
    }
}
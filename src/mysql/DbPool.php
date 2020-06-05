<?php


namespace qzzm\mysql;


use PDO;
use qzzm\lib\Singleton;
use Swoole\Database\PDOPool;
use Swoole\Database\PDOStatementProxy;

final class DbPool
{
    use Singleton;

    //数据库连接
    private $connections = [];
    private $tablePrefixs = [];
    private $pool;
    private $mysql;

    private function reset()
    {
        $this->mysql = null;
        $this->pool = null;
    }

    public function addConnection(Config $config, string $poolName = 'mysql'): DbPool
    {
        $pool = Connection::getInstance()->addPool($config, $poolName);
        $this->tablePrefixs[$poolName] = $config->getPrefix();
        $this->connections[$poolName] = $pool;
        return $this;
    }


    private function getPool(): ?PDOPool
    {
        if ($this->pool) {
            return $this->pool;
        } else {
            $pool = $this->connections[$this->mysql] ?? null;
            $this->pool = $pool;
            return $pool;
        }
    }

    public function getTablePrefix(): string
    {
        $prefix = $this->tablePrefixs[($this->mysql ?: 'mysql')] ?? '';
        $this->reset();
        return $prefix;
    }

    public function pool(string $mysql = 'mysql')
    {
        $this->mysql = $mysql;
        return $this;
    }

    /**
     * 设置查询返回的数据类型
     * @param PDOStatementProxy $stmt
     */
    private function setReturnMode(PDOStatementProxy $stmt, $returnType = null): void
    {
        $fType = strtoupper(gettype($returnType));
        switch ($fType) {
            case 'NULL':
                //返回数组
                $stmt->setFetchMode(PDO::FETCH_ORI_FIRST);
                break;
            case 'STRING':
                if (class_exists($returnType)) {
                    //返回指定命名空间路径下的类
                    $stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $returnType);
                } else {
                    $stmt->setFetchMode(PDO::FETCH_OBJ);
                }
                break;
            default:
                //返回匿名对象
                $stmt->setFetchMode(PDO::FETCH_OBJ);
                break;
        }
    }

    public function fetchAll($sql, $params, $returnType = null, $type = null)
    {
        $sqlResult = new Result();
        $resData = [];
        $pool = $this->getPool();
        $pdo = $pool->get();
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $stmt = $pdo->prepare($sql);
        try {
            $result = $stmt->execute($params);
            $this->setReturnMode($stmt, $returnType);
            $sqlResult->setSql($stmt->queryString);
            $resData = $stmt->fetchAll();
            $pool->put($pdo);
        } catch (\PDOException $ex) {
            $result = false;
            $sqlResult->setError($stmt->errorInfo());
        }
        if ($result) {
            $sqlResult->setFlag(true);
            $sqlResult->setResult($type === 'ONE' ? ($resData[0] ?? []) : $resData);
        } else {
            $sqlResult->setFlag(false);
            $sqlResult->setResult([]);
        }
        $this->reset();
        return $sqlResult;
    }

    public function insert(string $sql, array $data): Result
    {
        $sqlResult = new Result();
        $insertId = 0;
        $pool = $this->getPool();
        $pdo = $pool->get();
        $stmt = $pdo->prepare($sql);
        try {
            $result = $stmt->execute($data);
            $sqlResult->setSql($stmt->queryString);
            $insertId = intval($pdo->lastInsertId());
            $pool->put($pdo);
        } catch (\PDOException $ex) {
            $result = false;
            $sqlResult->setError($stmt->errorInfo());
        }
        if ($result) {
            $sqlResult->setFlag(true);
            $sqlResult->setResult($insertId);
        } else {
            $sqlResult->setFlag(false);
            $sqlResult->setResult(0);
        }
        $this->reset();
        return $sqlResult;
    }

    public function update(string $sql, array $params): Result
    {
        $sqlResult = new Result();
        $effectRow = 0;
        $pool = $this->getPool();
        $pdo = $pool->get();
        $stmt = $pdo->prepare($sql);
        try {
            $result = $stmt->execute($params);
            $sqlResult->setSql($stmt->queryString);
            $effectRow = intval($stmt->rowCount());
            $pool->put($pdo);
        } catch (\PDOException $ex) {
            $result = false;
            $sqlResult->setError($stmt->errorInfo());
        }

        if ($result) {
            $sqlResult->setFlag(true);
            $sqlResult->setResult($effectRow);
        } else {
            $sqlResult->setFlag(false);
            $sqlResult->setResult(0);
        }
        $this->reset();
        return $sqlResult;
    }

    /**
     * 开始事务 返回当前例
     * @param string $poolName
     * @return Db
     */
    public function startTrans(): DbPool
    {
        $pool = $this->getPool();
        $pdo = $pool->get();
        $pdo->beginTransaction();
        $pool->put($pdo);
        return $this;
    }

    /**
     * 完成事务
     * @param string $poolName
     */
    public function commit(): void
    {
        $pool = $this->getPool();
        $pdo = $pool->get();
        $pdo->commit();
        $pool->put($pdo);
    }

    /**
     * 回滚事务
     * @param string $poolName
     */
    public function rollBack(): void
    {
        $pool = $this->getPool();
        $pdo = $pool->get();
        $pdo->rollBack();
        $pool->put($pdo);
    }
}
<?php


namespace qzzm\mysql;

use PDO;
use qzzm\lib\Log;
use qzzm\lib\Singleton;
use qzzm\validate\Validate;
use Swoole\Coroutine;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Database\PDOProxy;
use Swoole\Database\PDOStatementProxy;

final class Db
{
    use Singleton;

    //是否sql数据验证
    private $isValidate = false;

    //查询条件变量
    private $tableName;
    private $tablePrefixs = [];
    private $where = '1=1';
    private $params = [];
    private $field = '*';
    private $join = '';
    private $orderBy = '';
    private $limit = '';
    private $groupBy = '';
    //返回类型
    private $returnType;

    //Qzzm分库同库分客户功能变量
    private $erpId = 0;
    private $mysql = 'mysql';


    /**
     * 重置查询条件
     */
    private function reset()
    {
        $this->isValidate = false;
        $this->tableName = null;
        $this->where = '1=1';
        $this->params = [];
        $this->field = '*';
        $this->join = '';
        $this->orderBy = '';
        $this->limit = '';
        $this->groupBy = '';

        $this->returnType = null;

        $this->erpId = 0;
        $this->mysql = 'mysql';
    }

    function __construct(int $erpId = 0, string $mysql = 'mysql')
    {
        $this->reset();
        $this->erpId = $erpId;
        $this->mysql = $mysql;
    }

    public function dataType($class = null): ?Db
    {
        $this->returnType = $class;
        return $this;
    }

    /**
     * 开始事务 返回当前例
     * @param string $poolName
     * @return Db
     */
    public function startTrans(): Db
    {
        DbPool::getInstance()->pool($this->mysql)->startTrans();
        return $this;
    }

    /**
     * 完成事务
     * @param string $poolName
     */
    public function commit(): void
    {
        DbPool::getInstance()->pool($this->mysql)->commit();
    }

    /**
     * 回滚事务
     * @param string $poolName
     */
    public function rollBack(): void
    {
        DbPool::getInstance()->pool($this->mysql)->rollBack();
    }

    /**
     * 设置简单增删改查单条操作的表名
     * @param null $tableName
     * @return Db|null
     */
    public function table(string $tableName = '')
    {
        $prefix = DbPool::getInstance()->pool($this->mysql)->getTablePrefix();
        $this->tableName = Builder::getInstance()->table($tableName, $prefix, $this->erpId);
        return $this;
    }

    public function where($where = null): Db
    {
        if ($where) {
            $build = Builder::getInstance()->where($where, $this->params);
            $this->where .= $build['where'];
            $this->params = array_merge($this->params, $build['params']);
        }
        return $this;
    }

    public function orWhere($where): Db
    {
        $build = Builder::getInstance()->where($where, $this->params);
        $this->where .= " OR (1=1{$build['where']})";
        $this->params = array_merge($this->params, $build['params']);
        return $this;
    }

    public function limit(int $beginRow, $pageSize = null): Db
    {
        $this->limit = Builder::getInstance()->limit($beginRow, $pageSize);
        return $this;
    }

    public function orderBy($orderBy = null): Db
    {
        if ($orderBy) {
            $strOrderBy = Builder::getInstance()->orderBy($orderBy);
            if ($this->orderBy) {
                $this->orderBy .= ", {$strOrderBy}";
            } else {
                $this->orderBy = " ORDER BY {$strOrderBy}";
            }
        }
        return $this;
    }

    public function field($fields = null): Db
    {
        if ($fields) {
            $this->field = Builder::getInstance()->field($fields);
        }
        return $this;
    }

    public function join(string $tableName, string $on, string $type = 'INNER', $isRaw = false): Db
    {
        if ($isRaw) {
            $prefix = '';
        } else {
            $prefix = DbPool::getInstance()->pool($this->mysql)->getTablePrefix();
        }
        $join = Builder::getInstance()->join($tableName, $prefix, $this->erpId, $on, $type, $isRaw);
        $this->join .= $join;
        return $this;
    }

    public function groupBy($groupBy = null): Db
    {
        if ($groupBy) {
            $strGroupBy = Builder::getInstance()->groupBy($groupBy);
            if ($this->groupBy) {
                $this->groupBy .= ", {$strGroupBy}";
            } else {
                $this->groupBy = " GROUP BY {$strGroupBy}";
            }
        }
        return $this;
    }

    public function updateInc(array $data): Result
    {
        $sqlResult = new Result();
        //迟点加数据验证

        $table = $this->tableName;
        $randomKey = count($this->params) + 1;
        $fields = [];
        foreach ($data as $key => $val) {
            array_push($fields, "`{$key}` = `{$key}` + :{$key}_{$randomKey}");
            $this->params[":{$key}_{$randomKey}"] = $val;
        }
        $strFields = implode(',', $fields);
        $sql = "UPDATE {$table} SET {$strFields} WHERE {$this->where};";
        $params = $this->params;
        $mysql = $this->mysql;
        $this->reset();
        return DbPool::getInstance()->pool($mysql)->update($sql, $params);;
    }


    public function updateDec(array $data): Result
    {
        $sqlResult = new Result();
        //迟点加数据验证

        $table = $this->tableName;
        $randomKey = count($this->params) + 1;
        $fields = [];
        foreach ($data as $key => $val) {
            array_push($fields, "`{$key}` = `{$key}` - :{$key}_{$randomKey}");
            $this->params[":{$key}_{$randomKey}"] = $val;
        }
        $strFields = implode(',', $fields);
        $sql = "UPDATE {$table} SET {$strFields} WHERE {$this->where};";
        $params = $this->params;
        $mysql = $this->mysql;
        $this->reset();
        return DbPool::getInstance()->pool($mysql)->update($sql, $params);;
    }

    public function count(string $field = '*')
    {
        if (strpos($field, ".")) {
            $field = '`' . str_replace('.', '`.`', $field) . '`';
        } else {
            $field = $field !== '*' ? "`{$field}`" : '*';
        }
        $table = $this->tableName;
        $where = $this->where !== '1=1' ? ' WHERE ' . $this->where : '';
        $sql = "SELECT COUNT({$field}) AS `count` FROM {$table} {$this->join} {$where} {$this->groupBy};";
        $params = $this->params;
        $mysql = $this->mysql;
        $this->reset();
        $res = DbPool::getInstance()->pool($mysql)->fetchAll($sql, $params, null, 'ONE');
        $result = new Result();
        $result->setFlag($res->getFlag());
        $result->setSql($res->getSql());
        $result->setError($res->getError());
        $result->setResult(array_values($res->getResult())[0] ?? 0);
        return $result;
    }

    /**
     * 查列表数据
     * @return Result
     */
    public function select(string $poolName = 'mysql'): Result
    {
        $table = $this->tableName;
        $where = $this->where !== '1=1' ? ' WHERE ' . $this->where : '';
        $sql = "SELECT {$this->field} FROM {$table} {$this->join} {$where} {$this->orderBy} {$this->limit};";
        $params = $this->params;
        $mysql = $this->mysql;
        $returnType = $this->returnType;
        $this->reset();
        return DbPool::getInstance()->pool($mysql)->fetchAll($sql, $params, $returnType);
    }


    /**
     * 查单条数据
     * @return Result
     */
    public function find(): Result
    {
        $table = $this->tableName;
        $where = $this->where !== '1=1' ? ' WHERE ' . $this->where : '';
        $sql = "SELECT {$this->field} FROM {$table} {$this->join} {$where} {$this->orderBy} LIMIT 1;";
        $params = $this->params;
        $mysql = $this->mysql;
        $returnType = $this->returnType;
        $this->reset();
        return DbPool::getInstance()->pool($mysql)->fetchAll($sql, $params, $returnType, 'ONE');
    }

    /**
     * 新增一条记录
     * @param array $data
     * @return int
     */
    public function insert(array $data): Result
    {
        $sqlResult = new Result();
        //数据验证
        $resDbCheck = $this->check('insert', $this->tableName, $data);

        if (!$resDbCheck->getFlag()) {
            $sqlResult->setFlag(false);
            $sqlResult->setError($resDbCheck->getError());
            return $sqlResult;
        }

        $table = $this->tableName;
        $keys = array_keys($data);
        $fields = implode('`, `', $keys);
        $fields = "`{$fields}`";
        $keyFields = ':' . implode(', :', $keys);
        $params = [];
        foreach ($data as $key => $val) {
            $params[':' . $key] = $val;
        }
        $sql = "INSERT INTO {$table}({$fields}) VALUES({$keyFields});";
        $mysql = $this->mysql;
        $this->reset();
        return DbPool::getInstance()->pool($mysql)->insert($sql, $params);
    }

    /**
     * 批量新增
     * @param bool $isTrans 是否自动事务
     * @return bool
     */
    public function insertBatch(bool $isTrans = true): bool
    {

    }

    public function update(array $data): Result
    {
        $sqlResult = new Result();
        //迟点加数据验证

        $table = $this->tableName;
        $randomKey = count($this->params) + 1;
        $fields = [];
        foreach ($data as $key => $val) {
            array_push($fields, "`{$key}`=:{$key}_{$randomKey}");
            $this->params[":{$key}_{$randomKey}"] = $val;
        }
        $strFields = implode(',', $fields);
        $sql = "UPDATE {$table} SET {$strFields} WHERE {$this->where};";
        $params = $this->params;
        $mysql = $this->mysql;
        $this->reset();
        return DbPool::getInstance()->pool($mysql)->update($sql, $params);
    }

    public function delete(): Result
    {
        $sqlResult = new Result();
        //迟点加数据验证
        $table = $this->tableName;
        $sql = "DELETE FROM {$table} WHERE {$this->where};";
        $params = $this->params;
        $mysql = $this->mysql;
        $this->reset();
        return DbPool::getInstance()->pool($mysql)->update($sql, $params);;
    }

    public function query(string $sql, array $params = []): Result
    {
        $mysql = $this->mysql;
        $returnType = $this->returnType;
        $this->reset();
        return DbPool::getInstance()->pool($mysql)->fetchAll($sql, $params, $returnType);
    }

    public function validate(bool $bool = false): Db
    {
        $this->isValidate = $bool;
        return $this;
    }

    public function check(string $action, string $tableName, array $data): \qzzm\validate\Result
    {
        if ($this->isValidate) {
            $tablePrefix = DbPool::getInstance()->pool($this->mysql)->getTablePrefix();
            $table = preg_replace('/\`/', '', $tableName);
            $table = str_replace($tablePrefix, '', $table);
            $result = Validate::getInstance()->dbCheck($action, $table, $data);
            return $result;
        } else {
            $result = new \qzzm\validate\Result();
            $result->setFlag(true);
            return $result;
        }

    }
}

<?php


namespace qzzm\mysql;

use qzzm\lib\Singleton;

final class Builder
{
    use Singleton;


    public function table(string $tableName, string $prefix, int $erpId = 0): string
    {
        $tableName = trim($tableName);
        $tbPrefix = substr($tableName, 0, strlen($prefix));
        if ($erpId > 0) {
            if ($prefix !== $tbPrefix) {
                $resTable = $prefix . $erpId . '_' . $tableName;
            } else {
                $tbArr = explode($prefix, $tableName);
                $resTable = $prefix . $erpId . '_' . $tbArr[1];
            }
        } else {
            $resTable = $prefix === $tbPrefix ? $tableName : $prefix . $tableName;
        }
        if (strpos($resTable, " ")) {
            $arr = explode(' ', $resTable);
            return "`{$arr[0]}` AS `{$arr[1]}`";
        } else {
            return "`{$resTable}`";
        }
    }

    public function field($fields = null)
    {
        $resField = '';
        $case = gettype($fields);
        switch ($case) {
            case 'array':
                $fieldArr = [];
                foreach ($fields as $field) {
                    //如果含 类似 a.xx  这个格式
                    if (strpos($field, ".")) {
                        $newField = str_replace('.', '`.`', $field);
                        $newField = preg_replace("/\s([aA][sS])\s/", '` AS `', $newField);
                        array_push($fieldArr, $newField);
                    } else {
                        $field = preg_replace("/\s([aA][sS])\s/", '` AS `', $field);
                        array_push($fieldArr, $field);
                    }
                }

                $resField = '`' . implode('`,`', $fieldArr) . '`';
                break;
            case 'string':
                $resField = $fields;
                break;
            default:
                break;
        }
        return $resField;
    }

    public function join(string $tableName, string $prefix, int $erpId, string $on, string $type, bool $isRaw = false)
    {
        if ($isRaw) {
            $joinTable = $tableName;
        } else {
            $joinTable = $this->table($tableName, $prefix, $erpId);
        }
        $on = str_replace('.', '`.`', $on);
        $on = str_replace('=', '`=`', $on);
        return " {$type} JOIN {$joinTable} ON `{$on}`";
    }

    public function where($where, $params): array
    {
        $randomKey = count($params) + 1;
        $strWhere = '';
        $fieldParams = [];
        if ($where) {
            $case = gettype($where);
            switch ($case) {
                case 'array':
                    if (count($where) == count($where, 1)) {
//                        echo '是一维数组';
                        $wfields = [];
                        foreach ($where as $key => $val) {
                            //处理字段为 'a.id' 这种情况问题
                            if (strpos($key, ".")) {
                                $field = str_replace('.', '`.`', $key);
                                $fieldKey = str_replace('.', '_', $key);
                            } else {
                                $field = $key;
                                $fieldKey = $key;
                            }
                            array_push($wfields, "`{$field}`=:{$fieldKey}_{$randomKey}");
                            $fieldParams[":{$fieldKey}_{$randomKey}"] = $val;
                        }
                        $strWhere = " AND " . implode(' AND ', $wfields);
                    } else {
                        // 不是一维数组 全部用以下格式
//                        $where = [
//                            ['id', '=', 123, 'AND'],
//                            ['name', '=', 'asde']
//                        ];
                        foreach ($where as $key => $item) {
                            $con = isset($item[3]) && $item[3] ? strtoupper($item[3]) : 'AND';
                            $random = "{$randomKey}_{$key}";
                            if (strpos($item[0], ".")) {
                                $field = str_replace('.', '`.`', $item[0]);
                                $fieldKey = str_replace('.', '_', $item[0]);
                            } else {
                                $field = $item[0];
                                $fieldKey = $item[0];
                            }
                            switch (strtoupper($item[1])) {
                                case 'IN':
                                    $inList = ":{$fieldKey}_{$random}_" . implode(",:{$fieldKey}_{$random}_", array_keys($item[2]));
                                    $strWhere .= " {$con} `{$field}` IN({$inList})";
                                    $fieldParams = array_merge($fieldParams, array_combine(explode(",", $inList), $item[2]));
                                    break;
                                case 'LIKE':
                                    $strWhere .= " {$con} `{$item[0]}` LIKE CONCAT('%',:{$fieldKey}_{$random},'%')";
                                    $fieldParams[":{$fieldKey}_{$random}"] = $item[2];
                                    break;
                                case 'LIKE%':
                                    $strWhere .= " {$con} `{$field}` LIKE CONCAT(:{$fieldKey}_{$random},'%')";
                                    $fieldParams[":{$fieldKey}_{$random}"] = $item[2];
                                    break;
                                case '%LIKE':
                                    $strWhere .= " {$con} `{$field}` LIKE CONCAT('%',:{$fieldKey}_{$random})";
                                    $fieldParams[":{$fieldKey}_{$random}"] = $item[2];
                                    break;
                                default:
                                    $strWhere .= " {$con} `{$field}` {$item[1]} :{$fieldKey}_{$random}";
                                    $fieldParams[":{$fieldKey}_{$random}"] = $item[2];
                                    break;
                            }
                        }
                    }
                    break;
                case 'string':
                    $strWhere = $where ?: '1=1';
                    break;
                case 'integer':
                    $strWhere = "`id`=:id_{$randomKey}";
                    $fieldParams[":id_{$randomKey}"] = $where;
                    break;
                default:
                    $strWhere = '1=1';
                    break;
            }
        } else {
            $strWhere = '1=1';
        }
        return [
            'where' => $strWhere,
            'params' => $fieldParams
        ];
    }

    public function limit(int $beginRow, $pageSize = null): string
    {
        if ($beginRow && !$pageSize) {
            $start = 0;
            $max = $beginRow;
        } else {
            $start = $beginRow;
            $max = $pageSize;
        }
        return " LIMIT {$start},$max";
    }

    public function orderBy($orderBy = null)
    {
        $strOrderBy = '';
        $case = gettype($orderBy);
        switch ($case) {
            case 'array':
                //只支持一维数组
                $arr = [];
                foreach ($orderBy as $key => $item) {
                    $desc = strtoupper($item);
                    array_push($arr, "`{$key}` {$desc}");
                }
                $strOrderBy = implode(',', $arr);
                break;
            case 'string':
                $strOrderBy = "{$orderBy}";
                break;
            default:
                break;
        }
        return $strOrderBy;
    }

    public function groupBy($groupBy = null)
    {
        $strGroupBy = '';
        $case = gettype($groupBy);
        switch ($case) {
            case 'array':
                $fieldArr = [];
                foreach ($groupBy as $field) {
                    if (strpos($field, ".")) {
                        $newField = str_replace('.', '`.`', $field);
                        array_push($fieldArr, $newField);
                    } else {
                        array_push($fieldArr, $field);
                    }
                }
                //只支持一维数组
                $strGroupBy = '`' . implode('`,`', $fieldArr) . '`';
                break;
            case 'string':
                if (strpos($groupBy, ".")) {
                    $groupBy = str_replace('.', '`.`', $groupBy);
                }
                $strGroupBy = "`{$groupBy}`";
                break;
            default:
                break;
        }
        return $strGroupBy;
    }
}

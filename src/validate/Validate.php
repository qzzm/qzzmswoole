<?php


namespace qzzm\validate;


use qzzm\utlis\Log;
use qzzm\utlis\Singleton;

class Validate
{
    use Singleton;

    /**
     * 验证数据库字段
     * @param string $table
     * @param string $tablePrefix
     * @param array $data
     * @return Result
     */
    public function dbCheck(string $action, string $table, array $data): Result
    {
        $result = new Result();
        try {
            $dir = "app\\validate\\" . ucfirst($table);
            if (!class_exists($dir)) {
                //不存在验证类
                $result->setFlag(true);
                return $result;
            }

            $class = new $dir();
            $rules = $class->rule();
            $scenes = $class->scene();
            //获取验证场景的字段
            $checkFields = $scenes[$action] ?? [];

            if (count($checkFields) === 0) {
                //没有该场景没有要验证的字段
                $result->setFlag(true);
                return $result;
            }

            $checkRules = [];
            foreach ($rules as $key => $rule) {
                if (in_array($key, $checkFields)) {
                    $checkRules[$key] = $rule;
                }
            }
            $checkRes = $this->check($data, $checkRules);
            return $checkRes;
        } catch (\Exception $ex) {
//            throw $ex;
            return $result;
        }
    }

    public function check(array $data, array $rules): Result
    {
//        Log::dump($data);
//        Log::dump($rules);
        $result = new Result();
        try {
            foreach ($data as $key => $dataVal) {
                $rule = $rules[$key] ?? [];
                if ($rule) {
                    foreach ($rule as $type => $option) {
                        if (!$this->checkRule($type, $option['value'], $dataVal)) {
                            $result->setFlag(false);
                            $result->setError("{$key}:[{$type}] {$option['error']}");
                            return $result;
                        }
                    }
                }
            }
            $result->setFlag(true);
            return $result;
        } catch (\Exception $ex) {
            return $result;
        }

    }

    private function checkRule(string $ruleType, $ruleValue, $dataValue): bool
    {
        switch ($ruleType) {
            case 'type':
                $result = strtoupper(gettype($dataValue)) === $ruleValue;
                break;
            case 'require':
                $result = !empty($dataValue) || '0' == $dataValue;
                break;
            case 'minLength':
                $result = strlen($dataValue) >= $ruleValue;
                break;
            case 'atLength':
                $result = strlen($dataValue) == $ruleValue;
                break;
            case 'maxLength':
                $result = strlen($dataValue) <= $ruleValue;
                break;
            case 'min':
                $dataType = strtoupper(gettype($dataValue));
                if ($dataType == 'INTEGER' || $dataType == 'DOUBLE') {
                    return $dataValue >= $ruleValue;
                } else {
                    $result = false;
                }
                break;
            case 'atNumber':
                $dataType = strtoupper(gettype($dataValue));
                if ($dataType == 'INTEGER' || $dataType == 'DOUBLE') {
                    return $dataValue == $ruleValue;
                } else {
                    $result = false;
                }
                break;
            case 'max':
                $dataType = strtoupper(gettype($dataValue));
                if ($dataType == 'INTEGER' || $dataType == 'DOUBLE') {
                    return $dataValue <= $ruleValue;
                } else {
                    $result = false;
                }
                break;
            case 'between':
                $dataType = strtoupper(gettype($dataValue));
                if ($dataType == 'INTEGER' || $dataType == 'DOUBLE') {
                    return $dataValue >= $ruleValue || $dataValue <= $ruleValue;
                } else {
                    $result = false;
                }
                break;
            default:
                return false;
                break;
        }
        return $result;
    }
}
<?php


namespace qzzm\validate;


abstract class AValidate
{
    protected $rules = [];

    /**
     * 是字符类型
     * @return AValidate
     */
    public function isString(string $errorMsg = ''): AValidate
    {
        $this->rules['type']['value'] = 'STRING';
        $this->rules['type']['error'] = $errorMsg ?: 'must be string type';
        return $this;
    }

    /**
     * 是整数类型
     * @return AValidate
     */
    public function isInt(string $errorMsg = ''): AValidate
    {
        $this->rules['type']['value'] = 'INTEGER';
        $this->rules['type']['error'] = $errorMsg ?: 'must be int type';
        return $this;
    }

    /**
     * 是数字类型
     * @return AValidate
     */
    public function isFloat(string $errorMsg = ''): AValidate
    {
        $this->rules['type']['value'] = 'DOUBLE';
        $this->rules['type']['error'] = $errorMsg ?: 'must be float type';
        return $this;
    }

    /**
     * 是逻辑类型
     * @return AValidate
     */
    public function isBoolean(string $errorMsg = ''): AValidate
    {
        $this->rules['type']['value'] = 'BOOLEAN';
        $this->rules['type']['error'] = $errorMsg ?: 'must be boolean type';
        return $this;
    }

    /**
     * 必须字段
     * @param string $errorMsg
     * @return AValidate
     */
    public function require(string $errorMsg = ''): AValidate
    {
        $this->rules['require']['value'] = true;
        $this->rules['require']['error'] = $errorMsg ?: 'must be required';
        return $this;
    }

    /**
     * 最小长度
     * @param int $value
     * @param string $errorMsg
     * @return AValidate
     */
    public function minLength(int $value = 0, string $errorMsg = ''): AValidate
    {
        $this->rules['minLength']['value'] = $value;
        $this->rules['minLength']['error'] = $errorMsg ?: "at least {$value} characters";
        return $this;
    }

    /**
     * 固定长度
     * @param int $value
     * @param string $errorMsg
     * @return AValidate
     */
    public function atLength(int $value = 0, string $errorMsg = ''): AValidate
    {
        $this->rules['atLength']['value'] = $value;
        $this->rules['atLength']['error'] = $errorMsg ?: "at {$value} characters";
        return $this;
    }

    /**
     * 最大长度
     * @param int $value
     * @param string $errorMsg
     * @return AValidate
     */
    public function maxLength(int $value = 0, string $errorMsg = ''): AValidate
    {
        $this->rules['maxLength']['value'] = $value;
        $this->rules['maxLength']['error'] = $errorMsg ?: "must be less than {$value} characters";
        return $this;
    }

    /**
     * 最小值
     * @param float $value
     * @param string $errorMsg
     * @return AValidate
     */
    public function min(float $value = 0, string $errorMsg = ''): AValidate
    {
        $this->rules['min']['value'] = $value;
        $this->rules['min']['error'] = $errorMsg ?: "value must be bigger than {$value}";
        return $this;
    }

    /**
     * 固定值
     * @param float $value
     * @param string $errorMsg
     * @return AValidate
     */
    public function atNumber(float $value = 0, string $errorMsg = ''): AValidate
    {
        $this->rules['atNumber']['value'] = $value;
        $this->rules['atNumber']['error'] = $errorMsg ?: "value must be equal {$value}";
        return $this;
    }

    /**
     * 最大值
     * @param float $value
     * @param string $errorMsg
     * @return AValidate
     */
    public function max(float $value = 0, string $errorMsg = ''): AValidate
    {
        $this->rules['max']['value'] = $value;
        $this->rules['max']['error'] = $errorMsg ?: "value must be less than {$value}";
        return $this;
    }

    /**
     * 区间数值
     * @param float $min
     * @param float $max
     * @param string $errorMsg
     * @return AValidate
     */
    public function between(float $min = 0, float $max, string $errorMsg = ''): AValidate
    {
        if ($min > $max) {
            $a = $max;
            $b = $min;
        } else {
            $a = $min;
            $b = $max;
        }
        $this->rules['between']['value'] = [$a, $b];
        $this->rules['between']['error'] = $errorMsg ?: "value must be between [{$a},{$b}]";
        return $this;
    }

    /**
     * 正则表达试验证
     * @param string $pattern
     * @param string $errorMsg
     * @return AValidate
     */
    public function regx(string $pattern, string $errorMsg = ''): AValidate
    {
        $this->rules['regx']['value'] = $pattern;
        $this->rules['regx']['error'] = $errorMsg ?: "verify fail";
        return $this;
    }

    /**
     * 获取验证规则
     * @return array
     */
    public function getRules(): array
    {
        $rules = $this->rules;
        $this->rules = [];
        return $rules;
    }

    // <editor-fold defaultstate="collapsed" desc="其它内置验证规则">

    // </editor-fold>

}
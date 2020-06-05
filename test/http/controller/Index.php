<?php


namespace app\http\controller;


use qzzm\mysql\Db;

class Index extends AController
{
    public function index()
    {
        $db = new Db(1, 'mysql1');
        $res = $db->table('erp_company_user cu')
            ->join("(SELECT * FROM qzzm_1_erp_company WHERE id in (1,2)) as c", 'cu.company_id=c.id', 'INNER', true)
            ->where(['cu.company_id' => 2])
            ->select();
        var_dump($res->getSql());
        return ['code' => 0, 'msg' => 'Hello Qzzm!', 'data' => $res->getResult()];
    }
}
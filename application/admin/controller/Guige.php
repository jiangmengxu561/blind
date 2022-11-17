<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 商品管理
 *
 * @icon fa fa-circle-o
 */
class Guige extends Backend
{

    /**
     * Commodity模型对象
     * @var \app\admin\model\Commodity
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Commodity;

    }

    /**
     * 查看
     */
    public function guige()
    {
        return $this->view->fetch();
    }

}
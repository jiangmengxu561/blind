<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
/**
 * 盲盒商品
 *
 * @icon fa fa-circle-o
 */
class Bshop extends Backend
{

    /**
     * Bshop模型对象
     * @var \app\admin\model\Bshop
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Bshop;

    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                    ->with(['bulid'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                
                $row->getRelation('bulid')->visible(['b_name']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
    public function bulid(){
        $result = array("rows" => [], "total" => 0);
        if ($this->request->isAjax()) {
            if ($this->request->request("keyValue")) {
                $id = $this->request->request("keyValue");
                $list = Db::name('bulid')
                    ->field("id,b_name")
                    ->where("id", $id)
                    ->select();
                return ['total' => 1, 'list' => $list];
            }
            $list = Db::name('bulid')
                ->field("id,b_name")

                ->select();
            $count = Db::name('bulid')
                ->field("id,b_name")
                ->count();
            $result = array("rows" => $list, "total" => $count);
            return json($result);
        }
        return json($result);
    }
}

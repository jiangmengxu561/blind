<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\exception\DbException;
use think\response\Json;
use app\admin\model\Bshop as B;

/**
 * 主播盲盒概率
 *
 * @icon fa fa-anchor
 */
class Anchor extends Backend
{

    /**
     * Anchor模型对象
     * @var \app\admin\model\Anchor
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Anchor;

    }

    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $list = $this->model
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);

        foreach ($list as $k =>$v) {

            $list[$k]['u_id'] =  Db::name('user')->where('id',$v['u_id'])->value('nickname');
            $list[$k]['m_id'] =  Db::name('bulid')->where('id',$v['m_id'])->value('b_name');
            $list[$k]['s_id'] =  Db::name('bshop')->where('id',$v['s_id'])->value('s_name');
        }
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }


    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    public function user(){
        $result = array("rows" => [], "total" => 0);
        if ($this->request->isAjax()) {
            if ($this->request->request("keyValue")) {
                $id = $this->request->request("keyValue");
                $list = Db::name('user')
                    ->field("id,username")
                    ->where("id", $id)
                    ->select();
                return ['total' => 1, 'list' => $list];
            }
            $list = Db::name('user')
                ->field("id,username")
                ->select();
            $count = Db::name('user')
                ->field("id,username")
                ->count();
            $result = array("rows" => $list, "total" => $count);
            return json($result);
        }
        return json($result);
    }
    public function index1()
    {
        $param1 = $this->request->param();

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            if (!empty($param1['custom'])) {
                $where1['c_id'] = $param1['custom']['id'];
            } else {
                $where1 = "id > 0";
            }
            $list = B::where($where1)
                ->order('id desc ')
                ->paginate($limit);
//                ->select();

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
//        }

    }
}

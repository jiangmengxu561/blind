<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{

    /**
     * Order模型对象
     * @var \app\admin\model\Order
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Order;
        $this->view->assign("statusList", $this->model->getStatusList());
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
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model

                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            foreach ($list->items() as $k=>$v){
                if ($v['source']==1){
                    $list->items()[$k]['source']='盲盒';
                    $list->items()[$k]['shopname']=Db::name('bshop')->where('id',$v['m_id'])->value('s_name');
                }else{
                    $list->items()[$k]['source']='商城';
                    $list->items()[$k]['shopname']=Db::name('commodity')->where('id',$v['m_id'])->value('title');

                }
                if ($v['type']==1){
                    $list->items()[$k]['type']='A';
                }else{
                    $list->items()[$k]['type']='B';

                }
            }

//            print_r($list->items());die;
            foreach ($list as $row) {
                $row->visible(['id','status','k_id','m_id','source','ReceiverName','ReceiverTel','ReceiverProvinceName','OrderCode','createtime','type','courierNumber','express','shopname']);

            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    public function order(){
        $result = array("rows" => [], "total" => 0);
        if ($this->request->isAjax()) {
            if ($this->request->request("keyValue")) {
                $id = $this->request->request("keyValue");
                $list = Db::name('kuaicom')
                    ->field("id,name")
                    ->where("id", $id)
                    ->select();
                return ['total' => 1, 'list' => $list];
            }
            $list = Db::name('kuaicom')
                ->field("id,company")

                ->select();
            $count = Db::name('kuaicom')
                ->field("id,company")
                ->count();
            $result = array("rows" => $list, "total" => $count);
            return json($result);
        }
        return json($result);
    }
}

<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 商品管理
 *
 * @icon fa fa-circle-o
 */
class Commodity extends Backend
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
                    ->with(['shopcate'])
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);
            foreach ($list as $row) {
                
                $row->getRelation('shopcate')->visible(['name']);
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }


    public function commodity(){
        $result = array("rows" => [], "total" => 0);
        if ($this->request->isAjax()) {
            if ($this->request->request("keyValue")) {
                $id = $this->request->request("keyValue");
                $list = Db::name('shopcate')
                    ->field("id,name")
                    ->where("id", $id)
                    ->select();
                return ['total' => 1, 'list' => $list];
            }
            $list = Db::name('shopcate')
                ->field("id,name")

                ->select();
            $count = Db::name('shopcate')
                ->field("id,name")
                ->count();
            $result = array("rows" => $list, "total" => $count);
            return json($result);
        }
        return json($result);
    }





    /**
     * 添加
     *
     * @return string
     * @throws \think\Exception
     */
    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
       $data = $this->request->param();
       $guige = $data['row']['guige'];
        unset($data['row']['guige']);
        unset($data['guige']);
        unset($data['dialog']);
        $res = Db::name('commodity')->insertGetId($data['row']);
        if ($res){
            foreach ($guige as $k=>$v){
                $guige[$k]['s_id'] = $res;
                $guige[$k]['createtime'] = time();
            }
        }
            Db::name('speci')->insertAll($guige);
            $this->success();
    }

    /**
     * 编辑
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);

        $data  = Db::name('speci')->where('s_id',$ids)->select();
        foreach ($data as $k=>$v){
            unset($data[$k]['id']);
            unset($data[$k]['s_id']);
            unset($data[$k]['createtime']);
        }
        $row['guize'] = json_encode($data);

//        print_r($data);die;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }

        $params = $this->request->post('row/a');
        $guize = $params['guige'];
        foreach ($guize as $k=>$v){
            $guize[$k]['s_id'] = $ids;
            $guize[$k]['createtime'] = time();
        }

        unset($params['guige']);
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            Db::name('speci')->where('s_id',$ids)->delete();
            Db::name('speci')->insertAll($guize);

            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

}
<?php

namespace app\admin\controller\delivery;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 发货订单管理
 *
 * @icon fa fa-circle-o
 */
class Expressbillprint extends Backend
{

    /**
     * Subjects模型对象
     * @var \app\addons\delivery\model\ExpressBill
     */
    protected $model = null;
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model =model('addons\delivery\model\ExpressBill');
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    public function import()
    {
        parent::import();
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
                ->where($where)
                ->field(true)
                ->order($sort, $order)
                ->paginate($limit);
            foreach ($list as $row){
                if(!empty($row->billsender)){
                    $row->name=$row->billsender['name'];
                    $row->mobile=$row->billsender['mobile'];
                    $row->long_address=$row->billsender['province'].$row->billsender['city'].$row->billsender['exp_area'].$row->billsender['address'];
                }

            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                //$params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                if($params['native_place']){
                    $native_place_arry = explode("/", $params['native_place']);
                    if(empty($native_place_arry) || count($native_place_arry)<3){
                        $this->error('地区选择错误');
                    }
                    $params['province']=$native_place_arry[0];
                    $params['city']=$native_place_arry[1];
                    $params['exp_area']=$native_place_arry[2];
                }

                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    if ($result !== false) {
                        //存发货人
                        $params['delivery_id']=$this->model->id;
                        $result= model('addons\delivery\model\ExpressBillSender')->allowField(true)->save($params);
                    }
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        $row->billsender;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                //$params = $this->preExcludeFields($params);
                if($params['native_place']){
                    $native_place_arry = explode("/", $params['native_place']);
                    if(empty($native_place_arry) || count($native_place_arry)<3){
                        $this->error('地区选择错误');
                    }
                    $params['province']=$native_place_arry[0];
                    $params['city']=$native_place_arry[1];
                    $params['exp_area']=$native_place_arry[2];
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    if ($result !== false) {
                        $model=model('addons\delivery\model\ExpressBillSender');
                        if(!empty($params['sender_id'])){
                            //修改发货人
                            $sender_row=$model->get($params['sender_id']);
                            $result = $sender_row->allowField(true)->save($params);
                        }else{
                            $params['delivery_id']=$row->id;
                            $result= $model->allowField(true)->save($params);
                        }

                    }
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $row['native_place']=$row['billsender']['province'].'/'.$row['billsender']['city'].'/'.$row['billsender']['exp_area'];
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 默认发件人
     */
    public function defaultsender($ids = null)
    {
        $this->model=model('addons\delivery\model\ExpressBillSender');
        $row = $this->model->get(['delivery_id'=>0]);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                //$params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                if($params['native_place']){
                    $native_place_arry = explode("/", $params['native_place']);
                    if(empty($native_place_arry) || count($native_place_arry)<3){
                        $this->error('地区选择错误');
                    }
                    $params['province']=$native_place_arry[0];
                    $params['city']=$native_place_arry[1];
                    $params['exp_area']=$native_place_arry[2];
                }

                $result = false;
                Db::startTrans();
                try {
                    if(!$row){
                        //是否采用模型验证
                        if ($this->modelValidate) {
                            $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                            $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                            $this->model->validateFailException(true)->validate($validate);
                        }
                        $result = $this->model->allowField(true)->save($params);
                    }else{
                        //是否采用模型验证
                        if ($this->modelValidate) {
                            $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                            $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                            $row->validateFailException(true)->validate($validate);
                        }
                        $result = $row->allowField(true)->save($params);
                    }
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }


        if ($row) {
            $row['native_place']=$row['province'].'/'.$row['city'].'/'.$row['exp_area'];
        }
        $this->assign('row',$row);
        return $this->view->fetch();
    }
}

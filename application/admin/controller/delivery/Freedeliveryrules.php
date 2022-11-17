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
class Freedeliveryrules extends Backend
{

    /**
     * Subjects模型对象
     * @var \app\addons\delivery\model\Order
     */
    protected $model = null;
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model =model('addons\delivery\model\FreeDeliveryRules');
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
                //没有选择地址，返回
                $postageRules= cache('citys');
                if(empty($postageRules)){
                    $this->error('请设置包邮规则');
                }
                if(!is_numeric($params['price'])){
                    $this->error('包邮金额请填写数字');
                }
                $params['city']=json_encode($postageRules);
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
        cache('citys', NULL);
        cache('freedeliveryrule_id', null);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
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
                //没有选择地址，返回
                $postageRules= cache('citys');
                if(empty($postageRules)){
                    $this->error('请设置包邮规则');
                }
                $params['city']=json_encode($postageRules);
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
        cache('freedeliveryrule_id', $row->id);
        cache('citys', json_decode($row['city'],true));
        $postageRules= cache('citys');

        $final_array=[];
        $model = model('addons\delivery\model\DistrictArr');
        $cityList = $model::getArr();
        $str='';
        foreach($postageRules as $i=>$v){
            $str .=$cityList[$v]['name'].'&nbsp;&nbsp;';
        }
        array_push($final_array,$str);
        $this->assign('final_array',$final_array);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 读取角色权限树
     *
     * @internal
     */
    public function select()
    {
        return $this->view->fetch();
    }
    /**
     * 读取城市树
     *
     * @internal
     */
    public function citytree()
    {
        $model = model('addons\delivery\model\DistrictArr');
        //排除已经选过的

        $row_id=cache('freedeliveryrule_id');
        if(!empty($row_id)){
            $city_json_array= $this->model->where('id','neq',$row_id)->where('deletetime',null)->column('city');
        }else{
            $city_json_array= $this->model->where('deletetime',null)->column('city');
        }
        $exist=[];
        foreach($city_json_array as $i=>$v){
            $city=json_decode($v,true);
            if(!empty($city)){
                foreach($city as $vv){
                    array_push($exist,$vv);
                }
            }
        }
        $nodeList = $model::getTree(1,$exist);
        //本次选择的城市需要勾上
        $postageRules= cache('citys');
        if(!empty($postageRules)){
            foreach($nodeList as $i=>&$v){
                if($v['parent']!='#'){
                    if(in_array($v['id'],$postageRules)){
                        $v['state']['selected']=true;
                    }
                }
            }
        }

        $this->success('', null, $nodeList);
    }
    /**
     * 临时添加运费规则
     * @internal
     */
    public function add_temp()
    {
        /*cache('postageRules', NULL);
        EXIT;*/
        $params = $this->request->post("row/a");
        if(empty($params['rules'])){
            $this->error(__('请选择省份'));
        }
        $model = model('addons\delivery\model\DistrictArr');
        $provinceList = $model::getTree(0,[]);
        $provinceIds=[];
        foreach($provinceList as $i=>$v){
            array_push($provinceIds,$v['id']);
        }
        $rules=explode(',',$params['rules']);
        $citys=array_diff($rules,$provinceIds);
        sort($citys);
        cache('citys', $citys);
        $postageRules= cache('citys');
        $final_array=[];
        $cityList = $model::getArr();
        $str='';
        foreach($postageRules as $i=>$v){
            $str .=$cityList[$v]['name'].'&nbsp;&nbsp;';
        }
        array_push($final_array,$str);
        return json([
            'code' => 1,
            'data' => $final_array,
            'type' => 1
        ]);
    }

}

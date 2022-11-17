<?php

namespace app\admin\controller\delivery;

use app\common\controller\Backend;
use fast\Tree;
use think\Db;
use
    think\Cache;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 运费规则管理
 *
 * @icon fa fa-circle-o
 */
class Postage extends Backend
{

    /**
     * Subjects模型对象
     * @var \app\addons\delivery\model\PostageRules
     */
    protected $model = null;
    //无需要权限判断的方法
    protected $noNeedRight = ['citytree'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model =model('addons\delivery\model\PostageRules');
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
                //没有选择地址，返回
                $postageRules= cache('postageRules');
                if(empty($postageRules)){
                    $this->error('请设置运费规则');

                }
                $params['detail']=json_encode($postageRules);
                $params['type']= $params['express_type'];
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
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
        cache('postageRules', NULL);
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
                $postageRules= cache('postageRules');
                if(empty($postageRules)){
                    $this->error('请设置运费规则');
                }
                $params['detail']=json_encode($postageRules);
                $params['type']= $params['express_type'];
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

        cache('postageRules', json_decode($row['detail'],true));
        $postageRules= cache('postageRules');

        $final_array=[];
        $model = model('addons\delivery\model\DistrictArr');
        $cityList = $model::getArr();
        foreach($postageRules as $i=>$v){
            $str='';
            $str='首重/件(克/个)：';
            $str .=$v['frist'].'&nbsp;&nbsp;';
            $str .='首费(元) ：';
            $str .=$v['frist_price'].'&nbsp;&nbsp;';
            $str .='续重/件(克/个) ：';
            $str .=$v['second'].'&nbsp;&nbsp;';
            $str .='续费(元) ：';
            $str .=$v['second_price'].'&nbsp;&nbsp;';
            $str .='&#10';
            $str .='省份：';
            foreach($v['province'] as $vv){
                $str .=$cityList[$vv]['name'].'&nbsp;&nbsp;';
            }
            array_push($final_array,$str);
        }
        $this->assign('final_array',$final_array);
        $this->view->assign("row", $row);
        return $this->view->fetch();
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
        $postageRules=cache('postageRules');

        if(empty($postageRules)){
            $postageRules=[];
        }
        array_push($postageRules,[
            'frist'=>$params['frist'],
            'frist_price'=>$params['frist_price'],
            'second'=>$params['second'],
            'second_price'=>$params['second_price'],
            'province'=>$citys,
        ]);
        sort($postageRules);
        cache('postageRules', $postageRules);
        $postageRules= cache('postageRules');
        $final_array=[];
        $cityList = $model::getArr();
        foreach($postageRules as $i=>$v){
            $str='';
            $str='首重/件(克/个)：';
            $str .=$v['frist'].'&nbsp;&nbsp;';
            $str .='首费(元) ：';
            $str .=$v['frist_price'].'&nbsp;&nbsp;';
            $str .='续重/件(克/个) ：';
            $str .=$v['second'].'&nbsp;&nbsp;';
            $str .='续费(元) ：';
            $str .=$v['second_price'].'&nbsp;&nbsp;';
            $str .='&#10';
            $str .='省份：';
            foreach($v['province'] as $vv){
                $str .=$cityList[$vv]['name'].'&nbsp;&nbsp;';
            }
            array_push($final_array,$str);
        }
        return json([
            'code' => 1,
            'data' => $final_array,
            'type' => 1
        ]);
    }
    /**
     * 临时添加运费规则
     * @internal
     */
    public function del_temp()
    {
        $key = $this->request->post("key");
        $postageRules= cache('postageRules');
        unset($postageRules[$key]);
        cache('postageRules', $postageRules);
        return json([
            'code' => 1,
            'data' => $postageRules,
            'type' => 1
        ]);

    }
    /**
     * 读取角色权限树
     *
     * @internal
     */
    public function select()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                //$params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $params['order_no']=$this->model->makeOrderNo();//生成订单号
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
        $this->assign('express_type',$this->request->param('express_type'));
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
        $postageRules= cache('postageRules');
        $exist=[];
        if(!empty($postageRules)){

            foreach($postageRules as $i=>$v){
                foreach($v['province'] as $ii=>$vv){
                    array_push($exist,$vv);
                }
            }

        }
        $nodeList = $model::getTree(1,$exist);

        if(!empty($nodeList)){

            $this->success('', null, $nodeList);
        }else{
            $this->success(__(''));
        }
    }
    /**
     * 批量操作
     */
    public function multi($ids = "")
    {
        $ids = $ids ? $ids : $this->request->param("ids");
        if ($ids) {
            $row = $this->model->get($ids);
            if (!$row) {
                $this->error(__('No Results were found'));
            }
            Db::startTrans();
            try {

                $row->save(['is_default'=>1]);
                $result=$this->model->where('id','neq',$ids)->update(['is_default'=>0]);
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success();
            } else {
                $this->error(__('系统出错'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
}

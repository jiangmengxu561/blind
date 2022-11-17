<?php

namespace app\admin\controller\delivery;

use app\common\controller\Backend;
use app\models\Goods;
use app\models\PostageRules;
use app\models\Sender;
use app\utils\KdOrder;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 发货订单管理
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
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
        $this->model =model('addons\delivery\model\Order');
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
                $row->goods_list=collection($row->orderdetail)->toArray();
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
                //没有选择商品
                if(empty($params['goods'])){
                    $this->error('请选择发货商品');
                }
                if($params['accept_native_place']){
                    $native_place_arry = explode("/", $params['accept_native_place']);
                    if(empty($native_place_arry) || count($native_place_arry)<3){
                        $this->error('收货地区选择错误');
                    }
                    $params['accept_province']=$native_place_arry[0];
                    $params['accept_city']=$native_place_arry[1];
                    $params['accept_exp_area']=$native_place_arry[2];
                }
                if($params['send_native_place']){
                    $native_place_arry = explode("/", $params['send_native_place']);
                    if(empty($native_place_arry) || count($native_place_arry)<3){
                        $this->error('收货地区选择错误');
                    }
                    $params['send_province']=$native_place_arry[0];
                    $params['send_city']=$native_place_arry[1];
                    $params['send_exp_area']=$native_place_arry[2];
                }
                $params['is_pay']=1;
                $params['pay_time']=time();
                $params['order_no']=$this->model->makeOrderNo();//生成订单号

                $order_goods=$this->model::checkGoods($params);  //将商品id一样的商品数量合并

                if(!$order_goods){
                    $this->error('商品选择有误，请检查');
                }

                $goods_price=0;//商品总费用

                foreach($order_goods as $i=>$v){
                    $goods_price +=$v['num']*$v['price'];
                }
                $order_detail_goods=$order_goods;
                foreach($order_goods as $i=>$v){
                    if(empty($v['freight'])){
                        unset($order_goods[$i]);
                    }
                }
                $express_price= $this->model::getExpressPrice($params,$order_goods,$goods_price);  //计算邮费


                $params['total_price']=$express_price+$goods_price;//订单总价
                $params['goods_price']=$goods_price;
                $params['express_price']=$express_price;

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
                    if($result){
                        $order_detail=model('addons\delivery\model\OrderDetail');
                        $insert_data=[];
                        foreach($order_detail_goods as $v){
                            $insert_data[]=[
                                'order_id'=>$this->model->id,
                                'goods_id'=>$v['goods_id'],
                                'num'=>$v['num'],
                                'price'=>$v['price'],
                                'name'=>$v['name'],
                                'cimage'=>$v['cimage'],
                                'total_price'=>$v['price']*$v['num'],
                                'freight'=>$v['freight'],
                                'weight'=>$v['weight'],
                            ];
                        }
                        $result=$order_detail->insertAll($insert_data);
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
        //获取默认发件人信息
        $row = model('addons\delivery\model\ExpressBillSender')->get(['delivery_id'=>0]);
        if($row){
            $row['send_native_place']=$row['province'].'/'.$row['city'].'/'.$row['exp_area'];
        }
        $this->assign('row',$row);
        return $this->view->fetch();
    }
    /**
     * 订单详情
     */
    public function detail($ids = null)
    {
        if ($this->request->isPost()) {
            //编辑订单
            $params = $this->request->post("row/a");
            if ($params) {
                $row = $this->model->get($params['id']);
                if (!$row) {
                    $this->error(__('No Results were found'));
                }
                if($params['accept_native_place']){
                    $native_place_arry = explode("/", $params['accept_native_place']);
                    if(empty($native_place_arry) || count($native_place_arry)<3){
                        $this->error('收货地区选择错误');
                    }
                    $params['accept_province']=$native_place_arry[0];
                    $params['accept_city']=$native_place_arry[1];
                    $params['accept_exp_area']=$native_place_arry[2];
                }
                if($params['send_native_place']){
                    $native_place_arry = explode("/", $params['send_native_place']);
                    if(empty($native_place_arry) || count($native_place_arry)<3){
                        $this->error('收货地区选择错误');
                    }
                    $params['send_province']=$native_place_arry[0];
                    $params['send_city']=$native_place_arry[1];
                    $params['send_exp_area']=$native_place_arry[2];
                }

                $goods_price=$row->goods_price;//商品总费用

                $order_goods=$row->orderdetail;
                $order_goods=$this->model::changeGoods($order_goods);//转换数据

                $express_price= $this->model::getExpressPrice($row,$order_goods,$goods_price);  //计算邮费
                $params['total_price']=$express_price+$goods_price;//订单总价
                $params['express_price']=$express_price;

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
        }else{
            $row = $this->model->get($ids);
            $row->orderdetail;
            if (!$row) {
                $this->error(__('No Results were found'));
            }
            $row['accept_native_place']=$row['accept_province'].'/'.$row['accept_city'].'/'.$row['accept_exp_area'];
            $row['send_native_place']=$row['send_province'].'/'.$row['send_city'].'/'.$row['send_exp_area'];
            $this->view->assign("row", $row);
            return $this->view->fetch();
        }


    }
    /**
     * 发货
     */
    public function delivery($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $params['send_time']=time();
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
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 快递公司
     */
    public function select()
    {
        $this->model=model('addons\delivery\model\Express');
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
     * 打印面单
     */
    public function print()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post();
            $order=model('addons\delivery\model\Order')->get(['id'=>$params['order_id']]);
            if ($params) {
                $express=model('addons\delivery\model\Express')->get(['name'=>$params['express']]);
                if(!$express){
                    $this->error('快递公司不正确');
                }

                $eorder=[];
                $delivery= model('addons\delivery\model\ExpressBill')->get(['express_id'=>$express['id']]);
                if (!$delivery) {
                    $delivery_id = 0;
                    $pay_type = 1;
                } else {
                    $delivery_id = $delivery->id;
                    $pay_type = 3;
                    $eorder['CustomerName'] = $delivery->customer_name;
                    $eorder['CustomerPwd'] = $delivery->customer_pwd;
                    $eorder['SendSite'] = $delivery->send_site;
                    $eorder['MonthCode'] = $delivery->month_code;
                }

                $sender_list = model('addons\delivery\model\ExpressBillSender')->get(['delivery_id' => $delivery_id]);
                if (!$sender_list) {
                    $this->error('请先设置发件人信息');
                }
                $eorder["ShipperCode"] = $express['code'];
                $eorder["OrderCode"] = $order['order_no'];
                $eorder["PayType"] = $pay_type;
                $eorder["ExpType"] = 1;
                $eorder["IsReturnPrintTemplate"] = 1;

                $sender = [];
                $sender["Company"] = $sender_list->company;
                $sender["Name"] = $sender_list->name;
                $sender["Mobile"] = $sender_list->mobile ? $sender_list->mobile : $sender_list->tel;
                $sender["ProvinceName"] = $sender_list->province;
                $sender["CityName"] = $sender_list->city;
                $sender["ExpAreaName"] = $sender_list->exp_area;
                $sender["Address"] = $sender_list->address;
                $sender["PostCode"] = $sender_list->post_code;

                $receiver = [];
                $receiver["Name"] = $order->accept_name;
                $receiver["Mobile"] = $order->accept_mobile;
                $receiver["ProvinceName"] = $order->accept_province;
                $receiver["CityName"] = $order->accept_city;
                $receiver["ExpAreaName"] = $order->accept_exp_area;
                $receiver["Address"] = str_replace(PHP_EOL, '', $order->accept_address);
                $receiver["PostCode"] = $params['post_code'];

                $good_list=$order->orderdetail;


                $commodity = [];
                foreach ($good_list as $index => $good) {

                    $commodityOne = [];
                    $desc = "";
                    /*foreach ($good['attr_list'] as $key => $value) {
                        $desc .= ',';
                        $desc .= $value['attr_group_name'] . ':' . $value['attr_name'];
                    }*/
                    $commodityOne["GoodsName"] = $good['name'] . '，数量：' . intval($good['num']) . $desc;
                    $commodityOne["GoodsCode"] = "";
                    $commodityOne["Goodsquantity"] = "";
                    $commodityOne["GoodsPrice"] = "";
                    $commodityOne["GoodsWeight"] = "";
                    $commodityOne['GoodsDesc'] = "";
                    $commodityOne['GoodsVol'] = "";
                    $commodity[] = $commodityOne;
                }

                $eorder["Sender"] = $sender;
                $eorder["Receiver"] = $receiver;
                $eorder["Commodity"] = $commodity;
                //调用电子面单
                $jsonParam = json_encode($eorder);

                $set_info=model('addons\delivery\model\Set')->get(1);

                //        echo "电子面单接口提交内容：<br/>".$jsonParam;
                $KdOrder=new \addons\delivery\library\KdOrder();
                $jsonResult = $KdOrder::submitEOrder($jsonParam, $set_info);
                //        echo "<br/><br/>电子面单提交结果:<br/>".$jsonResult;

                //解析电子面单返回结果
                $result = json_decode($jsonResult, true);

                if ($result["ResultCode"] == "100" || $result["ResultCode"] == '106') {
                    $this->success('成功','',$result['PrintTemplate']);//将面单显示出来
                }else{
                    $this->error( $result['Reason']);
                }

            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
    }
    /**
     * 发货商品
     */
    public function selectgoods()
    {
        $this->model=model('addons\delivery\model\Goods');
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
     * 快递鸟参数
     */
    public function kdniaoset($ids = null)
    {
        $this->model=model('addons\delivery\model\Set');
        $row = $this->model->get(1);
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
        $this->assign('row',$row);

        return $this->view->fetch();
    }
}

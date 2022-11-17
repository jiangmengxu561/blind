<?php

namespace app\admin\model;

use think\Model;


class Bshop extends Model
{

    

    

    // 表名
    protected $name = 'bshop';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







    public function bulid()
    {
        return $this->belongsTo('Bulid', 'c_Id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}

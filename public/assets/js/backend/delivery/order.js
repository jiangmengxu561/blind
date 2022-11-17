define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'delivery/order/index',
                    add_url: 'delivery/order/add',
                    del_url: 'delivery/order/del',
                    delivery_url: 'delivery/order/delivery',
                    detail_url: 'delivery/order/detail',
                    multi_url: 'delivery/order/multi',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                escape: false,
                sortName: 'addtime',
                pagination: true,
                commonSearch: true,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'order_no', title: __('订单号'), operate: 'LIKE'},
                        {field: 'accept_name', title: __('收货人姓名'), operate: 'LIKE'},
                        {field: 'accept_mobile', title: __('收货人手机'), operate: 'LIKE'},
                        {field: 'is_send', title: __('发货状态'),operate:'=',formatter: function (value, row, index) {
                                if (row['is_send'] == 1) {//已发货
                                    return '<span class="label label-success">已发货</span>';
                                }else{
                                    return '<span class="label label-info">待发货</span>';
                                }
                            }	,searchList: {"1": "已发货", "0": "待发货"}
                        },
                        {field: 'mobile', title: __('货品信息'), operate: false,formatter: function (value, row, index) {
                                var html='';
                                var obj=row.orderdetail;
                                for(let i in obj){
                                    html +='商品名：'+ obj[i].name + '&nbsp;&nbsp;数量：'+obj[i].num +'<br/>';
                                }
                                return html;
                            }
                        },
                        {field: 'addtime', title: __('下单时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table, events: Table.api.events.operate,
                            operate: false, formatter: function (value, row, index) {
                                var that = $.extend({}, this);
                                var table = $(that.table).clone(true);

                                if (row['is_send'] == 1 || row['is_pay'] == 0) {//未支付 已发货

                                    $(table).data('operate-delivery', null);
                                }
                                if (!row['express_no']) {//运单号为空

                                    $(table).data('operate-express', null);
                                }
                                that.table = table;
                                return Table.api.formatter.operate.call(that, value, row, index);
                            },
                            //formatter: Table.api.formatter.buttons,
                            buttons: [
                                {
                                    name: 'detail',
                                    title: '详情',
                                    icon: 'fa fa-navicon',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'delivery/order/detail',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    }

                                },
                                {
                                    name: 'delivery',
                                    title: '发货',
                                    icon: 'fa fa-send',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    url: 'delivery/order/delivery',
                                    callback: function (data) {
                                        Layer.alert("接收到回传数据：" + JSON.stringify(data), {title: "回传数据"});
                                    }

                                },
                            ]
                        }
                    ]
                ]
            });

            

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        select: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'delivery/order/select',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'id',
                sortOrder:'asc',
                pagination: true,
                commonSearch: true,
                columns: [
                    [
                      /*  {checkbox: true},*/
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'name', title: __('名称'), operate: 'LIKE'},
                        {
                            field: 'operate', title: __('Operate'), events: {
                                'click .btn-chooseone': function (e, value, row, index) {
                                    Fast.api.close(row);
                                },
                            }, formatter: function () {
                                return '<a href="javascript:;" class="btn btn-danger btn-chooseone btn-xs"><i class="fa fa-check"></i> ' + __('Choose') + '</a>';
                            }
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        selectgoods: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'delivery/order/selectgoods',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'id',
                sortOrder:'asc',
                pagination: true,
                commonSearch: true,
                columns: [
                    [
                        /*  {checkbox: true},*/
                        {field: 'id', title: __('Id'), operate: false},
                        {field: 'name', title: __('商品名称'), operate: 'LIKE'},
                        {field: 'cimage', title: __('商品缩略图'),operate:false, formatter: Table.api.formatter.image},
                        {field: 'cimages', title: __('商品图片'), formatter: Table.api.formatter.images,operate:false},
                        {
                            field: 'operate', title: __('Operate'), events: {
                                'click .btn-chooseone': function (e, value, row, index) {
                                    Fast.api.close(row);
                                },
                            }, formatter: function () {
                                return '<a href="javascript:;" class="btn btn-danger btn-chooseone btn-xs"><i class="fa fa-check"></i> ' + __('Choose') + '</a>';
                            }
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            $(document).on('click', '.img-center', function () {
                // window.open(this.src,'target','');
                // return false;
                var img = new Image();
                var imgWidth = this.getAttribute('data-width') || '800px';
                img.onload = function () {
                    var $content = $(img).appendTo('body').css({background: '#fff', width: imgWidth, height: 'auto'});
                    Layer.open({
                        type: 1, area: imgWidth, title: false, closeBtn: 1,
                        skin: 'layui-layer-nobg', shadeClose: true, content: $content,
                        end: function () {
                            $(img).remove();
                        },
                        success: function () {

                        }
                    });
                };
                img.onerror = function (e) {

                };
                img.src = this.src;
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        detail: function () {
            Controller.api.bindevent();
        },
        delivery: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                var refreshkey = function (data) {
                    $("input[name='row[express]']").val(data.name).trigger("change");
                    Layer.closeAll();
                    /*var keytitle = data.name;
                    var cont = $(".clickbox .create-click:first");
                    $(".keytitle", cont).remove();
                    if (keytitle) {
                        cont.append('<div class="keytitle">' + __('Event key') + ':' + keytitle + '</div>');
                    }*/
                };
                $(document).on('click', "#select-resources", function () {
                    parent.Backend.api.open($(this).attr("href"), __('选择快递公司'), {callback: refreshkey});
                    return false;
                });

                $(document).on('click', "#add_express", function () {
                    parent.Backend.api.open($(this).attr("href"));
                    return false;
                });
                $(document).on('click', "input[name='row[express_type]']", function () {
                    $(".kuaidi").toggleClass("hide", $(this).val() == 0 ?false:true);
                });
                $("input[name='row[express_type]']:checked").trigger("click");

                $(document).on('click', "#print_express_bill", function () {
                    var express_text=$("input[name='row[express]']").val();
                    if(!express_text){
                        Toastr.error("请选择快递公司");
                        return false;
                    }
                    var post_code=$("input[name='row[accept_post_code]']").val();
                    if(!post_code){
                        Toastr.error("请填写收件人编码");
                        return false;
                    }
                    var order_id=$("input[name='row[id]']").val();

                    $.ajax({
                        url: 'delivery/order/print',
                        type: 'post',
                        dataType: 'json',
                        data: {express:express_text,post_code:post_code,order_id:order_id},
                        success: function (ret) {
                            console.log('1111111111')
                            console.log(ret)

                            if (ret.hasOwnProperty("code")) {
                                if (ret.code === 1) {
                                    var data=ret.data;
                                    Layer.confirm(data, {
                                        btn: ['打印','关闭'],//按钮，
                                        title:"生成电子面单成功，请打印。",
                                    }, function(){
                                        print(data);
                                        Layer.closeAll();
                                        parent.Layer.closeAll();
                                    }, function(){
                                        Layer.closeAll();
                                        parent.Layer.closeAll();
                                    });
                                } else {
                                    Backend.api.toastr.error(ret.msg);
                                }
                            }
                        }, error: function (e) {
                            Backend.api.toastr.error(e.message);
                        }
                    });
                    return false;
                });
                //打印电子面单
                function print(html) {
                    //判断iframe是否存在，不存在则创建iframe
                    var iframe=document.getElementById("print-iframe");
                    if(!iframe){
                        var el = document.getElementById("printcontent");
                        iframe = document.createElement('IFRAME');
                        var doc = null;
                        iframe.setAttribute("id", "print-iframe");
                        iframe.setAttribute('style', 'position:absolute;width:0px;height:0px;left:-500px;top:-500px;');
                        document.body.appendChild(iframe);
                        doc = iframe.contentWindow.document;
                        doc.write('<div>'+html+'</div>');
                        doc.close();
                        iframe.contentWindow.focus();
                    }
                    iframe.contentWindow.print();
                    if (navigator.userAgent.indexOf("MSIE") > 0){
                        document.body.removeChild(iframe);
                    }
                }
                var refreshkeygoods = function (data) {
                    var cimage=Fast.api.cdnurl(data.cimage);
                    html='<li class="col-xs-3"><a href="'+cimage+'" data-url="'+cimage+'" target="_blank" class="thumbnail"><img src="'+cimage+'" class="img-responsive"></a><input type="number" name="row[goods][num][]" class="form-control" placeholder="请输入商品数量" value="1"/><input type="hidden" name="row[goods][id][]" class="form-control" value="'+data.id+'"/><a href="javascript:;" class="btn btn-danger btn-xs btn-trash"><i class="fa fa-trash"></i></a></li>';
                    $('#pre-goods').append(html);

                };
                // 移除按钮事件
                $(document.body).on("click", "#pre-goods .btn-trash", function () {
                    $(this).closest("li").remove();
                });
                Form.api.bindevent($("form#addsend-form"),  function(data, ret){
                    //如果我们需要在提交表单成功后做跳转，可以在此使用location.href="链接";进行跳转
                    var page=window.parent.frames.length-2;
                    console.log('78945264613')
                    console.log(page)
                    //$(window.parent.frames[page].document).find("#ceshi").html(html);
                    if(typeof window.parent.frames[page]==='undefined'){

                    }else{
                        window.parent.frames[page].location.reload();
                    }

                }, function(data, ret){
                    Toastr.success("失败");
                }, function(success, error){
                    return true;
                });
                $(document).on('click', ".send_button", function () {
                    parent.Backend.api.open($(this).attr("href"), __('发货'), {callback: refreshkey});
                    return false;
                });
                $(document).on('click', "#select-goods", function () {
                    parent.Backend.api.open($(this).attr("href"), __('选择发货商品'), {callback: refreshkeygoods});
                    return false;
                });
            }
        }
    };
    return Controller;
});
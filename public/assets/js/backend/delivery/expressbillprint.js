define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'delivery/expressbillprint/index',
                    add_url: 'delivery/expressbillprint/add',
                    edit_url: 'delivery/expressbillprint/edit',
                    del_url: 'delivery/expressbillprint/del',
                    multi_url: 'delivery/expressbillprint/multi',
                    defaultsender_url:'delivery/expressbillprint/defaultsender'
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
                        {field: 'express_com', title: __('快递公司')},
                        {field: 'send_name', title: __('网点名称')},
                        {field: 'send_name', title: __('网点编码')},
                        {field: 'customer_name', title: __('客户号')},
                        {field: 'mobile', title: __('发件人信息'),formatter: function (value, row, index) {
                                 var html='';
                                     html +='名称:'+row.name+'<br/>';
                                     html +='联系方式:'+row.mobile+'<br/>';
                                     html +='地址:'+row.long_address;
                                 return html;
                            }
                        },
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            

            // 为表格绑定事件
            Table.api.bindevent(table);
            var LODOP; //声明为全局变量
            //检测是否含有插件
            function CheckIsInstall() {
                try {
                    var LODOP = getLodop();
                    if (LODOP.VERSION) {
                        if (LODOP.CVERSION){
                            Toastr.success("当前有C-Lodop云打印可用!\n C-Lodop版本:" + LODOP.CVERSION + "(内含Lodop" + LODOP.VERSION + ")");
                        }else{
                            Toastr.error("本机已成功安装了Lodop控件！\n 版本号:" + LODOP.VERSION);
                        }

                    }
                } catch (err) {

                }
            }
            // 指定搜索条件
            $(document).on("click", ".btn-singlesearch", function () {
                CheckIsInstall();

                return false;
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        defaultsender: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                var refreshkey = function (data) {
                    $("input[name='row[express]']").val(data.name).trigger("change");
                    $("input[name='row[express_id]']").val(data.id);
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

            }
        }
    };
    return Controller;
});
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'delivery/goods/index',
                    add_url: 'delivery/goods/add',
                    edit_url: 'delivery/goods/edit',
                    del_url: 'delivery/goods/del',
                    multi_url: 'delivery/goods/multi',
                    addcat_url: 'delivery/goods/addcat',
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
                        {field: 'id', title: __('Id'),operate:false},
                        {field: 'name', title: __('名称'),operate:false},
                        {field: 'cat_name', title: __('分类'),operate:false},
                        {field: 'price', title: __('价格'),operate:false},
                        {field: 'freight_name', title: __('运费模板'),operate:false},
                        {field: 'cimage', title: __('商品缩略图'),operate:false, formatter: Table.api.formatter.image},
                        {field: 'cimages', title: __('商品图片'), formatter: Table.api.formatter.images,operate:false},
                        {field: 'addtime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,operate:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
        edit: function () {
            Controller.api.bindevent();
        },
        addcat: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                Form.api.bindevent($("form#addcat-form"),  function(data, ret){
                    //如果我们需要在提交表单成功后做跳转，可以在此使用location.href="链接";进行跳转
                    var html='112312';
                    var page=window.parent.frames.length-2;
                    //$(window.parent.frames[page].document).find("#ceshi").html(html);
                    window.parent.frames[page].location.reload();
                    //window.parent.location.reload();
                }, function(data, ret){

                    Toastr.success("失败");

                }, function(success, error){

                });
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
                    parent.Backend.api.open($(this).attr("href"), __('新增分类'), {callback: refreshkey});
                    return false;
                });

            }
        }
    };
    return Controller;
});
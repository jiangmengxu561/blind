define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'jstree'], function ($, undefined, Backend, Table, Form, undefined) {
    //读取选中的条目
    $.jstree.core.prototype.get_all_checked = function (full) {
        var obj = this.get_selected(), i, j;
        for (i = 0, j = obj.length; i < j; i++) {
            obj = obj.concat(this.get_node(obj[i]).parents);
        }
        obj = $.grep(obj, function (v, i, a) {
            return v != '#';
        });
        obj = obj.filter(function (itm, i, a) {
            return i == a.indexOf(itm);
        });
        return full ? $.map(obj, $.proxy(function (i) {
            return this.get_node(i);
        }, this)) : obj;
    };
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'delivery/postage/index',
                    add_url: 'delivery/postage/add',
                    edit_url: 'delivery/postage/edit',
                    del_url: 'delivery/postage/del',
                    select_url: 'delivery/postage/select',
                    multi_url: 'delivery/postage/multi',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                escape: false,
                sortName: 'addtime',
                sortOrder:'desc',
                pagination: true,
                commonSearch: false,
                columns: [
                    [
                        {field: 'name', title: __('名称')},
                        {
                            field: 'is_default',
                            title: __('默认规则'),
                            align: 'center',
                            formatter: Table.api.formatter.toggle
                        },
                        {field: 'addtime', title: __('创建时间'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        select: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"),  function(data, ret){
                    //如果我们需要在提交表单成功后做跳转，可以在此使用location.href="链接";进行跳转

                    if(ret.type){//是添加规则页面返回
                        var html = [];
                        for (var i = 0; i < data.length; i++) {
                            html.push('<div class="form-group"><label class="control-label col-xs-12 col-sm-2"></label><div class="col-xs-12 col-sm-8"><textarea id="c-content" class="form-control editor" rows="5" cols="50" disabled>'+data[i]+'</textarea><span><button type="button" key="'+i+'"   class="btn btn-primary content-del"><i class="fa fa-minus"></i>删除条目</button></span></div></div>');
                        }
                        var page=window.parent.frames.length-2;
                        $(window.parent.frames[page].document).find("#rulearea").html(html);
                    }
                }, function(data, ret){

                    //Toastr.success("失败");

                }, function(success, error){
                    if ($("#treeview").size() > 0) {
                        var r = $("#treeview").jstree("get_all_checked");
                        $("input[name='row[rules]']").val(r.join(','));
                    }
                    console.log('74113231313');
                    return true;
                });
                var refreshkey = function (data) {

                };
                $(document).on('click', "#addarea", function () {
                    var express_type=$("input[name='row[express_type]']:checked").val();
                    parent.Backend.api.open('delivery/postage/select/express_type/'+express_type, __('添加运费规则'), {callback: refreshkey});
                    return false;
                });
                $(document).on('click', ".content-del", function () {
                    var del_key= $(this).attr("key");
                    $(this).closest('.form-group').remove();
                    $.ajax({
                        url: 'delivery/postage/del_temp',
                        type: 'post',
                        dataType: 'json',
                        data: {key:del_key},
                        success: function (ret) {
                            if (ret.hasOwnProperty("code")) {
                                if (ret.code === 1) {

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
                //渲染权限节点树
                $.ajax({
                    url: "delivery/postage/citytree",
                    type: 'post',
                    dataType: 'json',
                    data: {},
                    success: function (ret) {
                        if (ret.hasOwnProperty("code")) {
                            var data = ret.hasOwnProperty("data") && ret.data != "" ? ret.data : "";
                            if (ret.code === 1) {
                                //销毁已有的节点树
                                $("#treeview").jstree("destroy");
                                Controller.api.rendertree(data);
                            } else {
                                Backend.api.toastr.error(ret.msg);
                            }
                        }
                    }, error: function (e) {
                        Backend.api.toastr.error(e.message);
                    }
                });
                //全选和展开
                $(document).on("click", "#checkall", function () {
                    $("#treeview").jstree($(this).prop("checked") ? "check_all" : "uncheck_all");
                });
                $(document).on("click", "#expandall", function () {
                    $("#treeview").jstree($(this).prop("checked") ? "open_all" : "close_all");
                });
            },
            rendertree: function (content) {
                $("#treeview")
                    .on('redraw.jstree', function (e) {
                        $(".layer-footer").attr("domrefresh", Math.random());
                    })
                    .jstree({
                        "themes": {"stripes": true},
                        "checkbox": {
                            "keep_selected_style": false,
                        },
                        "types": {
                            "root": {
                                "icon": "fa fa-folder-open",
                            },
                            "menu": {
                                "icon": "fa fa-folder-open",
                            },
                            "file": {
                                "icon": "fa fa-file-o",
                            }
                        },
                        "plugins": ["checkbox", "types"],
                        "core": {
                            'check_callback': true,
                            "data": content
                        }
                    });
            }
        }
    };
    return Controller;
});
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'bshop/index' + location.search,
                    add_url: 'bshop/add',
                    edit_url: 'bshop/edit',
                    del_url: 'bshop/del',
                    multi_url: 'bshop/multi',
                    import_url: 'bshop/import',
                    table: 'bshop',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'bulid.b_name', title: __('C_id')},
                        {field: 's_name', title: __('S_name'), operate: 'LIKE'},
                        {field: 's_image', title: __('S_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'title', title: __('Title'), operate: 'LIKE'},
                        {field: 'price', title: __('价格'), operate: 'LIKE'},
                        {field: 'probability', title: __('Probability'), operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        // {field: 'bulid.b_name', title: __('Bulid.b_name'), operate: 'LIKE'},
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

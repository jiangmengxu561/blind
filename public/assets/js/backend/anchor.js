define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'anchor/index' + location.search,
                    add_url: 'anchor/add',
                    edit_url: 'anchor/edit',
                    del_url: 'anchor/del',
                    multi_url: 'anchor/multi',
                    import_url: 'anchor/import',
                    table: 'anchor',
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
                        {field: 'u_id', title: __('U_id')},
                        {field: 'm_id', title: __('M_id')},
                        {field: 's_id', title: __('S_id'), operate: 'LIKE'},
                        {field: 'probability', title: __('Probability'), operate: 'LIKE'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件waterworks_id
            Table.api.bindevent(table);
        },
        add: function () {
            $("#c-s_id").data("params", function(){
                return {custom: {id: $("#c-m_id").val()}};
            });
            Controller.api.bindevent();
        },
        edit: function () {
            $("#c-s_id").data("params", function(){
                return {custom: {id: $("#c-m_id").val()}};
            });
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

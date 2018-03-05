<div class="hbox stretch wulaui">
    <aside class="aside aside-md b-r">
        <div class="vbox">
            <header class="header bg-light b-b clearfix">
                <p class="h4">云端配置</p>
            </header>
            <section class="scrollable m-t-sm">
                <div class="ztree m-l-n-xs" data-ztree="{"rest/cfg/dic"|app}" id="ztree" data-lazy></div>
            </section>
        </div>
    </aside>
    <section class="bg-white-only">
        <div class="vbox">
            <section class="scrollable" id="wrapper" data-load data-lazy>
                <div class="wrapper-lg">
                    <div class="text-muted h4"><i class="fa fa-hand-o-left"></i> 请从左侧选择一个配置.</div>
                </div>
            </section>
        </div>
    </section>
    <script>
		layui.use(['jquery', 'bootstrap', 'ztree.edit', 'ztree', 'wulaui', 'layer'], function ($, b, z, zz, wulaui, layer) {
			var docWrapper = $('#wrapper');
			$('#ztree').on('ztree.init', function (e) {
				var settings = {
					view    : {
						showLine      : !0,
						dblClickExpand: false,
						selectedMulti : false,
						removeHoverDom: function (tid, node) {
							var btn = $("#addBtn_" + node.tId);
							if (btn.length > 0) {
								$('#delBtn_' + node.tId).hide();
								btn.hide();
							}
						},
						addHoverDom   : function (tid, node) {
							var sObj = $("#" + node.tId + "_span"), btn = $("#addBtn_" + node.tId), del = null;
							if (node.editNameFlag) {
								return;
							}
							if (btn.length > 0) {
								btn.show();
								$('#delBtn_' + node.tId).show();
								return;
							}
							btn = $("<span class='button add' id='addBtn_" + node.tId + "' title='添加子配置'></span>");
							btn.data('treeNode', node).data('treeId', tid);
							del = $("<span class='button remove' id='delBtn_" + node.tId + "' title='删除配置'></span>");
							del.data('treeNode', node).data('treeId', tid);
							sObj.after(del);
							sObj.after(btn);
						}
					},
					data    : {
						keep: {
							parent: true
						}
					},
					callback: {
						onClick: function (e, treeId, treeNode) {
							if ($(e.target).is('.button')) {
								return false;
							}
							docWrapper.reload(wulaui.app('rest/cfg/edit/' + treeNode.id));
							return false;
						}
					}
				};
				$.extend(true, e.tree, {
					settings: settings
				});
			}).on('click', '.add', function () {
				var treeNode = $(this).data('treeNode');
				wulaui.ajax.dialog({
					content: wulaui.app('rest/cfg/add/' + treeNode.id),
					area   : '300px,auto',
					title  : '添加' + treeNode.name + '的子配置'
				}, $(this));
				return false;
			}).on('click', '.remove', function () {
				var treeNode = $(this).data('treeNode'), tree = $.fn.zTree.getZTreeObj($(this).data('treeId'));
				wulaui.ajax.confirm({
					url    : wulaui.app('rest/cfg/del/' + treeNode.id),
					success: function (data) {
						if (data.code === 200) {
							tree.removeNode(treeNode);
						}
					}
				}, '你真的要删除配置' + treeNode.name + '?');
			}).on('before.dialog', '.button', function (e) {
				var $this     = $(this);
				e.options.btn = ['保存', '取消'];
				e.options.yes = function () {
					$('#add-form').data('openor', $this).submit();
					return false;
				};
			}).wulatree('load');

			$('body').on('ajax.success', '#add-form', function (e, data) {
				var openor   = $(this).data('openor'),
					treeNode = openor.data('treeNode'),
					tree     = $.fn.zTree.getZTreeObj(openor.data('treeId')),
					cfg      = data.args.cfg;

				if (treeNode.zAsync) {
					tree.addNodes(treeNode, cfg);
				}
				tree.expandNode(treeNode, true, false, true);
				layer.close(openor.data('dialogId'));
			}).on('click', '.opt-save', function () {
				$('#cfg-form').submit();
			}).on('click', '.opt-reset', function () {
				$('#cfg-form').get(0).reset();
			}).on('ajax.success', '#cfg-form', function (e, data) {
				var tree  = $.fn.zTree.getZTreeObj('ztree'),
					cfg   = data.args.cfg,
					node  = tree.getNodeByParam('id', cfg.id, null);
				node.name = cfg.name;
				tree.updateNode(node);
			});
		});
    </script>
</div>
<div class="hbox stretch wulaui">
    <aside class="aside aside-lg b-r">
        <div class="vbox">
            <section class="scrollable m-t-sm">
                <div class="ztree m-l-n-xs" data-ztree="{"rest/doc/dic"|app}" id="rest-app-doc-dic" data-lazy></div>
            </section>
        </div>
    </aside>
    <section class="bg-white-only">
        <div class="vbox">
            <section class="scrollable" id="rest-app-doc" data-load data-lazy>
                <div class="wrapper-lg">
                    <div class="text-muted h4"><i class="fa fa-hand-o-left"></i> 请从左侧选择你要阅读的文档.</div>
                </div>
            </section>
        </div>
    </section>
    <script>
		layui.link("{'wula/jqadmin/css/md.min.css'|vendor}").use(['jquery', 'bootstrap', 'ztree', 'wulaui'], function ($, b, z, wulaui) {
			$('#rest-app-doc-dic').on('ztree.init', function (e) {
				var docWrapper = $('#rest-app-doc'), settings = {
					view    : {
						showLine: !0
					},
					data    : {
						keep: {
							leaf  : false,
							parent: true
						}
					},
					callback: {
						onClick: function (e, treeId, treeNode) {
							if (treeNode.v) {
								docWrapper.reload(wulaui.app('rest/doc/view/' + treeNode.v + '/' + treeNode.id + '/' + treeNode.post));
							} else if (treeNode.type) {
								docWrapper.reload(wulaui.app('rest/doc/doc/' + treeNode.type), true);
							}
							return false;
						}
					}
				};
				$.extend(true, e.tree, {
					settings: settings
				});
			}).wulatree('load')
		});
    </script>
</div>
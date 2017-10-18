<div class="hbox stretch">
    <aside class="aside aside-lg b-r">
        <div class="vbox">
            <header class="header bg-light dk b-b">
                <p class="h4">文档目录</p>
            </header>
            <section class="scrollable">
                <div class="slim-scroll" data-height="100%" data-disable-fade-out="true" data-distance="0"
                     data-size="5px" data-color="#333333">
                    <div class="ztree m-l-n-xs" data-ztree="{"~rest/doc/dic"|app}" id="rest-app-doc-dic"
                         data-lazy></div>
                </div>
            </section>
        </div>
    </aside>
    <section class="bg-white-only" id="rest-app-doc" data-load data-lazy>
        <div class="wrapper-lg">
            <div class="text-muted h4"><i class="fa fa-hand-o-left"></i> 请从左侧选择你要阅读的文档.</div>
        </div>
    </section>
    <script type="text/javascript">
		requirejs(['ztree'], function () {
			$('#rest-app-doc-dic').on('ztree.init', function (e) {
				var docWrapper  = $('#rest-app-doc');
				var settings    = {
					view    : {
						showLine: 0,
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
								docWrapper.reload(wulapp('~rest/doc/view/' + treeNode.v + '/' + treeNode.id + '/' + treeNode.post));
							} else if (treeNode.type) {
								docWrapper.reload(wulapp('~rest/doc/doc/' + treeNode.type), true);
							}
							return false;
						}
					}
				};
				e.tree.settings = $.extend(true, e.tree.settings, settings);
			}).wulatreeLoad();
		});
    </script>
</div>
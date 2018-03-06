<section class="vbox wulaui layui-hide" id="workset">
    <header class="header bg-light dk b-b clearfix">
        <div class="row m-t-sm">
            <div class="col-sm-12 m-b-xs">
                <a href="{'rest/vers/edit'|app}/{$appkey}" class="btn btn-sm btn-success edit-app" data-ajax="dialog"
                   data-area="700px,auto" data-title="新版本">
                    <i class="fa fa-plus"></i> 新版本
                </a>
                <a href="{'rest/vers/del'|app}" data-ajax data-grp="#table tbody input.grp:checked"
                   data-confirm="你真的要删除这些版本吗？" data-warn="请选择要删除的版本" class="btn btn-danger btn-sm"><i
                            class="fa fa-trash"></i> 删除</a>
            </div>
            <form data-table-form="#table">
                <input type="hidden" value="{$appkey}" name="appkey">
            </form>
        </div>
    </header>
    <section class="w-f bg-white">
        <div class="table-responsive">
            <table id="table" data-auto data-table="{'rest/vers/data'|app}/{$key}" data-sort="vercode,d"
                   style="min-width: 800px">
                <thead>
                <tr>
                    <th width="20"><input type="checkbox" class="grp"/></th>
                    <th width="90" data-sort="vercode,d">版本号</th>
                    <th width="90">版本</th>
                    <th width="80">平台</th>
                    <th width="100">配置</th>
                    <th width="60">强升</th>
                    <th width="200">软件包文件</th>
                    <th width="90">体积</th>
                    <th>发行说明</th>
                    <th width="40"></th>
                </tr>
                </thead>
            </table>
        </div>
    </section>
    <footer class="footer b-t">
        <div data-table-pager="#table" data-limit="50"></div>
    </footer>
    <script type="text/javascript">
		layui.use(['jquery', 'wulaui'], function ($) {
			$('#workset').on('before.dialog', '.edit-app', function (e) {
				var $this      = $(this);
				e.options.btn  = ['保存', '取消'];
				e.options.yes  = function () {
					$('#form').data('dialogId', $this.data('dialogId')).submit();
					return false;
				};
				e.options.btn2 = function () {
					if ($('#form').data('ajaxSending')) {
						return false;
					}
				}
			}).removeClass('layui-hide');
		});
    </script>
</section>

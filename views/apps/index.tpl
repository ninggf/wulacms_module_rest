<section class="vbox wulaui" id="rest-app-workset">
    <header class="header bg-light dk b-b clearfix">
        <div class="row m-t-sm">
            <div class="col-sm-6 m-b-xs">
                <a href="{'rest/apps/edit'|app}" class="btn btn-sm btn-success edit-app" data-ajax="dialog"
                   data-area="700px,auto" data-title="新的应用">
                    <i class="fa fa-plus"></i> 新的应用
                </a>
                <div class="btn-group">
                    <a href="{'rest/apps/del'|app}" data-ajax data-grp="#rest-apps-list tbody input.grp:checked"
                       data-confirm="你真的要删除这些应用吗？" data-warn="请选择要删除的应用" class="btn btn-danger btn-sm"><i
                                class="fa fa-trash"></i> 删除</a>
                    <a href="{'rest/apps/set-status/0'|app}" data-ajax
                       data-grp="#rest-apps-list tbody input.grp:checked" data-confirm="你真的要禁用这些应用吗？"
                       data-warn="请选择要禁用的应用" class="btn btn-sm btn-warning"><i class="fa fa-square-o"></i> 禁用</a>
                    <a href="{'rest/apps/set-status/1'|app}" data-ajax
                       data-grp="#rest-apps-list tbody input.grp:checked" data-confirm="你真的要激活这些应用吗？"
                       data-warn="请选择要激活的应用" class="btn btn-sm btn-primary"><i class="fa fa-check-square-o"></i>
                        激活</a>
                </div>
            </div>
            <div class="col-sm-6 m-b-xs text-right">
                <form data-table-form="#rest-apps-list" class="form-inline">
                    <div class="checkbox m-l-xs m-r-xs">
                        <label>
                            <input type="checkbox" name="status" value="0" id="astatus"/>
                            被禁用的
                        </label>
                    </div>
                    <div class="input-group input-group-sm">
                        <input type="text" name="q" class="input-sm form-control" placeholder="{'Search'|t}"/>
                        <span class="input-group-btn">
                            <button class="btn btn-sm btn-info" id="btn-do-search" type="submit">Go!</button>
                        </span>
                    </div>
                </form>
            </div>
        </div>
    </header>
    <section class="w-f bg-white">
        <div class="table-responsive">
            <table id="rest-apps-list" data-auto data-table="{'rest/apps/data'|app}" data-sort="status,d"
                   style="min-width: 800px">
                <thead>
                <tr>
                    <th width="20"><input type="checkbox" class="grp"/></th>
                    <th width="100">应用名</th>
                    <th width="120">APPKEY</th>
                    <th width="200">APPSECRET</th>
                    <th>说明</th>
                    <th width="60" data-sort="status,d">状态</th>
                    <th width="80"></th>
                </tr>
                </thead>
            </table>
        </div>
    </section>
    <footer class="footer b-t">
        <div data-table-pager="#rest-apps-list"></div>
    </footer>
    <script type="text/javascript">
		layui.use(['jquery', 'wulaui'], function ($) {
			$('#astatus').change(function () {
				$('#btn-do-search').click()
			});
			$('#rest-app-workset').on('before.dialog', '.edit-app', function (e) {
				e.options.btn  = ['保存', '取消'];
				e.options.yes  = function () {
					$('#rest-app-form').data('ajaxDone', 'close:dlg-app-form').submit();
					return false;
				};
				e.options.btn2 = function () {
					if ($('#rest-app-form').data('ajaxSending')) {
						return false;
					}
				}
			});
		});
    </script>
</section>

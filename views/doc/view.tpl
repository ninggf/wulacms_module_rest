<div class="bg-light">
    <div class="wrapper-lg p-b-xs p-t-xs"></div>
    <ul class="nav nav-tabs p-t-n-xs">
        <li class="m-l-lg active">
            <a href="#api-doc-tab" data-toggle="tab">接口文档</a>
        </li>
        <li><a href="#api-doc-rst" data-toggle="tab">返回数据</a></li>
        <li><a href="#api-sandbox" data-toggle="tab">测试沙盒</a></li>
    </ul>
</div>
<div class="bg-white-only wulaui">
    <div class="tab-content">
        <div class="tab-pane active" id="api-doc-tab">
            <div class="markdown-body">{$document}</div>
        </div>
        <div class="tab-pane" id="api-doc-rst">
            <div class="markdown-body">{$return}</div>
        </div>
        <div class="tab-pane" id="api-sandbox">
            <div class="wrapper-lg max-w-800">
                <form name="ApiTestForm" action="{'rest/doc/test'|app}" class="form-horizontal" method="post"
                      data-validate="{$rule|escape}" data-ajax>
                    <input type="hidden" name="v" value="{$version}"/>
                    <input type="hidden" name="api" value="{$api}"/>
                    <input type="hidden" name="_mehtod" value="{$method}"/>
                    <input type="hidden" name="_params" value="{$params}"/>
                    {$form|render}
                    <div class="form-group">
                        <div class="col-md-offset-2 col-md-10 col-xs-12">
                            <button type="submit" class="btn btn-primary">提交测试</button>
                            <button type="reset" class="btn btn-default">重置</button>
                        </div>
                    </div>
                </form>
                <div id="api-test-result"></div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
		layui.use(['jquery', 'highlight'], function ($) {
			$('.markdown-body pre code').each(function (i, code) {
				hljs.highlightBlock(code);
			});
			$('#api-test-result').on('content.updated', function () {
				$('#api-test-result').find('pre code').each(function (i, code) {
					hljs.highlightBlock(code);
				})
			});
		});
    </script>
</div>
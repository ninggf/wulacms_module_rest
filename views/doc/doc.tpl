<div class="vbox">
    <section class="scrollable">
        <div class="wrapper" id="com-api-doc">
            <div class="markdown-body">
                {$doc}
            </div>
            <script type="text/javascript">
				requirejs(['highlight'], function () {
					$('#com-api-doc .markdown-body pre code').each(function (i, code) {
						hljs.highlightBlock(code);
					});
				});
            </script>
        </div>
    </section>
</div>
<section class="vbox wulaui">
    <header class="header bg-light b-b clearfix">
        <p class="h4">{$cfgName}</p>
    </header>
    <section class="w-f scrollable">
        <div class="max-w-800 p-t-md">
            <form id="cfg-form" name="SettingForm" action="{'rest/cfg/save'|app}" data-validate="{$rules|escape}"
                  data-ajax method="post" role="form" class="form-horizontal" data-loading novalidate>
                {$form|render}
            </form>
        </div>
    </section>
    <footer class="footer bg-light b-t">
        <button class="btn btn-sm btn-primary opt-save">保存</button>
        <button class="btn btn-sm btn-warning opt-reset">重置</button>
    </footer>
</section>
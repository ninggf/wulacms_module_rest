<section class="vbox wulaui">
    <header class="header bg-light b-b clearfix lt">
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
    <footer class="footer bg-light b-t lt">
        <div class="row m-t-xs max-w-800">
            <div class="col-md-offset-3 col-md-9">
                <button class="btn btn-md btn-primary opt-save">保存</button>
                <button class="btn btn-md btn-warning opt-reset">重置</button>
            </div>
        </div>
    </footer>
</section>
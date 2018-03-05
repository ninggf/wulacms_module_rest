<div class="container-fluid m-t-sm">
    <div class="row wulaui">
        <div class="col-xs-12">
            <form id="form" name="Form" data-validate="{$rules|escape}" action="{'rest/vers/save'|app}" data-ajax
                  data-ajax-done="reload:#table;close:me" method="post" data-loading>
                {$form|render}
            </form>
        </div>
    </div>
</div>
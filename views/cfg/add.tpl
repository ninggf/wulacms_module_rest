<div class="container-fluid m-t-sm">
    <div class="row wulaui">
        <div class="col-xs-12">
            <form id="add-form" name="AddForm" data-validate="{$rules|escape}" action="{'rest/cfg/add'|app}" data-ajax
                  method="post" data-loading>
                <input type="hidden" name="pid" id="pid" value="{$pid}"/>
                {$form|render}
            </form>
        </div>
    </div>
</div>
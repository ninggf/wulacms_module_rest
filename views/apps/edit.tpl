<div class="row wulaui">
    <div class="col-xs-12">
        <form id="rest-app-form" name="RestAppForm" data-validate="{$rules|escape}" action="{'~rest/apps/save'|app}"
              data-ajax data-ajax-done="reload:#rest-app-list" method="post">
            <input type="hidden" name="id" id="id" value="{$id}"/>
            {$form|render}
        </form>
    </div>
</div>
<div class="container-fluid m-t-md">
    <div class="row wulaui">
        <div class="col-sm-12">
            <form id="core-channel-form" name="SettingForm" action="{'rest/channel/save'|app}"
                  data-validate="{$rules|escape}" data-ajax method="post" role="form"
                  class="form-horizontal {if $script}hidden{/if}" data-loading style="padding-top: 10px;">
                <input type="hidden" name="id" id="id" value="{$id}"/>
                {$form|render}
            </form>
        </div>

    </div>

</div>
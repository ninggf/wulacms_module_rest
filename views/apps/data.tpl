<tbody data-total="{$total}">
{foreach $items as $item}
    <tr>
        <td>
            <input type="checkbox" value="{$item.id}" class="grp"/>
        </td>
        <td>{$item.name}</td>
        <td>{$item.appkey}</td>
        <td>{$item.appsecret}</td>
        <td>{$item.note|escape}</td>
        <td class="text-center">
            {if $item.status}
                <span class="active"><i class="fa fa-check text-success text-active"></i></span>
            {else}
                <span><i class="fa fa-times text-danger text"></i></span>
            {/if}
        </td>
        <td class="text-right">
            <a href="{'~rest/apps/edit'|app}/{$item.id}" data-ajax="dialog" data-dialog-width="700px"
               data-dialog-id="dlg-app-form" data-dialog-title="编辑『{$item.name|escape}』" data-dialog-type="orange"
               data-dialog-icon="fa fa-anchor" class="btn btn-xs edit-app"> <i
                        class="fa fa-pencil-square-o text-primary"></i>
            </a>
        </td>
    </tr>
{/foreach}
</tbody>
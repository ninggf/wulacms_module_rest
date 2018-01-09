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
            <a href="{'rest/apps/edit'|app}/{$item.id}" data-ajax="dialog" data-area="700px,auto"
               data-title="编辑『{$item.name|escape}』" class="btn btn-xs edit-app">
                <i class="fa fa-pencil-square-o text-primary"></i>
            </a>
        </td>
    </tr>
{/foreach}
</tbody>
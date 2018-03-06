<tbody data-total="{$total}">
{foreach $items as $item}
    <tr>
        <td>
            <input type="checkbox" value="{$item.id}" class="grp"/>
        </td>
        <td>
            {if $item.callback_url}
                <a href="{$item.callback_url}" target="_blank">{$item.name}</a>
            {else}
                {$item.name}
            {/if}
        </td>
        <td>{$item.appkey}</td>
        <td>{$item.appsecret}</td>
        <td>{$platforms[$item.platform]}</td>
        <td>{$item.note|escape}</td>
        <td class="text-center">
            {if $item.status}
                <span class="active"><i class="fa fa-check text-success text-active"></i></span>
            {else}
                <span><i class="fa fa-times text-danger text"></i></span>
            {/if}
        </td>
        <td>1.0.0</td>
        <td class="text-right">
            <a href="{'rest/apps/edit'|app}/{$item.id}" data-ajax="dialog" data-area="700px,auto"
               data-title="编辑『{$item.name|escape}』" class="edit-app">
                <i class="fa fa-pencil-square-o text-primary"></i>
            </a>
            {if $pkgMng}
                <a href="{'rest/vers'|app}/{$item.appkey}" data-tab="&#xe643;" title="{$item.name}的软件包">
                    <i class="fa fa-list-ul"></i>
                </a>
            {/if}
        </td>
    </tr>
{/foreach}
</tbody>
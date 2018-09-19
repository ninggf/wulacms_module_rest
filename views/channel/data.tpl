<tbody data-total="{$total}" class="wulaui">
{foreach $rows as $row}
    <tr>
        <td> <input type="checkbox" value="{$row.id}" class="grp"/></td>
        <td>{$row.channel_name}</td>
        <td>{$row.channel}</td>
        <td>{$row.desc}</td>
        <td>{$row.create_time|date_format:'%Y-%m-%d %H:%M:%S'}</td>
        <td class="text-center">
            {if $row.status}
                <span class="active"><i class="fa fa-check text-success text-active"></i></span>
            {else}
                <span><i class="fa fa-times text-danger text"></i></span>
            {/if}
        </td>
        <td class="text-right">
            <a href="{'rest/channel/edit'|app}/{$row.id}" data-ajax="dialog" data-area="400px,auto"
               data-title="编辑『{$row.channel_name|escape}』" class="btn btn-xs btn-primary edit-admin">
                <i class="fa fa-pencil-square-o"></i>
            </a>
            <a href="{'rest/channel/del'|app}/{$row.id}" data-confirm="你真的要删除?" data-ajax
               class="btn btn-xs btn-danger">
                <i class="fa fa-trash-o"></i>
            </a>
        </td>
    </tr>
    {foreachelse}
    <tr>
        <td colspan="7" class="text-center">暂无相关数据!</td>
    </tr>
{/foreach}
</tbody>
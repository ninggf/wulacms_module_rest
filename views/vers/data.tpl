<tbody data-total="{$total}">
{foreach $rows as $row}
    <tr>
        <td>
            <input type="checkbox" class="grp" value="{$row.id}"/>
        </td>
        <td>
            {$row.vercode}
        </td>
        <td>
            {$row.version}
        </td>
        <td>
            {$platforms[$row.platform]}
        </td>
        <td>
            {$row.cfgName}
        </td>
        <td>
            {if $row.update_type}是{/if}
        </td>
        <td>
            {$row.file}
        </td>
        <td>
            {$row.size|readable_size}
        </td>
        <td>
            {$row.desc|escape|nl2br}
        </td>
        <td class="text-right">
            <a href="{'rest/vers/edit'|app}/{$row.appkey}/{$row.id}" data-ajax="dialog" data-area="500px,auto"
               title="编辑:{$row.version}" class="edit-app">
                <i class="fa fa-pencil-square-o text-primary"></i>
            </a>
        </td>
    </tr>
    {foreachelse}
    <tr>
        <td colspan="9" class="text-center">无版本</td>
    </tr>
{/foreach}
</tbody>
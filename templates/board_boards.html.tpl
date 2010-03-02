<table>
<thead>
<tr>
 <th>{if !empty($boardid)}<a href="viewboard.php?id={$boardid}">{/if}{$heading|default:"Forum"}{if !empty($boardid)}</a>{/if}</th>
 <th>Themen</th>
 <th>Letzte Antwort</th>
</tr>
</thead>
<tbody>
{foreach from=$subboards item=subboard}
{if $subboard->hasGroup()}
{assign var=group value=$subboard->getGroup()}{$group->load()}
<tr>
 <td>
  <a href="viewboard.php?id={$subboard->getBoardID()}">{$subboard->getName()}</a><br />
  {$subboard->getDesc()}
 </td>
 <td>{if $group !== null}{$group->getThreadCount()}{/if}</td>
 <td>{if $group !== null}{$group->getLastPostDate()|date_format:"%d.%m.%Y %H:%M"} von {$group->getLastPostAuthor()}{/if}</td>
</tr>
{/if}
{/foreach}
</tbody>
</table>

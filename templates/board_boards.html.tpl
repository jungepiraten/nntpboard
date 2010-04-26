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
{assign var=group value=$subboard->getGroup()}
{assign var=connection value=$group->getConnection($DATADIR)}
{$connection->open()}
<tr>
 <td>
  <a href="viewboard.php?id={$subboard->getBoardID()}">{$subboard->getName()}</a><br />
  {$subboard->getDesc()}
 </td>
 <td>{if $connection !== null}{$connection->getThreadCount()}{/if}</td>
 <td>{if $connection !== null}<a href="viewthread.php?boardid={$subboard->getBoardID()}&amp;messageid={$connection->getLastPostMessageID()}">{$connection->getLastPostDate()|date_format:"%d.%m.%Y %H:%M"}</a> von {$connection->getLastPostAuthor()}: <a href="viewthread.php?boardid={$subboard->getBoardID()|escape:url}&amp;threadid={$connection->getLastPostThreadID()|escape:url}">{$connection->getLastPostSubject()}</a>{/if}</td>
</tr>
{/if}
{/foreach}
</tbody>
</table>

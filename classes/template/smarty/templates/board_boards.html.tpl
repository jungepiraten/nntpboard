<table class="maintable">
<thead>
<tr>
 <th colspan=2>{if isset($boardid)}<a class="name" href="viewboard.php?boardid={$boardid}" title="{$subboard.desc|escape:html}">{$name}</a>{else}{$name}{/if}</th>
 <th>Themen</th>
 <th>Beitr&auml;ge</th>
 <th>Letzte Antwort</th>
</tr>
</thead>
<tbody>
{foreach from=$boards item=board name=counter}
  <tr class="boardentry {cycle values='even,odd'} {if $smarty.foreach.counter.first}first{/if}">
  <td class="icon"><img src="images/flagge{if $board.unread}_unread{/if}.png" /></td>
  <td class="title">
  <a class="name" href="viewboard.php?boardid={$board.boardid}" title="{$subboard.desc|escape:html}">{$board.name|escape:html}</a><br>
  <span class="titlebeschr">{$board.desc}</span>
   </td>
   <td class="threadcount">
  {if isset($board.threadcount)}
  <span class="threadcount">{$board.threadcount|escape:html}</span>
  {else}
  <span> - </span>
  {/if}
  </td>
   <td class="postcount">
  {if isset($board.messagecount)}
  <span class="messagecount">{$board.messagecount|escape:html}</span>
  {else}
  <span> - </span>
  {/if}
  </td>
  <td class="lastpost">
  {if isset($board.lastpostmessageid)}
  <a class="subject" href="viewthread.php?boardid={$board.lastpostboardid|escape:url}&amp;threadid={$board.lastpostthreadid|escape:url}">{$board.lastpostsubject|escape:html}</a>
  <span class="messageinfo">von </span><span class="author">{include file=address.html.tpl address=$board.lastpostauthor}</span><br>
  <span class="messageinfo">am </span><a class="date" href="viewthread.php?boardid={$board.lastpostboardid|escape:url}&amp;messageid={$board.lastpostmessageid|escape:url}">{$board.lastpostdate|date_format:"%d.%m.%Y %H:%M"}</a>

   
  {else}
  <span> - </span>
  {/if}
  </td>
  {if false && !empty($board.childs)}{include file=board_boards.html.tpl subboards=$board.childs}{/if}

  </tr>
{/foreach}
</tbody>
</table>

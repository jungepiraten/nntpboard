{foreach from=$subboards item=board name=counter}
  {if $smarty.foreach.counter.first && $board.parent.name == 'NNTPBoard'}<tr class="boardentryfirst">{else}<tr class="boardentry{cycle values="even,odd"}">{/if}
  {if $board.parent.name != 'NNTPBoard'}<td colspan=2 class="boardseparator">&nbsp;</td><td class="boardiconchild">{else}<td class="boardseparator">&nbsp;</td><td class="boardicon">{/if}
  <img src="images/flagge{if $board.unread}unread{/if}.png">
  </td>
  <td class="boardtitle" {if $board.parent.name == 'NNTPBoard'}colspan=2{/if}>
  <a class="name" href="viewboard.php?boardid={$board.boardid}" title="{$subboard.desc|escape:html}">{$board.name|escape:html}</a><br>
  <span class="titlebeschr">{$board.desc}</span>
   </td>
   <td class="boardposts">
  {if isset($board.threadcount)}
  <span class="threadcount">{$board.threadcount|escape:html}</span>
  {else}
  <span> - </span>
  {/if}
  </td>
  <td class="boardposter">
  {if isset($board.lastpostmessageid)}
  <a class="subject" href="viewthread.php?boardid={$board.boardid|escape:url}&amp;threadid={$board.lastpostthreadid|escape:url}">{$board.lastpostsubject|escape:html}</a>
  <span class="messageinfo">von </span><span class="author">{$board.lastpostauthor|escape:html}</span><br>
  <span class="messageinfo">am </span><a class="date" href="viewthread.php?boardid={$board.boardid|escape:url}&amp;messageid={$board.lastpostmessageid|escape:url}">{$board.lastpostdate|date_format:"%d.%m.%Y %H:%M"}</a>

   
  {else}
  <span> - </span>
  {/if}
  </td>
  {if !empty($board.childs)}{include file=board_boards.html.tpl subboards=$board.childs}{/if}

  </tr>
{/foreach}

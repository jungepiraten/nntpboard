{include file=header.html.tpl title=$board.name}

{include file=board_breadcrumb.html.tpl board=$board}

{if !empty($board.desc)}<p class="desc">{$board.desc}</p>{/if}
{if isset($board.childs)}
{include file=board_boards.html.tpl name="Foren" boards=$board.childs}
{foreach from=$board.childs item=child}
{if isset($child.childs)}
{include file=board_boards.html.tpl boardid=$child.boardid name=$child.name boards=$child.childs}
{/if}
{/foreach}
{/if}

{if ($mayPost)}<a href="post.php?boardid={$board.boardid}" class="newthread">Neuer Thread</a>{/if}

{if isset($threads) && !empty($threads)}
<div class="page">
{if $pages > 1}
Seite {$page+1} von {$pages} &bull; 
{/if}
{section name=page start=0 loop=$pages}
{assign var=p value=$smarty.section.page.index}
{if $page!=$p}<a href="viewboard.php?boardid={$board.boardid}&amp;page={$p}" class="pagenumber">{else}<span class="selected-page">{/if}{$p+1}{if $page!=$p}</a>{else}</span>{/if}
{/section}
</div>


<table class="maintable">
<thead>
<tr>
 <th colspan=3 style="padding-left:10px;">Thema</th>
 <th>Posts</th>
 <th>Geschrieben</th>
 <th>Letzte Antwort</th>
</tr>
</thead>
<tbody>
{foreach from=$threads item=thread name=counter}
 <tr class="boardentry{cycle values="even,odd"} {if $smarty.foreach.counter.first}boardentryfirst{/if}">
 <td class="boardseparator">&nbsp;</td><td class="boardicon"><img src="images/flagge{if $thread.unread}_unread{/if}.png"></td>
 <td class="boardtitle">
  <a class="subject" href="viewthread.php?boardid={$board.boardid|escape:url}&amp;threadid={$thread.threadid|escape:url}">{$thread.subject}</a>
  </td>
  <td class="boardposts">
  <span class="posts">{$thread.posts}</span>
  </td>
  <td class="boardposter">
  <span class="messageinfo">von </span><span class="author">{$thread.author}</span>
  <br><span class="messageinfo">am </span><span class="date">{$thread.date|date_format:"%d.%m.%Y %H:%M"}</span>
  </td>
  <td class="boardposter">
  <span class="messageinfo">von </span><span class="author">{$thread.lastpostauthor}</span>
  <br><span class="messageinfo">am </span><a class="date" href="viewthread.php?boardid={$board.boardid}&amp;messageid={$thread.lastpostmessageid}">{$thread.lastpostdate|date_format:"%d.%m.%Y %H:%M"}</a>
  </td>
 </tr>
{/foreach}
</tbody>
</table>

<div class="page">
Seite {$page+1} von {$pages} &bull; 
{section name=page start=0 loop=$pages}
{assign var=p value=$smarty.section.page.index}
{if $page!=$p}<a href="viewboard.php?boardid={$board.boardid}&amp;page={$p}" class="pagenumber">{else}<span class="selected-page">{/if}{$p+1}{if $page!=$p}</a>{else}</span>{/if}
{/section}
</div>
{/if}

{if ($mayPost)}<a href="post.php?boardid={$board.boardid}" class="newthread">Neuer Thread</a>{/if}
{include file=footer.html.tpl}

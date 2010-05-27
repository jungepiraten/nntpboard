{include file=header.html.tpl}

{include file=board_breadcrumb.html.tpl board=$board}

<p class="desc">{$board.desc}</p>
{if isset($board.childs)}
<table class="maintable">
<thead>
<tr>
 <th colspan=4 style="padding-left:10px;">Forum</th>
 <th>Themen</th>
 <th>Letzte Antwort</th>
</tr>
</thead>
<tbody>
{include file=board_boards.html.tpl subboards=$board.childs}
</tbody>
</table>
{/if}

{if ($mayPost)}<a href="post.php?boardid={$board.boardid}" class="newthread">Neuer Thread</a>{/if}

{if isset($threads) && !empty($threads)}
<span class="page">
{if $pages > 1}
Seite {$page+1} von {$pages} &bull; 
{/if}
{section name=page start=0 loop=$pages}
{assign var=p value=$smarty.section.page.index}
{if $page!=$p}<a href="viewboard.php?boardid={$board.boardid}&amp;page={$p}" class="pagenumber">{else}<span class="selected-page">{/if}{$p+1}{if $page!=$p}</a>{else}</span>{/if}
{/section}
</span>


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
{if $smarty.foreach.counter.first}<tr class="boardentryfirst">{else}<tr class="boardentry{cycle values="even,odd"}">{/if}
 <td class="boardseparator">&nbsp;</td><td class="boardicon"><img src="images/flagge.png{if $board.unread}unread{/if}"></td>
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

<span class="page">
Seite X von Y
{section name=page start=0 loop=$pages}
{assign var=p value=$smarty.section.page.index}
{if $page!=$p}<a href="viewboard.php?boardid={$board.boardid}&amp;page={$p}" class="pagenumber">{else}<span class="selected-page">{/if}{$p+1}{if $page!=$p}</a>{else}</span>{/if}
{/section}
</span>
{/if}

{if ($mayPost)}<a href="post.php?boardid={$board.boardid}" class="newthread">Neuer Thread</a>{/if}
{include file=footer.html.tpl}

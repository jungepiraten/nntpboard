{include file=header.html.tpl title=$board.name}

<ul class="breadcrumb">{include file=board_breadcrumb.html.tpl board=$board}</ul>

{if !empty($board.desc)}<p class="well">{$board.desc}</p>{/if}

{if isset($board.childs)}
{assign var=restforen value=0}
{capture assign=childboards}
{foreach from=$board.childs item=child}
{if isset($child.childs)}
{include file=board_boards.html.tpl boardid=$child.boardid name=$child.name boards=$child.childs zeigekategorien=true}
{else}{assign var=restforen value=1}
{/if}
{/foreach}
{/capture}
{if $restforen == 1}{include file=board_boards.html.tpl name="Foren" boards=$board.childs}{/if}
{$childboards}
{/if}

{if isset($threads)}
<div class="container-fluid no-padding">
<div class="row-fluid">
	<div class="span6">
		{if $pages > 1}
		{capture assign="baseurl"}viewboard.php?boardid={$board.boardid}&amp;page={/capture}
		{include file="pagination.html.tpl" baseurl=$baseurl page=$page pagecount=$pages}
		{/if}
		&nbsp;
	</div>

	<div class="btn-toolbar span6">
		<div class="btn-group pull-right no-top-bottom-margin">
			<a href="unread.php?markread={$board.boardid}" class="btn"><i class="icon-flag"></i> Forum als gelesen markieren</a>
			{if ($mayPost)}<a href="post.php?boardid={$board.boardid}" class="btn btn-primary"><i class="icon-edit icon-white"></i> Neuer Thread</a>{/if}
		</div>
	</div>
</div>

<table class="row-fluid table table-striped table-bordered">
<thead>
<tr>
 <th class="title" colspan="2">Thema</th>
 <th class="postcount">Posts</th>
 <th class="poster">Geschrieben</th>
 <th class="lastpost">Letzte Antwort</th>
</tr>
</thead>
<tbody>
{foreach from=$threads item=thread name=counter}
 <tr class="boardentry thread {cycle values="even,odd"} {if $smarty.foreach.counter.first}first{/if}">
  <td class="icon"><img src="images/flagge{if $thread.unread}_unread{/if}.png" alt="Es sind {if $thread.unread}ungelesene{else}keine ungelesenen{/if} Posts vorhanden"/></td>
  <td class="title">
  <a class="subject" id="thread{$smarty.foreach.counter.iteration}" href="viewthread.php?boardid={$board.boardid|escape:url}&amp;threadid={$thread.threadid|encodeMessageID|escape:url}">{$thread.subject}</a>
  </td>
  <td class="postcount">
  <span class="posts">{$thread.posts}</span>
  </td>
  <td class="poster">
  <span class="info">von </span><span class="author">{include file=address.html.tpl address=$thread.author}</span>
  <br /><span class="info">am </span><span class="date">{$thread.date|date_format:"%d.%m.%Y %H:%M"}</span>
  </td>
  <td class="lastpost">
  <span class="info">von </span><span class="author">{include file=address.html.tpl address=$thread.lastpostauthor}</span>
  <br /><span class="info">am </span><a class="date" href="viewthread.php?boardid={$board.boardid|escape:url}&amp;messageid={$thread.lastpostmessageid|encodeMessageID|escape:url}">{$thread.lastpostdate|date_format:"%d.%m.%Y %H:%M"}</a>
  </td>
 </tr>
{foreachelse}
 <tr class="boardentry boardempty">
  <td colspan="5">Es wurden noch keine Threads verfasst.</td>
 </tr>
{/foreach}
</tbody>
</table>

<div class="row-fluid">
	<div class="span6">
		{if $pages > 1}
		{capture assign="baseurl"}viewboard.php?boardid={$board.boardid}&amp;page={/capture}
		{include file="pagination.html.tpl" baseurl=$baseurl page=$page pagecount=$pages}
		{/if}
		&nbsp;
	</div>

	<div class="btn-toolbar span6">
		<div class="btn-group pull-right">
			<a href="unread.php?markread={$board.boardid}" class="btn"><i class="icon-flag"></i> Forum als gelesen markieren</a>
			{if ($mayPost)}<a href="post.php?boardid={$board.boardid}" class="btn btn-primary"><i class="icon-pencil icon-white"></i> Neuer Thread</a>{/if}
		</div>
	</div>
</div>
</div>
{/if}

{literal}
<script type="text/javascript">
$(function() {
    $('tr.boardentry').css("cursor", "pointer").click(function(e) {
        if (e.which !== 1) return;
        document.location.href = $(this).find('td.title a.subject').attr('href');
    });
});
</script>
{/literal}

{include file=footer.html.tpl}

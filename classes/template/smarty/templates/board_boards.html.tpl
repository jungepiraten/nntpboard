<table class="table table-striped">
<tr>
 <th>{if isset($boardid)}<a class="name" href="viewboard.php?boardid={$boardid|escape:url}" title="{$board.desc|escape:html}">{$name}</a>{else}{$name}{/if}</th>
 <th class="hidden-phone">Themen</th>
 <th class="hidden-phone">Beitr&auml;ge</th>
 <th>Letzte Antwort</th>
</tr>
{foreach from=$boards item=board name=counter}
{if $zeigekategorien || (isset($board.hasthreads) && $board.hasthreads)}
 <tr class="clickable">
  <td>
  <a class="name" id="board{if isset($boardid)}{$boardid}{/if}.{$smarty.foreach.counter.iteration}" href="viewboard.php?boardid={$board.boardid|escape:url}" title="{$board.desc|escape:html}">{$board.name|escape:html}</a><br>
    {if $board.unread}<a href="unread.php?markread={$board.boardid|escape:url}"><i class="icon-comments"></i></a>{/if}
    <span>{$board.desc}</span>
   </td>
   <td class="span1 hidden-phone">
  {if isset($board.threadcount)}
  {$board.threadcount|escape:html}
  {else}
  <span> - </span>
  {/if}
  </td>
   <td class="hidden-phone span1">
  {if isset($board.messagecount)}
  {$board.messagecount|escape:html}
  {else}
  <span> - </span>
  {/if}
  </td>
  <td class="span4">
  {if isset($board.lastpostmessageid)}
  <a class="subject" href="viewthread.php?boardid={$board.lastpostboardid|escape:url}&amp;threadid={$board.lastpostthreadid|encodeMessageID|escape:url}">{$board.lastpostsubject|escape:html}</a>
  <span class="info">von </span><span class="author">{include file="address.html.tpl" address=$board.lastpostauthor}</span><br>
  <span class="info">am </span><a class="date" href="viewthread.php?boardid={$board.lastpostboardid|escape:url}&amp;messageid={$board.lastpostmessageid|encodeMessageID|escape:url}">{$board.lastpostdate|date_format:"%d.%m.%Y %H:%M"}</a>
  {else}
  <span> - </span>
  {/if}
  </td>
  </tr>
{/if}
{/foreach}
</table>

{literal}
<script type="text/javascript">
$(function() {
    $('tr.clickable').css("cursor","pointer").click(function(e) {
        if (e.which !== 1) return;
        document.location.href = $(this).find('a.name').attr('href');
    });
});
</script>
{/literal}

{include file="header.html.tpl" title="Suchergebnisse"}

<p class="well">{$term|escape:html}</p>

{if $pages > 1}
{capture assign="baseurl"}search.php?term={$term|escape:url}&amp;page={/capture}
{include file="pagination.html.tpl" baseurl=$baseurl page=$page pagecount=$pages}
{/if}

<table class="table table-striped">
<tr>
 <th class="board">Forum</th>
 <th class="subject">Betreff</th>
 <th class="poster hidden-phone">Geschrieben</th>
</tr>
{foreach from=$results item=result name=counter}
 <tr class="resultentry">
  <td class="board"><a class="board" href="viewboard.php?boardid={$result.board.boardid|escape:url}">{$result.board.name}</a></td>
  <td class="subject"><a class="subject" href="viewthread.php?boardid={$result.board.boardid|escape:url}&amp;messageid={$result.message.messageid|encodeMessageID|escape:url}">{$result.message.subject|escape:html}</a></td>
  <td class="poster hidden-phone">
  <span class="info">von </span><span class="author">{include file="address.html.tpl" address=$result.message.author}</span>
  <br /><span class="info">am </span><span class="date">{$result.message.date|date_format:"%d.%m.%Y %H:%M"}</span>
  </td>
 </tr>
{foreachelse}
 <tr class="">
  <td colspan="5">Keine Treffer.</td>
 </tr>
{/foreach}
</table>

{if $pages > 1}
{capture assign="baseurl"}viewboard.php?boardid={$board.boardid}&amp;page={/capture}
{include file="pagination.html.tpl" baseurl=$baseurl page=$page pagecount=$pages}
{/if}

{literal}
<script type="text/javascript">
$(function() {
    $('tr.resultentry').css("cursor", "pointer").click(function(e) {
        if (e.which !== 1) return;
        document.location.href = $(this).find('td.subject a.subject').attr('href');
    });
});
</script>
{/literal}

{include file=footer.html.tpl}

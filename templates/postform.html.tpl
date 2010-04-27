{include file=header.html.tpl}
<h1>{$board->getName()}</h1>

{include file=board_breadcrumb.html.tpl board=$board}

<form action="post.php" method="post">
 <fieldset>
  <input type="hidden" name="boardid" value="{$board->getBoardID()|escape:html}" />
  <input type="hidden" name="reference" value="{$reference|escape:html}" />

  <label for="subject">Betreff:</label>
  <input type="text" name="subject" value="{if isset($smarty.request.subject)}{$smarty.request.subject|escape:html}{else}{$subject|escape:html}{/if}" />
  <label for="body">Text:</label>
  <textarea name="body" rows="15" cols="70">{if isset($smarty.request.body)}{$smarty.request.body|escape:html}{else}{$body|escape:html}{/if}</textarea>

  <input type="submit" name="post" />
 </fieldset>
</form>
{include file=footer.html.tpl}

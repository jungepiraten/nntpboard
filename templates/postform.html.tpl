{include file=header.html.tpl}
<h1>{$board->getName()}</h1>

{include file=board_breadcrumb.html.tpl board=$board}

<form action="post.php" method="post" accept-charset="{$CHARSET}" class="post">
 <fieldset>
  <input type="hidden" name="charset" value="{$CHARSET}" />
  <input type="hidden" name="boardid" value="{$board->getBoardID()|escape:html}" />
  <input type="hidden" name="reference" value="{$reference|escape:html}" />

  <label for="user" class="user">Benutzer:</label> <span class="user">{$auth->getAddress()|escape:html}</span>
  <label for="subject" class="subject">Betreff:</label>
  <input type="text" name="subject" class="subject" value="{if isset($smarty.request.subject)}{$smarty.request.subject|escape:html}{else}{$subject|escape:html}{/if}" />
  <label for="body" class="body">Text:</label>
  <textarea name="body" class="body">{if isset($smarty.request.body)}{$smarty.request.body|escape:html}{else}{$body|escape:html}{/if}</textarea>

  <input type="submit" name="post" class="submit" value="Schreiben" />
 </fieldset>
</form>
{include file=footer.html.tpl}

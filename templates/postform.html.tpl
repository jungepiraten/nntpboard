{include file=header.html.tpl}
<h1>{$board.name}</h1>

<ul class="breadcrumb">{include file=board_breadcrumb.html.tpl board=$board}</ul>

<form action="post.php" method="post" accept-charset="{$CHARSET}" class="post">
 <fieldset>
  <input type="hidden" name="charset" value="{$CHARSET}" />
  <input type="hidden" name="boardid" value="{$board.boardid|escape:html}" />
  <input type="hidden" name="reference" value="{$reference|escape:html}" />

  <label for="user" class="user">Benutzer:</label>
  {if isset($address)}
   <span class="user">{$address|escape:html}</span>
  {else}
   <input type="text" class="user" name="user" value="{if isset($smarty.request.user)}{$smarty.request.user|escape:html}{else}{$user|escape:html}{/if}" />
  <label for="user" class="user">E-Mail:</label>
   <input type="text" class="email" name="email" value="{if isset($smarty.request.email)}{$smarty.request.email|escape:html}{else}{$email|escape:html}{/if}" />
  {/if}
  <label for="subject" class="subject">Betreff:</label>
  <input type="text" name="subject" class="subject" value="{if isset($smarty.request.subject)}{$smarty.request.subject|escape:html}{else}{$subject|escape:html}{/if}" />
  <label for="body" class="body">Text:</label>
  <textarea name="body" class="body">{if isset($smarty.request.body)}{$smarty.request.body|escape:html}{else}{$body|escape:html}{/if}</textarea>

  <input type="submit" name="post" class="submit" value="Schreiben" />
 </fieldset>
</form>
{include file=footer.html.tpl}

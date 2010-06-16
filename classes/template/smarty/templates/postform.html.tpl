{include file=header.html.tpl title=$board.name}

{include file=board_breadcrumb.html.tpl board=$board}

{literal}
<script type="text/javascript">
<!--

function toggleSignature(id) {
	if (document.getElementById("signature" + id).style.display == "none") {
		document.getElementById("signaturelink" + id).innerHTML = "Signatur verstecken";
		document.getElementById("signature" + id).style.display = "block";
	} else {
		document.getElementById("signaturelink" + id).innerHTML = "Signatur anzeigen";
		document.getElementById("signature" + id).style.display = "none";
	}
}

//-->
</script>
{/literal}

{if isset($preview)}
{include file=message.html.tpl message=$preview first=1 id="post"}
{/if}

<form action="post.php" method="post" accept-charset="{$CHARSET}" class="post">
  <input type="hidden" name="charset" value="{$CHARSET}" />
  <input type="hidden" name="boardid" value="{$board.boardid|escape:html}" />
  <input type="hidden" name="reference" value="{$reference|escape:html}" />
<table class="posttable">
	<tr>
		<td>  <label for="user">Benutzer:</label></td>
  {if isset($address)}
   <td><span>{include file=address.html.tpl address=$address}</span></td>
	</tr>
  {else}
   <td><input type="text" name="user" size="30" value="{if isset($smarty.request.user)}{$smarty.request.user|stripslashes|escape:html}{else}{$user|escape:html}{/if}" />
 		</td><td>
   <label for="user" style="float:right">E-Mail:</label></td>
   <td style="width:180px"><input type="text" name="email" size ="30" style="float:right" value="{if isset($smarty.request.email)}{$smarty.request.email|stripslashes|escape:html}{else}{$email|escape:html}{/if}" />
  </td></tr>
  {/if}
  <tr><td>
  <label for="subject">Betreff:</label></td>
  <td colspan=3><input type="text" name="subject" size="80" value="{if isset($smarty.request.subject)}{$smarty.request.subject|stripslashes|escape:html}{else}{$subject|escape:html}{/if}" />
  </td></tr>
  <tr><td style="vertical-align:top;"><label for="body" >Text:</label></td>
  <td colspan=3><textarea name="body" cols="80" rows="20">{if isset($smarty.request.body)}{$smarty.request.body|stripslashes|escape:html}{else}{$body|escape:html}{/if}</textarea>
  </td></tr>
  <tr><td></td><td><input type="submit" name="post" value="Schreiben" /><input type="submit" name="preview" value="Vorschau" /></td></tr>
</table>
</form>

{include file=footer.html.tpl}

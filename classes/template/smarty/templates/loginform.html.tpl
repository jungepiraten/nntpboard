{include file=header.html.tpl}

{if $loginfailed}
<p class="error">Login fehlgeschlagen!</p>
{/if}

<form action="login.php" method="post" class="login">
 <fieldset>
  <input type="hidden" name="redirect" value="{$referer|escape:html}" />
  <label for="username" class="username">Benutzername:</label>
  <input type="text" class="username" name="username" value="{if isset($smarty.request.username)}{$smarty.request.username|stripslashes|escape:html}{/if}" />
  <label for="password" class="password">Passwort:</label>
  <input type="password" class="password" name="password" id="password"/>
  <input type="checkbox" class="permanent" name="permanent" id="permanent"/> <label for="permanent">Dauerhaft anmelden?</label>

  <input type="submit" class="submit" name="login" value="Login" />
 </fieldset>
</form>
{include file=footer.html.tpl}

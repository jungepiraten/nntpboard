{include file=header.html.tpl}
<h1>Login</h1>

{if $loginfailed}
<p class="error">Login fehlgeschlagen!</p>
{/if}

<form action="userpanel.php" method="post">
 <fieldset>
  <label for="username">Benutzername:</label>
  <input type="text" name="username" value="{if isset($smarty.request.username)}{$smarty.request.username|escape:html}{/if}" />
  <label for="password">Passwort:</label>
  <input type="password" name="password" />

  <input type="submit" name="login" />
 </fieldset>
</form>
{include file=footer.html.tpl}

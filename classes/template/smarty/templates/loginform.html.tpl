{include file=header.html.tpl}

{if $loginfailed}
<p class="error">Login fehlgeschlagen!</p>
{/if}

<form action="login.php" method="post" class="login" class="form-horizontal">
	<fieldset class="control-group">
		<input type="hidden" name="redirect" value="{$referer|escape:html}" />
		
		<label for="username" class="control-label">Benutzername:</label>
		<p class="controls">
			<input type="text" class="username" name="username" value="{if isset($smarty.request.username)}{$smarty.request.username|stripslashes|escape:html}{/if}" />
		</p>

		<label for="password" class="control-label">Passwort:</label>
		<p class="controls">
			<input type="password" class="password" name="password" id="password"/>
		</p>
		
		<label class="checkbox" for="permanent">
			<input type="checkbox" class="permanent" name="permanent" id="permanent"/> Dauerhaft anmelden?
		</label>

		<div class="form-actions">
			<button type="submit" class="btn btn-primary" name="login" value="1">Anmelden</button>
		</div>
	</fieldset>
</form>
{include file=footer.html.tpl}

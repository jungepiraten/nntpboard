<div class="pagination pagination-left">
	<ul>
		{if $page > 3}
			{section name=pages loop=$pagecount start=0 max=2}
			<li><a href="{$baseurl}{$smarty.section.pages.index}">{$smarty.section.pages.index+1}</a></li>
			{/section}
			<li class="disabled"><a>...</a></li>
			{section name=pages loop=$pagecount start=$page-1 max=1}
			<li><a href="{$baseurl}{$smarty.section.pages.index}">{$smarty.section.pages.index+1}</a></li>
			{/section}
		{else}
			{section name=pages loop=$pagecount start=0 max=$page}
			<li><a href="{$baseurl}{$smarty.section.pages.index}">{$smarty.section.pages.index+1}</a></li>
			{/section}
		{/if}
		<li class="active"><a href="{$baseurl}{$page}">{$page+1}</a></li>
		{if $pagecount-$page > 3}
			{section name=pages loop=$pagecount start=$page+1 max=1}
			<li><a href="{$baseurl}{$smarty.section.pages.index}">{$smarty.section.pages.index+1}</a></li>
			{/section}
			<li class="disabled"><a>...</a></li>
			{section name=pages loop=$pagecount start=$pagecount-2 max=2}
			<li><a href="{$baseurl}{$smarty.section.pages.index}">{$smarty.section.pages.index+1}</a></li>
			{/section}
		{else}
			{section name=pages loop=$pagecount start=$page+1 max=$pagecount-$page-1}
			<li><a href="{$baseurl}{$smarty.section.pages.index}">{$smarty.section.pages.index+1}</a></li>
			{/section}
		{/if}
	</ul>
</div>

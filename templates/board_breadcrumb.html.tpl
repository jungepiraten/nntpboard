{if $board->hasParent()}{include file=board_breadcrumb.html.tpl board=$board->getParent()} &raquo; {/if}<a href="viewboard.php?id={$board->getBoardID()}">{$board->getName()}</a>

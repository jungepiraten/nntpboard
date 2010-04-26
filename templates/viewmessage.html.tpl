{assign var=boardid value=$board->getBoardID()}
{assign var=threadid value=$thread->getThreadID()}
{assign var=articlenum value=$message->getArticleNum()}
{assign var=url value="viewthread.php?boardid=$boardid&threadid=$threadid#article$articlenum"}
{redirect url=$url}

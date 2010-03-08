{assign var=boardid value=$board->getBoardID()}
{assign var=threadid value=$thread->getThreadID()}
{assign var=messageid value=$message->getMessageID()}
{assign var=url value="viewthread.php?boardid=$boardid&threadid=$threadid#message$messageid"}
{redirect url=$url}

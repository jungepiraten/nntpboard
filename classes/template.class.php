<?php

interface Template {
	public function viewexception($exception);

	public function viewboard($board, $group, $threads = null, $mayPost = false);
	public function viewthread($board, $thread, $messages, $mayPost = false);
	public function viewmessage($board, $thread, $message, $mayPost = false);

	public function viewpostform($board, $reference = null);
	public function viewpostsuccess($board, $thread, $message);
	public function viewpostmoderated($board, $thread, $message);

	public function viewloginform();
	public function viewloginfailed();
	public function viewloginsuccess($auth);
	public function viewlogoutsuccess();
	public function viewuserpanel();
}

?>

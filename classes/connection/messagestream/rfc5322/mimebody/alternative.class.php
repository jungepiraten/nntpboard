<?php

require_once(dirname(__FILE__) . "/../mimebody.class.php");

class RFC5322AlternativeMimeBody extends RFC5322MimeBody {
	public function getBodyPart($mimetype, $charset = "UTF-8") {
		if (!is_array($mimetype)) {
			$mimetype = array($mimetype);
		}
		$text = "";
		foreach ($this->getParts() as $part) {
			if ($part instanceof RFC5322MimeBody) {
				$p = $part->getBodyPart($mimetype, $charset);
				if ($p != null && trim($p) != "") {
					$text = $p;
				}
			} else if (in_array($part->getMimeType(), $mimetype)) {
				$text = $part->getBody($charset);
			}
		}
		return $text;
	}

	public function getAttachmentParts() {
		$attachments = array();
		foreach ($this->getParts() as $part) {
			if ($part instanceof RFC5322MimeBody) {
				$attachments = array_merge($attachments, $part->getAttachmentParts());
			}
		}
		return $attachments;
	}
}

?>

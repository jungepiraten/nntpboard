<?php

require_once(dirname(__FILE__) . "/../mimebody.class.php");

class RFC5322MixedMimeBody extends RFC5322MimeBody {
	public function getBodyPart($mimetype, $charset = "UTF-8") {
		if (!is_array($mimetype)) {
			$mimetype = array($mimetype);
		}
		$text = "";
		foreach ($this->getParts() as $part) {
			if ($part instanceof RFC5322MimeBody) {
				$text .= $part->getBodyPart($mimetype, $charset);
			} else if (in_array($part->getMimeType(), $mimetype)) {
				$text .= $part->getBody($charset);
			}
		}
		return $text;
	}

	public function getAttachmentParts() {
		$attachments = array();
		foreach ($this->getParts() as $part) {
			if ($part instanceof RFC5322MimeBody) {
				$attachments = array_merge($attachments, $part->getAttachmentParts());
			} else if (strtolower($part->getDisposition()) == "attachment") {
				$attachments[] = $part;
			}
		}
		return $attachments;
	}
}

?>

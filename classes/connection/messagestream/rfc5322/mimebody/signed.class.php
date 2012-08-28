<?php

require_once(dirname(__FILE__) . "/../mimebody.class.php");

class RFC5322SignedMimeBody extends RFC5322MimeBody {
	private function getContentPart() {
		return array_shift(array_slice($this->getParts(),0,1));
	}

	public function getBodyPart($mimetype, $charset = null) {
		if (!is_array($mimetype)) {
			$mimetype = array($mimetype);
		}
		$part = $this->getContentPart();
		if ($part instanceof RFC5322MimeBody) {
			return $part->getBodyPart($mimetype, $charset);
		} else if (in_array($part->getMimeType(), $mimetype)) {
			return $part->getBody($charset);
		}
		return null;
	}

	public function getAttachmentParts() {
		$part = $this->getContentPart();
		if ($part instanceof RFC5322MimeBody) {
			return $this->getContentPart()->getAttachmentParts();
		}
		return null;
	}
}

?>

<?php

/**
 * RFC 2387
 **/

require_once(dirname(__FILE__) . "/../mimebody.class.php");

class RFC5322RelatedMimeBody extends RFC5322MimeBody {
	private function getRootPart() {
		if ($this->getHeader()->get("Content-Type")->hasExtra("start")) {
			foreach ($this->getParts() AS $part) {
				if ($part->getHeader()->has("Content-ID") && $part->getHeader()->get("Content-ID")->getValue() == $this->getHeader->get("Content-Type")->getExtra("start"))
				{
					return $part;
				}
			}
		}
		$parts = $this->getParts();
		return reset($parts);
	}

	public function getBodyPart($mimetype, $charset = "UTF-8") {
		if (!is_array($mimetype)) {
			$mimetype = array($mimetype);
		}
		$part = $this->getRootPart();
		if ($part == null) {
			return null;
		}

		if ($part instanceof RFC5322MimeBody) {
			return $part->getBodyPart($mimetype, $charset);
		} else if (in_array($part->getMimeType(), $mimetype)) {
			return $part->getBody($charset);
		}
		return null;
	}

	public function getAttachmentParts() {
		$attachments = array();
		$rootpart = $this->getRootPart();
		foreach ($this->getParts() AS $part) {
			// Der Hauptteil ist kein Anhang ;)
			if ($part == $rootpart) {
				continue;
			}
			if ($part instanceof RFC5322MimeBody) {
				$attachments = array_merge($attachments, $part->getAttachmentParts());
			} else {
				$attachments[] = $part;
			}
		}
		return $attachments;
	}
}

?>

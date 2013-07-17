<?php

require_once(dirname(__FILE__)."/../auth.class.php");

class AnonAuth extends AbstractAuth {
	public function isAnonymous() {
		return true;
	}

        public function getUsername() {
                return null;
        }

        public function getPassword() {
                return null;
        }

        public function getAddress() {
                return null;
        }

        public function mayCancel($message) {
                return false;
        }

        public function getNNTPUsername() {
                return null;
        }

        public function getNNTPPassword() {
                return null;
        }
}

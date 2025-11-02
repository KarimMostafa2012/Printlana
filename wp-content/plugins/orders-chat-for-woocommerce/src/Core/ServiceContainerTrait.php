<?php namespace U2Code\OrderMessenger\Core;

trait ServiceContainerTrait {

	public function getContainer() {
		return ServiceContainer::getInstance();
	}

}

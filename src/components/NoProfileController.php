<?php

namespace santilin\churros\components;

class NoProfileController extends \Da\User\Controller\SettingsController
{
    public function actionProfile()
    {
		$this->layout = "/login";
		return parent::actionAccount();
	}
    public function actionAccount()
    {
		$this->layout = "/login";
		return parent::actionAccount();
	}
}

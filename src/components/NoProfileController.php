<?php

namespace santilin\churros\components;

class NoProfileController extends \Da\User\Controller\SettingsController
{
    public function actionProfile()
    {
		return $this->actionAccount();
	}
}

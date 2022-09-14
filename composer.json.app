{
	"name": "santilin/yii2base",
	"description": "Santilin Basic Project Template",
	"keywords": ["yii2", "framework", "basic", "project template"],
	"homepage": "http://www.yiiframework.com/",
	"type": "project",
	"license": "BSD-3-Clause",
	"support": {
		"issues": "https://github.com/yiisoft/yii2/issues?state=open",
		"forum": "http://www.yiiframework.com/forum/",
		"wiki": "http://www.yiiframework.com/wiki/",
		"irc": "irc://irc.freenode.net/yii",
		"source": "https://github.com/yiisoft/yii2"
	},
	"minimum-stability": "dev",
	"require": {
		"php": ">=7.0",
		"yiisoft/yii2": "~2.0.14",
		"yiisoft/yii2-bootstrap4": "~2.0.0",
		"kartik-v/yii2-widgets": "@dev",
		"kartik-v/yii2-grid": "@dev",
		"kartik-v/yii2-widget-datepicker": "@dev",
		"kartik-v/yii2-widget-datetimepicker": "@dev",
		"kartik-v/yii2-datecontrol": "dev-master",
		"kartik-v/yii2-date-range": "@dev",
		"kartik-v/yii2-widget-fileinput": "@dev",
		"kartik-v/yii2-mpdf": "dev-master",
		"kartik-v/yii2-bootstrap4-dropdown": "@dev",
		"santilin/yii2-sqlite3-full-support": "@dev",
		"santilin/yii2-churros": "dev-develop",
		"2amigos/yii2-usuario": "dev-master",
		"2amigos/yii2-tinymce-widget": "~1.1",
		"2amigos/yii2-type-ahead-widget": "~2.0",
		"2amigos/yii2-selectize-widget": "^1.1",
		"kdn/yii2-json-editor": "*",
		"tuyakhov/yii2-json-api": "*",
		"yiisoft/yii2-jui": "^2.0@dev",
		"globalcitizen/php-iban": "dev-master",
		"aki/yii2-bot-telegram": "*",
		"symfony/mailer": "5.4.x-dev",
		"synamen/yii2-tabler-theme": "~1.0",
		"rmrevin/yii2-fontawesome": "~3.0",
		"horat1us/yii2-uuid-behavior":"^1.0"
	},
	"require-dev": {
		"yiisoft/yii2-debug": "~2.1.0",
		"yiisoft/yii2-faker": "~2.0.0",
        "codeception/codeception": "^4.0",
        "codeception/verify": "~0.5.0 || ~1.1.0",
        "codeception/specify": "~0.4.6",
        "symfony/browser-kit": ">=2.7 <=4.2.4",
        "codeception/module-filesystem": "^1.0.0",
        "codeception/module-yii2": "^1.0.0",
        "codeception/module-asserts": "^1.0.0",
		"justinrainbow/json-schema": "5.x-dev",
		"codeception/module-webdriver": "2.0.x-dev",
		"codeception/module-db": "2.x-dev"
	},
	"config": {
		"check-platform": false,
		"process-timeout": 1800,
		"fxp-asset":{
			"installer-paths": {
				"npm-asset-library": "vendor/npm",
				"bower-asset-library": "vendor/bower"
			}
		},
		"allow-plugins": {
			"yiisoft/yii2-composer": true
		},
        "preferred-install": {
            "santilin/*": "source",
            "2amigos/yii2-usuario": "source"
		}
	},
	"scripts": {
		"post-install-cmd": [
			"yii\\composer\\Installer::postInstall"
		],
		"post-create-project-cmd": [
			"yii\\composer\\Installer::postCreateProject",
			"yii\\composer\\Installer::postInstall"
		]
	},
	"extra": {
		"yii\\composer\\Installer::postCreateProject": {
			"setPermission": [
				{
					"runtime": "0777",
					"web/assets": "0777",
					"yii": "0755"
				}
			]
		},
		"yii\\composer\\Installer::postInstall": {
			"generateCookieValidationKey": [
				"config/web.php"
			],
			"setPermission": [
				{
					"runtime": "0777",
					"web/assets": "0777",
					"yii": "0755"
				}
			]
		},
		"asset-installer-paths": {
			"npm-asset-library": "vendor/npm",
			"bower-asset-library": "vendor/bower"
		}
	},
	"repositories": [
		{
			"type": "composer",
			"url": "https://asset-packagist.org"
		},
		{
			"type": "vcs",
			"url": "git@github.com:santilin/yii2-churros"
		},
		{
			"type": "vcs",
			"url": "git@github.com:santilin/yii2-usuario"
		},
		{
			"type": "vcs",
			"url": "git@github.com:santilin/yii2-sqlite3-full-support"
		}
	]
}

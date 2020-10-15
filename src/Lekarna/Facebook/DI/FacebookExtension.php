<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Lekarna\Facebook\DI;

use Lekarna\Facebook\Api\CurlClient;
use Lekarna\Facebook\Configuration;
use Nette;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\Validators;
use Nette\DI\Extensions\InjectExtension;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class FacebookExtension extends Nette\DI\CompilerExtension
{

	/**
	 * {@inheritdoc}
	 */
	public function getConfigSchema(): Schema
	{
		return Expect::array([
			'appId' => NULL,
			'appSecret' => NULL,
			'verifyApiCalls' => TRUE,
			'fileUploadSupport' => FALSE,
			'trustForwarded' => FALSE,
			'clearAllWithLogout' => TRUE,
			'domains' => [],
			'permissions' => [],
			'canvasBaseUrl' => NULL,
			'graphVersion' => '',
			'curlOptions' => CurlClient::$defaultCurlOptions,
			'debugger' => '%debugMode%',
			'configurationClass' => Configuration::class
		]);
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$config = $this->getConfig();
		Validators::assert($config['appId'], 'string', 'Application ID');
		Validators::assert($config['appSecret'], 'string:32', 'Application secret');
		Validators::assert($config['fileUploadSupport'], 'bool', 'file upload support');
		Validators::assert($config['trustForwarded'], 'bool', 'trust forwarded');
		Validators::assert($config['clearAllWithLogout'], 'bool', 'clear the facebook session when user changes');
		Validators::assert($config['domains'], 'array', 'api domains');
		Validators::assert($config['permissions'], 'list', 'permissions scope');
		Validators::assert($config['canvasBaseUrl'], 'null|url', 'base url for canvas application');

		$configurator = $builder->addDefinition($this->prefix('config'))
			->setClass($config['configurationClass'])
			->setArguments([$config['appId'], $config['appSecret']])
			->addSetup('$verifyApiCalls', [$config['verifyApiCalls']])
			->addSetup('$fileUploadSupport', [$config['fileUploadSupport']])
			->addSetup('$trustForwarded', [$config['trustForwarded']])
			->addSetup('$permissions', [$config['permissions']])
			->addSetup('$canvasBaseUrl', [$config['canvasBaseUrl']])
			->addSetup('$graphVersion', [$config['graphVersion']])
			->addTag(InjectExtension::TAG_INJECT, true);

		$configurator->addSetup('loadConfiguration');

		if ($config['domains']) {
			$configurator->addSetup('$service->domains = ? + $service->domains', [$config['domains']]);
		}

		$builder->addDefinition($this->prefix('session'))
			->setClass('Lekarna\Facebook\SessionStorage')
			->addTag(InjectExtension::TAG_INJECT, true);

		foreach ($config['curlOptions'] as $option => $value) {
			if (defined($option)) {
				unset($config['curlOptions'][$option]);
				$config['curlOptions'][constant($option)] = $value;
			}
		}

		$apiClient = $builder->addDefinition($this->prefix('apiClient'))
			->setFactory('Lekarna\Facebook\Api\CurlClient')
			->setClass('Lekarna\Facebook\ApiClient')
			->addSetup('$service->curlOptions = ?;', [$config['curlOptions']])
			->addTag(InjectExtension::TAG_INJECT, true);

		if ($config['debugger']) {
			$builder->addDefinition($this->prefix('panel'))
				->setClass('Lekarna\Facebook\Diagnostics\Panel')
				->addTag(InjectExtension::TAG_INJECT, true);

			$apiClient->addSetup($this->prefix('@panel') . '::register', ['@self']);
		}

		$builder->addDefinition($this->prefix('client'))
			->setClass('Lekarna\Facebook\Facebook')
			->addTag(InjectExtension::TAG_INJECT, true);

		if ($config['clearAllWithLogout']) {
			$builder->getDefinition('user')
				->addSetup('$sl = ?; ?->onLoggedOut[] = function () use ($sl) { $sl->getService(?)->clearAll(); }', [
					'@container', '@self', $this->prefix('session')
				]);
		}
	}



	/**
	 * @param \Nette\Configurator $configurator
	 */
	public static function register(Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('facebook', new FacebookExtension());
		};
	}

}

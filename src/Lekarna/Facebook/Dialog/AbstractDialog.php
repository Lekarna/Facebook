<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Lekarna\Facebook\Dialog;

use Lekarna\Facebook;
use Nette\Application\UI\Component;
use Nette\Application\UI\Presenter;
use Nette\Http\UrlScript;
use Nette\Utils\Html;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @property Facebook\Facebook $facebook
 * @method onResponse(AbstractDialog $dialog)
 */
abstract class AbstractDialog extends Component implements Facebook\Dialog
{

	/**
	 * @var array of function(AbstractDialog $dialog)
	 */
	public $onResponse = [];

	/**
	 * @var Facebook\Facebook
	 */
	protected $facebook;

	/**
	 * @var Facebook\Configuration
	 */
	protected $config;

	/**
	 * Display mode in which to render the Dialog.
	 * @var string
	 */
	protected $display;

	/**
	 * @var bool
	 */
	protected $showError;

	/**
	 * @var UrlScript
	 */
	protected $currentUrl;



	/**
	 * @param Facebook\Facebook $facebook
	 */
	public function __construct(Facebook\Facebook $facebook)
	{
		$this->facebook = $facebook;
		$this->config = $facebook->config;
		$this->currentUrl = $facebook->getCurrentUrl();

		$this->monitor(Presenter::class, function () {
			$this->currentUrl = new UrlScript($this->link('//response!'));
		});
	}



	/**
	 * @return Facebook\Facebook
	 */
	public function getFacebook()
	{
		return $this->facebook;
	}



	/**
	 * Facebook get's the url for this handle when redirecting to login dialog.
	 * It automatically calls the onResponse event.
	 * @crossOrigin
	 */
	public function handleResponse()
	{
		$this->onResponse($this);

		if (!empty($this->config->canvasBaseUrl)) {
			$this->presenter->redirectUrl($this->config->canvasBaseUrl);
		}

		$this->presenter->redirect('this', ['state' => NULL, 'code' => NULL]);
	}



	/**
	 * @return array
	 */
	public function getQueryParams()
	{
		$data = [
			'client_id' => $this->facebook->config->appId,
			'redirect_uri' => (string)$this->currentUrl,
			'show_error' => $this->showError
		];

		if ($this->display !== NULL) {
			$data['display'] = $this->display;
		}

		return $data;
	}



	/**
	 * @param string $display
	 * @param bool $showError
	 *
	 * @return string
	 */
	public function getUrl($display = NULL, $showError = FALSE)
	{
		$url = clone $this->currentUrl;

		$this->display = $display;
		$this->showError = $showError;

		$url->appendQuery($this->getQueryParams());
		return (string)$url;
	}



	/**
	 * @throws \Nette\Application\AbortException
	 */
	public function open()
	{
		$this->presenter->redirectUrl($this->getUrl());
	}



	/**
	 * Opens the dialog.
	 */
	public function handleOpen()
	{
		$this->open();
	}



	/**
	 * @param string $display
	 * @param bool $showError
	 * @return Html
	 */
	public function getControl($display = NULL, $showError = FALSE)
	{
		return Html::el('a')->href($this->getUrl($display, $showError));
	}

}

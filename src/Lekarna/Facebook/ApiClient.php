<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace Lekarna\Facebook;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
interface ApiClient
{

	/**
	 * Invoke the old restserver.php endpoint.
	 *
	 * @param array $params Method call object
	 * @throws \Lekarna\Facebook\FacebookApiException
	 * @return mixed The decoded response object
	 */
	function restServer(array $params);

	/**
	 * Invoke the Graph API.
	 *
	 * @param string $path The path (required)
	 * @param string $method The http method (default 'GET')
	 * @param array $params The query/post data
	 * @throws \Lekarna\Facebook\FacebookApiException
	 * @return mixed The decoded response object
	 */
	function graph($path, $method = 'GET', array $params = []);

	/**
	 * Make a OAuth Request.
	 *
	 * @param string $url The path (required)
	 * @param array $params The query/post data
	 *
	 * @return string The decoded response object
	 * @throws \Lekarna\Facebook\FacebookApiException
	 */
	function oauth($url, array $params);

}

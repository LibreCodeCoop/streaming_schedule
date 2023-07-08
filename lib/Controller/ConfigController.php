<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Vitor Mattos <vitor@php.rio>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\StreamingSchedule\Controller;

use OCA\StreamingSchedule\AppInfo\Application;
use OCA\StreamingSchedule\Service\GoogleAPIService;
use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\IL10N;

use OCP\IRequest;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Controller;

class ConfigController extends Controller {

	/** @var string */
	private $userId;

	const YOUTUBE_SCOPE = 'https://www.googleapis.com/auth/youtube';

	public function __construct(string $appName,
								string $userId,
								private IRequest $request,
								private IConfig $config,
								private IURLGenerator $urlGenerator,
								private IL10N $l,
								private GoogleAPIService $googleApiService) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->l = $l;
		$this->googleApiService = $googleApiService;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Receive oauth code and get oauth access token
	 *
	 * @param string $code request code to use when requesting oauth token
	 * @param string $state value that was sent with original GET request. Used to check auth redirection is valid
	 * @param string $scope scopes allowed by user
	 * @param ?string $error
	 * @return RedirectResponse to user settings
	 */
	public function oauthRedirect(string $code = '', string $state = '',  string $scope = '', string $error = ''): RedirectResponse {
		$configState = $this->config->getUserValue($this->userId, Application::APP_ID, 'youtube_oauth_state');
		$clientID = $this->config->getAppValue(Application::APP_ID, 'youtube_client_id');
		$clientSecret = $this->config->getAppValue(Application::APP_ID, 'youtube_client_secret');

		// Store given scopes in space-separated string
		$scopes =  explode(' ', $scope);

		$scopesArray = [
			'can_access_youtube' => in_array(self::YOUTUBE_SCOPE, $scopes) ? 1 : 0,
		];

		$this->config->setUserValue($this->userId, Application::APP_ID, 'user_scopes', json_encode($scopesArray));

		// reset state
		$this->config->setUserValue($this->userId, Application::APP_ID, 'youtube_oauth_state', '');

		if ($clientID && $clientSecret && $configState !== '' && $configState === $state) {
			$result = $this->googleApiService->getToken($code);
			if (isset($result['access_token'], $result['refresh_token'])) {
				$accessToken = $result['access_token'];
				$refreshToken = $result['refresh_token'];
				$this->config->setUserValue($this->userId, Application::APP_ID, 'google_token', $accessToken);
				$this->config->setUserValue($this->userId, Application::APP_ID, 'refresh_token', $refreshToken);
				return new RedirectResponse(
					$this->urlGenerator->linkToRoute('settings.PersonalSettings.index')
				);
			}
			$message = $result['error']
				?? (isset($result['access_token'])
					? $this->l->t('Missing refresh token in Google response.')
					: '');
			$result = $this->l->t('Error getting OAuth access token.') . ' ' . $message;
		} else {
			$result = $this->l->t('Error during OAuth exchanges');
		}
		return new RedirectResponse($this->urlGenerator->linkToRoute('settings.PersonalSettings.index'));
	}
}

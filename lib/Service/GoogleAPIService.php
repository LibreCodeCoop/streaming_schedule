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

namespace OCA\StreamingSchedule\Service;

use Google\Client;
use OCA\StreamingSchedule\AppInfo\Application;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUserSession;

class GoogleAPIService {
	/** @var Client */
	private $client;

	/**
	 * Service to make requests to Google v3 (JSON) API
	 */
	public function __construct (
		private IUserSession $userSession,
		private IConfig $config,
		private IURLGenerator $urlGenerator
	) {
		$this->userSession = $userSession;
		$this->config = $config;
		$this->client = new Client();
		$this->client->setClientId($this->config->getAppValue(Application::APP_ID, 'youtube_client_id'));
		$this->client->setClientSecret($this->config->getAppValue(Application::APP_ID, 'youtube_client_secret'));
		$this->client->setPrompt('consent');
		$this->client->setAccessType('offline');
		$this->client->addScope([
			'https://www.googleapis.com/auth/youtube',
			'https://www.googleapis.com/auth/youtube.force-ssl',
			'https://www.googleapis.com/auth/youtube.readonly',
		]);
	}

	public function getClient(): Client {
		return $this->client;
	}

	public function setUserId($userId): void {
		$this->userId = $userId;
	}

	private function getUserId(): string {
		$currentUser = $this->userSession->getUser();
		if (is_null($currentUser)) {
			return $this->userId;
		}
		return $currentUser->getUID();
	}

	public function getAuthUrl(): string {
		$userState = md5((string) time());
		$this->config->setUserValue($this->getUserId(), Application::APP_ID, 'youtube_oauth_state', $userState);
		$this->client->setState($userState);
		$this->client->setRedirectUri($this->urlGenerator->linkToRouteAbsolute(Application::APP_ID . '.config.oauthRedirect'));
		return $this->client->createAuthUrl();
	}

	public function getToken($code) {
		$this->client->setRedirectUri($this->urlGenerator->linkToRouteAbsolute(Application::APP_ID . '.config.oauthRedirect'));
		return $this->client->fetchAccessTokenWithAuthCode($code);
	}
}

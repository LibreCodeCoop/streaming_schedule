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

namespace OCA\StreamingSchedule\Command;

use OC\Core\Command\Base;
use OCA\StreamingSchedule\AppInfo\Application;
use OCA\StreamingSchedule\Service\GoogleAPIService;
use OCP\IConfig;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigureYouTube extends Base {
	/** @var IConfig */
	private $config;
	/** @var GoogleAPIService */
	private $googleAPIService;

	public function __construct(
		IConfig $config,
		GoogleAPIService $googleAPIService
	) {
		parent::__construct();
		$this->config = $config;
		$this->googleAPIService = $googleAPIService;
	}

	protected function configure() {
		$this
			->setName('streamingscheduling:config-youtube')
			->setDescription('Configure youtube streaming')
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'User to configure API settings'
			)
			->addOption('api-key',
				'k',
				InputOption::VALUE_REQUIRED,
				'YouTube API key',
			)
			->addOption('client-id',
				'i',
				InputOption::VALUE_REQUIRED,
				'Client ID',
			)
			->addOption('client-secret',
				's',
				InputOption::VALUE_REQUIRED,
				'Client Secret',
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if (!$input->getOption('api-key')) {
			throw new InvalidOptionException('Specify an API_KEY with --api-key=YOUR_API_KEY');
		}
		if (!$input->getOption('api-key')) {
			throw new InvalidOptionException('Specify an client ID with --client-id=YOUR_CLIENT_ID');
		}
		if (!$input->getOption('api-key')) {
			throw new InvalidOptionException('Specify an client secret with --client-secret=YOUR_CLIENT_SECRET');
		}
		$this->config->setAppValue(Application::APP_ID, 'youtube_apy_key', $input->getOption('api-key'));
		$this->config->setAppValue(Application::APP_ID, 'youtube_client_id', $input->getOption('client-id'));
		$this->config->setAppValue(Application::APP_ID, 'youtube_client_secret', $input->getOption('client-secret'));

		$this->googleAPIService->setUserId($input->getArgument('user'));

		$output->writeln('<info>Access the follow URL on your browser to get an access token to YouTube API:</info>');
		$output->writeln($this->googleAPIService->getAuthUrl());
		return self::SUCCESS;
	}
}

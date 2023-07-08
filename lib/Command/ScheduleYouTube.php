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

use Google\Http\MediaFileUpload;
use Google\Service\YouTube;
use Google\Service\YouTube\LiveBroadcast;
use Google\Service\YouTube\LiveBroadcastSnippet;
use Google\Service\YouTube\LiveBroadcastStatus;
use OC\Core\Command\Base;
use OCA\StreamingSchedule\AppInfo\Application;
use OCA\StreamingSchedule\Service\GoogleAPIService;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduleYouTube extends Base {
	public function __construct(
		private IConfig $config,
		private GoogleAPIService $googleApiService
	) {
		parent::__construct();
		$this->config = $config;
		$this->googleApiService = $googleApiService;
	}

	protected function configure() {
		$this
			->setName('streamingscheduling:schedule-youtube')
			->setDescription('Schedule a streaming')
			->addArgument(
				'user',
				InputArgument::REQUIRED,
				'User to configure API settings'
			)
			->addOption(
				'title',
				't',
				InputOption::VALUE_REQUIRED,
				'The broadcast\'s title.'
			)
			->addOption(
				'description',
				'd',
				InputOption::VALUE_REQUIRED,
				'The broadcast\'s description.'
			)
			->addOption(
				'start-time',
				's',
				InputOption::VALUE_REQUIRED,
				'The date and time that the broadcast is scheduled to start. The value is specified in ISO 8601 (YYYY-MM-DDThh:mm:ss.sZ) format.'
			)
			->addOption(
				'end-time',
				'e',
				InputOption::VALUE_REQUIRED,
				'The date and time that the broadcast is scheduled to end. The value is specified in ISO 8601 (YYYY-MM-DDThh:mm:ss.sZ) format.'
			)
			->addOption(
				'kids',
				'k',
				InputOption::VALUE_NONE,
				'This value indicates whether the broadcast is designated as child-directed.'
			)
			->addOption(
				'privacy-status',
				'p',
				InputOption::VALUE_REQUIRED,
				'The broadcast\'s privacy status.',
				'private'
			)
			->addOption(
				'thumbnail',
				null,
				InputOption::VALUE_REQUIRED,
				'The broadcast\'s picture.'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$broadcastSnippet = new LiveBroadcastSnippet();
		$broadcastSnippet->setTitle($input->getOption('title'));
		if ($input->getOption('description')) {
			$broadcastSnippet->setDescription($input->getOption('description'));
		}
		$broadcastSnippet->setScheduledStartTime($input->getOption('start-time'));
		if ($input->getOption('end-time')) {
			$broadcastSnippet->setScheduledEndTime($input->getOption('end-time'));
		}

		$status = new LiveBroadcastStatus();
		if ($input->getOption('kids')) {
			$status->setSelfDeclaredMadeForKids(true);
		} else {
			$status->setSelfDeclaredMadeForKids(false);
		}
		$status->setPrivacyStatus($input->getOption('privacy-status'));

		$broadcastInsert = new LiveBroadcast();
		$broadcastInsert->setSnippet($broadcastSnippet);
		$broadcastInsert->setStatus($status);
		$broadcastInsert->setKind('youtube#liveBroadcast');

		$client = $this->googleApiService->getClient();
		$client->setAccessToken($this->config->getUserValue($input->getArgument('user'), Application::APP_ID, 'google_token'));
		$youtube = new YouTube($client);
		$broadcastsResponse = $youtube->liveBroadcasts->insert('snippet,status', $broadcastInsert);

		if ($input->getOption('thumbnail')) {
			$client->setDefer(true);
			$setRequest = $youtube->thumbnails->set($broadcastsResponse->getId());
			$chunkSizeBytes = 1 * 1024 * 1024;
			$media = new MediaFileUpload(
				$client,
				$setRequest,
				'image/png',
				null,
				true,
				$chunkSizeBytes
			);
			$imagePath = $input->getOption('thumbnail');
			$media->setFileSize(filesize($imagePath));
			$handle = fopen($imagePath, 'rb');
			$status = false;
			while (!$status && !feof($handle)) {
				$chunk = fread($handle, $chunkSizeBytes);
				$status = $media->nextChunk($chunk);
			}
			fclose($handle);
			$client->setDefer(false);
		}

		$output->writeln('Scheduled');
		return self::SUCCESS;
	}
}

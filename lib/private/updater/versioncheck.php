<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Steffen Lindner <mail@steffen-lindner.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Updater;

use OC\Hooks\BasicEmitter;
use OC\HTTPHelper;
use OC_Util;
use OCP\IConfig;
use OC\Setup;
use OCP\Util;

class VersionCheck extends BasicEmitter {

	/** @var \OC\HTTPHelper $helper */
	private $httpHelper;
	
	/** @var IConfig */
	private $config;

	/**
	 * @param HTTPHelper $httpHelper
	 * @param IConfig $config
	 */
	public function __construct(HTTPHelper $httpHelper,
								IConfig $config) {
		$this->httpHelper = $httpHelper;
		$this->config = $config;
	}


	/**
	 * Check if a new version is available
	 *
	 * @param string $updaterUrl the url to check, i.e. 'http://apps.owncloud.com/updater.php'
	 * @return array|bool
	 */
	public function check($updaterUrl = null) {

		// Look up the cache - it is invalidated all 30 minutes
		if (((int)$this->config->getAppValue('core', 'lastupdatedat') + 1800) > time()) {
			return json_decode($this->config->getAppValue('core', 'lastupdateResult'), true);
		}

		if (is_null($updaterUrl)) {
			$updaterUrl = 'https://updates.owncloud.com/server/';
		}

		$this->config->setAppValue('core', 'lastupdatedat', time());

		if ($this->config->getAppValue('core', 'installedat', '') === '') {
			$this->config->setAppValue('core', 'installedat', microtime(true));
		}

		$version = Util::getVersion();
		$version['installed'] = $this->config->getAppValue('core', 'installedat');
		$version['updated'] = $this->config->getAppValue('core', 'lastupdatedat');
		$version['updatechannel'] = \OC_Util::getChannel();
		$version['edition'] = \OC_Util::getEditionString();
		$version['build'] = \OC_Util::getBuild();
		$versionString = implode('x', $version);

		//fetch xml data from updater
		$url = $updaterUrl . '?version=' . $versionString;

		$tmp = [];
		$xml = $this->httpHelper->getUrlContent($url);
		if ($xml) {
			$loadEntities = libxml_disable_entity_loader(true);
			$data = @simplexml_load_string($xml);
			libxml_disable_entity_loader($loadEntities);
			if ($data !== false) {
				$tmp['version'] = (string)$data->version;
				$tmp['versionstring'] = (string)$data->versionstring;
				$tmp['url'] = (string)$data->url;
				$tmp['web'] = (string)$data->web;
			} else {
				libxml_clear_errors();
			}
		} else {
			$data = [];
		}

		// Cache the result
		$this->config->setAppValue('core', 'lastupdateResult', json_encode($data));
		return $tmp;
	}
}


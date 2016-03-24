<?php
/**
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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

class Test_ReleaseNotes extends \Test\TestCase {
	protected $prefix = 'ocx_';

	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		$this->expected = [];
		parent::tearDown();
	}

	public function resultProvider82to90(){
		return [
			[ [], false, 20 ],
			[ [], false, 1000000 ],
			[ [], true, 20 ],
			[ [ 
				\OC::$server->getL10N('core')->t(
						"You have an ownCloud installation with over 200.000 files so the upgrade might take a while. Hint: You can speed up the upgrade by executing this SQL command manually: ALTER TABLE %s ADD COLUMN checksum varchar(255) DEFAULT NULL AFTER permissions;",
						['ocx_filecache']
				)
			], true, 1000000 ],
		];
	}

	/**
	 * @dataProvider resultProvider82to90
	 */
	public function test82to90($expected, $isMysql, $fileCount){
		$releaseNotesMock = $this->getReleaseNotesMock($isMysql, $fileCount);
		$actual = $releaseNotesMock->getReleaseNotes('8.2.22', '9.0.1');
		$this->assertEquals($expected, $actual);
	}



	public function resultProvider90to91(){
		return [
			[ [], false, 20 ],
			[ [], false, 1000000 ],
			[ [], true, 20 ],
			[ [], true, 1000000 ],
		];
	}

	/**
	 * @dataProvider resultProvider90to91
	 */
	public function test90to91($expected, $isMysql, $fileCount){
		$releaseNotesMock = $this->getReleaseNotesMock($isMysql, $fileCount);
		$actual = $releaseNotesMock->getReleaseNotes('9.0.1', '9.1.0');
		$this->assertEquals($expected, $actual);
	}


	private function getReleaseNotesMock($isMysql, $fileCount){
		$dbConnectionMock = $this->getMockBuilder('OCP\IDBConnection')
				->setMethods(array_merge($this->getMethods('OCP\IDBConnection'), ['getPrefix']))
				->getMock()
		;
		$dbConnectionMock->expects($this->any())
				->method('getPrefix')
				->willReturn($this->prefix)
		;
		$releaseNotesMock = $this->getMockBuilder('OC\ReleaseNotes')
				->setConstructorArgs([$dbConnectionMock])
				->setMethods(['isMysql', 'countFilecacheEntries'])
				->getMock()
		;

		$releaseNotesMock->expects($this->any())
				->method('isMysql')
				->willReturn($isMysql)
		;
		$releaseNotesMock->expects($this->any())
				->method('countFilecacheEntries')
				->willReturn($fileCount)
		;
		return $releaseNotesMock;
	}
	
	private function getMethods($class){
		$methods = [];
		if (class_exists($class) || interface_exists($class)) {
			$reflector = new ReflectionClass($class);
			foreach ($reflector->getMethods( ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_ABSTRACT ) as $method) {
				$methods[] = $method->getName();
			}
		}
		return $methods;
	}
}

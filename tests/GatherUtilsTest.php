<?php
use PHPUnit\Framework\TestCase;

global $relative_dir;
$relative_dir = '../public/';
require_once "{$relative_dir}/gather_utils.php";

class GatherUtilsTest extends TestCase {
	/**
	 * @dataProvider provide_map_usernames_to_gather_id
	 */
	public function test_map_usernames_to_gather_id($usernames, $ids, $expected) {
		$result = map_usernames_to_gather_id($usernames, $ids);
		$this->assertEquals($expected, $result);
	}

	public function provide_map_usernames_to_gather_id() {
		$map = [
			'Luke' => 12,
			'Leia' => 23,
			'Han' => 34,
			'Chewie' => 45,
		];

		return [
			[[], [], []],
			[['Chewie'], $map, ['Chewie' => 45]],
			[['Luke', 'Leia', 'Han'], $map,
				['Luke' => 12, 'Leia' => 23, 'Han' => 34]],

			// error state where a username doesn't exist... Gather
			// should catch this instead of a blank user ID
			[['foo'], $map, 0],
			[['foo', 'bar', 'quux'], $map, 0],
			[['Luke', 'Leia', 'Han', 'Yoda'], $map, 0],
		];
	}
}


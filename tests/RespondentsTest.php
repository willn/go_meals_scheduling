<?php
use PHPUnit\Framework\TestCase;

global $relative_dir;
$relative_dir = '../public/';
require_once "{$relative_dir}/globals.php";
require_once "{$relative_dir}/classes/respondents.php";

class RespondentsTest extends TestCase {
	protected $respondents;

	public function setUp() : void {
		$this->respondents = new Respondents();
	}

	/**
	 * @dataProvider provideRenderPercentageBar
	 */
	public function testRenderPercentageBar($input, $expected) {
		$result = $this->respondents->renderPercentageBar($input);
		$debug = [
			'input' => $input,
			'expected' => $expected,
			'result' => $result,
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function provideRenderPercentageBar() {
		$example = <<<EOSVG
<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="300px" height="30px">
	<rect x="0" y="0" width="300" height="30" style="fill:#C6D9FD;"/>
	<rect x="0" y="0" width="68.7" height="30" style="fill:#4D89F9;"/>
	<rect x="60" y="0" width="1" height="30" style="fill:#99c;"/><rect x="120" y="0" width="1" height="30" style="fill:#99c;"/><rect x="180" y="0" width="1" height="30" style="fill:#99c;"/><rect x="240" y="0" width="1" height="30" style="fill:#99c;"/><rect x="300" y="0" width="1" height="30" style="fill:#99c;"/>
</svg>
EOSVG;

		return [
			[22.9, $example],
		];
	}
}
?>

<?php
use PHPUnit\Framework\TestCase;

set_include_path('../' . PATH_SEPARATOR . '../public/');

require_once '../public/constants.php';
require_once '../public/config.php';
require_once '../public/season.php';
require_once '../public/classes/worker.php';
require_once '../public/classes/worker_comments.php';
require_once 'testing_utils.php';

class WorkerCommentsTest extends TestCase {
	protected $worker_comments;

	public function setUp() : void {
		$this->worker_comments = new WorkerComments();
	}

	/**
	 * @dataProvider provideRenderWorkerComments
	 */
	public function testRenderWorkerComments($input, $expected) {
		$result = $this->worker_comments->renderWorkerComments($input);
		$result = remove_html_whitespace($result);
		$expected = remove_html_whitespace($expected);
		$debug = [
			'input' => $input,
			'expected' => $expected,
			'result' => $result,
		];
		$this->assertEquals($expected, $result, print_r($debug, TRUE));
	}

	public function provideRenderWorkerComments() {
		$basic_response = <<<EOHTML
<h2 id="worker_comments">Comments</h2>
<h2 id="confirm_checks">Confirm results check</h2>
<div class="confirm_results"></div>
EOHTML;

		$small_data_file = file_get_contents('data/worker_comments.json');
		$small_data = json_decode($small_data_file, TRUE);

		$results_file = RESULTS_FILE;
		$small_output = <<<EOHTML
<h2 id="worker_comments">Comments</h2>
		<fieldset>
			<legend>apples - 2018-12-22 21:39:00</legend>

		</fieldset>		<fieldset>
			<legend>bananas - 2018-12-18 23:19:47</legend>
			<p>avoids: cherries<br>
prefers: apples,donuts,eggplant<br>
<br></p>

		</fieldset>		<fieldset>
			<legend>cherries - 2018-12-18 23:19:04</legend>
			<p>prefers: apples<br>
<br></p>

		</fieldset>		<fieldset>
			<legend>donuts - 2018-12-22 01:47:37</legend>
			<p>avoids: apples<br>
clean_after_self: no<br>
<br></p>

		</fieldset>		<fieldset>
			<legend>eggplant - 2018-12-15 12:33:15</legend>
			<p><br>There is so much to comment on around here!</p>

		</fieldset><h2 id="confirm_checks">Confirm results check</h2>
<div class="confirm_results">echo "-----------";
echo 'bananas' avoid workers 'cherries'
grep 'bananas' {$results_file} | grep 'cherries'
echo "-----------";
echo 'bananas' prefers 'apples'
grep 'bananas' {$results_file} | grep 'apples'
echo "-----------";
echo 'bananas' prefers 'donuts'
grep 'bananas' {$results_file} | grep 'donuts'
echo "-----------";
echo 'bananas' prefers 'eggplant'
grep 'bananas' {$results_file} | grep 'eggplant'
echo "-----------";
echo 'cherries' prefers 'apples'
grep 'cherries' {$results_file} | grep 'apples'
echo "-----------";
echo 'donuts' avoid workers 'apples'
grep 'donuts' {$results_file} | grep 'apples'
echo "-----------";
echo 'donuts' clean after self: 'no'
grep 'donuts.*donuts' {$results_file}</div>
EOHTML;

		return [
			[[], $basic_response],
			[$small_data, $small_output],
		];
	}
}
?>

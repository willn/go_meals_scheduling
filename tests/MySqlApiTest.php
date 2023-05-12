<?php
require '../public/mysql_api.php';
require_once '../public/database_config.php';

use PHPUnit\Framework\TestCase;

class MysqlApiTest extends TestCase {
	private $mysqlApi;

	// Set up any necessary dependencies or configurations
    protected function setUp(): void {
		$hdup = get_hdup();
        $this->mysqlApi = new MysqlApi('localhost', 'unit_test_go_work', 'unit_testing',
			$hdup['unit_test_password']);
    }

    public function testConnectSuccess(): void {
        $result = $this->mysqlApi->connect();
        $this->assertTrue($result);
        $this->assertNotNull($this->mysqlApi->getLink());
    }

    public function testConnectFailure(): void {
        $badMysqlApi = new MysqlApi('localhost', 'database', 'nobody', 'password');
        $result = $badMysqlApi->connect();

        // Assert
        $this->assertFalse($result);
        $this->assertFalse($badMysqlApi->getLink());
    }

    public function testQuery(): void {
        $result = $this->mysqlApi->query('SELECT COUNT(*) FROM auth_user');
        $this->assertInstanceOf(mysqli_result::class, $result);
    }

    public function testGet(): void {
        $result = $this->mysqlApi->get('SELECT * FROM work_app_season');
        $this->assertIsArray($result);
    }
}

class MysqlApiFunctionTest extends TestCase {
    public function testGetMysqlApi(): void {
        // Arrange

        // Act
        $mysqlApi = get_mysql_api();

        // Assert
        $this->assertInstanceOf(MysqlApi::class, $mysqlApi);
    }
}


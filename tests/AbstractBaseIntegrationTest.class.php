<?php
/**
 * @package modules.synchro.tests
 */
abstract class synchro_tests_AbstractBaseIntegrationTest extends synchro_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->loadSQLResource('integration-test.sql', true, false);
	}
}
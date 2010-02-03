<?php
/**
 * @package modules.synchro.tests
 */
abstract class synchro_tests_AbstractBaseFunctionalTest extends synchro_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->loadSQLResource('functional-test.sql', true, false);
	}
}
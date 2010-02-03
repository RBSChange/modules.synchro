<?php
/**
 * @package modules.synchro.tests
 */
abstract class synchro_tests_AbstractBaseUnitTest extends synchro_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->resetDatabase();
	}
}
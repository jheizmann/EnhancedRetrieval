<?php
/**
 * @file
 * @ingroup EnhancedRetrievalTests
 * 
 * @defgroup EnhancedRetrievalTests Enhanced Retrieval unit tests
 * @ingroup EnhancedRetrieval
 * 
 * @author OP
 */

require_once 'testcases/TestER.php';

class ERTests
{
	public static function suite()
	{
		$suite = new PHPUnit_Framework_TestSuite('EnhancedRetrieval');

		$suite->addTestSuite("TestER");
		return $suite;
	}
}

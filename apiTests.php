<?php
require_once __DIR__ . '/api.php';
use PHPUnit\Framework\TestCase;

class apiTests extends TestCase
{
    function setUp(): void
    { }
    /**
     * @group getTransactionOrErrorFromRequestPaymentResponse
     */
    function testReturnsAGenericErrorWhenWTFJustHappened(): void
    {
        $response = getTransactionOrErrorFromRequestPaymentResponse('wtf just happened');
        $this->assertEquals(
            array(
                'error' => $GLOBALS['genericRequestPaymentError']
            ),
            $response
        );
    }
}

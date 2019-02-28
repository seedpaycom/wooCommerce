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
                'errors' => array($GLOBALS['genericRequestPaymentError'])
            ),
            $response
        );
    }
    /**
     * @group getTransactionOrErrorFromRequestPaymentResponse
     */
    function testReturnsAGenericErrorWhenNoResponseGiven(): void
    {
        $response = getTransactionOrErrorFromRequestPaymentResponse(null);
        $this->assertEquals(
            array(
                'errors' => array($GLOBALS['genericRequestPaymentError'])
            ),
            $response
        );
    }
    /**
     * @group getTransactionOrErrorFromRequestPaymentResponse
     */
    function testReturnsAGenericErrorWhenNoStatusCodeIsProvided(): void
    {
        $response = getTransactionOrErrorFromRequestPaymentResponse(array());
        $this->assertEquals(
            array(
                'errors' => array($GLOBALS['genericRequestPaymentError'])
            ),
            $response
        );
    }
    /**
     * @group getTransactionOrErrorFromRequestPaymentResponse
     */
    function testReturnsAGenericErrorWhenTheResponseIsntJson(): void
    {
        $options = array(
            'statusCode' => 200,
            'response' => 'oh noz',
            'error' => ''
        );
        $response = getTransactionOrErrorFromRequestPaymentResponse($options);
        $this->assertEquals(
            array(
                'errors' => array($GLOBALS['genericRequestPaymentError'])
            ),
            $response
        );
    }
    /**
     * @group getTransactionOrErrorFromRequestPaymentResponse
     */
    function testReturnsAGenericErrorWhenGivenAnError(): void
    {
        $options = array(
            'statusCode' => 200,
            'response' => '{}',
            'error' => 'i amz errorz'
        );
        $response = getTransactionOrErrorFromRequestPaymentResponse($options);
        $this->assertEquals(
            array(
                'errors' => array($GLOBALS['genericRequestPaymentError'])
            ),
            $response
        );
    }
    /**
     * @group getTransactionOrErrorFromRequestPaymentResponse
     */
    function testReturnsAcceptedAndPaidStatusWhenPaymentAlreadyReceived(): void
    {
        $options = array(
            'statusCode' => 400,
            'response' => '{
                "errors": [
                    "Payment already received"
                ]
            }',
            'error' => ''
        );
        $response = getTransactionOrErrorFromRequestPaymentResponse($options);
        $this->assertEquals(
            json_decode($options['response']),
            $response
        );
    }
    /**
     * @group getTransactionOrErrorFromRequestPaymentResponse
     */
    function testReturnsGivenMessageAsAMessage(): void
    {
        $options = array(
            'statusCode' => 200,
            'response' => '{
                "message": "Invitation sent to 5038661114"
            }',
            'error' => ''
        );
        $response = getTransactionOrErrorFromRequestPaymentResponse($options);
        $this->assertEquals(
            json_decode($options['response']),
            $response
        );
    }
    /**
     * @group getTransactionOrErrorFromRequestPaymentResponse
     */
    function testReturnsFirstGivenError(): void
    {
        $options = array(
            'statusCode' => 400,
            'response' => '{
                "errors": [
                    "wtf mate?"
                ]
            }',
            'error' => ''
        );
        $response = getTransactionOrErrorFromRequestPaymentResponse($options);
        $this->assertEquals(
            json_decode($options['response']),
            $response
        );
    }
}

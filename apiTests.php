<?php
require_once __DIR__ . '/api.php';
use PHPUnit\Framework\TestCase;

class apiTests extends TestCase
{
    function setUp(): void
    { }
    /**
     * @group getStatusOrErrorFromRequestPaymentResponse
     */
    function testReturnsAGenericErrorWhenWTFJustHappened(): void
    {
        $response = getStatusOrErrorFromRequestPaymentResponse('wtf just happened');
        $this->assertEquals(
            array(
                'errors' => array($GLOBALS['genericRequestPaymentError'])
            ),
            $response
        );
    }
    /**
     * @group getStatusOrErrorFromRequestPaymentResponse
     */
    function testReturnsAGenericErrorWhenNoResponseGiven(): void
    {
        $response = getStatusOrErrorFromRequestPaymentResponse(null);
        $this->assertEquals(
            array(
                'errors' => array($GLOBALS['genericRequestPaymentError'])
            ),
            $response
        );
    }
    /**
     * @group getStatusOrErrorFromRequestPaymentResponse
     */
    function testReturnsAGenericErrorWhenNoStatusCodeIsProvided(): void
    {
        $response = getStatusOrErrorFromRequestPaymentResponse(array());
        $this->assertEquals(
            array(
                'errors' => array($GLOBALS['genericRequestPaymentError'])
            ),
            $response
        );
    }
    /**
     * @group getStatusOrErrorFromRequestPaymentResponse
     */
    function testReturnsAGenericErrorWhenTheResponseIsntJson(): void
    {
        $options = array(
            'statusCode' => 200,
            'response' => 'oh noz',
            'error' => ''
        );
        $response = getStatusOrErrorFromRequestPaymentResponse($options);
        $this->assertEquals(
            array(
                'errors' => array($GLOBALS['genericRequestPaymentError'])
            ),
            $response
        );
    }
    /**
     * @group getStatusOrErrorFromRequestPaymentResponse
     */
    function testReturnsAGenericErrorWhenGivenAnError(): void
    {
        $options = array(
            'statusCode' => 200,
            'response' => '{}',
            'error' => 'i amz errorz'
        );
        $response = getStatusOrErrorFromRequestPaymentResponse($options);
        $this->assertEquals(
            array(
                'errors' => array($GLOBALS['genericRequestPaymentError'])
            ),
            $response
        );
    }
    /**
     * @group getStatusOrErrorFromRequestPaymentResponse
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
        $response = getStatusOrErrorFromRequestPaymentResponse($options);
        $this->assertEquals(
            json_decode($options['response']),
            $response
        );
    }
    /**
     * @group getStatusOrErrorFromRequestPaymentResponse
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
        $response = getStatusOrErrorFromRequestPaymentResponse($options);
        $this->assertEquals(
            json_decode($options['response']),
            $response
        );
    }
    /**
     * @group getStatusOrErrorFromRequestPaymentResponse
     */
    function testReturnsAcceptedAndPaidStatusWhenPaymentAlreadyReceived(): void
    {
        $options = array(
            'statusCode' => 400,
            'response' => '{
                "errors": [
                    "Payment already received"
                ],
                "transaction": {
                    "status": "acceptedAndPaid"
                }
            }',
            'error' => ''
        );
        $response = getStatusOrErrorFromRequestPaymentResponse($options);
        $this->assertEquals(
            json_decode($options['response']),
            $response
        );
    }
}

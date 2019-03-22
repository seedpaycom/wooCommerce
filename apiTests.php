<?php
require_once __DIR__ . '/api.php';
use PHPUnit\Framework\TestCase;

class apiTests extends TestCase
{
    function setUp(): void
    { }
    /**
     * @group getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse
     */
    function testReturnsAGenericErrorWhenWTFJustHappened(): void
    {
        $response = getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse('wtf just happened');
        $this->assertEquals(
            array(
                'errors' => array($GLOBALS['genericRequestPaymentError'])
            ),
            $response
        );
    }
    /**
     * @group getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse
     */
    function testReturnsAGenericErrorWhenNoResponseGiven(): void
    {
        $response = getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse(null);
        $this->assertEquals(
            array(
                'errors' => array($GLOBALS['genericRequestPaymentError'])
            ),
            $response
        );
    }
    /**
     * @group getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse
     */
    function testReturnsAGenericErrorWhenNoStatusCodeIsProvided(): void
    {
        $response = getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse(array());
        $this->assertEquals(
            array(
                'errors' => array($GLOBALS['genericRequestPaymentError'])
            ),
            $response
        );
    }
    /**
     * @group getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse
     */
    function testReturnsAGenericErrorWhenTheResponseDotResponseIsntAnObject(): void
    {
        $options = array(
            'statusCode' => 200,
            'response' => '{}',
            'error' => ''
        );
        $response = getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse($options);
        $this->assertEquals(
            array(
                'errors' => array($GLOBALS['genericRequestPaymentError'])
            ),
            $response
        );
    }
    /**
     * @group getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse
     */
    function testReturnsAGenericErrorWhenGivenAnError(): void
    {
        $options = array(
            'statusCode' => 200,
            'response' => json_decode('{}'),
            'error' => 'i amz errorz'
        );
        $response = getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse($options);
        $this->assertEquals(
            array(
                'errors' => array($GLOBALS['genericRequestPaymentError'])
            ),
            $response
        );
    }
    /**
     * @group getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse
     */
    function testReturnsGivenMessageAsAMessage(): void
    {
        $options = array(
            'statusCode' => 200,
            'response' => json_decode('{
                "message": "Invitation sent to 5038661114"
            }'),
            'error' => ''
        );
        $response = getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse($options);
        $this->assertEquals(
            $options['response'],
            $response
        );
    }
    /**
     * @group getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse
     */
    function testReturnsFirstGivenError(): void
    {
        $options = array(
            'statusCode' => 400,
            'response' => json_decode('{
                "errors": [
                    "wtf mate?"
                ]
            }'),
            'error' => ''
        );
        $response = getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse($options);
        $this->assertEquals(
            $options['response'],
            $response
        );
    }
    /**
     * @group getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse
     */
    function testReturnsAcceptedAndPaidStatusWhenPaymentAlreadyReceived(): void
    {
        $options = array(
            'statusCode' => 400,
            'response' => json_decode('{
                "errors": [
                    "Payment already received"
                ],
                "transaction": {
                    "status": "acceptedAndPaid"
                }
            }'),
            'error' => ''
        );
        $response = getApiResponseObjectOrGenericErrorsFromRequestPaymentResponse($options);
        $this->assertEquals(
            $options['response'],
            $response
        );
    }
}

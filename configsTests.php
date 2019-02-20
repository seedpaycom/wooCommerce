<?php
require_once __DIR__ . '/configs.php';
use PHPUnit\Framework\TestCase;

class configsTests extends TestCase
{
    function setUp() : void
    {
        $GLOBALS['isTestMode'] = 'notYes';
        $GLOBALS['getOptions'] = function () {
            return array('environment' => $GLOBALS['isTestMode']);
        };
        $_ENV['seedpayTestModeApiUrl'] = null;
        $_ENV['seedpayApiUrl'] = null;
    }
    function testReturnsTheSecureProdUrlWhenNotInTestMode() : void
    {
        $this->assertStringContainsStringIgnoringCase('https', apiUrl($GLOBALS['getOptions']), 'prod is secure');
        $this->assertStringContainsStringIgnoringCase('//api.seedpay.com', apiUrl($GLOBALS['getOptions']));
    }
    function testReturnsTheStagingUrlWhenInTestMode() : void
    {
        $GLOBALS['isTestMode'] = 'yes';
        $this->assertStringContainsStringIgnoringCase('staging', apiUrl($GLOBALS['getOptions']));
    }
    function testUsesTheTestModeApiUrl() : void
    {
        $_ENV['seedpayTestModeApiUrl'] = 'i amzor urlz';
        $GLOBALS['isTestMode'] = 'yes';
        $this->assertEquals($_ENV['seedpayTestModeApiUrl'], apiUrl($GLOBALS['getOptions']));
    }
    function testUsesTheApiUrl() : void
    {
        $_ENV['seedpayApiUrl'] = 'i amzor prodzor urlz';
        $this->assertEquals($_ENV['seedpayApiUrl'], apiUrl($GLOBALS['getOptions']));
    }
}

<?php
require_once __DIR__ . '/configs.php';
use PHPUnit\Framework\TestCase;

class configsTests extends TestCase
{
    function setUp(): void
    {
        $GLOBALS['isTestMode'] = 'notYes';
        $GLOBALS['getOptions'] = function () {
            return array('environment' => $GLOBALS['isTestMode']);
        };
        putenv("seedpayTestModeApiUrl");
        putenv("seedpayApiUrl");
    }
    function testReturnsTheSecureProdUrlWhenNotInTestMode(): void
    {
        $this->assertStringContainsStringIgnoringCase('https', apiUrl($GLOBALS['getOptions']), 'prod is secure');
        $this->assertStringContainsStringIgnoringCase('//api.seedpay.com', apiUrl($GLOBALS['getOptions']));
    }
    function testReturnsTheStagingUrlWhenInTestMode(): void
    {
        $GLOBALS['isTestMode'] = 'yes';
        $this->assertStringContainsStringIgnoringCase('staging', apiUrl($GLOBALS['getOptions']));
    }
    function testUsesTheTestModeApiUrl(): void
    {
        putenv('seedpayTestModeApiUrl=i amzor urlz');
        $GLOBALS['isTestMode'] = 'yes';
        $this->assertEquals(getenv('seedpayTestModeApiUrl'), apiUrl($GLOBALS['getOptions']));
    }
    function testUsesTheApiUrl(): void
    {
        putenv('seedpayApiUrl=i amzor urlz');
        $this->assertEquals(getenv('seedpayApiUrl'), apiUrl($GLOBALS['getOptions']));
    }
}

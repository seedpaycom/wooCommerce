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
        $_ENV['NODE_ENV'] = 'production';
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
    function testReturnsTheLocalUrlWhenInTestModeAndDevelopmentEnvironment() : void
    {
        $_ENV['NODE_ENV'] = 'development';
        $GLOBALS['isTestMode'] = 'yes';
        $this->assertStringContainsStringIgnoringCase('localhost', apiUrl($GLOBALS['getOptions']));
    }
}

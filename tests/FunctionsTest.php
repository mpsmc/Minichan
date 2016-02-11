<?php

class SampleTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        require_once 'vendor/autoload.php';
        require_once 'includes/config.php';
        require_once 'includes/database.class.php';
        require_once 'includes/functions.php';
        require_once 'includes/async_functions.php';
        require_once 'includes/unicode.php';
    }

    public function testMatchIgnoredName()
    {
        $this->assertTrue(matchIgnoredName(array(
            'ayy !lmao',
        ), 'ayy', '!lmao'));

        $this->assertTrue(matchIgnoredName(array(
            'ayy !lmao',
        ), 'ayy', ' !lmao'));

        $this->assertFalse(matchIgnoredName(array(
            'ayy !lmao',
        ), 'ayy', '!lmaoo'));

        $this->assertFalse(matchIgnoredName(array(
            'ayy !lmao',
        ), 'ayy', '!lma'));

        $this->assertTrue(matchIgnoredName(array(
            'something !else',
            'ayy !lmao',
        ), 'ayy', '!lmao'));

        $this->assertTrue(matchIgnoredName(array(
            'ayy !lmao',
            'something !else',
        ), 'ayy', '!lmao'));

        $this->assertTrue(matchIgnoredName(array(
            '!lmao',
        ), 'ayy', '!lmao'));

        $this->assertTrue(matchIgnoredName(array(
            'ayy',
        ), 'ayy', '!lmao'));
    }
}

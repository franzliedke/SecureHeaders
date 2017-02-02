<?php

namespace Tests;

use Aidantwoods\SecureHeaders\Http\StringHttpAdapter;
use Aidantwoods\SecureHeaders\SecureHeaders;
use PHPUnit_Framework_TestCase;

class StrictModeHeadersTest extends PHPUnit_Framework_TestCase
{
    private $assertions = array(
        'Contains',
        'NotContains',
        'Equals',
        'Regexp',
        'NotRegExp'
    );

    function dataStrictMode()
    {
        return array(
            array(
                'test' => 
                    function(&$headers){
                        $headers->strictMode();
                    },
                'assertions' => array(
                    'Contains' =>
                        'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload'
                )
            ),
            array(
                'test' => 
                    function(&$headers){
                        $headers->strictMode();
                        $headers->cspNonce('script');
                    },
                'assertions' => array(
                    'Regexp' =>
                        "/Content-Security-Policy: script-src 'nonce-[^']+' 'strict-dynamic'/"
                )
            ),
            array(
                'test' => 
                    function(&$headers){
                        $headers->strictMode();
                        $headers->cspNonce('default');
                    },
                'assertions' => array(
                    'Regexp' =>
                        "/Content-Security-Policy: default-src 'nonce-[^']+' 'strict-dynamic'/"
                )
            ),
            array(
                'test' => 
                    function(&$headers){
                        $headers->strictMode();
                        $headers->cspNonce('default');
                        $headers->cspNonce('script');
                    },
                'assertions' => array(
                    'Regexp' =>
                        "/script-src 'nonce-[^']+' 'strict-dynamic'/",
                    'NotRegexp' =>
                        "/default-src 'nonce-[^']+' 'strict-dynamic'/"
                )
            ),
            array(
                'test' => 
                    function(&$headers){
                        $headers->strictMode();
                        $headers->cspHash('default', 'abcd');
                    },
                'assertions' => array(
                    'Regexp' =>
                        "/Content-Security-Policy: default-src 'sha[^']+' 'strict-dynamic'/"
                )
            ),
            array(
                'test' => 
                    function(&$headers){
                        $headers->strictMode();
                        $headers->cspHash('script', 'abcd');
                    },
                'assertions' => array(
                    'Regexp' =>
                        "/Content-Security-Policy: script-src 'sha[^']+' 'strict-dynamic'/"
                )
            ),
            array(
                'test' => 
                    function(&$headers){
                        $headers->strictMode();
                        $headers->cspHash('default', 'abcd');
                        $headers->cspHash('script', 'abcd');
                    },
                'assertions' => array(
                    'Regexp' =>
                        "/script-src 'sha[^']+' 'strict-dynamic'/",
                    'NotRegexp' =>
                        "/default-src 'sha[^']+' 'strict-dynamic'/"
                )
            ),
            array(
                'test' => 
                    function(&$headers){
                        $headers->strictMode();
                        $headers->csp('default', 'http://some-cdn.org');
                        $headers->csp('script', 'http://other-cdn.net');
                    },
                'assertions' => array(
                    'NotContains' =>
                        "'strict-dynamic'"
                )
            ),
        );
    }

    /**
     * @dataProvider dataStrictMode
     * @param $test
     * @param $assertions
     */
    public function testStrictMode($test, $assertions)
    {
        $headers = new SecureHeaders($headerStrings = new StringHttpAdapter);
        $headers->errorReporting(false);
        $test($headers);
        $headers->done();

        $headersString = $headerStrings->getSentHeaders();

        foreach ($this->assertions as $assertion)
        {
            if (isset($assertions[$assertion]))
            {
                if ( ! is_array($assertions[$assertion]))
                {
                    $assertions[$assertion] = array($assertions[$assertion]);
                }
                foreach ($assertions[$assertion] as $assertionString)
                {
                    $this->{'assert'.$assertion}(
                        $assertionString,
                        $headersString
                    );
                }
            }
        }
      }
}   
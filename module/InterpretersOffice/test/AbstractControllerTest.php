<?php

/**
 * module/InterpretersOffice/test/AbstractControllerTest.php.
 */

namespace ApplicationTest;

use Laminas\Stdlib\ArrayUtils;
use Laminas\Stdlib\Parameters;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

use Laminas\Dom\Document;

/**
 * base class for unit tests.
 */
abstract class AbstractControllerTest extends AbstractHttpControllerTestCase
{
    public function setUp()
    {
        $configOverrides = [

            'module_listener_options' => [
                'config_glob_paths' => [
                    //__DIR__.'/config/autoload/{{,*.}test,{,*.}local}.php',
                    __DIR__.'/config/autoload/doctrine.test.php',
                ],
            ],
        ];

        $this->setApplicationConfig(ArrayUtils::merge(
            include __DIR__.'/../../../config/application.config.php',
            $configOverrides
        ));
        parent::setUp();
    }

    /**
     * logs in a user through the AuthController.
     *
     * @param string $identity
     * @param string $password
     *
     * @return AbstractControllerTest
     */
    public function login($identity, $password)
    {

        $token = $this->getCsrfToken('/login', 'login_csrf');
        $this->getRequest()->setMethod('POST')->setPost(
            new Parameters(
                [
                    'identity' => $identity,
                    'password' => $password,
                    'login_csrf' => $token,
                ]
            )
        );
        $this->dispatch('/login');

        $auth = $this->getApplicationServiceLocator()->get('auth');

        if (! $auth->hasIdentity()) {
            echo "\nWARNING:  failed authentication\n";
        } // else {   echo "\nlogin IS OK !!!\n";  }

        //var_dump($_SESSION);
        return $this;
    }
    //static $count = 0;

    /**
     * parses out a csrf token from a form.
     *
     * @param string $url  to dispatch
     * @param string $name name of the CSRF form element
     *
     * @return string $token parsed from document body
     */
    public function getCsrfToken($url, $name = 'csrf')
    {

        $this->dispatch($url, 'GET');
        $html = $this->getResponse()->getBody();
        $DEBUG = "\nGET: $url in getCsrfToken\n";
        $DEBUG .= "...parsing $name in getCsrfToken\n";
        $auth = $this->getApplicationServiceLocator()->get('auth');
        $DEBUG .= sprintf("...authenticated? %s, element name: $name\n", $auth->hasIdentity() ? "YES" : "NO");
        //echo "HTML in ".__FUNCTION__.":\n$html";
        $DEBUG .= sprintf("...$url html string length in %s: %d\n", __FUNCTION__, strlen($html));
        $DEBUG .= "is $name in HTML? ";
        $DEBUG .= (boolean)strstr($html, "name=\"$name\"") ? "YES" : "NO!";
        //echo "\n=================================\n";
        $document = new Document($html, Document::DOC_HTML);
        $query = new Document\Query();
        $selector = sprintf('input[name="%s"]', $name);
        try {
            $results = $query->execute($selector, $document, Document\Query::TYPE_CSS);
        } catch (\Exception $e) {
            echo "\nWARNING! ".$e->getMessage(),"\n";
        }

        if (!isset($results) or ! count($results)) {
            //echo($html);
            throw new \Exception("selector was $selector -- could not parse "
                    . "CSRF token! response HTML:\n\n$html");
        }
        $node = $results->current();
        $token = $node->attributes->getNamedItem('value')->nodeValue;

        $this->reset(true);

        return $token;
    }

    /**
     * spits out the response body
     */
    public function dumpResponse()
    {
        echo $this->getResponse()->getBody();
    }

     /**
     * asserts that option element having $label exists and is selected
     *
     * @param \DOMElement $select a SELECT element
     * @param string $label
     */
    public function assertOptionIsSelected(\DOMElement $select, $label)
    {
        $found = false;
        foreach ($select->childNodes as $option) {
            if ($option->nodeValue == $label) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
        $this->assertEquals($option->getAttribute('selected'), 'selected');
    }
}

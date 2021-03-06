<?php

/**
 * module/Application/test/AuthenticationTest.php.
 */

namespace ApplicationTest\Controller;

use ApplicationTest\AbstractControllerTest;
use ApplicationTest\FixtureManager;
use ApplicationTest\DataFixture;
use InterpretersOffice\Service\Authentication;
use Laminas\Authentication\AuthenticationService;

class AuthenticationTest extends AbstractControllerTest
{
    /**
     * @var Laminas\Authentication\AuthenticationService
     */
    protected $auth;

    public function setUp()
    {
        $fixtureExecutor = FixtureManager::getFixtureExecutor();
        $fixtureExecutor->execute([
            new DataFixture\LanguageLoader(),
            new DataFixture\HatLoader(),
            new DataFixture\InterpreterLoader(),
            new DataFixture\LocationLoader(),
            new DataFixture\JudgeLoader(),
            new DataFixture\UserLoader(),
        ]);
        $adapter = new Authentication\Adapter(
            FixtureManager::getEntityManager()
        );
        $this->auth = new AuthenticationService(null, $adapter);
        parent::setUp();
    }

    public function testAuthenticateWithEmailAndWithUsername()
    {
        $adapter = $this->auth->getAdapter(); //john_somebody@nysd.uscourts.gov
        $adapter->setIdentity('david@davidmintz.org')->setCredential('boink');
        $this->assertInstanceOf(AuthenticationService::class, $this->auth);
        $this->assertInstanceOf(\InterpretersOffice\Service\Authentication\Adapter::class, $adapter);

        $result = $this->auth->authenticate();

        $this->assertInstanceOf(Authentication\Result::class, $result);
        $this->assertTrue($result->isValid());
        $this->auth->clearIdentity();
        $adapter->setIdentity('david')->setCredential('boink');
        $result = $this->auth->authenticate();
        $this->assertTrue($result->isValid());
        //echo "\n",$result->getCode(),"\n"; print_r($result->getMessages());
    }

    public function testAuthenticationFailsIfAccountIsNotActive()
    {
        $adapter = $this->auth->getAdapter();
        $adapter->setIdentity('david@davidmintz.org')->setCredential('boink');
        $em = FixtureManager::getEntityManager();
        $david = $em->getRepository('InterpretersOffice\Entity\User')
                ->findOneBy(['username' => 'david']);
        $david->setActive(false);
        $em->flush();
        $result = $this->auth->authenticate();
        $this->assertFalse($result->isValid());
        $this->assertEquals(
            Authentication\Result::FAILURE_USER_ACCOUNT_DISABLED,
            $result->getCode()
        );
    }

    public function testAuthenticationFailsIfPasswordIsWrong()
    {
        $adapter = $this->auth->getAdapter();
        $adapter->setIdentity('david@davidmintz.org')->setCredential('not correct');
        $result = $this->auth->authenticate();
        $this->assertFalse($result->isValid());
        $this->assertEquals(
            Authentication\Result::FAILURE_CREDENTIAL_INVALID,
            $result->getCode()
        );
    }
}

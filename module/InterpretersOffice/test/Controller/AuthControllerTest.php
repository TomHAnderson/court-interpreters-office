<?php

/** module/Application/test/Controller/AuthControllerTest.php */

namespace ApplicationTest\Controller;

use ApplicationTest\AbstractControllerTest;
use ApplicationTest\FixtureManager;
use ApplicationTest\DataFixture;

class AuthControllerTest extends AbstractControllerTest
{
    public function setUp()
    {
        parent::setUp();
        $fixtureExecutor = FixtureManager::getFixtureExecutor();
        $fixtureExecutor->execute([
            new DataFixture\MinimalUserLoader(),
        ]);
    }
    /**
     * asserts that susie_somebody@nysd.uscourts.gov can log in
     * with either username or email address.
     */
    public function testLoginAdminUser()
    {
        $token = $this->getCsrfToken('/login', 'login_csrf');
        $auth = $this->getApplicationServiceLocator()->get('auth');

//echo spl_object_hash($auth), " is the hash of our auth object in the unit test\n";

        $params =
        [
            'identity' => 'susie',
            'password' => 'boink',
            'login_csrf' => $token,
        ];
        $this->dispatch('/login', 'POST', $params);
        $this->assertResponseStatusCode(302);
        $this->assertRedirect();
        $this->assertRedirectTo('/admin');
        
        $this->assertTrue($auth->hasIdentity(), 'failed asserting that $auth->hasIdentity()');

        $auth->clearIdentity();
        // just checking :-)
        $this->assertFalse($auth->hasIdentity());

        // now try it using email instead of username
        $params['identity'] = 'susie_somebody@nysd.uscourts.gov';
        $params['csrf'] = $this->getCsrfToken('/login', 'login_csrf');
        $this->dispatch('/login', 'POST', $params);
        $this->assertTrue($auth->hasIdentity());
        //echo $auth->getIdentity()->getRole(); return;
        $this->assertRedirect();
        $this->assertRedirectTo('/admin');
    }
    /**
     * tests that an admin user who is redirected from an admin page to
     * the login will be redirected back after successful login.
     */
    public function testRedirectionFollowingAuthentication()
    {
        $this->dispatch('/admin/languages/add');
        $this->assertRedirect();
        $this->assertRedirectTo('/login');
        //echo "\n".$_SESSION['Authentication']->redirect_url . " is our shit here in ".__FUNCTION__."...\n";
        $this->reset(true);
        $params =
        [
            'identity' => 'susie',
            'password' => 'boink',
            'login_csrf' => $this->getCsrfToken('/login', 'login_csrf'),
        ];
        $this->dispatch('/login', 'POST', $params);
        $this->assertRedirect();
        $this->assertRedirectTo('/admin/languages/add');

        $this->dispatch('/logout');

        // demote susie to see what happens next time she tries to access an admin page
        $em = FixtureManager::getEntityManager();
        $user = $em->getRepository('InterpretersOffice\Entity\User')->findOneBy(['username' => 'susie']);
        $role = $em->getRepository('InterpretersOffice\Entity\Role')->findOneBy(['name' => 'submitter']);

        $user->setRole($role);
        $em->flush();

        $this->dispatch('/admin/languages/add');
        $this->assertRedirect();
        $this->reset(true);
        $params =
        [
            'identity' => 'susie',
            'password' => 'boink',
            'login_csrf' => $this->getCsrfToken('/login', 'login_csrf'),
        ];
        $this->dispatch('/login', 'POST', $params);
        $this->assertRedirect();
        $this->assertRedirectTo('/admin/languages/add');

        //echo $this->getResponseHeader('Location'),"\n";
        //$auth = $this->getApplicationServiceLocator()->get('auth');
        //var_dump($auth->hasIdentity());
        //$em->refresh($user);
        //echo "role: {$user->getRole()}\n";
        //printf("\nTO DO: resolve failed \$this->assertNotRedirectTo('/admin/languages/add') in AuthControllerTest at %d?\n", __LINE__);
        // problem
        //$this->assertNotRedirectTo('/admin/languages/add');
    }

    /**
     * asserts that a non-administrative user cannot go to /admin.
     */
    public function testNonAdministrativeUserCannotAccessAdmin()
    {
        // demote user Susie
        $entityManager = FixtureManager::getEntityManager();
        $susie = $entityManager->getRepository('InterpretersOffice\Entity\User')
                ->findByUsername('susie')[0];
        $susie->setRole(
            $entityManager->getRepository('InterpretersOffice\Entity\Role')
                ->findByName('submitter')[0]
        );
        $entityManager->flush();
        // sanity-check it first
        $this->assertEquals('submitter', (string) $susie->getRole());

        $token = $this->getCsrfToken('/login', 'login_csrf');
        $params =
        [
            'identity' => 'susie',
            'password' => 'boink',
            'login_csrf' => $token,
        ];
        $this->dispatch('/login', 'POST', $params);
        $this->assertRedirect();
        $this->assertNotRedirectTo('/admin');

        $this->dispatch('/admin');
        $this->assertRedirect();
        $this->assertResponseStatusCode(303);
    }
    /**
     * asserts that the wrong identity or password fails to authenticate.
     */
    public function testBadLoginNameOrPasswordFailsToAuthenticate()
    {
        // previous assertions established that the password is 'boink'
        // so anything else should fail
        $this->dispatch(
            '/login',
            'POST',
            ['identity' => 'susie',
                 'password' => 'notCorrect',
                 'login_csrf' => $this->getCsrfToken('/login', 'login_csrf'),
               ]
        );
        $this->assertNotRedirect();
        $auth = $this->getApplicationServiceLocator()->get('auth');
        $this->assertFalse($auth->hasIdentity());
        $this->assertQuery('div.alert-warning');
        $this->assertQueryContentRegex('div.alert-warning', '/authentication failed/i');
        $this->dispatch(
            '/login',
            'POST',
            ['identity' => 'nobody',
                     'password' => 'notCorrect',
                     'login_csrf' => $this->getCsrfToken('/login', 'login_csrf'),
                 ]
        );
        $this->assertNotRedirect();
        $this->assertFalse($auth->hasIdentity());
        $this->assertQuery('div.alert-warning');
        $this->assertQueryContentRegex('div.alert-warning', '/authentication failed/i');
    }

    public function testAccountIsDisabledAfterTooManyAuthenticationFailures()
    {
        $entityManager = FixtureManager::getEntityManager();
        $susie = $entityManager->getRepository('InterpretersOffice\Entity\User')
                ->findByUsername('susie')[0];
                // make sure we start out NOT disabled
        $this->assertTrue($susie->isActive());
        // FixtureManager::getFixtureExecutor();
        for ($i = 0; $i <= 5; $i++) {
            $this->dispatch(
                '/login',
                'POST',
                ['identity' => 'susie',
                     'password' => 'definitely not correct',
                     'login_csrf' => $this->getCsrfToken('/login', 'login_csrf'),
                   ]
            );
        }
        $entityManager->refresh($susie);
        // $susie = $entityManager->getRepository('InterpretersOffice\Entity\User')
        //         ->findByUsername('susie')[0];
        $this->assertQuery('div.login-failures');
        $this->assertFalse($susie->isActive());
        $this->dispatch(
            '/login',
            'POST',
            ['identity' => 'susie',
                 'password' => 'boink',
                 'login_csrf' => $this->getCsrfToken('/login', 'login_csrf'),
               ]
        );
        $auth = $this->getApplicationServiceLocator()->get('auth');
        $this->assertFalse($auth->hasIdentity());
    }

    public function testAdminCanRestoreAnotherUserAccountAfterTooManyAuthenticationFailures()
    {
        $entityManager = FixtureManager::getEntityManager();
        $susie = $entityManager->getRepository('InterpretersOffice\Entity\User')
                ->findByUsername('susie')[0];
        $susie->setFailedLogins(6)->setActive(false);
        $entityManager->flush();
        // sanity check
        $this->assertFalse($susie->isActive());
        $id = $susie->getId();
        $this->dispatch(
                '/login',
                'POST',
                ['identity' => 'admin',
                     'password' => 'boink',
                     'login_csrf' => $this->getCsrfToken('/login', 'login_csrf'),
                   ]
            );
        $this->reset(true);
        $token = $this->getCsrfToken('/admin/users/edit/'.$id);
        $this->dispatch(
            '/admin/users/edit/'.$id,
            'POST',
            [
                'csrf'=> $token,
                'user' => [
                    'active' => 1, // the significant thing
                    'username' => 'susie',
                    'id' => $id,
                    'role' => $susie->getRole()->getId(),
                    'person' => [
                        'hat' => $susie->getPerson()->getHat()->getId(),
                        'firstname'=> $susie->getPerson()->getFirstname(),
                        'lastname'=> $susie->getPerson()->getLastname(),
                        'middlename'=> $susie->getPerson()->getMiddlename(),
                        'office_phone' => $susie->getPerson()->getOfficePhone(),
                        'email' =>$susie->getPerson()->getEmail(),
                        'mobile_phone' => $susie->getPerson()->getMobilePhone(),
                        'id' => $susie->getPerson()->getId(),
                        'active' => 1,
                    ],
                ],
            ]
        );
        //$this->dumpResponse();//{"status":"success","validation_errors":null}
        $this->assertResponseStatusCode(200);
        $response = $this->getResponse()->getBody();
        $data = json_decode($response);
        $this->assertIsObject($data);
        $this->assertEquals("success",$data->status);
        $entityManager->refresh($susie);
        $this->assertTrue($susie->isActive());
        $this->assertTrue($susie->getFailedLogins() == 0);
        $this->reset();
        $this->dispatch('/login', 'POST',
            [   'identity' => 'susie',
                'password' => 'boink',
                'login_csrf' => $this->getCsrfToken('/login', 'login_csrf'),
            ]
        );
        $this->assertRedirect(); // because login suceeded
        $auth = $this->getApplicationServiceLocator()->get('auth');
        $this->assertTrue($auth->hasIdentity());
    }
}

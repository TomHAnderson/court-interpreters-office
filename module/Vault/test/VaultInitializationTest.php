<?php
namespace SDNY\Vault\Test;

use ApplicationTest\AbstractControllerTest;

use SDNY\Vault\Service\Vault as VaultClient;

class VaultInitializationTest extends AbstractControllerTest 
{
    public function setUp()
    {
        parent::setUp();
    }
    
    public function testVaultCanBeInstantiatedDirectly()
    {
        $vault = new VaultClient(['vault_address' => 'whatever']);
        $this->assertTrue(is_object($vault));
        $this->assertInstanceOf( VaultClient::class, $vault);
    }
    
    public function testVaultCanBeInstantiatedViaServiceManager()
    {
        $container = $this->getApplicationServiceLocator();        
        $vault = $container->get(VaultClient::class);
        $this->assertTrue(is_object($vault));
        $this->assertInstanceOf(VaultClient::class, $vault);
        return $vault;
    }
    
    /**
     * @depends testVaultCanBeInstantiatedViaServiceManager
     * @param VaultClient $vault
     */
    public function testGetAddress(VaultClient $vault)
    {
        $this->assertTrue(is_string($vault->getVaultAddress()));
    }
    
    /**
     * @depends testVaultCanBeInstantiatedViaServiceManager
     * @param VaultClient $vault
     */
    public function testUserAuthentication(VaultClient $vault)
    {
        $response = $vault->authenticateUser('username','password');
        // this is all for now. setting up a real vault instance
        // is a bit much for now
        $this->assertTrue(is_array($response));
        //$data = json_decode($response);
        //$this->assertTrue(is_object($data));
        
        return $vault;
    }
    
    /**
     * @depends testVaultCanBeInstantiatedViaServiceManager
     * @param VaultClient $vault
     */
    public function testAuthenticateTLSCert(VaultClient $vault)
    {
        $vault->authenticateTLSCert();        
        //$this->assertTrue(is_object($response));
        //$data = json_decode($response);
        //$this->assertTrue(is_object($data));
        //$token = $data['auth']['client_token'];
        $token = $vault->getAuthToken();
        $this->assertTrue(is_string($token));
        return $vault;
    }
    
    /**
     * @depends testAuthenticateTLSCert
     * @param VaultClient $vault
     */
    public function testAcquireCipherAccessToken(VaultClient $vault)
    {        
        
        $this->assertTrue(is_object($vault->acquireCipherAccessToken()));
        //$data = json_decode($response,JSON_OBJECT_AS_ARRAY);
        //$this->assertTrue(is_array($data));
        //$this->assertArrayNotHasKey('errors', $response);
        //$token = $data['auth']['client_token'];
        //$this->assertTrue(is_string($token));
        $this->assertTrue(is_string($vault->getAuthToken()));
        //print_r($data);
    }
}
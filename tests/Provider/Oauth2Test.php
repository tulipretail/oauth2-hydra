<?php
namespace Hydra\OAuth2\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use Hydra\OAuth2\Provider\OAuth2;
use League\OAuth2\Client\Token\AccessToken;

class OAuth2Test extends TestCase
{

    protected $domain;
    protected $oauth2;

    protected function setUp()
    {
        $this->domain = 'https://'.uniqid().'hydra.com';
        $this->oauth2 = new OAuth2([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'domain' => $this->domain,
        ]);
    }

    public function testGetDomain()
    {
        $result = $this->oauth2->getDomain();
        $this->assertEquals($this->domain, $result);
    }

    public function testGetBaseAuthorizationUrl()
    {
        $result = $this->oauth2->getBaseAuthorizationUrl();
        $this->assertEquals($this->domain.Oauth2::PATH_AUTHORIZE, $result);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];
        $result = $this->oauth2->getBaseAccessTokenUrl($params);
        $this->assertEquals($this->domain.Oauth2::PATH_TOKEN, $result);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn('{"access_token":"mock_access_token", "scope":"", "token_type":"bearer"}');
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);
        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->oauth2->setHttpClient($client);
        $token = $this->oauth2->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }
}

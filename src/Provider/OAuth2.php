<?php

namespace Hydra\OAuth2\Provider;

use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Grant\AbstractGrant;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use Hydra\OAuth2\Token\AccessToken as HydraAccessToken;

class OAuth2 extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * Hydra OAuth server authorization endpoint.
     *
     * @var string
     */
    const PATH_AUTHORIZE = '/oauth2/auth';

    /**
     * Hydra OAuth server token request endpoint.
     *
     * @var string
     */
    const PATH_TOKEN = '/oauth2/token';

    /**
     * API endpoint to retrieve logged in user information.
     *
     * @var string
     */
    const PATH_API_USER = '/userinfo';

    /**
     * Hydra host URL
     *
     * @var string
     */
    protected $domain;

    /**
     * @param array $options
     * @param array $collaborators
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);
        if (empty($options['domain'])) {
            throw new \InvalidArgumentException('The "domain" option not set. Please set a domain.');
        }
    }

    /**
     * Get the Hydra host URL.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @inheritdoc
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->domain.self::PATH_AUTHORIZE;
    }

    /**
     * @inheritdoc
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->domain.self::PATH_TOKEN;
    }

    /**
     * @inheritdoc
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->domain.self::PATH_API_USER;
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultScopes()
    {
        return [];
    }

    /**
     * Check a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw new Exception\IdentityProviderException(
                'Bad Response from Auth Provider ['.$response->getStatusCode().']: '.$response->getBody()
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getParsedResponse(RequestInterface $request)
    {
        try {
            return parent::getParsedResponse($request);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new Exception\ConnectionException($e->getMessage());
        }
    }

    /**
     * Override default options to put client id/secret in headers.
     *
     * @param array $params
     * @return array
     */
    protected function getAccessTokenOptions(array $params)
    {
        $options = parent::getAccessTokenOptions($params);
        $options['headers']['authorization'] = 'Basic '.base64_encode(
            implode(':', [
                $this->clientId,
                $this->clientSecret,
            ])
        );

        return $options;
    }

    /**
     * @inheritdoc
     */
    protected function createAccessToken(array $response, AbstractGrant $grant)
    {
        return new HydraAccessToken($response);
    }

    /**
     * @inheritdoc
     */
    public function getAccessToken($grant, array $options = [])
    {
        $grant = $this->verifyGrant($grant);

        $params = [
            'redirect_uri'  => $this->redirectUri,
            'scope'         => isset($options['scope']) ? $options['scope'] : '',
        ];

        $params   = $grant->prepareRequestParameters($params, $options);
        $request  = $this->getAccessTokenRequest($params);
        $response = $this->getParsedResponse($request);
        $prepared = $this->prepareAccessTokenResponse($response);
        $token    = $this->createAccessToken($prepared, $grant);

        return $token;
    }

    /**
     * @inheritdoc
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new ResourceOwner($response, 'sub');
    }
}

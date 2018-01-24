<?php

namespace Hydra\OAuth2\Token;

class AccessToken extends \League\OAuth2\Client\Token\AccessToken
{
    protected $idToken;

    /**
     * @inheritdoc
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        if (!empty($this->values['id_token'])) {
            $this->idToken = $this->values['id_token'];
        }
    }

    /**
     * Returns the id token, if one was present in the response.
     *
     * @return string|null
     */
    public function getIdToken()
    {
        return $this->idToken;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        $parameters = parent::jsonSerialize();
        if ($this->idToken) {
            $parameters['id_token'] = (string)$this->idToken;
        }
        return $parameters;
    }
}

<?php

namespace Hydra\OAuth2\Provider;

use League\OAuth2\Client\Provider\GenericResourceOwner;

class ResourceOwner extends GenericResourceOwner implements \JsonSerializable
{
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}

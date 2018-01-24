Hydra PHP Oauth2 Client
=======================

This package provides [Hydra](https://github.com/ory/hydra) OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require pnicolcev-tulipretail/oauth2-hydra
```

## Usage

Usage is the same as The League's OAuth client, using `\Hydra\OAuth2\Provider\OAuth2` as the provider.

### With the Hydra SDK

You can use this library to acquire an access token for use with the Hydra SDK.

Here we get one with the 'hydra.clients' scope:

```
    $provider = new \Hydra\OAuth2\Provider\OAuth2([
        'clientId' => 'admin',
        'clientSecret' => 'demo-password',
        'domain' => 'https://your-hydra-domain',
    ]);

    try {
        // Get an access token using the client credentials grant.
        // Note that you must separate multiple scopes with a plus (+)
        $accessToken = $provider->getAccessToken(
            'client_credentials', ['scope' => 'hydra.clients']
        );
    } catch (\Hydra\Oauth2\Provider\Exception\ConnectionException $e) {
        die("Connection to Hydra failed: ".$e->getMessage());
    } catch (\Hydra\Oauth2\Provider\Exception\IdentityProviderException $e) {
        die("Failed to get an access token: ".$e->getMessage());
    }

    // You may now pass $accessToken to the hydra SDK to manage clients
```

### As an OIDC Client

You can also use this library if you are a Relying Party.

Here we send users to the Hydra to authenticate so that we can complete the authorization code flow:

```
    $provider = new \Hydra\OAuth2\Provider\OAuth2([
        'clientId' => 'admin',
        'clientSecret' => 'demo-password',
        'domain' => 'https://your-hydra-domain',
        // Be sure this is a redirect URI you registered with Hydra for your client!
        'redirectUri' => 'http://your-domain.com/bobsflowers',
    ]);

    if (!isset($_GET['code'])) {

        // If we don't have an authorization code then get one
        $authUrl = $provider->getAuthorizationUrl(['scope' => ['openid']]);
        $_SESSION['oauth2state'] = $provider->getState();
        header('Location: '.$authUrl);
        die();

    // Check given state against previously stored one to mitigate CSRF attack
    } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

        unset($_SESSION['oauth2state']);
        die('Invalid state');

    } else {

        // Try to get an access token (using the authorization code grant)
        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        // Optional: Now you have a token you can look up a users profile data
        try {

            // We got an access token, let's now get the user's details
            $user = $provider->getResourceOwner($token);

            // $user contains public claims from the id token
            printf('User info: ', json_encode($user));

        } catch (\Hydra\Oauth2\Provider\Exception\IdentityProviderException $e) {
            die('Unable to fetch user details: '.$e->getMessage());
        }

        // Use this to interact with an API on the users behalf
        echo $token->getToken();
    }

```

<?php

namespace OAuth\OAuth2\Service;

use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Uri\UriInterface;

class Foursquare extends AbstractService
{
    private $apiVersionDate = '20130829';

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
        return new Uri('https://foursquare.com/oauth2/authenticate');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://foursquare.com/oauth2/access_token');
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        $data = json_decode($responseBody, true);

        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth2Token();
        $token->setAccessToken($data['access_token']);
        // Foursquare tokens evidently never expire...
        $token->setEndOfLife(StdOAuth2Token::EOL_NEVER_EXPIRES);
        unset($data['access_token']);

        $token->setExtraParams($data);

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function request($path, $method = 'GET', $body = null, array $extraHeaders = array())
    {
        $uri = new Uri($this->baseApiUri . $path);
        $uri->addToQuery('v', $this->apiVersionDate);

        return parent::request($uri, $method, $body, $extraHeaders);
    }

    /**
     * Returns a UriInterface to be used as base api url if none is provided
     *
     * @return null|UriInterface
     */
    protected function getDefaultBaseApiUrl()
    {
        return new Uri('https://api.foursquare.com/v2/');
    }
}

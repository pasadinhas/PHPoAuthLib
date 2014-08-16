<?php

namespace OAuth\OAuth2\Service;

use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Client\ClientInterface;

class Pocket extends AbstractService
{

    public function getRequestTokenEndpoint()
    {
        return new Uri('https://getpocket.com/v3/oauth/request');
    }
    
    public function getAuthorizationEndpoint()
    {
        return new Uri('https://getpocket.com/auth/authorize');
    }
    
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://getpocket.com/v3/oauth/authorize');
    }
    
    public function getAuthorizationUri(array $additionalParameters = array())
    {
        $parameters = array_merge(
            $additionalParameters,
            array(
                'redirect_uri' => $this->credentials->getCallbackUrl(),
            )
        );
        
        // Build the url
        $url = clone $this->getAuthorizationEndpoint();
        foreach ($parameters as $key => $val) {
            $url->addToQuery($key, $val);
        }

        return $url;
    }
    
    public function requestRequestToken()
    {
        $responseBody = $this->httpClient->retrieveResponse(
            $this->getRequestTokenEndpoint(),
            array(
                'consumer_key' => $this->credentials->getConsumerId(),
                'redirect_uri' => $this->credentials->getCallbackUrl(),
            )
        );
        
        $code = $this->parseRequestTokenResponse($responseBody);

        return $code;
    }
    
    protected function parseRequestTokenResponse($responseBody)
    {
        parse_str($responseBody, $data);
        
        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (!isset($data['code'])) {
            throw new TokenResponseException('Error in retrieving code.');
        }
        return $data['code'];
    }
    
    public function requestAccessToken($code)
    {
        $bodyParams = array(
            'consumer_key'     => $this->credentials->getConsumerId(),
            'code'             => $code,
        );

        $responseBody = $this->httpClient->retrieveResponse(
            $this->getAccessTokenEndpoint(),
            $bodyParams,
            $this->getExtraOAuthHeaders()
        );
        $token = $this->parseAccessTokenResponse($responseBody);
        $this->storage->storeAccessToken($this->service(), $token);

        return $token;
    }
    
    protected function parseAccessTokenResponse($responseBody)
    {
        parse_str($responseBody, $data);

        return $this->parseAccessToken($data, false);
    }

    /**
     * Returns a UriInterface to be used as base api url if none is provided
     *
     * @return null|UriInterface
     */
    protected function getDefaultBaseApiUrl()
    {
        return new Uri('https://getpocket.com/v3/');
    }
}

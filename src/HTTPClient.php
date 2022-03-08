<?php declare(strict_types=1);

namespace Amadeus;

use Amadeus\Client\AccessToken;
use GuzzleHttp\Client as GuzzleHttpClient;
use JsonMapper;
use JsonMapper_Exception;

class HTTPClient
{
    protected const BASE_URL = 'https://test.api.amadeus.com';

    protected GuzzleHttpClient $httpClient;

    protected AccessToken $accessToken;

    private Configuration $configuration;

    /**
     * @param Configuration $configuration
     * @throws JsonMapper_Exception
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;

        $this->httpClient = $this->createHttpClient();
        $this->accessToken = $this->fetchAccessToken();
    }

    /**
     * @param string $path
     * @param array $query
     * @return object
     * @throws JsonMapper_Exception
     */
    public function get(string $path, array $query): object
    {
        $headers = array(
            'Content-Type' => 'application/vnd.amadeus+json',
            'Accept'=> 'application/json, application/vnd.amadeus+json',
            'Authorization' => $this->getAuthorizedToken()->getHeader(),
        );

        $response = $this->httpClient->get(
            $path,[
            'headers' => $headers,
            'query' => $query,
        ]);

        return json_decode($response->getBody()->__toString());
    }

    /**
     * @param string $path
     * @param string $body
     * @return object
     * @throws JsonMapper_Exception
     */
    public function post(string $path, string $body): object
    {
        $headers = array(
            'Content-Type' => 'application/vnd.amadeus+json',
            'Accept'=> 'application/json, application/vnd.amadeus+json',
            'Authorization' => $this->getAuthorizedToken()->getHeader(),
        );

        $response = $this->httpClient->post(
            $path,[
            'headers' => $headers,
            'body' => $body,
        ]);

        return json_decode($response->getBody()->__toString());
    }

    /**
     * @return AccessToken
     * @throws JsonMapper_Exception
     */
    public function getAuthorizedToken(): AccessToken
    {
        // Checks if the current access token expires.
        if($this->accessToken->getAccessToken()!=null){
            if($this->accessToken->getExpiresAt() < time()){
                print_r('AccessToken expired!');
                // If expired then refresh the token
                return $this->fetchAccessToken();
            }else{
                // Else still return the current token
                return $this->accessToken;
            }
        }else{
            // Else still return the current token
            return $this->fetchAccessToken();
        }
    }

    /**
     * @return AccessToken
     * @throws JsonMapper_Exception
     */
    protected function fetchAccessToken(): AccessToken
    {
        $response = $this->httpClient->post('/v1/security/oauth2/token', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->configuration->getClientId(),
                'client_secret' => $this->configuration->getClientSecret(),
            ],
        ]);

        $result = json_decode($response->getBody()->__toString());

        $mapper = new JsonMapper();
        $mapper->bIgnoreVisibility = true;

        return $mapper->map($result, new AccessToken());
    }

    /**
     * @return Configuration
     */
    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * @return GuzzleHttpClient
     */
    protected function createHttpClient(): GuzzleHttpClient
    {
        return new GuzzleHttpClient([
            'base_uri' => self::BASE_URL,
            'http_errors' => false,
            'verify' => '/CA/cacert.pem',
        ]);
    }

}
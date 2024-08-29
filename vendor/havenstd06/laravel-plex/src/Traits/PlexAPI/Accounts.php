<?php

namespace Havenstd06\LaravelPlex\Traits\PlexAPI;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Utils;

trait Accounts
{
    /**
     * Get accounts details.
     *
     * @return array|StreamInterface|string
     * @throws \Throwable
     *
     */
    public function getPlexAccount(): StreamInterface|array|string
    {
        $this->apiBaseUrl = $this->config['plex_tv_api_url'];

        $this->apiEndPoint = "users/account.json";

        $this->verb = 'get';

        return $this->doPlexRequest();
    }

    /**
     * Get account information
     *
     * @return array|StreamInterface|string
     * @throws \Throwable
     *
     */
    public function getAccounts(): StreamInterface|array|string
    {
        $this->apiBaseUrl = $this->config['server_api_url'];

        $this->apiEndPoint = "accounts";

        $this->verb = 'get';

        return $this->doPlexRequest();
    }

    /**
     * Get Plex.TV account information.
     *
     * @return array|StreamInterface|string
     * @throws \Throwable
     *
     */
    public function getServerPlexAccount(): StreamInterface|array|string
    {
        $this->apiBaseUrl = $this->config['server_api_url'];

        $this->apiEndPoint = "myplex/account";

        $this->verb = 'get';

        return $this->doPlexRequest();
    }

    /**
     * Use basic auth to Sign in to plex.tv for validating plex username/password and receive an auth token
     *
     * @param array $data
     * @param bool $useHeaderToken
     *
     * @return array|StreamInterface|string
     * @throws \Throwable
     */
    public function signIn(array $data, bool $useHeaderToken = false): StreamInterface|array|string
    {
        /*$this->apiBaseUrl = $this->config['plex_tv_api_url'];

        $this->apiEndPoint = "users/sign_in.json";

        if (isset($data['auth']) && (! $useHeaderToken || empty($this->config['token']))) {
            $this->removeRequestHeader('X-Plex-Token');
        }

        if (isset($data['auth'])) {
            $this->options['auth'] = $data['auth'];
        }

        if (isset($data['headers'])) {
            $this->setArrayRequestHeader($data['headers']);
        }

        $this->verb = 'post';

        return $this->doPlexRequest();*/

        $apiUrl = "https://plex.tv/api/v2/users/signin";
        $params = array(
            'login'=>$data['auth'][0],
            'password'=>$data['auth'][1]
        );

        $response =  $this->curlPost($apiUrl, $params);
        $result = [];

        if ($this->isXml($response)) {
            $content = str_replace(array("\n", "\r", "\t"), '', $response);
            $content = trim(str_replace('"', "'", $content));
            $xml = new \SimpleXMLElement($content);

            $result = Utils::jsonEncode($this->xmlToJson($xml), true);
        }

        return Utils::jsonDecode($result, true);

    }

    public function curlPost($url, $params){
        $headers = array(
            'X-Plex-Client-Identifier: '.uniqid(),
            'Content-Type: application/json'
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);

        return $response;
    }
}
<?php

namespace Havenstd06\LaravelJellyfin\Traits\JellyfinAPI;

use Psr\Http\Message\StreamInterface;

trait Sessions
{
    public function getSessionsByUser()
    {
        $this->apiBaseUrl = $this->config['server_api_url'];
        $this->apiEndPoint = "Sessions";
        $this->verb = 'get';

        return $this->doJellyfinRequest();
    }
}
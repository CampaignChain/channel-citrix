<?php
/*
 * Copyright 2016 CampaignChain, Inc. <info@campaignchain.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CampaignChain\Channel\CitrixBundle\REST;

use CampaignChain\CoreBundle\Exception\ExternalApiException;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Session\Session;
use Guzzle\Plugin\Oauth\OauthPlugin;

class CitrixClient
{
    const RESOURCE_OWNER = 'Citrix';
    const BASE_URL   = 'https://api.citrixonline.com/';

    protected $container;

    /** @var  Client */
    protected $client;

    protected $organizerKey;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function connectByActivity($activity){
        return $this->connectByLocation($activity->getLocation());
    }

    public function connectByLocation($location){

        $this->organizerKey = $location->getIdentifier();

        $oauthApp = $this->container->get('campaignchain.security.authentication.client.oauth.application');
        $application = $oauthApp->getApplication(self::RESOURCE_OWNER);

        // Get Access Token and Token Secret
        $oauthToken = $this->container->get('campaignchain.security.authentication.client.oauth.token');
        $token = $oauthToken->getToken($location);

        return $this->connect($application->getKey(), $application->getSecret(), $token->getAccessToken(), $token->getTokenSecret());
    }

    public function connect($appKey, $appSecret, $accessToken, $tokenSecret)
    {
        try {
            $this->client = new Client([
                'base_uri' => self::BASE_URL,
            ]);

            return $this;
        } catch (\Exception $e) {
            throw new ExternalApiException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function request($method, $uri, $body = array())
    {
        try {
            $res = $this->client->request($method, $uri, $body);
            return json_decode($res->getBody(), true);
        } catch(\Exception $e){
            throw new ExternalApiException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getUpcomingWebinars()
    {
        return $this->request('GET','G2W/rest/organizers/'.$this->organizerKey.'/upcomingWebinars');
    }

    public function getWebinar($webinarKey)
    {
        return $this->request('GET','G2W/rest/organizers/'.$this->organizerKey.'/webinars/'.$webinarKey);
    }

    public function updateWebinarDate($webinarKey, \DateTime $startDate, \DateTime $endDate)
    {
        $body['times'][0] = array(
            'startTime' => $startDate->format(\DateTime::ISO8601),
            'endTime'   => $endDate->format(\DateTime::ISO8601),
        );

        return $this->request(
            'PUT',
            'G2W/rest/organizers/'.$this->organizerKey.'/webinars/'.$webinarKey,
            array(
                'header' => array('Content-Type' => 'application/json'),
                'body'   => json_encode($body),
            )
        );
    }

    public function getWebinarSessions($webinarKey)
    {
        return $this->request('GET','G2W/rest/organizers/'.$this->organizerKey.'/webinars/'.$webinarKey.'/sessions');
    }
}
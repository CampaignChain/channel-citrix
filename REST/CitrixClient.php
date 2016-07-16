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

use Symfony\Component\HttpFoundation\Session\Session;
use Guzzle\Http\Client;
use Guzzle\Plugin\Oauth\OauthPlugin;

class CitrixClient
{
    const RESOURCE_OWNER = 'Citrix';
    const BASE_URL   = 'https://api.citrixonline.com';

    protected $container;

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

    public function connect($appKey, $appSecret, $accessToken, $tokenSecret){
        try {
            $this->client = new Client(self::BASE_URL);
            $this->client->setDefaultOption('headers', array('Authorization' => 'OAuth oauth_token='.$accessToken));

            return $this;
        }
        catch (ClientErrorResponseException $e) {

            $req = $e->getRequest();
            $resp =$e->getResponse();
            print_r($resp);die('1');
        }
        catch (ServerErrorResponseException $e) {

            $req = $e->getRequest();
            $resp =$e->getResponse();
            die('2');
        }
        catch (BadResponseException $e) {
            $req = $e->getRequest();
            $resp =$e->getResponse();
            print_r($resp);
            die('3');
        }
        catch( Exception $e){
            echo "AGH!";
            die('4');
        }
    }

    public function getUpcomingWebinars()
    {
        $request = $this->client->get('G2W/rest/organizers/'.$this->organizerKey.'/upcomingWebinars');
        return $request->send()->json();
    }

    public function getWebinar($webinarKey)
    {
        $request = $this->client->get('G2W/rest/organizers/'.$this->organizerKey.'/webinars/'.$webinarKey);
        return $request->send()->json();
    }

    public function getWebinarSessions($webinarKey)
    {
        $request = $this->client->get('G2W/rest/organizers/'.$this->organizerKey.'/webinars/'.$webinarKey.'/sessions');
        return $request->send()->json();
    }
}
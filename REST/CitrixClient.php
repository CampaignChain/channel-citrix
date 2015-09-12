<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
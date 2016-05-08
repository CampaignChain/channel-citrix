<?php
/*
 * This file is part of the CampaignChain package.
 *
 * (c) CampaignChain, Inc. <info@campaignchain.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CampaignChain\Channel\CitrixBundle\Controller;

use CampaignChain\CoreBundle\Entity\Location;
use CampaignChain\Location\CitrixBundle\Entity\CitrixUser;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;

class CitrixController extends Controller
{
    const RESOURCE_OWNER = 'Citrix';
    const LOCATION_BUNDLE = 'campaignchain/location-citrix';
    const LOCATION_MODULE = 'campaignchain-citrix-user';

    private $applicationInfo = array(
        'key_labels' => array('id', 'Consumer Key'),
        'secret_labels' => array('secret', 'Consumer Secret'),
        'config_url' => 'https://developer.citrixonline.com/user/me/apps',
        'parameters' => array(),
        'wrapper' => array(
            'class'=>'Hybrid_Providers_Citrix',
            'path' => 'vendor/campaignchain/channel-citrix/REST/CitrixOAuth.php'
        ),
    );

    public function createAction()
    {
        $oauthApp = $this->get('campaignchain.security.authentication.client.oauth.application');
        $application = $oauthApp->getApplication(self::RESOURCE_OWNER);

        if(!$application){
            return $oauthApp->newApplicationTpl(self::RESOURCE_OWNER, $this->applicationInfo);
        }
        else {
            return $this->render(
                'CampaignChainChannelCitrixBundle:Create:index.html.twig',
                array(
                    'page_title' => 'Connect with GoToWebinar',
                    'app_id' => $application->getKey(),
                )
            );
        }
    }

    public function loginAction(Request $request){
            $oauth = $this->get('campaignchain.security.authentication.client.oauth.authentication');
            $status = $oauth->authenticate(self::RESOURCE_OWNER, $this->applicationInfo);
            $profile = $oauth->getProfile();

            if($status){
                try {
                    $em = $this->getDoctrine()->getManager();
                    $em->getConnection()->beginTransaction();

                    $wizard = $this->get('campaignchain.core.channel.wizard');
                    $wizard->setName($profile->displayName);

                    // Get the location module.
                    $locationService = $this->get('campaignchain.core.location');
                    $locationModule = $locationService->getLocationModule(self::LOCATION_BUNDLE, self::LOCATION_MODULE);

                    $location = new Location();
                    $location->setIdentifier($profile->identifier);
                    $location->setName($profile->displayName);
                    $location->setLocationModule($locationModule);
                    $wizard->addLocation($location->getIdentifier(), $location);

                    $channel = $wizard->persist();
                    $wizard->end();

                    $oauth->setLocation($channel->getLocations()[0]);

                    $user = new CitrixUser();
                    $user->setLocation($channel->getLocations()[0]);
                    $user->setIdentifier($profile->identifier);
                    $user->setDisplayName($profile->displayName);
                    $user->setFirstName($profile->firstName);
                    $user->setLastName($profile->lastName);
                    $user->setEmail($profile->email);

                    $em->persist($user);
                    $em->flush();

                    $em->getConnection()->commit();

                    $this->get('session')->getFlashBag()->add(
                        'success',
                        'The Citrix location <a href="#">'.$profile->displayName.'</a> was connected successfully.'
                    );
                } catch (\Exception $e) {
                    $em->getConnection()->rollback();
                    throw $e;
                }
            } else {
                $this->get('session')->getFlashBag()->add(
                    'warning',
                    'A location has already been connected for this Citrix account.'
                );
            }

        return $this->render(
            'CampaignChainChannelCitrixBundle:Create:login.html.twig',
            array(
                'redirect' => $this->generateUrl('campaignchain_core_channel')
            )
        );
    }
}
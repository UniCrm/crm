<?php
namespace UniCrm\Bundles\CalendarBundle\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UniCrm\Bundles\CalendarBundle\Drivers\GoogleDriver;
use UniCrm\Bundles\CalendarBundle\Drivers\OutlookDriver;
use UniCrm\Bundles\CalendarBundle\Interfaces\CalendarBase;


/**
 * Class Calendar
 * CALENDAR FACTORY CLASS
 * @package UniCrm\Bundles\CalendarBundle\Service
 */
class Calendar extends CalendarBase {

    /**
     * RETURNS THE SUPPORTED DRIVER INSTANCE
     */
    public function driver($driver = null)
    {
        //IF IT IS SUPPORTED
        if (true == $this->_supports($driver)){

            if ('google' == $driver){

                if (in_array('google' , self::$instances)){

                    return self::$instances['google'];
                }

                $redirectUri = $this->container->get('router')
                    ->generate($this->container
                        ->getParameter('calendar.google.redirect_route'),[],UrlGeneratorInterface::ABSOLUTE_URL);


                $googleDriver = new GoogleDriver(
                    $this->container->getParameter('calendar.google.application_name'),
                    $this->container->getParameter('calendar.google.client_id'),
                    $this->container->getParameter('calendar.google.auth_uri'),
                    $this->container->getParameter('calendar.google.token_uri'),
                    $this->container->getParameter('calendar.google.client_secret'),
                    $redirectUri
                );

                self::$instances['google'] = $googleDriver;

                return self::$instances['google'];
            }

            if ('outlook' == $driver){

                if (in_array('outlook' , self::$instances)){
                    return self::$instances['outlook'];
                }

                $outlookDriver = new OutlookDriver();
                $outlookDriver->setClientId($this->container->getParameter('calendar.outlook.client_id'));
                $outlookDriver->setClientSecret($this->container->getParameter('calendar.outlook.client_secret'));

                $redirectUri = $this->container->get('router')
                    ->generate($this->container->getParameter('calendar.outlook.redirect_route'),[],UrlGeneratorInterface::ABSOLUTE_URL);

                $outlookDriver->setRedirectUri($redirectUri);

                self::$instances['outlook'] = $outlookDriver;

                return self::$instances['outlook'];
            }

        }

    }


}
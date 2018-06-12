<?php

namespace UniCrm\Bundles\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use UniCrm\Bundles\CalendarBundle\Service\Calendar;

/**
 * Class DefaultController
 * @Route("/calendar" , name="calendar.")
 */
class DefaultController extends Controller
{
    /**
     * @Route("/" , name="index")
     * some change in master
     */
    public function indexAction( SessionInterface $session )
    {
        $gcalendar = $this->get('calendar')->driver('google');

        try{
            $token = $gcalendar->setAccessToken($session->get('google_token'));
        }catch(\Exception $e){
            //token is null
            return new RedirectResponse( $gcalendar->createAuthUrl());
        }


        if ($token){
            $session->set('google_token',  $token);
        }


        // Print the next 10 events on the user's calendar.
        $calendarId = 'primary';
        $optParams = array(
            'maxResults' => 100,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => date("c", strtotime(date('Y-m-01'))),

        );

        $results = $gcalendar->listEvents($calendarId, $optParams);

        dump($results);die();

       //

        return $this->render('UniCrmCalendarBundle:Default:index.html.twig');
    }

    /**
     * @Route("/google/callback" , name="google.callback")
     */
    public function googleCallback(Request $request , SessionInterface $session){

        $gcalendar = $this->get('calendar')->driver('google');

        $actoken = $gcalendar->authorize($request->get('code'));

        $session->set('google_token' , $actoken);

        return new RedirectResponse($this->generateUrl('calendar.index'));
    }

    /**
     * @Route("/outlook" , name="outlook")
     */
    public function outlookAction(Request $request ,  SessionInterface $session ){

        $request = $this->get('request_stack')->getMasterRequest();

        $outlookCalendar = $this->get('calendar')->driver('outlook');

        /**
         * some changes made on branch
         *
         *
         */
        if (empty($session->get('outlook_token'))) {
            //IF WE DO NOT HAVE A TOKEN WE WILL HAVE TO
            //ASK USER FOR PERMISSION IN ORDE TO GET TOKEN
            return new RedirectResponse($outlookCalendar->createAuthUrl());
        }

       $outlookCalendar->authenticateWithToken($session->get('outlook_token'));

        $calendarId = null;

        $optParams = array(
        );

        $events = $outlookCalendar->listEvents($calendarId,$optParams);

        dump($events);die();
    }

    /**
     * @Route("/outlook/callback" , name="outlook.callback")
     */
    public function outlookCallbackAction(Request $request , SessionInterface $session ){

        $request = $this->get('request_stack')->getMasterRequest();

        $outlookCalendar = $this->get('calendar')->driver('outlook');

        if ($request->query->has('code') && $request->get('code')) {

            $token = $outlookCalendar->authenticateWithCode($request->get('code'));

            $access_token = $token;

            $session->set('outlook_token', $access_token);
        }

        return $this->redirectToRoute('calendar.outlook');
    }


}

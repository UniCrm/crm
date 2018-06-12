<?php

namespace UniCrm\Bundles\CalendarBundle\Drivers;

use UniCrm\Bundles\CalendarBundle\Exceptions\InvalidArgumentException;
use UniCrm\Bundles\CalendarBundle\Interfaces\CalendarDriverInterface;
use Google_Client;

class GoogleDriver implements CalendarDriverInterface{

    protected $googleClient;
    protected $googleServiceCalendar;


    /**
     * REQUIRED PARAMETERES
     */
    public function __construct(
        $aplicationName = null,
        $clientId = null,
        $authUri = null,
        $tokenUri = null,
        $clientSecret = null,
        $redirectUri= null
    ){
       $this->googleClient =  new Google_Client([
               'application_name' => $aplicationName,
               'client_id' => $clientId,
               'client_secret' => $clientSecret,
               'redirect_uri' => $redirectUri
       ]);

       $this->googleClient->setScopes(\Google_Service_Calendar::CALENDAR_READONLY);
       $this->googleClient->setAccessType('offline');
       $this->googleClient->setPrompt('consent');

    }


    public function getGoogleClient(){
        return $this->googleClient;
    }


    public function createAuthUrl(){
        return $this->googleClient->createAuthUrl();
    }

    public function setApplicationName($name = null){

        if (null == $name){
            throw new InvalidArgumentException('Name Cannot be Null');
        }

        $this->googleClient->setApplicationName($name);
    }

    public function fetchAccessTokenWithAuthCode($code = null){
        return $this->googleClient->fetchAccessTokenWithAuthCode($code);
    }

    public function setAccessToken($token = null){

       $this->googleClient->setAccessToken($token);

        if ( $this->googleClient->isAccessTokenExpired()) {

            $this->googleClient->fetchAccessTokenWithRefreshToken( $this->googleClient->getRefreshToken());

            //EVENT TOKEN REFRESHED
            //TO DO
        }

        $this->initGoogleServiceCalendar();
    }

    public function authorize($code = null){
        $accessToken = $this->fetchAccessTokenWithAuthCode($code);
        $this->initGoogleServiceCalendar();
        return $accessToken;
    }

    public function initGoogleServiceCalendar(){
        $this->googleServiceCalendar =  new \Google_Service_Calendar($this->googleClient);
    }

    public function listEvents($calendarId, $optParams){
        return $this->googleServiceCalendar->events->listEvents($calendarId, $optParams);
    }

    public function authenticate($code = null)
    {
        // TODO: Implement authenticate() method.
    }

    public function refreshToken($refreshToken)
    {
        // TODO: Implement refreshToken() method.
    }

    public function isAccessTokenExpired($token)
    {
        // TODO: Implement isAccessTokenExpired() method.
    }

    public function getEvent($calendarId, $eventId, $optParams = [])
    {
        // TODO: Implement getEvent() method.
    }

    public function addEvent($calendarId, \DateTime $eventStart, \DateTime $eventEnd, $eventSummary, $eventDescription, $eventAttendee = "", $location = "", $optionalParams = [], $allDay = false
    )
    {
        // TODO: Implement addEvent() method.
    }

    public function updateEvent()
    {
        // TODO: Implement updateEvent() method.
    }

    public function deleteEvent($calendarId, $eventId)
    {
        // TODO: Implement deleteEvent() method.
    }


}


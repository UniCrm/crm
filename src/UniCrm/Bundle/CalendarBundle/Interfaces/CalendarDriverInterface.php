<?php

namespace UniCrm\Bundles\CalendarBundle\Interfaces;

/**
 * Interface CalendarDriverInterface
 * The Interface class that will define the drivers
 * required methods to manage google/outlook calendar
 * @package UniCrm\Bundles\CalendarBundle\Interfaces
 */
interface CalendarDriverInterface{

    /**
     * ---------------------------------------------------------------
     * AUTHENTICATION METHODS
     * ---------------------------------------------------------------
     */


    /**
     * @param null $code
     * @return array Token
     */
    public function authenticateWithCode($code = null);

    /**
     * @param null $token
     * @return mixed
     */
    public function authenticateWithToken($token = null);


    /**
     * @param $refreshToken
     * @return array Token
     */
    public function refreshToken($refreshToken);

    /**
     * @param $token
     * @return mixed
     */
    public function  isTokenExpired($token);

    /**
     * @return mixed
     */
    public function createAuthUrl();


    /**
     * ---------------------------------------------------------------
     * AUTHORIZATION METHODS
     * ---------------------------------------------------------------
     */


    /**
     * @param $token
     * @return mixed
     */
    public function isAccessTokenExpired($token);


    /**
     * ---------------------------------------------------------------
     * EVENT METHODS
     * ---------------------------------------------------------------
     */

    /**
     * List events
     * @return mixed
     */
    public function listEvents($calendarId, $optParams);

    /**
     * Get Single Event
     * @param $calendarId
     * @param $syncToken
     * @return mixed
     */
    public function getEvent($calendarId, $eventId, $optParams = []);


    /**
     * Create and event
     * @param $calendarId
     * @param \DateTime $eventStart
     * @param \DateTime $eventEnd
     * @param $eventSummary
     * @param $eventDescription
     * @param string $eventAttendee
     * @param string $location
     * @param array $optionalParams
     * @param bool $allDay
     * @return mixed
     */
    public function addEvent(
        $calendarId,
        \DateTime $eventStart,
        \DateTime $eventEnd,
        $eventSummary,
        $eventDescription,
        $eventAttendee = "",
        $location = "",
        $optionalParams = [],
        $allDay = false
    );


    /**
     * Update Event
     * @return mixed
     */
    public function updateEvent(
        $calendarId,
        $eventId,
        \DateTime $eventStart,
        \DateTime $eventEnd,
        $eventSummary,
        $eventDescription,
        $eventAttendee = "",
        $location = "",
        $optionalParams = [],
        $allDay = false
    );


    /**
     * Delete Event
     * @param $calendarId
     * @param $eventId
     * @return mixed
     */
    public function deleteEvent($calendarId, $eventId);






}
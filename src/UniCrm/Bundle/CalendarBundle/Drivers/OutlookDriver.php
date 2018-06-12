<?php

namespace UniCrm\Bundles\CalendarBundle\Drivers;

use UniCrm\Bundles\CalendarBundle\Exceptions\InvalidArgumentException;
use UniCrm\Bundles\CalendarBundle\Interfaces\CalendarDriverInterface;

class OutlookDriver implements CalendarDriverInterface{

    protected $clientId;

    protected $clientSecret;

    protected $redirect_uri;

    protected $token;

    protected $authority = "https://login.microsoftonline.com";

    protected $authorizeUrl = '/common/oauth2/v2.0/authorize?client_id=%1$s&redirect_uri=%2$s&state=%3$s&scope=%4$s&response_type=code';

    protected $tokenUrl = "/common/oauth2/v2.0/token";

    protected $logoutUrl = '/common/oauth2/logout?post_logout_redirect_uri=%1$s';

    protected $outlookApiUrl = "https://outlook.office.com/api/v2.0";

    protected $scopes = "";

    protected $parameters = [];

    public function __construct()
    {
        $this->scopes = implode(' ', ['openid', 'https://outlook.office.com/calendars.readwrite', 'offline_access']);
    }

    public function authenticateWithCode($code = null)
    {
        $token = $this->getTokenFromAuthCode($code);

        $this->setToken($token);

        return $this->getToken();
    }

    public function authenticateWithToken($token = null)
    {
        $this->setToken($token);

        if(true == $this->isTokenExpired($this->token)){

            $newToken = $this->refreshToken($this->token['refresh_token']);

            $this->setToken($newToken);

            //todo event Outlook token refreshed
            return $newToken;
        }
    }

    public function refreshToken($refreshToken)
    {
        // Build the form data to post to the OAuth2 token endpoint
        $token_request_data = [
            "grant_type"    => "refresh_token",
            "refresh_token" => $refreshToken,
            "redirect_uri"  => $this->redirect_uri,
            "scope"         => $this->scopes,
            "client_id"     => $this->clientId,
            "client_secret" => $this->clientSecret
        ];

        $token_request_body = http_build_query($token_request_data);

        $curl = curl_init($this->authority . $this->tokenUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $token_request_body);

//        if ($this->enableFiddler) {
//            // ENABLE FIDDLER TRACE
//            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
//            // SET PROXY TO FIDDLER PROXY
//            curl_setopt($curl, CURLOPT_PROXY, "127.0.0.1:8888");
//        }

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($this->isFailure($httpCode)) {
            return [
                'errorNumber' => $httpCode,
                'error'       => 'Token request returned HTTP error ' . $httpCode
            ];
        }

        // Check error
        $curl_errno = curl_errno($curl);
        $curl_err = curl_error($curl);
        if ($curl_errno) {
            $msg = $curl_errno . ": " . $curl_err;

            return [
                'errorNumber' => $curl_errno,
                'error'       => $msg
            ];
        }

        curl_close($curl);

        // The response is a JSON payload, so decode it into
        // an array.
        $json_vals = json_decode($response, true);

        return $json_vals;
    }

    public function createAuthUrl()
    {
        $loginUrl = $this->authority . sprintf($this->authorizeUrl, $this->clientId, urlencode($this->redirect_uri),
                $this->base64UrlEncode(json_encode($this->parameters)), urlencode($this->scopes));

        return $loginUrl;
    }

    public function isAccessTokenExpired($token)
    {
        // TODO: Implement isAccessTokenExpired() method.
    }

    public function listEvents($calendarId , $optParams)
    {
       //DEFAULT PARAMS
       $defaultOptParams = [
         'timeMin' => date('Y-m-01\TH:i:s'),//current month first date
         'timeMax' => date('Y-m-t\TH:i:s'),//current month last date
       ];


       $mergedParams =  array_merge($defaultOptParams,$optParams);

        // Build the API request URL
        $calendarViewUrl = $this->outlookApiUrl . '/me/' . (!is_null($calendarId) ? 'calendars/' . $calendarId . '/' : '') . 'calendarview?'
            . "startDateTime=" . $mergedParams['timeMin']
            . "&endDateTime=" . $mergedParams['timeMax']
            . '';//&$select=Subject,Start,End,Location

        return $this->makeApiCall($this->token['access_token'], "GET", $calendarViewUrl);
    }

    public function getEvent($calendarId, $eventId, $optParams = [])
    {
        // Build the API request URL
        $eventUrl = $this->outlookApiUrl . '/me/' . (!is_null($calendarId) ? 'calendars/' . $calendarId . '/' : '') . 'events/'.$eventId;

        return $this->makeApiCall($this->token['access_token'], "GET", $eventUrl);
    }

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
    )
    {
        $eventStart->setTimeZone(new \DateTimeZone("UTC"));
        $eventEnd->setTimeZone(new \DateTimeZone("UTC"));

        if ($allDay) {
            $eventStart = clone $eventStart;
            $eventEnd->setTime(0, 0, 0);

            $eventEnd = clone $eventStart;
            $eventEnd->modify('+1 day');
        }

        $tz = $eventStart->getTimezone();

        // Generate the JSON payload
        $event = [
            "Subject" => $eventSummary,
            "Start"   => [
                "DateTime" => $eventStart->format('Y-m-d\TH:i:s\Z'),
                "TimeZone" => $tz->getName()
            ],
            "End"     => [
                "DateTime" => $eventEnd->format('Y-m-d\TH:i:s\Z'),
                "TimeZone" => $tz->getName()
            ],
            "Body"    => [
                "ContentType" => "HTML",
                "Content"     => $eventDescription
            ]
        ];
        if ($location != "") {
            $event['Location'] = [
                "DisplayName" => $location
            ];
        }

        $attendeeAddresses = $eventAttendee;
        if (!is_array($attendeeAddresses)) {
            $attendeeAddresses = array_filter(explode(';', $eventAttendee));
        }
        if (count($attendeeAddresses)) {
            $attendees = [];
            foreach ($attendeeAddresses as $address) {
                if ($address != "") {
                    $attendee = [
                        "EmailAddress" => [
                            "Address" => $address
                        ],
                        "Type"         => "Required"
                    ];

                    $attendees[] = $attendee;
                }
            }

            $event["Attendees"] = $attendees;
        }

        $eventPayload = json_encode($event);

        $createEventUrl = $this->outlookApiUrl . "/me/events";

        $response = $this->makeApiCall($this->token['access_token'], "POST", $createEventUrl, $eventPayload);

        // If the call succeeded, the response should be a JSON representation of the
        // new event. Try getting the Id property and return it.
        if (isset($response['Id'])) {
            return $response['Id'];
        } else {
            return $response;
        }
    }

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
    )
    {
        $eventStart->setTimeZone(new \DateTimeZone("UTC"));
        $eventEnd->setTimeZone(new \DateTimeZone("UTC"));

        if ($allDay) {
            $eventStart = clone $eventStart;
            $eventEnd->setTime(0, 0, 0);

            $eventEnd = clone $eventStart;
            $eventEnd->modify('+1 day');
        }

        $tz = $eventStart->getTimezone();
        // Generate the JSON payload
        $event = [
            "Subject" => $eventSummary,
            "Start"   => [
                "DateTime" => $eventStart->format('Y-m-d\TH:i:s\Z'),
                "TimeZone" => $tz->getName()
            ],
            "End"     => [
                "DateTime" => $eventEnd->format('Y-m-d\TH:i:s\Z'),
                "TimeZone" => $tz->getName()
            ],
            "Body"    => [
                "ContentType" => "HTML",
                "Content"     => $eventDescription
            ]
        ];
        if ($location != "") {
            $event['Location'] = [
                "DisplayName" => $location
            ];
        }

        $attendeeAddresses = $eventAttendee;
        if (!is_array($attendeeAddresses)) {
            $attendeeAddresses = array_filter(explode(';', $eventAttendee));
        }
        if (count($attendeeAddresses)) {
            $attendees = [];
            foreach ($attendeeAddresses as $address) {
                if ($address != "") {
                    $attendee = [
                        "EmailAddress" => [
                            "Address" => $address
                        ],
                        "Type"         => "Required"
                    ];

                    $attendees[] = $attendee;
                }
            }

            $event["Attendees"] = $attendees;
        }

        $eventPayload = json_encode($event);


        $calendarViewUrl = $this->outlookApiUrl . "/me/events/" . $eventId;

        $response = $this->makeApiCall($this->token['access_token'], "PATCH", $calendarViewUrl, $eventPayload);

        // If the call succeeded, the response should be a JSON representation of the
        // new event. Try getting the Id property and return it.
        if (isset($response['Id'])) {
            return $response['Id'];
        } else {
            return $response;
        }
    }

    public function deleteEvent($calendarId, $eventId)
    {

    }


    /**
     * -----------------------------------------------------------
     * OUTLOOK DRIVER EXTRA METHODS
     * -----------------------------------------------------------
     */

    public function getClientId()
    {
        return $this->clientId;
    }

    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return mixed
     */
    public function getRedirectUri()
    {
        return $this->redirect_uri;
    }

    /**
     * @param mixed $redirect_uri
     */
    public function setRedirectUri($redirect_uri)
    {
        $this->redirect_uri = $redirect_uri;
        $this->forceHttps();
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        if (is_string($token)) {
            if ($json = json_decode($token, true)) {
                $token = $json;
            } else {
                // assume $token is just the token string
                $token = array(
                    'access_token' => $token,
                );
            }
        }
        if ($token == null) {
            throw new InvalidArgumentException('invalid json token');
        }
        if (!isset($token['access_token'])) {
            throw new InvalidArgumentException("Invalid token format");
        }

        $this->token = $token;
    }

    /**
     * @param $inputStr
     *
     * @return string
     */
    public static function base64UrlEncode($inputStr)
    {
        return strtr(base64_encode($inputStr), '+/=', '-_,');
    }

    /**
     * @param $inputStr
     *
     * @return string
     */
    public static function base64UrlDecode($inputStr)
    {
        return base64_decode(strtr($inputStr, '-_,', '+/='));
    }

    protected function forceHttps(){
        $replaced =str_replace('http','https', $this->redirect_uri);
        if (strlen($replaced)){
            $this->redirect_uri =  $replaced;
        }
    }

    protected function getTokenFromAuthCode($authCode){

        if (strlen($authCode) == 0) {
            throw new InvalidArgumentException("Invalid code");
        }

        // Build the form data to post to the OAuth2 token endpoint
        $token_request_data = [
            "grant_type"    => "authorization_code",
            "code"          => $authCode,
            "redirect_uri"  => $this->redirect_uri,
            "client_id"     => $this->clientId,
            "client_secret" => $this->clientSecret,
            "scope"         => $this->scopes
        ];

        // Calling http_build_query is important to get the data
        // formatted as Azure expects.
        $token_request_body = http_build_query($token_request_data);

        $curl = curl_init($this->authority . $this->tokenUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $token_request_body);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);


        if ($this->isFailure($httpCode)) {
            return [
                'errorNumber' => $httpCode,
                'error'       => 'Token request returned HTTP error ' . $httpCode
            ];
        }


        // Check error
        $curl_errno = curl_errno($curl);
        $curl_err = curl_error($curl);
        if ($curl_errno) {
            $msg = $curl_errno . ": " . $curl_err;

            return [
                'errorNumber' => $curl_errno,
                'error'       => $msg
            ];
        }

        curl_close($curl);
        // The response is a JSON payload, so decode it into
        // an array.
        $json_vals = json_decode($response, true);

        return $json_vals;
    }

    /**
     * @param $httpStatus
     *
     * @return bool
     */
    public function isFailure($httpStatus)
    {
        // Simplistic check for failure HTTP status
        return ($httpStatus >= 400);
    }

    /**
     * Make an API call.
     *
     * @param      $access_token
     * @param      $method
     * @param      $url
     * @param null $payload
     *
     * @return array|mixed
     * @throws \Exception
     */
    public function makeApiCall($access_token, $method, $url, $payload = null)
    {
        // Generate the list of headers to always send.
        $headers = [
            "User-Agent: php-tutorial/1.0",         // Sending a User-Agent header is a best practice.
            "Authorization: Bearer " . $access_token, // Always need our auth token!
            "Accept: application/json",             // Always accept JSON response.
            "client-request-id: " . $this->makeGuid(), // Stamp each new request with a new GUID.
            "return-client-request-id: true",       // Tell the server to include our request-id GUID in the response
        ];

        $curl = curl_init($url);

        switch (strtoupper($method)) {
            case "GET":
                // Nothing to do, GET is the default and needs no
                // extra headers.
                break;
            case "POST":
                // Add a Content-Type header (IMPORTANT!)
                $headers[] = "Content-Type: application/json";
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
                break;
            case "PATCH":
                // Add a Content-Type header (IMPORTANT!)
                $headers[] = "Content-Type: application/json";
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
                curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
                break;
            case "DELETE":
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            default:
                throw new \Exception("INVALID METHOD: " . $method);
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($curl);

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $curl_errno = curl_errno($curl);
        $curl_err = curl_error($curl);

        if ($httpCode >= 400) {
            return [
                'errorNumber' => $httpCode,
                'error'       => 'Request returned HTTP error ' . $httpCode,
            ];
        }

        if ($curl_errno) {
            $msg = $curl_errno . ": " . $curl_err;
            curl_close($curl);

            return [
                'errorNumber' => $curl_errno,
                'error'       => $msg
            ];
        } else {
            curl_close($curl);

            return json_decode($response, true);
        }
    }

    /**
     * This function generates a random GUID.
     *
     * @return string
     */
    public function makeGuid()
    {
        if (function_exists('com_create_guid')) {
            return strtolower(trim(com_create_guid(), '{}'));
        } else {
            $charid = strtolower(md5(uniqid(rand(), true)));
            $hyphen = chr(45);
            $uuid = substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12);

            return $uuid;
        }
    }

    /**
     * @param $access_token
     *
     * @return bool
     */
    public function isTokenExpired($access_token)
    {
        $events = $this->listEvents(null, []);

        if (!array_key_exists('error', $events)) {
            return false;
        }

        return true;
    }

}
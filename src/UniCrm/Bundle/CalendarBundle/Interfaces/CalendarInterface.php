<?php

namespace UniCrm\Bundles\CalendarBundle\Interfaces;

interface CalendarInterface{

    /**
     * SUPPORTED DRIVERS
     */
    CONST SUPPORTED = [
      'google',
      'outlook'
    ];


    /**
     * RETURNS THE DRIVER INSTANCE
     * @return CalendarDriverInterface
     */
    public function driver();


    /**
     * RETURNS WHETHER THE DRIVER IS SUPPORTED OR NOT
     * @return boolean
     */
    public function _supports($driver = null);


}
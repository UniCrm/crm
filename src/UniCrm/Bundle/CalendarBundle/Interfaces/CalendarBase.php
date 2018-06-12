<?php

namespace UniCrm\Bundles\CalendarBundle\Interfaces;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use UniCrm\Bundles\CalendarBundle\Exceptions\InvalidArgumentException;

abstract  class CalendarBase implements CalendarInterface{

    use ContainerAwareTrait;

    protected static $instances = array();
    protected static $definitions = null;


    public  function _supports($driver = null)
    {
        if ($driver == null || $driver == ''){
            throw  new InvalidArgumentException('Driver Canot be Null');
        }

        if (!in_array($driver , self::SUPPORTED )){
            throw  new InvalidArgumentException('Driver Not Supported');
        }

        return true;
    }


}
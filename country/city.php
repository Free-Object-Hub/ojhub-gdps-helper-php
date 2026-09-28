<?php
error_reporting(E_ALL);
require_once('geoip2.phar');
use GeoIp2\Database\Reader;

function getCity(string $IP) {
    if ($IP == '127.0.0.1')
        return ['Localhost','CurrentPC'];
    $reader = new Reader(__DIR__.'/GeoLite2-City.mmdb');

    $record = $reader->city($IP);

    if ($record->city->name == null)
        $city = 'Unknown';
    else 
        $city = $record->city->name;

    return [$record->country->name, $city];
}
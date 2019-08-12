<?php
/**
 * Math
 * Perform math operations
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Math
{
    /**
     * Calculates the distance between two points (in km)
     *
     * @param array  $point_1  Point 1
     * @param array  $point_2  Point 2
     * @return float
     */
    public static function getDistanceInKilometers(Array $point_1, Array $point_2)
    {
        $latitude_1  = $point_1[0];
        $latitude_2  = $point_2[0];
        $longitude_1 = $point_1[1];
        $longitude_2 = $point_2[1];

        $earth_radius = 6371; // in km

        $sin_latitude         = sin(deg2rad($latitude_2 - $latitude_1));
        $sin_latitude_squared = $sin_latitude * $sin_latitude;

        $sin_longitude         = sin(deg2rad($longitude_2 - $longitude_1));
        $sin_longitude_squared = $sin_longitude * $sin_longitude;

        $cos_latitude_1 = cos($latitude_1);
        $cos_latitude_2 = cos($latitude_2);

        $square_root = sqrt($sin_latitude_squared + ($cos_latitude_1 * $cos_latitude_2 * $sin_longitude_squared));

        return abs(2 * $earth_radius * asin($square_root));
    }


    /**
     * Converts kilometers to miles
     *
     * @param float  $kilometers  Distance in kilometers
     * @return float
     */
    public static function convertKilometersToMiles($kilometers)
    {
        return $kilometers * 0.621371;
    }
}
<?php
/**
 * Modifier_distance_in_km_from
 * Gets the distance (in kilometers) between the variable and a set of coordinates
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_distance_in_km_from extends Modifier
{
    public function index($value, $parameters=array()) {
        if (!isset($parameters[0])) {
            return 'Unknown';
        }

        if (!preg_match(Pattern::COORDINATES, $value, $point_1_matches)) {
            return 'Unknown';
        }

        if (!preg_match(Pattern::COORDINATES, $parameters[0], $point_2_matches)) {
            return 'Unknown';
        }

        $point_1 = array($point_1_matches[1], $point_1_matches[2]);
        $point_2 = array($point_2_matches[1], $point_2_matches[2]);

        return Math::getDistanceInKilometers($point_1, $point_2);
    }
}
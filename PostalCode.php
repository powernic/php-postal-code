<?php
/**
 *  @package    postal_code
 */

/**
 *  Postal Code Range and Distance Calculation
 *
 *  Calculate the distance between postal codes and find all postal codes within a
 *  given distance of a known postal code.
 *
 *  This class has been modified by Jay Williams to work with the GeoNames.org Postal Code database.
 *
 *  Project page: https://github.com/jaywilliams/PHP-PostalCodeCode-Class
 *  Original Project page: https://github.com/Quixotix/PHP-ZipCodeClass-Class
 *
 *  @package    postal_code
 *  @author     Micah Carrick, with modifications by Jay Williams <http://myd3.com>
 *  @copyright  (c) 2011 - Micah Carrick
 *  @version    2.0
 *  @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License v3
 */
class PostalCode
{
    private $country_code;
    private $postal_code;
    private $place_name;
    private $admin_name1;
    private $admin_code1;
    private $admin_name2;
    private $admin_code2;
    private $admin_name3;
    private $admin_code3;
    private $latitude;
    private $longitude;
    private $accuracy;

    public $mysql_table = 'postal_codes';
    public $mysql_conn = false;
    private $mysql_row;

    private $print_name;
    private $location_type;

    const UNIT_MILES = 1;
    const UNIT_KILOMETERS = 2;
    const MILES_TO_KILOMETERS = 1.609344;

    const LOCATION_POSTAL_CODE = 1;
    const LOCATION_PLACE_NAME = 2;

    /**
     *  Constructor
     *
     *  Instantiate a new PostalCode object by passing in a location. The location
     *  can be specified by a string containing a 5-digit postal code, city and
     *  state, or latitude and longitude.
     *
     *  @param  string
     *  @return PostalCode
     */
    public function __construct($location)
    {
        if (is_array($location)) {
            $this->setPropertiesFromArray($location);
            $this->print_name = $this->postal_code;
            $this->location_type = $this::LOCATION_POSTAL_CODE;
        } else {
            $this->location_type = $this->locationType($location);

            switch ($this->location_type) {

                case PostalCode::LOCATION_POSTAL_CODE:
                    $this->postal_code = $this->sanitizePostalCode($location);
                    $this->print_name = $this->postal_code;
                    break;

                case PostalCode::LOCATION_PLACE_NAME:
                    $a = $this->parsePlaceName($location);
                    $this->place_name = $a[0];
                    $this->admin_code1 = $a[1];
                    $this->print_name = $this->place_name;
                    break;

                default:
                    throw new Exception('Invalid location type for '.__CLASS__);
            }
        }
    }

    public function __toString()
    {
        return $this->print_name;
    }

    /**
    *   Calculate Distance using SQL
    *
    *   Calculates the distance, in miles, to a specified location using MySQL
    *   math functions within the query.
    *
    *   @access private
    *   @param  string
    *   @return float
    */
    private function calcDistanceSql($location)
    {
        $sql = 'SELECT 3956 * 2 * ATAN2(SQRT(POW(SIN((RADIANS(t2.latitude) - '
              .'RADIANS(t1.latitude)) / 2), 2) + COS(RADIANS(t1.latitude)) * '
              .'COS(RADIANS(t2.latitude)) * POW(SIN((RADIANS(t2.longitude) - '
              .'RADIANS(t1.longitude)) / 2), 2)), '
              .'SQRT(1 - POW(SIN((RADIANS(t2.latitude) - RADIANS(t1.latitude)) / 2), 2) + '
              .'COS(RADIANS(t1.latitude)) * COS(RADIANS(t2.latitude)) * '
              .'POW(SIN((RADIANS(t2.longitude) - RADIANS(t1.longitude)) / 2), 2))) '
              .'AS "miles" '
              ."FROM {$this->mysql_table} t1 INNER JOIN {$this->mysql_table} t2 ";


        switch ($this->location_type) {

            case PostalCode::LOCATION_POSTAL_CODE:
                // note: postal code is sanitized in the constructor
                $sql .= "WHERE t1.postal_code = '{$this->postal_code}' ";
                break;

            case PostalCode::LOCATION_PLACE_NAME:
                $place_name = @mysql_real_escape_string($this->place_name);
                $admin_code = @mysql_real_escape_string($this->admin_code1);
                $sql .= "WHERE (t1.place_name = '$place_name' AND t1.admin_code1 = '$admin_code') AND t2.postal_code = '$postal_code_to'";
                break;

            default:
                throw new Exception('Invalid location type for '.__CLASS__);
        }

        switch (PostalCode::locationType($location))
        {
            case PostalCode::LOCATION_POSTAL_CODE:
                $postal_code_to = $this->sanitizePostalCode($location);
                $sql .= "AND t2.postal_code = '$postal_code_to'";
                break;
            case PostalCode::LOCATION_PLACE_NAME:
                $a = $this->parsePlaceName($location);
                $place_name = @mysql_real_escape_string($a[0]);
                $admin_code = @mysql_real_escape_string($a[1]);
                $sql .= "AND (t2.place_name = '$place_name' AND t2.admin_code1 = '$admin_code')";
                break;
        }

        $r = @mysql_query($sql);

        if (!$r) {
            throw new Exception(mysql_error());
        }

        if (mysql_num_rows($r) == 0) {
            throw new Exception("Record does not exist calculatitudeing distance between $postal_code_from and $postal_code_to");
        }

        $miles = mysql_result($r, 0);
        mysql_free_result($r);

        return $miles;
    }

    public function getCity()
    {
        if (empty($this->place_name)) $this->setPropertiesFromDb();
        return $this->place_name;
    }

    public function getCounty()
    {
        if (empty($this->admin_name2)) $this->setPropertiesFromDb();
        return $this->admin_name2;
    }

    public function getStateName()
    {
        if (empty($this->admin_name1)) $this->setPropertiesFromDb();
        return $this->admin_name1;
    }

    public function getStatePrefix()
    {
        if (empty($this->admin_code1)) $this->setPropertiesFromDb();
        return $this->admin_code1;
    }

    public function getDbRow()
    {
        if (empty($this->mysql_row)) $this->setPropertiesFromDb();
        return $this->mysql_row;
    }

    /**
    *   Get Distance To Postal Code
    *
    *   Gets the distance to another postal code. The distance can be obtained in
    *   either miles or kilometers.
    *
    *   @param  string
    *   @param  integer
    *   @param  integer
    *   @return float
    */
    public function getDistanceTo($postal_code, $units=PostalCode::UNIT_MILES)
    {
        $miles = $this->calcDistanceSql($postal_code);

        if ($units == PostalCode::UNIT_KILOMETERS) {
            return $miles * PostalCode::MILES_TO_KILOMETERS;
        } else {
            return $miles;
        }
    }

    public function getPostalCodesInRange($range_from, $range_to, $units=1)
    {
        if (empty($this->country_code)) $this->setPropertiesFromDb();

        $sql = "SELECT 3956 * 2 * ATAN2(SQRT(POW(SIN((RADIANS({$this->latitude}) - "
              .'RADIANS(z.latitude)) / 2), 2) + COS(RADIANS(z.latitude)) * '
              ."COS(RADIANS({$this->latitude})) * POW(SIN((RADIANS({$this->longitude}) - "
              ."RADIANS(z.longitude)) / 2), 2)), SQRT(1 - POW(SIN((RADIANS({$this->latitude}) - "
              ."RADIANS(z.latitude)) / 2), 2) + COS(RADIANS(z.latitude)) * "
              ."COS(RADIANS({$this->latitude})) * POW(SIN((RADIANS({$this->longitude}) - "
              ."RADIANS(z.longitude)) / 2), 2))) AS \"miles\", z.* FROM {$this->mysql_table} z "
              ."WHERE postal_code <> '{$this->postal_code}' "
              ."AND latitude BETWEEN ROUND({$this->latitude} - (25 / 69.172), 4) "
              ."AND ROUND({$this->latitude} + (25 / 69.172), 4) "
              ."AND longitude BETWEEN ROUND({$this->longitude} - ABS(25 / COS({$this->latitude}) * 69.172)) "
              ."AND ROUND({$this->longitude} + ABS(25 / COS({$this->latitude}) * 69.172)) "
              ."AND 3956 * 2 * ATAN2(SQRT(POW(SIN((RADIANS({$this->latitude}) - "
              ."RADIANS(z.latitude)) / 2), 2) + COS(RADIANS(z.latitude)) * "
              ."COS(RADIANS({$this->latitude})) * POW(SIN((RADIANS({$this->longitude}) - "
              ."RADIANS(z.longitude)) / 2), 2)), SQRT(1 - POW(SIN((RADIANS({$this->latitude}) - "
              ."RADIANS(z.latitude)) / 2), 2) + COS(RADIANS(z.latitude)) * "
              ."COS(RADIANS({$this->latitude})) * POW(SIN((RADIANS({$this->longitude}) - "
              ."RADIANS(z.longitude)) / 2), 2))) <= $range_to "
              ."AND 3956 * 2 * ATAN2(SQRT(POW(SIN((RADIANS({$this->latitude}) - "
              ."RADIANS(z.latitude)) / 2), 2) + COS(RADIANS(z.latitude)) * "
              ."COS(RADIANS({$this->latitude})) * POW(SIN((RADIANS({$this->longitude}) - "
              ."RADIANS(z.longitude)) / 2), 2)), SQRT(1 - POW(SIN((RADIANS({$this->latitude}) - "
              ."RADIANS(z.latitude)) / 2), 2) + COS(RADIANS(z.latitude)) * "
              ."COS(RADIANS({$this->latitude})) * POW(SIN((RADIANS({$this->longitude}) - "
              ."RADIANS(z.longitude)) / 2), 2))) >= $range_from "
              ."ORDER BY 1 ASC";

        $r = mysql_query($sql);
        if (!$r) {
            throw new Exception(mysql_error());
        }
        $a = array();
        while ($row = mysql_fetch_array($r, MYSQL_ASSOC))
        {
            // TODO: load PostalCode from array
            $a[$row['miles']] = new PostalCode($row);
        }

        return $a;
    }

    private function hasDbConnection()
    {
        if ($this->mysql_conn) {
            return mysql_ping($this->mysql_conn);
        } else {
            return mysql_ping();
        }
    }



    private function locationType($location)
    {
        if (PostalCode::isValidPostalCode($location)) {
            return PostalCode::LOCATION_POSTAL_CODE;
        } elseif (PostalCode::isValidPlaceName($location)) {
            return PostalCode::LOCATION_PLACE_NAME;
        } else {
            return false;
        }
    }

    static function isValidPostalCode($postal_code)
    {
        return preg_match('/^[0-9]{5}/', $postal_code);
    }

    static function isValidPlaceName($location)
    {
        $words = explode(',', $location);

        if (empty($words) || count($words) != 2 || strlen(trim($words[1])) != 2) {
            return false;
        }

        if (!is_numeric($words[0]) && !is_numeric($words[1]))  {
            return true;
        }

        return false;
    }

    static function parsePlaceName($location)
    {
        $words = explode(',', $location);

        if (empty($words) || count($words) != 2 || strlen(trim($words[1])) != 2) {
            throw new Exception("Failed to parse place_name and state from string.");
        }

        $place_name = trim($words[0]);
        $admin_code = trim($words[1]);

        return array($place_name, $admin_code);
    }

    // @access protected
    private function sanitizePostalCode($postal_code)
    {
        return preg_replace("/[^0-9]/", '', $postal_code);
    }

    private function setPropertiesFromArray($a)
    {
        if (!is_array($a)) {
            throw new Exception("Argument is not an array");
        }

        foreach ($a as $key => $value)
        {
            $this->$key = $value;
        }

        $this->mysql_row = $a;
    }

    private function setPropertiesFromDb()
    {
        switch ($this->location_type) {

            case PostalCode::LOCATION_POSTAL_CODE:
                $sql = "SELECT * FROM {$this->mysql_table} t "
                      ."WHERE postal_code = '{$this->postal_code}' LIMIT 1";
                break;

            case PostalCode::LOCATION_PLACE_NAME:
                $sql = "SELECT * FROM {$this->mysql_table} t "
                      ."WHERE place_name = '{$this->place_name}' "
                      ."AND admin_code1 = '{$this->admin_code1}' LIMIT 1";
                break;
        }

        $r = mysql_query($sql);
        $row = mysql_fetch_array($r, MYSQL_ASSOC);
        mysql_free_result($r);

        if (!$row)
        {
            throw new Exception("{$this->print_name} was not found in the database.");
        }

        $this->setPropertiesFromArray($row);
    }
}

<?php
/**
 * Statamic_Validate
 * Provides validation utility functionality for Statamic
 *
 * @author      Mubashar Iqbal
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @copyright   2012 Statamic
 * @link        http://www.statamic.com
 * @license     http://www.statamic.com
 */
class statamic_validate
{
  /**
   * required
   * Checks to see that a given $field's value exists
   *
   * @param array  $data  List of data
   * @param string  $field  Key within the array to check
   * @return boolean
   */
  public static function required($data, $field)
  {
    return isset($data[$field]);
  }


  /**
   * numeric
   * Checks to see if a given $field's value is numeric
   *
   * @param array  $data  List of data
   * @param string  $field  Key within the array to check
   * @return boolean
   */
  public static function numeric($data, $field)
  {
    return (isset($data[$field]) && is_numeric($data[$field]));
  }

  /**
   * date
   * Checks to see if a given $field's value is a date in a given $format
   *
   * @param array  $data  List of data
   * @param string  $field  Key within the array to check
   * @return boolean
   */
  public static function date($data, $field, $format="Y-m-d")
  {
    if (isset($data[$field])) {
      $value = $data[$field];
      $converted = strtotime($value);
      if ($value == date($format, $converted)) {
        return true;
      }
    }

    return false;
  }


  /**
   * folder_slug_exists
   * Checks to see if a given folder $slug exists within any of the given $folders
   *
   * @param array  $folders  List of folders to look through
   * @param string  $slug  Slug to check for
   * @return boolean
   */
  public static function folder_slug_exists($folders, $slug)
  {
    foreach ($folders as $key => $entry) {
      $nslug = substr(Path::clean($entry['slug']), 2);
      if ($nslug == $slug) {
        return true;
      }
    }

    return false;
  }


  /**
   * content_slug_exists
   * Checks to see if a given content $slug exists with any of the given $entries
   *
   * @param array  $entries  List of entries to look through
   * @param string  $slug  Slug to check for
   * @return boolean
   */
  public static function content_slug_exists($entries, $slug)
  {
    foreach ($entries as $key => $entry) {
      //rint " CHECKING {$slug} against {$entry['slug']}";
      if ($entry['slug'] == $slug) {
        return true;
      }
    }

    return false;
  }


  /**
   * _test
   * Unit-testing for this object
   *
   * @return void
   */
  public static function _test()
  {
    $data = array();
    $data['first_name'] = 'John';
    $data['last_name'] = 'Doe';
    $data['valid_age'] = 33;
    $data['invalid_age'] = 'a';
    $data['valid_dob'] = "1970-10-01";
    $data['invalid_dob'] = "1970-14-01";

    // required
    if (self::required($data, 'first_name')) {
      print "\nRequired field: first_name is valid";
    } else {
      print "\nRequired field: first_name is not valid";
    }

    if (self::required($data, 'middle_name')) {
      print "\nRequired field: middle is valid";
    } else {
      print "\nRequired field: middle is not valid";
    }

    // numeric
    if (self::numeric($data, 'valid_age')) {
      print "\nNumeric field: valid_age is valid";
    } else {
      print "\nNumeric field: valid_age is not valid";
    }

    if (self::numeric($data, 'invalid_age')) {
      print "\nNumeric field: invalid_age is valid";
    } else {
      print "\nNumeric field: invalid_age is not valid";
    }

    // date
    if (self::date($data, 'valid_dob')) {
      print "\nDate field: valid_dob is valid";
    } else {
      print "\nDate field: valid_dob is not valid";
    }

    if (self::date($data, 'invalid_dob')) {
      print "\nDate field: invalid_dob is valid";
    } else {
      print "\nDate field: invalid_dob is not valid";
    }
  }
}

// TO TEST
// Statamic_Validate::_test();

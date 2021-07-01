<?php

namespace basic;

class BasicDateTime {

  const HUMAN_DATE_FORMAT = 'M j, Y'; 
  const SQL_DATETIME_FORMAT = 'Y-m-d H:i:s';
  const SQL_DATE_FORMAT = 'Y-m-d';
  const SQL_FIRST_DAY_OF_MONTH_DATETIME_FORMAT = 'Y-m-01 00:00:00'; 
  const MONTH_FORMAT = 'M';
  const CHICAGO_TZ = 'America/Chicago';

  // Transforms UTC date  to clients local TZ
  static function get_client_date_time($date)
  {
    $client_time_zone = $_SESSION['client_time_zone'] ?? self::CHICAGO_TZ;
    
    $new_date_utc = new \DateTime($date, new \DateTimeZone('UTC')); //create date time UTC

    $new_date_client_tz = $new_date_utc->setTimezone(new \DateTimeZone($client_time_zone)); // Change datetime to clients time zone

    return $new_date_client_tz->format(self::SQL_DATETIME_FORMAT);
  }

  // this transformation is to transform a date with TZ and save it to the DB in UTC
  static function get_utc_date_time($date)
  {
    $original_date_obj = new \DateTime($date);
    $utc_date_obj = $original_date_obj->setTimezone(new \DateTimeZone('UTC'));

    return $utc_date_obj->format(self::SQL_DATETIME_FORMAT);
  }

  static function add_months_to_date($month_start_date, $month_to_be_added) 
  {
    $month_first_day = date_create($month_start_date);
    date_add($month_first_day, date_interval_create_from_date_string("$month_to_be_added month"));
    $month_first_day = date_format($month_first_day, self::SQL_FIRST_DAY_OF_MONTH_DATETIME_FORMAT);

    return $month_first_day;
  }

  static function get_month_last_day ($month_date)
  {
    $month_date = date_create($month_date);
    date_add($month_date, date_interval_create_from_date_string("1 month -1 seconds"));
    $month_last_day = date_format($month_date, self::SQL_DATETIME_FORMAT);

    return $month_last_day;
  }

  static function validateDate($date, $format = 'Y-m-d H:i:s')
  {
    $d = \DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
  }

  //Sort files by modification date.
  static function sort_by_mtime($file1, $file2)
  {
    $time1 = filemtime($file1);
    $time2 = filemtime($file2);
    if ($time1 == $time2)
    {
      return 0;
    }
    return ($time1 < $time2) ? 1 : -1;
  }

  static function get_datetime($when = "today")
  {
    // Creates datetime object with todays date and date time 0's
    $todays_date_obj = new \DateTime($when); 
    
    // Returns sql date time format: 2021-06-07 00:00:00
    return $todays_date_obj->format(self::SQL_DATETIME_FORMAT); 
  }
}
?>

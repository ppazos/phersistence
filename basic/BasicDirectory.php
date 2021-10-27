<?php

namespace basic;

class BasicDirectory
{

  static function get_files_info($folder)
  {
    //Param: folder
    //returns: array of files in folder
    $files = array();
    foreach (new \DirectoryIterator($folder) as $file) {
      if ($file->isDot()) continue;

      // Uses modification date as key, enables to sort with krsort.
      $modification_date = $file->getMTime();

      $files[$modification_date]['name'] =  $file->getFilename();
      $files[$modification_date]['size'] =  $file->getSize();
      $files[$modification_date]['last_modify_date'] = gmdate(\basic\BasicDateTime::SQL_DATETIME_FORMAT, $modification_date);
    }
    
    // Sort files is descending order
    krsort($files);

    return $files;
  }
}

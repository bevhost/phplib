<?php
 /*
  * PHP Base Library
  *
  * Copyright (c) 1998-2000 NetUSE AG
  *                    Boris Erdmann, Kristian Koehntopp
  *
  * $Id: csv_table.inc,v 1.2 2000/07/12 18:22:33 kk Exp $
  *
  */

class CSV_Table extends Table {
  var $classname = "CSV_Table";

  ## CSV Tables are very simple. We don't need
  ## row or column openers/closers
  
  function table_open($class = "") {
    return;
  }
  
  function table_close() {
    return;
  }
  
  function table_row_open($row, $data, $class = "") {
    return;
  }
  
  function table_row_close($row) {
    printf("\n");
  }

  ## Cell creator functions
  ## Need to translate " in $val fields into ""...
  function table_cell($row, $cell, $key, $val, $class) {
    printf("\"%s\",", ereg_replace("\"", "\"\"", $val));
  }
  
  function table_heading_cell($col, $val, $class) {
    printf("\"%s\",", ereg_replace("\"", "\"\"", $val));
  }
  
  function table_checkbox_cell($row, $row_key, $data, $class) {
    printf("\"\",");
  }
}

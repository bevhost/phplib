<?php
/*
 * PHP Base Library
 *
 * Copyright (c) 1998-2000 NetUSE AG
 *                    Boris Erdmann, Kristian Koehntopp,
 *                    Jeffrey Galbraith
 *
 * $Id: table.inc,v 1.2 2000/07/12 18:22:35 kk Exp $
 * 
 * History: 990617: Modularized entire table class. Modularity breaks larger
 *                  objects into smaller, autonomous objects in order to 
 *                  exercise OOP requirements of code reuse. This give 
 *                  programmers the ability to use the high-level code, or 
 *                  get down and dirty with the low-level code.
 *                  Everything I have changed should maintain backwards 
 *                  compatibility, except for show_table_heading_row_result(),
 *                  which was unpreventable in order to get the full 
 *                  magnitude of OOP.
 *          990618: Added table_cell_open(), table_cell_close(),
 *                  table_heading_cell_open() and table_heading_cell_close(). 
 *                  Gives better modularity to class.(JSG)
 *          990619: Added $add_extra. If set, calls extra cell functions:
 *                  table_heading_row_add_extra and table_row_add_extra.
 *                  Override these functions in a derived class to add 
 *                  additional cells to a row of output data.
 *          990620: Added column name remapping. This allows the column names
 *                  of a database to be remapped into something more useful,
 *                  or, perhaps, another language. Array variable "map_cols"
 *                  added. (JSG)
 *
 *          090612: Added In Place Editing by Row or Cell. Requires client
 *                  side JavaScript.  Uses oohforms javascript for cell edits
 *                  and server side php validate for row edits.
 */ 

  #==========================================================================
  # Table (class)
  #--------------------------------------------------------------------------
  # Creates an HTML table based on either a PHP array or a
  # database result.
  #==========================================================================
  
class Table
{
  var $classname = "Table";              ## Persistence Support
  var $checkbox_menu = array('Print');
  var $checkbox_form_id = 'ResultsTable';
  var $check = false;                    ## if set, create checkboxes named 
                                         ## to the result of $check[$key]
  var $description = false;
  var $filter = "[A-Za-z][A-Za-z0-9_]*"; ## Regexp: Field names to show
  var $fields;                           ## Array of field names to show
  var $all_fields;			 ## Complete field names list
  var $orig_fields = Array();		 ## Original field name where display field is mapped to another db table.
  var $heading;                          ## if set, create <th> section
  var $add_extra = false;                ## Call extra cell functions
  var $add_total;			 ## Display Total Line
  var $add_insert;			 ## Display Insert/Search Line
  var $add_insert_buttons = "Add,Search";## Buttons to appear in add insert line.
  var $add_extra_search_fields = Array();## Extra Fields for InPlaceSearch (ips)
  var $edit = false;			 ## Allow editing of rows
  var $extra_html;			 ## Add extra HTML to column(s) of cells.
  var $form;				 ## tpl form to hold insert/edit record
  var $js_sent;				 ## has javascript be sent for this form?
  var $map_cols;                         ## remap database columns to new names
  var $funcs = Array();			 ## Group By Aggregator functions for each field
  var $align = Array();			 ## Allow alignment of fields.
  var $format = Array();		 ## php money_format
  var $sortable = '';			 ## Allow headings to have SortCommands to resort output by that column. (DaveB)
  var $trust_the_data = false;		 ## If set to true allows embeded html in the data - dangerous - use with caution
  var $total;				 ## Stores running total of colums
  var $limit = 65;
  var $primary_key = "id";		 ## Primary Key used for Cell Level In Place Editing
  var $ipe_table;			 ## Table which Cell Level In Place Editing to save to.
  var $no_close = '';			 ## Allows table to stay open so more rows can be printed in a second call.
  var $no_open = '';			 ## Allows printing of more rows to a table that is already open. 
  var $primary_field = "id";		 ## default key for add_extra
  var $rowcount = 0;			 ## holds value from db->affected rows or count(array);

  #==========================================================================
  ## Public functions
  #==========================================================================

  function add_hidden_fields($data) 
  {
	foreach ($this->all_fields as $f) {
		if (!in_array($f,$this->fields,TRUE)) {
			if (isset($data[$f])) echo "\n<input type='hidden' name='$f' value='".$data[$f]."'>";
		}
	}
  }


  #==========================================================================
  ## Page functions
  #==========================================================================

  #==========================================================================
  # Function : start
  #--------------------------------------------------------------------------
  # Purpose  : Starts the display of a two-dimensional array in an HTML table
  #            format.
  # Arguments: $ary   - The 2D array to display.
  #            $class - [optional] Used for CSS control.
  # Returns  : The number of rows displayed.
  # Comments : See function: show
  # History  :
  #==========================================================================
  function start($ary, $class="") 
  {
    return ($this->show($ary, $class));
  }

  #==========================================================================
  # Function : start_result
  #--------------------------------------------------------------------------
  # Purpose  : Starts the display of a database query result in an HTML table
  #            format.
  # Arguments: $db    - The database result.
  #            $class - [optional] Used for CSS control.
  # Returns  : The number of rows displayed.
  # Comments : See function: show_result
  # History  :
  #==========================================================================
  function start_result($db, $class="") 
  {
    return ($this->show_result($db, $class));
  }

  #==========================================================================
  # Function : show
  #--------------------------------------------------------------------------
  # Purpose  : Starts the display of a two-dimensional array in an HTML table
  #            format.
  # Arguments: $ary   - The 2D array to diaplay.
  #            $class - [optional] Used for CSS control.
  # Returns  : The number of rows displayed.
  # Comments :
  # History  : 990616 - removed redundant code.(JSG)
  #==========================================================================
  function show($ary, $class="") 
  {
    global $debug;
    
    if (!$this->verify_2d_array($ary))
      return 0;

    $rows = 0;

    if (!$this->no_open) $this->table_open($class);
    if ($this->show_table_heading_row($ary, $class)) {
	echo "<tbody>\n";
	$rows = $this->show_table_rows($ary, $class);
	echo "</tbody>\n";
	$this->show_table_totals_row($rows);
    }
    if (!$this->no_close) $this->table_close($class);
    
    return $rows;
  }
  
  #==========================================================================
  # Function : show_result
  #--------------------------------------------------------------------------
  # Purpose  : Starts the display of a database query result in an HTML table
  #            format.
  # Arguments: $db    - The database result.
  #            $class - [optional] Used for CSS control.
  # Returns  : The number of rows displayed.
  # Comments :
  # History  :
  #==========================================================================
  function show_result($db, $class="") 
  {
   // pdo database can return full dataset in one piece
   if ($db->type=="pdo") return $this->show($db->sth->fetchAll(PDO::FETCH_ASSOC),$class);

   if (!$this->add_insert) {
    if (!$this->verify_db($db))
      return 0;
   }

    $rows = 0;

    $this->rowcount = $db->affected_rows();

    if (!$this->no_open) $this->table_open($class);
	if ($this->show_table_heading_row_result($db, $class)) {
		echo "<tbody>\n";
      		$rows = $this->show_table_rows_result($db, $class);  
		echo "</tbody>\n";
		$this->show_table_totals_row($rows);
	}
	
    if (!$this->no_close) $this->table_close($class);
    
    return $rows;
  }

  #==========================================================================
  # Function : show_page
  #--------------------------------------------------------------------------
  # Purpose  : Starts the display of a two-dimensional array in an HTML table
  #            format. Only the rows $start to $start+$num are displayed.
  # Arguments: $ary   - The 2D array to display.
  #            $start - The starting row to display.
  #            $num   - The number of rows to display.
  #            $class - [optional] Used for CSS control.
  # Returns  : The number of rows displayed.
  # Comments :
  # History  :
  #==========================================================================
  function show_page($ary, $start, $num, $class ="") 
  {
    global $debug;
    
    if (!$this->verify_2d_array($ary))
      return 0;

    $rows = 0;
    
    $this->table_open($class);
	if ($this->show_table_heading_row($ary, $class))
      $rows = $this->show_table_page_rows($ary, $start, $num, $class="");
    $this->table_close($class);
    
    return $rows;
  }

  #==========================================================================
  # Function : show_result_page
  #--------------------------------------------------------------------------
  # Purpose  : Starts the display of a database object in an HTML table
  #            format. Only the rows $start to $start+$num are displayed.
  # Arguments: $db    - The database result.
  #            $start - The starting row to display.
  #            $num   - The number of rows to display.
  #            $class - [optional] Used for CSS control.
  # Returns  : The number of rows displayed.
  # Comments :
  # History  :
  #==========================================================================
  function show_result_page($db, $start, $num, $class="") 
  {
    global $debug;
    
    if (!$this->verify_db($db))
      return 0;

    $rows = 0;

    $this->table_open($class);
    if ($this->show_table_heading_row_result($db, $class))
		$rows = $this->show_table_page_rows_result($db, $start, $num, $class);
    $this->table_close($class);
    
    return $rows;
  }



  #==========================================================================
  ## Row functions
  #==========================================================================

  #==========================================================================
  # Function : show_table_heading_row
  #--------------------------------------------------------------------------
  # Purpose  : Uses the passed array to create an HTML header row.
  # Arguments: $ary   - The array to use.
  #            $class - [optional] Used for CSS control.
  # Returns  : 1 on success, 0 on error.
  # Comments :
  # History  :
  #==========================================================================
  function show_table_heading_row($ary, $class="")
  {
    if (!$this->verify_2d_array($ary))
      return 0;
      
    if (isset($this->heading) && $this->heading)
    {
      reset($ary);
      list($key, $val) = each($ary);
      $this->table_heading_row($val, $class);
      if (isset($this->add_insert))
       $this->table_insert_row($val, $class);
    }

    return 1;
  }
  
  # Function : show_table_heading_row_result
  #--------------------------------------------------------------------------
  # Purpose  : Uses the passed database object to create an HTML header row.
  # Arguments: $db    - The database object
  #            $class - [optional] Used for CSS control.
  # Returns  : 1 on success, 0 on error.
  # Comments :
  # History  :
  #==========================================================================
  function show_table_heading_row_result($db, $class="")
  {
   if (!$this->add_insert) {
    if (!$this->verify_db($db))
      //return 0;
	{}
   }
    if ($this->heading)
    {
      if ($db->next_record())
      {
        $this->table_heading_row($db->Record, $class);
        if (isset($this->add_insert))
          $this->table_insert_row($db->Record, $class);
#        $db->seek($db->Row-1);	// changed function show_table_rows_result() so this is not required
	if ($this->check) 
	  echo "<form method='post' name='$this->checkbox_form_id' id='$this->checkbox_form_id'>\n";
        return 1;
      }
      else
      {
	if (isset($this->add_insert))
          $this->table_insert_row($db->Record, $class);
	if ($this->check) 
	  echo "<form method='post' id='$this->checkbox_form_id'>\n";
        return 0;
      }
    }
    return 1;
  }
  


  #==========================================================================
  # Function : table_heading_row
  #--------------------------------------------------------------------------
  # Purpose  : Outputs HTML code to create a table heading row.
  # Arguments: $data  - The array of data that represents cells within a row.
  #            $class - [optional] Used for CSS control.
  # Returns  :
  # Comments :
  # History  : 990618 - Fixed return on select_colnames (JSG).
  #==========================================================================
  function table_heading_row($data, $class="") 
  {
    if (!is_array($data))
      return;

    $d = $this->select_colnames($data);

    $row = 0;
    echo "<thead>\n";
    $this->table_row_open($row, $d, $class);
    $this->set_checkbox_heading($class);
    $this->show_table_heading_cells($data, $class);

    # call virtual function
    if ($this->add_extra)
      $this->table_heading_row_add_extra($data, $class);

    $this->table_row_close(0, $class);
    echo "</thead>\n";
  }

  function table_insert_row($data, $class="")
  {
    if (!is_array($data))
    {
#	echo "<script>alert('no data')</script>";
#      return;
	$data = $this->map_cols;
	$this->table_heading_row($data);
    }

    $d = $this->select_colnames($data);

    $row=0;
    $this->table_row_open($row, $d, $class);
    $this->table_form_start($this->add_insert);
	echo "\n";
    $this->set_checkbox_insert($class);
    $this->show_table_insert_cells($data, "insert_row $class");

    if ((isset($this->all_fields)) and (isset($this->default_values))) {
	$this->add_hidden_fields($this->default_values);
    }

    # call virtual function
    // if ($this->add_extra)
      $this->table_insert_row_add_extra($data, $class);

    $this->table_form_finish($this->add_insert);
    $this->table_row_close(0, $class);
  }

  function show_table_insert_cells($data, $class="")
  {
    global $debug;

    if ($debug)
      printf("<p>show_table_insert_cells()<br>\n");

    if (!$this->verify_array($data))
      return 0;

    $col = 0;
    $d = $this->select_colnames($data);

    ## Create regular cells
    reset($d);
    while(list($key, $val) = each($d))
    {
      $this->table_insert_cell($col++, $val, $class);
    }

    return 1;
  }


  function table_insert_cell($col, $val, $class)
  {
   global $PHP_SELF, $DOCUMENT_ROOT;
    $align=""; $title="";
    $this->table_cell_open($class, $align, $title);
    if (array_key_exists($val,$this->orig_fields)) {
	$val = $this->orig_fields[$val];
    }
    if (preg_match("/Search/",$this->add_insert_buttons)) {
	$this->form->form_data->elements[$val]["ob"]->class="ips";
	$this->form->form_data->elements[$val]["ob"]->value=""; // Prevent default value from showing.
    	if (isset($this->extra_html[$val])) $this->form->form_data->elements[$val]["ob"]->extrahtml=$this->extra_html[$val];
	else $this->form->form_data->elements[$val]["ob"]->extrahtml = "";
    } else {
	$this->form->form_data->elements[$val]["ob"]->class="ipi";
    	if (isset($this->extra_html[$val])) $this->form->form_data->elements[$val]["ob"]->extrahtml=$this->extra_html[$val];
    }
    if (array_key_exists($val,$_POST)) $this->form->form_data->elements[$val]["ob"]->value = $_POST[$val];
    $postval = array_key_exists($val,$_POST) ? $_POST[$val] : "";
#echo "<!-- $val = $postval -->";
    $this->form->form_data->show_element($val);
    $this->table_cell_close($class);
  }

  function table_form_start($form) 
  {
	$this->form = new $form;
#echo "<!-- form: $form -->";
	$this->form->setup("inside_table",$this->js_sent[$form], $this->ipe_table ? $this->classname : false );
	$this->form->form_data->start($form);
  }

  function table_form_finish($form)
  {
        if (!$this->js_sent[$form]) {
                $this->js_sent[$form] = 1;
                $this->form->form_data->finish();
        } else {
                echo "<input type='hidden' name='form_name' value='$form'></form>";
        }
  }

  function table_insert_row_add_extra($data,$class)
  {
	global $rowcount, $startingwith, $sortorder, $export_results;
	if (!$startingwith) $startingwith = 0;
	if (!$rowcount) $rowcount = 100;
        $buttons = explode(',',$this->add_insert_buttons);
	if (!$class) $class='btable';
        echo "<td class='$class'>";
        $this->form->form_data->elements["submit"]["ob"]->extrahtml = "onclick='this.form.onsubmit=\"\"'";
	foreach($buttons as $button) {
        	$this->form->form_data->show_element('submit',$button);
	}
        echo "</td>";
	$custom_query = array_key_exists("custom_query",$_POST) ? $_POST["custom_query"] : "";
    	if (preg_match("/Search/",$this->add_insert_buttons)) {
		echo "\n   <input type='hidden'  name='sortorder' id='ips_sort_order' value='$sortorder'>";
		echo "\n   <input type='hidden'  name='rowcount' id='ips_row_count' value='$rowcount'>";
		echo "\n   <input type='hidden'  name='startingwith' id='ips_starting_with' value='$startingwith'>";
		echo "\n   <input type='hidden'  name='custom_query' id='ips_custom_query' value='$custom_query'>";
		echo "\n   <input type='hidden'  name='export_results' id='ips_export_results' value='$export_results'>";
	}
	foreach ($this->add_extra_search_fields as $sf) {
		$sfv = array_key_exists($sf,$_POST) ? $_POST[$sf] : "";
		echo "\n   <input type='hidden'  name='$sf' id='ips_$sf' value='$sfv'>";
	}
  }

  #==========================================================================
  # Function : show_table_rows
  #--------------------------------------------------------------------------
  # Purpose  : Walks the passed array displaying each row of data as an HTML
  #            table row.
  # Arguments: $ary   - The array of data to display.
  #            $class - [optional] Used for CSS control.
  # Returns  :
  # Comments :
  # History  :
  #==========================================================================
  function show_table_rows($ary, $class="")
  {
    global $debug;
    
    if ($debug)
      printf("<p>show_table_rows()<br>\n");

    if (!$this->verify_2d_array($ary))
      return 0;
      
    $row = 0;
      
    reset($ary);
    while(list($key, $val) = each($ary))
    {
      ## Process a single row
      $this->table_row($row++, $key, $val, $class);
    }
    
    return $row;
  }

  #==========================================================================
  # Function : show_table_rows_result
  #--------------------------------------------------------------------------
  # Purpose  : Walks the passed database object displaying each record as an 
  #            HTML table row.
  # Arguments: $db    - The database object
  #            $class - [optional] Used for CSS control.
  # Returns  :
  # Comments :
  # History  : 990617 - fixed return. Was "row" changed to "$row".
  #==========================================================================
  function show_table_rows_result($db, $class="")
  {
    global $debug;
    
    if ($debug)
      printf("<p>show_table_rows_result()<br>\n");

    if (!$this->verify_db($db))
      return 0;
    
    $row = 0;
    
    if ($this->heading) # We already retrieved the first row of data to get the col names
    {
      $this->table_row($row, $row, $db->Record, $class);	// better show that first row
      $row++;
    }
    
    while($db->next_record())					// show the rest of the rows
    {
      ## Process a table row
      $this->table_row($row, $row, $db->Record, $class);
      $row++;
    }
    
    return $row;
  }


  function show_table_totals_row($row) 
  {
    $class = "total";
    $this->table_row_open($row, $this->total);    
    $this->set_checkbox_total();
    if ($this->add_total) 
	$this->show_table_cells(0,0,$this->total,$class);
    $this->table_row_close($row);    
  }

  
  #==========================================================================
  # Function : show_table_page_rows
  #--------------------------------------------------------------------------
  # Purpose  : Walks the passed array displaying each row of data as an HTML
  #            table row. However, data does not start displaying until
  #            $start element and end after $num rows.
  # Arguments: $ary   - The array object.
  #            $start - Start row displaying at this element.
  #            $num   - The number of rows to display.
  #            $class - [optional] Used for CSS control.
  # Returns  :
  # Comments :
  # History  : 990616 - $row was incrementing (++) in for loop and within
  #                     the table_row function call.
  #==========================================================================
  function show_table_page_rows($ary, $start, $num, $class="")
  {
    global $debug;
    
    if ($debug)
      printf("<p>show_table_page_rows()<br>\n");

    if (!$this->verify_2d_array($ary))
      return 0;
      
    $row = 0;
    
    $max = count($ary);
    if (($start < 0 ) || ($start > $max))
      return 0;
    $max = min($start+$num, $max);
      
    for ($row = $start; $row < $max; $row++)
    {
      ## Process a single row
      $this->table_row($row, $key, $val, $class);
    }
    
    return ($row - $start);
  }
  
  #==========================================================================
  # Function : show_table_page_rows_result
  #--------------------------------------------------------------------------
  # Purpose  : Walks the passed database object displaying each record as an 
  #            HTML table row. However, data does not start displaying until
  #            $start record and ends after $num records have been displayed.
  # Arguments: $db    - The database object.
  #            $start - Start row displaying at this record.
  #            $num   - The number of rows to display.
  #            $class - [optional] Used for CSS control.
  # Returns  : The number of rows displayed
  # Comments :
  # History  :
  #==========================================================================
  function show_table_page_rows_result($db, $start, $num, $class="")
  {
    global $debug;
    
    if ($debug)
      printf("<p>show_table_page_rows_result()<br>\n");

    if (!$this->verify_db($db))
      return 0;
    
    $row = $start;
    $fin = $start + $num;

    $db->seek($start);
    while($db->next_record() && ($row < $fin))    
    {
      ## Process a table row
      $this->table_row($row, $row, $db->Record, $class);
      $row++;
    }
    
    return ($row - $start);
  }

  #==========================================================================
  # Function : table_row
  #--------------------------------------------------------------------------
  # Purpose  : Outputs HTML code to create a table row. Calls all of the 
  #            cell-related functions.
  # Arguments: $row     -
  #            $row_key -
  #            $data    - The array of data that represents cells within a row.
  #            $class   - [optional] Used for CSS control.
  # Returns  :
  # Comments :
  # History  :
  #==========================================================================
  function table_row($row, $row_key, $data, $class="") 
  {
    global $debug;
    
    if ($debug)
      printf("<p>table_row()<br>\n");

    $d = $this->select_colnames($data);    
    if ($this->edit) $this->table_form_start($this->edit);
    $this->table_row_open($row, $d, $class);    

    $this->set_checkbox($row, $row_key, $data, $class);
    $this->show_table_cells($row, $row_key, $data, $class);

    # call virtual function
    if ($this->add_extra)
      $this->table_row_add_extra($row, $row_key, $data, $class);

    $this->table_row_close($row, $class);
    if ($this->edit) $this->table_form_finish($this->edit);
  }
  


  #==========================================================================
  ## Field/Cell functions
  #==========================================================================
  
  #==========================================================================
  # Function : set_checkbox_heading
  #--------------------------------------------------------------------------
  # Purpose  : This function creates an empty header cell to coincide with
  #            the checkbox option for that column.
  # Arguments: $class   - [optional] Used for CSS control.
  # Returns  :
  # Comments :
  # History  :
  #==========================================================================
  function set_checkbox_heading($class="")
  {
    global $debug;
    
    if ($debug)
      printf("<p>set_checkbox_heading()<br>\n");

    ## Checkbox handling...
    if ($this->check)
      $this->table_heading_cell(0, " ", $class);
  }
  function set_checkbox_insert($class="")
  {
	$this->set_checkbox_heading();
  }
  function set_checkbox_total($class="")
  {
	$this->set_checkbox_heading();
  }

  #==========================================================================
  # Function : set_checkbox
  #--------------------------------------------------------------------------
  # Purpose  : Creates an HTML checkbox based on the passed data, only if
  #            the member variable $check is set.
  # Arguments: $row     - The row number.
  #            $row_key - The row key.
  #            $data    - The data array.
  #            $class   - [optional] Used for CSS control.
  # Returns  :
  # Comments :
  # History  :
  #==========================================================================
  function set_checkbox($row, $row_key, $data, $class="")
  {
    global $debug;
    
    if ($debug)
      printf("<p>set_checkbox()<br>\n");

    ## Checkbox handling...
    if ($this->check)
      $this->table_checkbox_cell($row, $row_key, $data, $class);
  }

  #==========================================================================
  # Function : show_table_heading_cells
  #--------------------------------------------------------------------------
  # Purpose  : Walks the passed array and displays each item in an HTML table
  #            header cell.
  # Arguments: $data    - The data array.
  #            $class   - [optional] Used for CSS control.
  # Returns  : 1 on success, 0 on error.
  # Comments :
  # History  : 990618 - Fixed problem with filtering headers (JSG).
  #==========================================================================
  function show_table_heading_cells($data, $class="") 
  {
    global $debug;
    
    if ($debug)
      printf("<p>show_table_heading_cells()<br>\n");

    if (!$this->verify_array($data))
      return 0;
  
    $col = 0;
    $d = $this->select_colnames($data);
      
    ## Create regular cells
    reset($d);
    while(list($key, $val) = each($d)) 
    {
      $this->table_heading_cell($col++, $val, $class);
    }

    return 1;
  }
  
  #==========================================================================
  # Function : show_table_cells
  #--------------------------------------------------------------------------
  # Purpose  : Walks the passed array and displays each item in an HTML table
  #            cell.
  # Arguments: $row     - The row number.
  #            $row_key - The row key.                  [for derived classes]
  #            $data    - The data array.
  #            $class   - [optional] Used for CSS control.
  # Returns  : 1 on success, 0 on error.
  # Comments :
  # History  :
  #==========================================================================
  function show_table_cells($row, $row_key, $data, $class="") 
  {
    global $debug;
    
    if ($debug)
      printf("<p>show_table_cells()<br>\n");

    if (!$this->verify_array($data))
      return 0;

    $cell = 0;
    $d = $this->select_colnames($data);
      
    ## Create regular cells
    reset($d);
    while(list($key, $val) = each($d)) 
    {
	$comment = "";
	$align = "left";

	$output = $this->format_table_cell($val,$data,$align,$comment,$class);

	if ($output==0 and $class=="total" and $output<>"TOTAL") $output=" ";

	if (@array_key_exists($val,$this->align)) $align = $this->align[$val];

	if (array_key_exists($this->primary_key,$data)) $index=$data[$this->primary_key]; else $index=false;

	$this->table_cell($row, $cell++, $val, $output, $class, $align, $comment, $index);
    }

    return 1;
  }

  #==========================================================================
  # Function : format_table_cell
  #--------------------------------------------------------------------------
  # Purpose  : Provides a hook to allow the formatting of data in a cell.
  #		attempts to have sensible default formatting by guessing data type.
  # Arguments: $val     - The column name.
  #            $data    - The data array.
  #            $align   - The cell alignment (left, center, right).
  #            $class   - HTML Title for the cell.
  # Returns  : formatted string
  # Comments : should be overriden by user class to provide extra functionality.
  # History  : used ot be built in to show_table_cells
  #==========================================================================
  function format_table_cell($val,$data,&$align,&$comment,&$class) {
	$dval = array_key_exists($val,$data) ? str_replace("\r","",$data[$val]) : "";
	if ((!empty($dval)) and (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $dval, $regs))) {
		$output = date("d M Y",strtotime($dval));   /* DD Mmm YYYY */
		$comment = substr($dval,11,10);
	} else if ((!empty($dval)) and (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/", $dval, $regs))) {
	   if (date("Y-m-d") == substr($dval,0,10)) {
		// Date Field (Today)
      		$output = substr($dval,11,10);
	   } else {
		// Date Field (Not Today)
		switch ($val) {
			case "Updated":
			case "AcctStartTime":
				$output = sprintf("%s/%s %s:%s",$regs[3],$regs[2],$regs[4],$regs[5],$regs[6]);
				break;
			case "AcctStopTime":
				$output = substr($dval,11,5);
				break;
      			default: 
				// $output = substr($dval,0,10);  /* YYYY-MM-DD */
				$output = date("d M Y",strtotime($dval));   /* DD Mmm YYYY */
				$comment = substr($dval,11,10);
		}
	   }
	   $this->total[$val]=0;
	} else if ((!empty($dval)) and (preg_match("/([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}/",$dval, $regs))) {
		// IPv4 Address
		$align="center";
		$output = $dval;
	} else if ((isset($dval)) and ($dval<>".") and (preg_match("/(^[$|-]*[0-9|,|.]+$)/",$dval, $regs))) {
		// Numeric Field
		if (!isset($this->total[$val])) $this->total[$val]=0;
		$number = str_replace(",","",str_replace("$","",$dval));
		$this->total[$val] += $number;
		switch ($val) {
			case "AcctSessionTime":
				$align = "right";
				$output=TimeStr($dval);
				break;
			case "AcctOutputOctets":
			case "AcctInputOctets":
				$output=ByteStr($dval);
				break;
      			default: 
				$align="right";
				if (array_key_exists($val,$this->format)) {
					if ($fmat=$this->format[$val]) $output = money_format($fmat,$number);
					else $output=$dval;
				} else {	
					if (strpos($number,".")) $output = number_format($number,2);
					else switch($val) {
						case "MerchantID":
						case "Mobile":
						case "WorkPhone":
						case "HomePhone":
						case "Fax":
						case "PostCode":	// don't format these columns
							$output=$dval;
							break;
						default:
							$output = number_format($number);
					}
				}
				$vval = " $val";
				if (strpos($vval,"time") or strpos($vval,"date")) $comment = @date("Y-m-d H:i:s",$number);
		}
	} else { 
		// Normal Field
		$output = $dval;
	}

	$output = str_replace("(TEST TRANSACTION ONLY)","<b>(TEST)</b>",$output);

	return $output;
  }	


  #==========================================================================
  # Function : table_cell
  #--------------------------------------------------------------------------
  # Purpose  : Outputs HTML code to render a single cell.
  # Arguments: $row   - The row number.                 [for derived classes]
  #            $col   - The column number.              [for derived classes]
  #            $key   - The key value.                  [for derived classes]
  #            $val   - The data value.
  #            $class - [optional] Used for CSS control.
  #	       $align - [optional] Alignment
  # Returns  : Nothing
  # Comments :
  # History  :
  #==========================================================================
  function table_cell($row, $col, $key, $val, $class="", $align="", $comment="", $index) 
  {
    global $debug;
	if ($debug) echo "table_cell($val)";
   global $PHP_SELF, $DOCUMENT_ROOT;
    $title="";
    $shown = false;
    if (array_key_exists($key,$this->orig_fields)) $key = $this->orig_fields[$key];
    if (isset($this->total[$key])) $title=number_format($this->total[$key]);
    if ($comment) $title = $comment;
    $this->table_cell_open($class, $align, $title, $key, $index);
    if (!empty($this->edit)) {
/*
	if ($this->form->form_data->elements[$key]["ob"]->multiple) $val = explode(",",$val);
*/
	$this->form->form_data->elements[$key]["ob"]->value=$val;
	$this->form->form_data->elements[$key]["ob"]->class="ipe";
	
	$this->form->form_data->elements[$key]["ob"]->extrahtml = " ".str_replace('$index',"$index",$this->form->form_data->elements[$key]["ob"]->extrahtml);

    	if (isset($this->extra_html[$key])) $this->form->form_data->elements[$key]["ob"]->extrahtml = " ".$this->extra_html[$key];

	if (isset($this->form->form_data->elements[$key]["ob"]->rows)) {
		$this->form->form_data->elements[$key]["ob"]->rows = substr_count($val,"\n")+1;
	}
	if ($this->form->form_data->elements[$key]["ob"]->multiple) {
		foreach ($this->form->form_data->elements[$key]["ob"]->options as $option) {
			$this->form->form_data->show_element($key,$option);
			echo " $option<br>\n";
		}
	} else $this->form->form_data->show_element($key,$val);
    } else {
        if (strlen($val)>$this->limit+5) {$val = substr($val,0,$this->limit)." ...";}
	if ((isset($this->map_links)) and ($this->verify_array($this->map_links)) and array_key_exists($key,$this->map_links) and ($target = $this->map_links[$key])) {
                printf("<a href=\"%s%s\">%s</a>",$target,$val,htmlspecialchars($val,ENT_COMPAT,"UTF-8"));
        } else {
		if ($this->trust_the_data) 
			echo $val;
		else
			echo htmlspecialchars($val,ENT_COMPAT,"UTF-8");
	}
    }
    $this->table_cell_close($class);
  }
  
  function table_cell_open($class="", $align="", $title="")
  {
    printf("  <td%s%s>", 
      $class?" class='$class align$align'":"",
      $title?" title='$title'":"");
  }

  function table_cell_close($class="")
  {
    printf("</td>\n");
  }

  #==========================================================================
  # Function : table_heading_cell
  #--------------------------------------------------------------------------
  # Purpose  : Outputs HTML code to render a single header cell.
  # Arguments: $col   - The column number.              [for derived classes]
  #            $val   - The data value.
  #            $class - [optional] Used for CSS control.
  # Returns  : Nothing
  # Comments :
  # History  : 990620 - Added column remapping.
  #==========================================================================
  function table_heading_cell($col, $val, $class="") 
  {
    $align=" alignleft";
    $func = "";
    if (array_key_exists($val,$this->align)) $align = " align".$this->align[$val];
    if (array_key_exists($val,$this->funcs)) $func = strtoupper($this->funcs[$val]).":";
    $this->table_heading_cell_open($class,$align);

    ## Check for column name remapping
    if ($this->verify_array($this->map_cols))
    {
      reset($this->map_cols);
      while(list($key, $name) = each($this->map_cols)) 
      {
        if ($key == $val)
        {
          $val = $name;
          $found = 1;
          break;
        }
      }
    }
    if (!$class) $class='toplink';
    global $sess, $sortorder;
    if (isset($key) and ($key==$sortorder)) $key .= " desc";
    if ($this->sortable) {
	if (isset($this->add_insert))
		printf("<a class='%s' href='javascript:showSortedBy(\"%s\")'>%s</a>", $class, $key, $func.$val);
	else 
		printf("<a class='%s' href='%s'>%s</a>", $class, $this->table_heading_sortable_link($key), $func.$val);
    } else {
	printf("%s", $val);
    }
    $this->table_heading_cell_close($class);
  }

  
  #==========================================================================
  # Function : table_heading_sortable_link
  #--------------------------------------------------------------------------
  # Purpose  : returns a link to the current program with sortorder variable
  # Arguments: $key - column to sort the table by
  # Returns  : href link
  # Comments : Can be overriden, to provide mod_rewrite links instead
  # History  : 
  #==========================================================================

  function table_heading_sortable_link($key) {
        global $sess;
        return $sess->self_url().$sess->add_query(array("sortorder"=>$key));
  }

  
  #==========================================================================
  # Function : table_heading_cell_open
  #--------------------------------------------------------------------------
  # Purpose  : Starts a header cell.
  # Arguments: $class - [optional] Used for CSS control.
  # Returns  : Nothing
  # Comments : Low-level function for table_heading_cell()
  # History  : 
  #==========================================================================
  function table_heading_cell_open($class="", $align="")
  {
        printf("  <th class='$class$align'>");  
  }
  
  #==========================================================================
  # Function : table_heading_cell_close
  #--------------------------------------------------------------------------
  # Purpose  : Ends a header cell.
  # Arguments: $class - [optional] Used for CSS control.
  # Returns  : Nothing
  # Comments : Low-level function for table_heading_cell()
  # History  : 
  #==========================================================================
  function table_heading_cell_close($class="") 
  {
    printf("</th>\n");
  }
  
  #==========================================================================
  # Function : table_checkbox_cell
  #--------------------------------------------------------------------------
  # Purpose  : Outputs HTML code to display a checkbox. This function runs
  #            if the member variable $check has been set. $check should be
  #            set to some key within the $data array (ex: if $data["myKey"],
  #            then set $check="myKey").
  # Arguments: $row     - The row currently being written.
  #            $row_key - If $data[$this-check] is empty, then this variable
  #                       is used instead.
  #            $data    - An array of data information.
  #            $class   - [optional] Used for CSS control.
  # Returns  :
  # Comments : 
  # History  :
  #==========================================================================
  function table_checkbox_cell($row, $row_key, $data, $class="") 
  {
    if ($this->check)
    {
      printf("  <td%s nowrap><input type=\"checkbox\" name=\"%s[%s]\" value=\"%s\"></td>\n",
        $class?" class=$class":"",
        $this->check,
        $row,
        empty($data[$this->check])?$row_key:$data[$this->check]);
    }
  }

  #==========================================================================
  ## Utility functions (used to be in util.inc, but were used only here and
  ## did create a lot of confusion on installation) -- KK
  #==========================================================================

  #==========================================================================
  # Function : verify_array
  #--------------------------------------------------------------------------
  # Purpose  : Verifies an array
  # Arguments: $ary   - The array to verify.
  # Returns  : 1 on success, 0 on error.
  # Comments : 
  # History  : 
  #==========================================================================
  function verify_array($ary)
  {
    if (!is_array($ary))
      return 0;
    
    return 1;
  }
    
  #==========================================================================
  # Function : verify_2d_array
  #--------------------------------------------------------------------------
  # Purpose  : Verifies a 2D array
  # Arguments: $ary   - The array to verify.
  # Returns  : 1 on success, 0 on error.
  # Comments : 
  # History  : 990616 - Removed "$this->" from "verify_array". (JSG)
  #==========================================================================
  function verify_2d_array($ary)
  {
    if (!$this->verify_array($ary))
      return 0;

    $this->rowcount = count($ary);
    
    reset($ary);
    if (!is_array(current($ary)))
      return 0;

    reset($ary);
    
    return 1;
  }
    
  #==========================================================================
  # Function : verify_db
  #--------------------------------------------------------------------------
  # Purpose  : Verifies a database object for results.
  # Arguments: $db   - The database object to verify.
  # Returns  : 1 on success, 0 on error.
  # Comments : 
  # History  : 
  #==========================================================================
  function verify_db($db)
  {
    if (!isset($db) && !$db)
      return 0;
    
   // if ($db->num_rows() > 0)    # in pdo, this does not work for all databases.
    if (is_object($db)) 
      return 1;   

    return 0;
  }

  ## Debugging function that prints an array
  ##   Recursive is_array found within array
  function print_array($ary)
  {
    if (is_array($ary))
    {
      while(list($key, $val) = each($ary))
      {
        echo "&nbsp;&nbsp;$key = $val<br>\n";
        if (is_array($val))
          print_array($val);
      }
    }
  }

  #==========================================================================
  ## Helper functions
  #==========================================================================
  
  #==========================================================================
  # Function : select_colnames
  #--------------------------------------------------------------------------
  # Purpose  : Selects the column names that should be displayed in an HTML
  #            table. This is based on the $fields variable, if set. If it
  #            is not set, then all fields names are used. This is how you
  #            filter displayed data.
  # Arguments: $data - A array containing information about the column 
  #                    names. If $fields is not used, then this variable is
  #                    used instead.
  # Returns  : An array containing the column names.
  # Comments :
  # History  :
  #==========================================================================
  function select_colnames($data) 
  {
    global $debug;
    
    if ($debug)
      printf("<p>select_colnames()<br>\n");

    if (!is_array($this->fields) && is_array($data)) 
    {
      reset($data);
      while(list($key, $val) = each($data)) 
      {
        if (preg_match("/".$this->filter."/", $key))
          $this->fields[] = $key;
      }
    } 
    $d = $this->fields;

    if ($debug)
    {
      var_dump($d);
      printf("select_colnames() return<br>");
    }
    
    return $d;
  }

  #==========================================================================
  # Misc. functions
  #==========================================================================

  #--------------------------------------------------------------------------
  ## The following functions provide a very basic rendering
  ## of a HTML table with CSS class tags. Table is useable
  ## with them or the functions can be overridden for a
  ## more complex functionality.
  #--------------------------------------------------------------------------

  #--------------------------------------------------------------------------
  ## Table open and close functions.
  #--------------------------------------------------------------------------

  #==========================================================================
  # Function : table_open
  #--------------------------------------------------------------------------
  # Purpose  : Outputs HTML code to open a table.
  # Arguments: $class - [optional] Used for CSS control.
  # Returns  : Nothing
  # Comments :
  # History  :
  #==========================================================================
  function table_open($class="")
  {
    global $debug;
    
    if ($debug)
      printf("<p>table_open()<br>\n");

if ($this->edit and $this->ipe_table) echo "
<script type='text/javascript'>
<!--
function ".$this->classname."SaveCell(elem,index) {
  if (".$this->edit."_Validator(elem.form)) {
    ajaxSaveCell(elem,index,'".$this->ipe_table."','".$this->primary_key."');
  } else alert('Validation Error');
}
-->
</script>
";
	
    printf("<table%s >\n", $class?" class='$class'":""); 
	$this->total = array();
  }

  #==========================================================================
  # Function : table_close
  #--------------------------------------------------------------------------
  # Purpose  : Outputs HTML code to close a table.
  # Arguments: $class - [optional] Used for CSS control.
  # Returns  : Nothing
  # Comments : $class is not used by this function, but is available for
  #            derived classes that override this function.
  # History  :
  #==========================================================================
  function table_close($class="") 
  {
    global $debug;
    
    if ($debug)
      printf("<p>table_close()<br>\n");


    if (!$this->edit and $this->check) {
	if (!$this->description) $this->description = str_replace('Table','',$this->classname)." entries";
	$cols = count($this->fields);
	echo "<tr><td colspan='$cols'>\n";
        echo "&nbsp;&nbsp;<img src='/images/arrow_ltr.png' alt='^---'></img>\n";
        echo "<a href=\"javascript:checkall('$this->checkbox_form_id')\">Check All</a>\n";
        echo "<a href=\"javascript:uncheckall('$this->checkbox_form_id')\">Uncheck All</a>\n";
        echo "<a href=\"javascript:invert('$this->checkbox_form_id')\">Invert</a>\n";
        echo "<select selected name='WithSelected' onchange=\"confirmsubmit(this,'$this->checkbox_form_id','$this->description')\">\n";
        echo "<option value=''>with selected...</option>\n";
	foreach ($this->checkbox_menu as $menu_item) echo "<option value='$menu_item'>$menu_item</option>\n";
        echo "</select>\n";
	echo "</td></tr>";
        echo "</form>\n";
    }

    printf("</table>\n");
  }

  ## Row open and close functions.

  #==========================================================================
  # Function : table_row_open
  #--------------------------------------------------------------------------
  # Purpose  : Outputs HTML code to open a table row.
  # Arguments: $row -   This variable is for derived classes that override 
  #                     this function that want access to the row number for 
  #                     the row about to be opened.
  #            $data -  This variable is for derived classes that override 
  #                     this function that want access to the row data for 
  #                     the row about to be opened.
  #            $class - [optional] Used for CSS control.
  # Returns  :
  # Comments :
  # History  :
  #==========================================================================
  function table_row_open($row, $data, $class="") 
  {
    printf(" <tr class='table_cell' >\n");
  }

  #==========================================================================
  # Function : table_row_close
  #--------------------------------------------------------------------------
  # Purpose  : Outputs HTML code to close a table row.
  # Arguments: $row -     This variable is for derived classes that override 
  #                       this function that want access to the row number 
  #                       for the row about to be closed.
  #            $class   - [optional] Used for CSS control.
  # Returns  :
  # Comments : $class is not used by this function, but is available for
  #            derived classes that override this function.
  # History  :
  #==========================================================================
  function table_row_close($row, $class="") 
  {
    printf(" </tr>\n");
  }

  #==========================================================================
  ## Function overrides
  #==========================================================================
    
  #==========================================================================
  # Function : table_heading_row_add_extra
  #--------------------------------------------------------------------------
  # Purpose  : Virtual function for derived classes. This function is called 
  #            after all header cells have been created. It allows the 
  #            programmer to add additional HTML code to the header row 
  #            before it is closed.
  # Arguments: $data
  #            $class   - [optional] Used for CSS control.
  # Returns  :
  # Comments :
  # History  :
  #==========================================================================
  function table_heading_row_add_extra($data, $class="")
  {
        global $sess, $rowcount, $startingwith, $sortorder;
        if (!$startingwith) $startingwith = 0;
        if (!$rowcount) $rowcount = 100;
	$tenpages = $rowcount * 10;
 	if ($startingwith>0 or $this->rowcount >= $rowcount) {
		if ($class) echo "<th class='$class'>"; else echo "<th>";
		if (isset($this->add_insert)) {
 	 		printf("<a href='javascript:Back10Pages();'>&lt;&lt;</a>&nbsp;");
			printf("<a href='javascript:prevPage();'>&lt;</a>&nbsp;");
	  		printf("<a href='javascript:nextPage();'>&gt;</a>&nbsp;");
			printf("<a href='javascript:Jump10Pages();'>&gt;&gt;</a></th>");
		} else {
			$self=$sess->self_url();
			$lotsless = $startingwith-$tenpages; 
			if ($lotsless<0) $lotsless=0;
			$less = $startingwith-$rowcount; 
			if ($less<0) $less=0;
			$more = $startingwith+$rowcount;
			$lotsmore = $startingwith+$tenpages;
			printf("<a href='%s%s'>&lt;&lt;</a>&nbsp;",$self,$sess->add_query(array("rowcount"=>$rowcount,"startingwith"=>$lotsless)));
			printf("<a href='%s%s'>&lt;</a>&nbsp;",$self,$sess->add_query(array("rowcount"=>$rowcount,"startingwith"=>$less)));
			printf("<a href='%s%s'>&gt;</a>&nbsp;",$self,$sess->add_query(array("rowcount"=>$rowcount,"startingwith"=>$more)));
			printf("<a href='%s%s'>&gt;&gt;</a></th>",$self,$sess->add_query(array("rowcount"=>$rowcount,"startingwith"=>$lotsmore)));
		}
	}
  }

  #==========================================================================
  # Function : table_row_add_extra
  #--------------------------------------------------------------------------
  # Purpose  : Virtual function for derived classes. This function is called 
  #            after all cells have been created. It allows the programmer to 
  #            add additional HTML code to the row before it is closed.
  # Arguments: $row
  #            $row_key
  #            $data
  #            $class   - [optional] Used for CSS control.
  # Returns  : 
  # Comments :
  # History  :
  #==========================================================================
/* Suggested CSS
td.ae a {
        display: block;
        float: left;
        width:18px;
        height:18px;
        overflow:hidden;
        text-indent:99px;
}
td.ae a.ae_view { background: url('images/view.jpg'); }
td.ae a.ae_copy { background: url('images/copy.jpg'); }
td.ae a.ae_edit { background: url('images/edit.jpg'); }
td.ae a.ae_delete { background: url('images/delete.jpg'); }
*/
  function old_table_row_add_extra($row, $row_key, $data, $class="")
  {
     global $sess;

     if ($this->add_extra=='on') $this->add_extra = $_SERVER["PHP_SELF"];
     echo "<td class='ae' nowrap>";
     echo "<a class='ae_view' href=\"".$sess->url($this->add_extra).
                $sess->add_query(array("cmd"=>"View",$this->primary_key=>$data[$this->primary_field]))."\" title='View'>view</a>";

     if ($sess->have_edit_perm()) {
        echo " <a class='ae_edit' href=\"".$sess->url($this->add_extra).
                $sess->add_query(array("cmd"=>"Edit",$this->primary_key=>$data[$this->primary_field]))."\" title='Edit'>edit</a>";
        echo " <a class='ae_copy' href=\"".$sess->url($this->add_extra).
                $sess->add_query(array("cmd"=>"Copy",$this->primary_key=>$data[$this->primary_field]))."\" title='Copy'>copy</a>";
        echo " <a class='ae_delete' href=\"".$sess->url($this->add_extra).
                $sess->add_query(array("cmd"=>"Delete",$this->primary_key=>$data[$this->primary_field]))."\" title='Delete'>delete</a>";

        if ($this->edit) {
                echo "<input type='submit' value='Save' name='submit' class='ipeh'> ";
                echo "<input type='hidden' value='".$data[$this->primary_key]."' name='id'> ";
        }
    }

    if ($this->all_fields) $this->add_hidden_fields($data);

    echo "</td>";
  }

  #####################################
  #  HOWTO use add extra, 
  #  set to..
  #  var $add_extra = "on";  					# use all defaults.
  #  var $add_extra = "SomeFile.php"; 				# use defaults, but point to a different target file.
  #  var $add_extra = array("View","Edit","Copy","Delete"); 	# just specify the command names.
  #  var $add_extra = array(					# or specify parameters as well.
  #                      "View" => array("target"=>"PayPal.php","key"="id","perm"=>"admin","display"=>"view","class"=>"ae_view"),
  #                      )
  #
  #####################################
  function table_row_add_extra($row, $row_key, $data, $class="")
  {
	global $debug, $sess;

	if (($this->add_extra=='on') or ($this->add_extra===true)) $this->add_extra = $_SERVER["PHP_SELF"];

	$count = 0;
        if (is_array($this->add_extra)) {
		$default_target = $_SERVER["PHP_SELF"];
                $ae = $this->add_extra;
        } else {
		$default_target = $this->add_extra;
                $ae = array("View","Edit","Delete");
        }
        foreach($ae as $k=>$v) {

                if (is_array($v)) $cmd=$k; else { $cmd=$v; $v=array(); }
		
		if ($cmd=="View") $default_perm = true; 
		else $default_perm = $sess->have_edit_perm();

                //         If there is no value    use this          otherwise use value given
                $target  = empty($v["target"])  ? $default_target	: $v["target"];
                $key     = empty($v["key"])     ? $this->primary_field  : $v["key"];
		$add	 = empty($v["add"])     ? false			: $v["add"];
                $display = empty($v["display"]) ? strtolower($cmd)	: $v["display"];
                $class   = empty($v["class"])   ? "ae_$display"         : $v["class"];
                $title   = empty($v["title"])   ? $display	        : $v["title"];
                $perm    = empty($v["perm"])    ? $default_perm		: $v["perm"];
		switch ($display) {
			case "email_attach": $icon = "icon-envelope"; break;
			case "refund": $icon = "icon-step-backward"; break;
			case "boot": $icon = "icon-eject"; break;
			case "process": $icon = "icon-play-circle"; break;
			case "copy": $icon = "icon-share"; break;
			case "view": $icon = "icon-search"; break;
			case "delete": $icon = "icon-trash"; break;
			default: $icon = "icon-".$display; break;
		}
		if (strlen($perm)>2) {
			$perm = $sess->have_perm($perm);  // convert string question to boolean answer.
		}
		if ($key)
		if ($perm) {
			if ($count==0) echo "  <td class='ae' nowrap>"; else echo " ";
			$count++;
			if ($add) {
				echo "\n   <a class='$class' href='$target";
				echo $data[$add];
			} else {
				echo "\n   <a class='$class' href='".$sess->url($target);
				echo @$sess->add_query(array("cmd"=>$cmd,$this->primary_key=>$data[$key]));
			}
			echo "' title='$title' key='$key'><i class='$icon'></i> $display</a>";
		}
        }
        if ($this->edit) {
                echo "\n   <input type='submit' value='Save' name='submit' class='ipeh'> ";
                echo "\n   <input type='hidden' value='".$data[$this->primary_key]."' name='id'> ";
        }

	if ($debug) echo "\n   <!-- hidden start -->";
	if ($this->all_fields) $this->add_hidden_fields($row, $row_key, $data, $class);
	if ($debug) echo "\n   <!-- hidden end -->";
	if ($count) echo "\n  </td>\n";
  }

  function select($form, $join="") {
    $class = $form->classname;
    $this->orig_fields = Array();
    if (strtolower(substr($class,0,3))=="my_") $class=substr($class,3);
    if (strtolower(substr($class,0,2))=="my") $class=substr($class,2);
    $db = new $_ENV["MyForeignKeysDB"];
    $sql_table = $db->qi($this->sql_table);
    $from = "FROM $sql_table";
    $where = "WHERE ";
    $tc = 0;
    if (!$this->edit) {
      $links = "";
      $sql = "select * from ".$_ENV["MyForeignKeys"]." where FormName = '$class' order by id";
      $db->query($sql);
      while ($db->next_record()) {
        extract($db->Record);
	$q_FieldName = $db->qi($FieldName);
	$q_LinkField = $db->qi($LinkField);
	if (($LinkDesc) and (in_array($FieldName,$this->fields))) {
		$tc++;
		$table = "_t$tc";
		$links .= ", $table.$LinkDesc AS $table$LinkDesc";
		$from .= " LEFT OUTER JOIN $LinkTable AS $table ON $sql_table.$q_FieldName=$table.$q_LinkField";
		$this->map_cols["$table$LinkDesc"] = $this->map_cols[$FieldName];
		$this->orig_fields["$table$LinkDesc"] = $FieldName;
		$this->fields[array_search($FieldName,$this->fields)] = "$table$LinkDesc";
	}
      }
    }
    $select = "";
    if ($tc) $sql_table .= "."; else $sql_table = "";
    foreach ($this->fields as $this_field) if (substr($this_field,0,2)<>"_t") {
	if ($select) $select .= ", "; else $select="SELECT ";
	$qf = $db->qi($this_field);
	$func = array_key_exists($this_field,$this->funcs) ? $this->funcs[$this_field] : false;
	if ($func=="distinct") {$dist = "distinct ";$func="count";} else $dist="";
	$select .= $func ? "$func($dist$sql_table$qf) as $qf" : "$sql_table$qf";
    }
    $custom = array_key_exists("custom_query",$_POST) ? $_POST["custom_query"] : "";
    return("$select $links $from $join $custom $where");
  }

}
?>

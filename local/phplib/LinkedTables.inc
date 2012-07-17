<?php

class LinkedTablesform extends tpl_form {
  var $table = "LinkedTables";
  var $key = "id";
  var $key_field = "id";
  var $classname = "LinkedTablesform";
  var $database_class = "DB_hotspot";

  function setup_fields () {
    $this->form_data->add_element(array("type"=>"hidden","name"=>"id","field"=>"id","size"=>"12"));
    $this->form_data->add_element(array("type"=>"text","name"=>"FormName","maxlength"=>"64","size"=>"21"
		,"minlength"=>1,"length_e"=>"Form Name must not be blank.  Required field.",
		"field"=>"FormName","extrahtml"=>""
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"FieldName","maxlength"=>"64","size"=>"21"
		,"minlength"=>1,"length_e"=>"Field Name must not be blank.  Required field.",
		"field"=>"FieldName","extrahtml"=>""
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"LinkTable","maxlength"=>"64","size"=>"21"
		,"minlength"=>1,"length_e"=>"Link Table must not be blank.  Required field.",
		"field"=>"LinkTable","extrahtml"=>""
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"LinkField","maxlength"=>"64","size"=>"21"
		,"minlength"=>1,"length_e"=>"Link Field must not be blank.  Required field.",
		"field"=>"LinkField","extrahtml"=>""
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"LinkDesc","maxlength"=>"40","size"=>"13"
		,"minlength"=>1,"length_e"=>"Link Desc must not be blank.  Required field.",
		"field"=>"LinkDesc","extrahtml"=>""
		,"value"=>""));
    $this->form_data->add_element(array("type"=>"text","name"=>"NullValue","maxlength"=>"255","size"=>"85",
		"field"=>"NullValue","extrahtml"=>""
		));
    $this->form_data->add_element(array("type"=>"text","name"=>"NullDesc","maxlength"=>"255","size"=>"85",
		"field"=>"NullDesc","extrahtml"=>""
		));
    $this->form_data->add_element(array("type"=>"text","name"=>"LinkCondition","maxlength"=>"255","size"=>"85",
		"field"=>"LinkCondition","extrahtml"=>""
		));
    $this->form_data->add_element(array("type"=>"text","name"=>"LinkErrorMsg","maxlength"=>"255","size"=>"85",
		"field"=>"LinkErrorMsg","extrahtml"=>""
		));
    $this->form_data->add_element(array("type"=>"text","name"=>"DefaultValue","maxlength"=>"255","size"=>"85",
		"field"=>"DefaultValue","extrahtml"=>""
		));
    $this->form_data->add_element(array("type"=>"submit","name"=>"submit","value"=>"Submit"));
  }

}
class LinkedTablesTable extends Table {
  var $classname = "LinkedTablesTable";
  var $sql_table = "LinkedTables";
  var $primary_key = "id";
  var $primary_field = "id";
  var $all_fields = array(
			"FormName",
			"FieldName",
			"LinkTable",
			"LinkField",
			"LinkDesc",
			"NullValue",
			"NullDesc",
			"LinkCondition",
			"LinkErrorMsg",
			"DefaultValue");

  /* comment out or delete some of these default entries so that the table isn't too wide for the screen */
  var $default = array(
			"FormName",
			"FieldName",
			"LinkTable",
			"LinkField",
			"LinkDesc",
			"NullValue",
			"NullDesc",
			"LinkCondition",
			"LinkErrorMsg",
			"DefaultValue");

  // These fields will be searchable and displayed in results.
  // Format is "RealFieldName"=>"Field Name Formatted For Display",
  var $map_cols = array(
			"FormName"=>"Form Name",
			"FieldName"=>"Field Name",
			"LinkTable"=>"Link Table",
			"LinkField"=>"Link Field",
			"LinkDesc"=>"Link Desc",
			"NullValue"=>"Null Value",
			"NullDesc"=>"Null Desc",
			"LinkCondition"=>"Link Condition",
			"LinkErrorMsg"=>"Link Error Msg",
			"DefaultValue"=>"Default Value");
}
class LinkedTables_Sql_Query extends Sql_Query {
  var $classname = "LinkedTables_Sql_Query";
  var $primary_key = "id";
  var $primary_field = "id";
}


<?php

/*
	basis for permission controls.

	self => I can lookup my own records

	site => I can see records at my location


	perm => blank means anyone, self means UserName=auth[uname], site means loc in mylocs, others exist in auth[perm]
	field => if different
	cond => if not "=", eg like%, %like%, >, <, !=
	value => to search for
	this => field is used for determination of site or self.
*/


switch ($self[0]) {
	case "cart": $the_fields = array(
                "UserId"=>array(
                        "perm"=>"self,admin,support,accounts",
                ),
	); break;
	case "PayPal": $the_fields = array(
		"UserId"=>array(
			"perm"=>"self,admin,support,accounts",
		),
		"SrchUserName"=>array(	
			"perm"=>"self,admin,support,accounts",
			"field"=>"custom",
			"this"=>"self",
		),
		"TaxInvoiceNo"=>array(
			"perm"=>"self,admin,support,accounts",
		),
	); break;
	default: $the_fields = array();
}
		
$Locations = false;
foreach ($the_fields as $srchField=>$attr) if ((array_key_exists("this",$attr)) and ($attr["this"]=="site")) { 
	$Locations=true; 
	$LocField=array_key_exists("field",$attr) ? $attr["field"] : $srchField; 
}
if ($Locations) include ("phplib/locations.inc");
else $LocField = false;

$permOk = true;
$mysite = false;
$myself = false;
$query = "";
foreach ($the_fields as $srchField=>$attr) {
	if (array_key_exists("field",$attr)) {
		$field = $attr["field"];
	} else {
		$field = $srchField;
	}
	$thisField = false;
	if (array_key_exists("this",$attr)) {
		$thisField = $attr["this"];
		$permField[$thisField] = $field;
	}
	if (array_key_exists($srchField,$_REQUEST)) {
		if (array_key_exists("value",$attr)) {
			$value = $attr["value"];
		} else {
			if (is_string($value = $_REQUEST[$srchField])) {
				$newcond = false;
				switch (substr($value,0,1)) {			/* condition override in 1st char of data */
					case ">": $newcond = ">"; break;
					case "<": $newcond = "<"; break;
					case "!": $newcond = "!="; break;
				}
				if ($newcond) {
					$value = substr($value,1);
					$attr["cond"] = $newcond;
				}
			}
		}
		if (array_key_exists("perm",$attr)) {
			$permOk = false;					/* not just anyone */
			foreach(explode(",",$attr["perm"]) as $need) {
				switch ($need) {
					case "site": $mysite=true; break;		/* use these later to restrict to allowed */
					case "self": $myself=true; break;
					default: if ($perm->have_perm($need)) $permOk = true; 	/* eg: if admin then ok */
				}
			}
		}
		if ($thisField) {
			switch ($thisField) {
				case "self": if ($value==$auth->auth["uname"]) $permOk = true; break;	/* user searching for himself */
				case "site": if (strpos($Locations,$value)===false) $permOk = false; else $permOk = true;  /* site mgr searching own location */
			}
		}
		if (array_key_exists("cond",$attr)) {
			$cond = $attr["cond"];
		} else {
			$cond = "=";
		}
		switch ($cond) { 			/* warning - no breaks, all flows on */
			case "%like%": 
				$value = "%".$value;
			case "like%": 
				$value .= "%"; 
				$cond = "LIKE"; 
			default:
				$value = $db->quote($value);
		}
		if ($query) $query .= " AND ";			/* add to query string */
		$query .= "$field $cond $value";
	}
}
if (!$permOk) {
	if ($mysite and $permField["site"] ) {
		if ($query) $query .= " AND ";
		$query .= $permField["site"]." IN (".$Locations.")";
		$permOk = true;
	}
}
if (!$permOk) {
	if ($myself and $permField["self"] ) {
		if ($query) $query .= " AND ";
		$query .= $permField["self"]."=".$db->quote($auth->auth["uname"]);
		$permOk = true;
	}
}
					
if (is_object($f)) { // pull in class declarations for sql tables linked to by this form class.
	$db->query("select LinkTable from ".$_ENV["MyForeignKeys"]." where FormName='".$f->classname."'");
	while ($db->next_record()) {
		require_once("phplib/".$db->f(0).".inc");
	}
}

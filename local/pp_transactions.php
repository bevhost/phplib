<?php
include('phplib/prepend.php');

page_open(array("sess" => $_ENV["SessionClass"], "auth" => $_ENV["AuthClass"], "perm" => $_ENV["PermClass"]));

echo "<script language=JavaScript src=/js/scripts.js></script>
<script language=JavaScript src=/js/datefunc.js>
//Parts taken from ts_picker.js
//Script by Denis Gritcyuk: tspicker@yahoo.com
//Submitted to JavaScript Kit (http://javascriptkit.com)
//Visit http://javascriptkit.com for this script
</script> \n";

$db = new DB_bevo;
$self = neatstr(substr($_SERVER["PHP_SELF"],1,-4));
echo "<h2>$self</h2>";

check_view_perms();

$f = new pp_transactionsform;

$paypal_fraud_codes = array(
	'1'=>'AVS No Match',
	'2'=>'AVS Partial Match',
	'3'=>'AVS Unavailable/Unsupported',
	'4'=>'Card Security Code (CSC) Mismatch',
	'5'=>'Maximum Transaction Amount',
	'6'=>'Unconfirmed Address',
	'7'=>'Country Monitor',
	'8'=>'Large Order Number',
	'9'=>'Billing/Shipping Address Mismatch',
	'10'=>'Risky ZIP Code',
	'11'=>'Suspected Freight Forwarder Check',
	'12'=>'Total Purchase Price Minimum',
	'13'=>'IP Address Velocity',
	'14'=>'Risky Email Address Domain Check',
	'15'=>'Risky Bank Identification Number (BIN) Check',
	'16'=>'Risky IP Address Range',
	'17'=>'PayPal Fraud Model',
	);

if ($submit) {
  switch ($submit) {
   case "Copy": $id="";
   case "Save":
    if ($id) $submit = "Edit";
    else $submit = "Add";
   case "Add":
   case "Edit":
    if (isset($auth)) {
     check_edit_perms();
     if (!$f->validate($result)) {
        $cmd = $submit;
        echo "<font class=bigTextBold>$cmd pp transactions</font>\n";
        $f->display();
        page_close();
        exit;
     }
     else
     {
        echo "Saving....";
        $f->save_values();
        echo "<b>Done!</b><br>\n";
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to pp_transactions.</a><br>\n";
        page_close();
        exit;
     }
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br>\n";
    }
   case "View":
   case "Back":
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"0; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to pp_transactions.</a><br>\n";
        page_close();
        exit;

   case "Delete":
    if (isset($auth)) {
        check_edit_perms();
        echo "Deleting....";
        $f->save_values();
        echo "<b>Done!</b><br>\n";
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br>\n";
    }
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to pp_transactions.</a><br>\n";
        page_close();
        exit;
  }
} else {
    if ($txn_id) {
	$f->find_values($txn_id,'pp_transactions','txn_id');
    }
}
switch ($cmd) {
    case "View":
    case "Delete":
	$f->freeze();
    case "Add":

    case "Copy":
	if ($cmd=="Copy") $id="";
    case "Edit":
	echo "<font class=bigTextBold>$cmd pp transactions</font>\n";
	$f->display();

	$db->query("SELECT fraud_managment_pending_filters from pp_fraud WHERE txn_id='$txn_id' ORDER BY offset");
	while ($db->next_record()) {
		echo "Fraud Filter: ".$db->f(0)."<br>\n";
	}

        $t = new pp_optionsTable;
        $t->fields = array(
                        "option_name",
                        "option_selection");
        $t->map_cols = array(
                        "txn_id"=>"txn id",
                        "offset"=>"offset",
                        "option_name"=>"option name",
                        "option_selection"=>"option selection");
	$db->query("SELECT * FROM pp_options WHERE txn_id='$txn_id' ORDER BY offset");
	if ($db->num_rows()) {
		echo "<h4>options</h4>";
		$t->show_result($db, "default");
	}

        $t = new pp_cartTable;
        $t->heading = 'on';
        $t->fields = array(
                        "item_number",
                        "item_name",
                        "quantity",
                        "tax");
        $t->map_cols = array(
                        "txn_id"=>"txn id",
                        "offset"=>"offset",
                        "item_name"=>"item name",
                        "item_number"=>"item number",
                        "quantity"=>"quantity",
                        "tax"=>"tax");
	$db->query("SELECT * FROM pp_cart WHERE txn_id='$txn_id' ORDER BY offset");
	if ($db->num_rows()) {
		echo "<h4>cart</h4>";
		$t->show_result($db, "default");
	}

	break;
    default:
	$cmd="Query";
	$t = new pp_transactionsTable;
	$t->heading = 'on';
	$t->add_extra = 'on';
	$db = new DB_bevo;

        echo "&nbsp<a href=\"".$sess->self_url()
		.$sess->add_query(array("cmd"=>"Add"))."\">Add pp transactions</a>&nbsp;\n";
        echo "&nbsp<a href=\"".$sess->url("/index.php")."\">Home</a>&nbsp;\n";
	echo "<font class=bigTextBold>$cmd pp transactions</font>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
	$t->fields = array(
			"invoice",
			"custom",
			"payment_status",
			"payment_date",
			"txn_id",
			"txn_type",
			"payment_type",
			"mc_gross",
			"mc_currency",
			"payer_email",
			"payer_id",
			"payer_status",
			"processed_by",
			"processed_date");
        $t->map_cols = array(
			"business"=>"business",
			"receiver_email"=>"receiver email",
			"receiver_id"=>"receiver id",
			"invoice"=>"invoice",
			"custom"=>"custom",
			"memo"=>"memo",
			"tax"=>"tax",
			"num_cart_items"=>"num cart items",
			"payment_status"=>"payment status",
			"pending_reason"=>"pending reason",
			"reason_code"=>"reason code",
			"payment_date"=>"payment date",
			"txn_id"=>"txn id",
			"parent_txn_id"=>"parent txn id",
			"txn_type"=>"txn type",
			"payment_type"=>"payment type",
			"auth_id"=>"auth id",
			"auth_exp"=>"auth exp",
			"auth_amount"=>"auth amount",
			"auth_status"=>"auth status",
			"mc_gross"=>"mc gross",
			"mc_fee"=>"mc fee",
			"mc_currency"=>"mc currency",
			"settle_amount"=>"settle amount",
			"settle_currency"=>"settle currency",
			"remaining_settle"=>"remaining settle",
			"exchange_rate"=>"exchange rate",
			"payment_gross"=>"payment gross",
			"payment_fee"=>"payment fee",
			"for_auction"=>"for auction",
			"auction_buyer_id"=>"auction buyer id",
			"auction_closing_date"=>"auction closing date",
			"auction_multi_item"=>"auction multi item",
			"first_name"=>"first name",
			"last_name"=>"last name",
			"payer_business_name"=>"payer business name",
			"address_name"=>"address name",
			"address_street"=>"address street",
			"address_city"=>"address city",
			"address_state"=>"address state",
			"address_zip"=>"address zip",
			"address_country"=>"address country",
			"address_country_code"=>"address country code",
			"address_status"=>"address status",
			"payer_email"=>"payer email",
			"payer_id"=>"payer id",
			"payer_status"=>"payer status",
			"residence_country"=>"residence country",
			"subscr_date"=>"subscr date",
			"subscr_effective"=>"subscr effective",
			"notify_version"=>"notify version",
			"verify_sign"=>"verify sign",
			"processed_by"=>"processed by",
			"processed_date"=>"processed date");

  // When we hit this page the first time,
  // there is no .
  if (!isset($q_pp_transactions)) {
    $q_pp_transactions = new pp_transactions_Sql_Query;     // We make one
    $q_pp_transactions->conditions = 1;     // ... with a single condition (at first)
    $q_pp_transactions->translate  = "on";  // ... column names are to be translated
    $q_pp_transactions->container  = "on";  // ... with a nice container table
    $q_pp_transactions->variable   = "on";  // ... # of conditions is variable
    $q_pp_transactions->lang       = "en";  // ... in English, please
    $q_pp_transactions->primary_key = "txn_id";  // let Query engine know primary key
    $q_pp_transactions->default_query = "`txn_id`!='0'";  // let Query engine know primary key

    $sess->register("q_pp_transactions");   // and don't forget this!
  }

  if (isset($rowcount)) {
        $q_pp_transactions->start_row = $startingwith;
        $q_pp_transactions->row_count = $rowcount;
  }

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if (isset($x)) {
    $query = $q_pp_transactions->where("x", 1);
  }

  if ($submit=='Search') $query = $q_pp_transactions->search($t->map_cols);

  if (!$sortorder) $sortorder="txn_id";
  if (!$query) { $query="`txn_id`!='0' order by `txn_id`"; }
  $db->query("SELECT COUNT(*) as total from `pp_transactions` where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q_pp_transactions->start_row - $q_pp_transactions->row_count))
      { $q_pp_transactions->start_row = $db->f("total") - $q_pp_transactions->row_count; }

  if ($q_pp_transactions->start_row < 0) { $q_pp_transactions->start_row = 0; }

  $query .= " LIMIT ".$q_pp_transactions->start_row.",".$q_pp_transactions->row_count;

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
  printf($q_pp_transactions->form("x", $t->map_cols, "query"));
  printf("<hr>");

  // Do we have a valid query string?
  if ($query) {
    // Show that condition
    printf("Query Condition = %s<br>\n", $query);

    // Do that query
    $db->query("select * from `pp_transactions` where ". $query);

    // Dump the results (tagged as CSS class default)
    printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

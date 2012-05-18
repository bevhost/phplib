<?php 
class My_Cart extends Cart {
  var $classname = "My_Cart";


 // var $sandbox = ".sandbox";

  var $database_class = "";
  var $database_table = "items";
  var $shoppers_table = "AddressBook";
  var $options_form = "item_optionsform";
  var $fields = array(
		"key"=>"id",			/* Primary Key */
		"itemcode"=>"ProductCode",	/* Can be the same as key */
		"itemname"=>"Name",
		"price"=>"Price",
		"options"=>"Options",
		"description"=>"Description",	/* optional */
		"min_order_qty"=>"",		/* optional */
		"details_url"=>"",		/* optional */
		"weight"=>"",			/* optional */
	);
  var $paypal = array(
		"business"=>"hosting@bevhost.com",
		"notify_url"=>"pp_ipn.php",
		"return"=>"pp_return.php",
		"cancel"=>"items.php",
	);
  var $db;
  var $sum = 0;

  function total_all($PriceField,$Discount) {
	$db = new $_ENV["DatabaseClass"];

	$this->sum = 0; 
	reset($this->item);
	while(list($item, $attr) = each($this->item)) {
		$sql = sprintf("SELECT `%s` FROM `%s` WHERE `%s`='%s'", 
					$this->fields["price"],
					$this->database_table,
					$this->fields["key"],
					$this->item[$item]["art"]);
		$db->query($sql);
		if ($db->next_record()) {
			$rowsum = $db->f(0) * $this->item[$item]["num"];
		} else $rowsum=0;
		$this->sum += $rowsum;
	}
	return $this->sum;
  }



  function show_cart_open() {
    $db = new $_ENV["DatabaseClass"];
    if ($sf = $_ENV["Subfolder"]) $sf .= "/";
    global $price_column, $discount, $item_count, $auth, $sess, $cartFields, $HTTPS, $EndUserid;
    $cartFields = ""; 
    if ($HTTPS=='on') $proto = "https:"; else $proto = "http";
    $host = $_SERVER["HTTP_HOST"];
    print "<fieldset><legend>Shopping Basket</legend>\n";
    print "<form id=CartForm name=ShoppingCart method='POST' action='https://www".$this->sandbox.".paypal.com/cgi-bin/webscr'>\n";
    print "<input type='hidden' name='business' value='".$this->paypal["business"]."'>\n";
    if ($EndUserid) {
	$db2->query("select * from EndUser where id='$EndUserid'");
	if ($db2->next_record()) {
		extract($db2->Record);
		switch ($Country) {
			case "Australia": 	$CC = "AU"; break;
			case "New Zealand": 	$CC = "NZ"; break;
		}
		$names = explode(" ",$Contact);
		$LastName = array_pop($names);
		$FirstName = implode(" ",$names);
    		print "<input type='hidden' name='custom' value='$EmailAddress'>\n";
    		print "<input type='hidden' name='email' value='$EmailAddress'>\n";
    		print "<input type='hidden' name='first_name' value='$FirstName'>\n";
    		print "<input type='hidden' name='last_name' value='$LastName'>\n";
    		print "<input type='hidden' name='address1' value='$AddressLine1'>\n";
    		print "<input type='hidden' name='address2' value='$AddressLine2'>\n";
    		print "<input type='hidden' name='state' value='$State'>\n";
    		print "<input type='hidden' name='city' value='$City'>\n";
    		print "<input type='hidden' name='zip' value='$PostCode'>\n";
		if ($CC)
    		print "<input type='hidden' name='country' value='$CC'>\n";
    		print "<input type='hidden' name='cmd' value='_ext-enter'>\n";
    		print "<input type='hidden' name='redirect_cmd' value='_cart'>\n";
        } else {
    		print "<input type='hidden' name='cmd' value='_cart'>\n";
	}
    } else {
    	print "<input type='hidden' name='cmd' value='_cart'>\n";
    }
    print "<input type='hidden' name='upload' value='1'>\n";
    print "<input type='hidden' name='no_shipping' value='1'>\n";
    print "<input type='hidden' name='notify_url' value='$proto://$host/$sf".$this->paypal["notify_url"]."'>\n";
    print "<input type='hidden' name='return' value='$proto://$host/$sf".$this->paypal["return"]."'>\n";
    print "<input type='hidden' name='cancel_return' value='$proto://$host/$sf".$this->paypal["cancel"]."'>\n";
    print "<input type='hidden' name='no_note' value='1'>\n";
    print "<input type='hidden' name='currency_code' value='".$_ENV["LocalCurrency"]."'>\n";
    print "<input type='hidden' name='lc' value='EN'>\n";

    print "<table border=0 cellspacing=0 cellpadding=3>\n";
    if (!is_object($this->db)) {
      $this->db = new $_ENV["DatabaseClass"];
    }
    $this->sum = 0;
    $item_count = 0;

    if ($this->currentItem > 1) {
	echo "<tr><td>Item Code</td><td>Item Name</td>";
	echo "<td align=center>Each Price</td><td>Qty</td><td>Total</td></tr>\n";
    }
  }
 
  function show_cart_close() {
    global $PurchaseAmount, $price_column, $cartFields, $defCurr, $sess;
    if ($this->currentItem==1) echo "<td colspan=3>Your shopping cart is empty.";
    else {
    	echo "<td colspan=2>";
        echo "<a href=javascript:emptycart()>Empty Cart</a>";
        echo "</td><td>\n";
        echo '<input type="image" border="0" src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" ';
	echo 'name="submit" alt="Make payments with PayPal - it\'s fast, free and secure!" id="PayPal">';
	echo $this->sandbox;
     }
    echo "</td><td align=center colspan=2>\n";
    echo "TOTAL \$AUD";
    echo "<td colspan=1>".sprintf("%0.2f",$this->sum)."</td><td></tr>\n";
    printf("</table></form>\n");
    printf("</fieldset>\n");
    $PurchaseAmount = $this->sum;
  }

  function show_item($art, $num, $options) {
    global $item_count, $sess, $cartFields; 
    global $cartAtCheckout, $details, $price_column, $discount;
    if (!is_object($this->db)) {
      $this->db = new $_ENV["DatabaseClass"];
    }
	
		$item_count++;
		$sql = sprintf("SELECT * FROM `%s` WHERE `%s`='%s'", 
					$this->database_table,
					$this->fields["key"],
					$art);
		$this->db->query($sql);
		if ($this->db->next_record()) {
			$price = $this->db->f($this->fields["price"]);
			$rowsum = $price * $num;
		} else $rowsum=0;
		$this->sum += $rowsum;
	
		printf(" <tr>\n  <td class=cart_cell><input type=hidden name=item_number_%s value='%s'>%s</td>\n",$item_count,$art,$art);
		printf("  <td class=cart_cell><input type=hidden name=item_name_%d value='%s'>%s</td>\n",
			$item_count,
			$this->db->f($this->fields["itemname"]),
			$this->db->f($this->fields["itemname"]));
		printf("  <td class=cart_cell align=right>%0.2f</td>\n", $price);
		printf("  <td class=cart_cell><input type='text' size='5' name='%s' value='%s' onblur='updatecart(this)'></td>\n",$art, $num);
		printf("  <input type=hidden name='quantity_%s' value='%s'>\n",$item_count,$num);
		printf("  <input type=hidden name='amount_%d' value='%0.2f'>\n",$item_count,$price);
		$option_count = 0;
		$option_str = '';
		if (is_array($options)) {
			foreach($options as $on => $os) {
				printf("  <input type=hidden name='on%d_%d' value='%s'>\n",$option_count,$item_count,$on);
				printf("  <input type=hidden name='os%d_%d' value='%s'>\n",$option_count,$item_count,$os);
				$option_count++;
				$option_str .= "$on=$os ";
			}
		}
		$cartFields .= sprintf("  showField('Item%sOpt%s',%s,currency);\n", $art, $options, $price);
		printf("  <td align=right class=cart_cell>%0.2f</td>\n", $rowsum);
		printf(" </tr>\n");
		if ($option_str) echo " <tr><td>Options:</td><td colspan=4>$option_str</td></tr>\n";
  }






}  // class
?>

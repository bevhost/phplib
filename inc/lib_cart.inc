<?php
/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998-2000 NetUSE AG
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * $Id: cart.inc,v 1.3 2000/07/12 18:22:33 kk Exp $
 *
 */ 

class Cart {
  var $classname = "Cart";
  var $persistent_slots = array("item", "currentItem");

  var $item = array();  ## The shopping cart array($item, array("art"=>..., "num"=>..., ...))
  var $currentItem = 1; ## Next item number in cart
  
  ##
  ## Return the position and number of units 
  ## of an article in the cart(or false and 0, 
  ## if it is not in there)
  ##
  function check($art,$euid) {

    if (!is_array($this->item))
      return array(false, 0);

    foreach ($this->item as $item => $attr) {
    
      if (isset($attr["art"]) 
       && ($attr["euid"] == $euid)
       && ($attr["art"] == $art)) {
           return array($item, $attr["num"]);
      }
    }
    
    return array(false, 0);
  }

  ##
  ## Delete all articles from current cart
  ##
  function reset() {

    foreach ($this->item as $item => $attr) {
      unset($this->item[$item]);
    }
    $this->currentItem = 1;
    
    return true;
  }

  ##
  ## Add num units of an article to the cart
  ## and return the item number (or false on error).
  ##
  function add_item($art, $num, $euid) {
  
    ## Check to see if we already have some of these
    list($item, $have) = $this->check($art,$euid);
    
    ## We already have them
    if ($item) {
      $this->item[$item]["num"] += $num;
      return $item;
    }
    
    ## New article
    $item = $this->currentItem++;
    $this->item[$item]["art"] = $art;
    $this->item[$item]["num"] = $num;
    $this->item[$item]["euid"] = $euid;		// Added by DaveB for GL Computing				

    return $item;
  }
  
  ##
  ## Take num units of an article from the cart
  ## and return the item number (or false on error)
  ##
  function remove_item($art, $num,$euid) {
  
    ## Check to see if we have some of these
    list($item, $have) = $this->check($art,$euid);
    
    ## Can't take them out
    if (!$item || ($num > $have)) {
      return false;
    }
    
    ## Drop the item
    if ($num == $have) {
      unset($this->item[$item]);
      return $item;
    }
    
    ## Take $num out...
    $this->item[$item]["num"] -= $num;
    return $item;
  }

  ##
  ## Set quantity of an article in the cart to exactly $num
  ## and return the item number
  ##
  function set_item($art, $num, $euid) {
  
    ## Check to see if we already have some of these
    list($item, $have) = $this->check($art,$euid);
    
    ## We already have them
    if ($item) {
     if ($num > 0) {
      $this->item[$item]["num"] = $num;
     } else {
      unset($this->item[$item]);
     }
     return $item;
    }
    
    if ($num > 0) {
      ## New article
      $item = $this->currentItem++;
      $this->item[$item]["art"] = $art;
      $this->item[$item]["num"] = $num;
      $this->item[$item]["euid"] = $euid;     // Added by DaveB for GL Computing
    }

    return $item;
  }

  #
  # Return the number of articles in current cart.
  #
  function num_items() {
    if (!is_array($this->item))
      return 0;

    return count($this->item);
  }

  function tot_arts() {
    printf("Please use \$cart->num_items() instead.\n"); ## Comment out, if you want.
    return $this->num_items();
  }

  #
  # Iterator to show cart contents.
  #
  function show_all() {
    if (!is_array($this->item) or $this->num_items() == 0) {
      $this->show_empty_cart();
      return false;
    }

    $this->show_cart_open();
    foreach ($this->item as $item => $attr) {
      $this->show_item($attr["art"], $attr["num"], $attr["euid"]);
	// printf(" %s %s %s\n",$attr["art"], $attr["num"], $attr["euid"]);
    }
    $this->show_cart_close();
  }

  function update_all() {
    global $item_count;
    $db = new DB_glcomputing;
    if (!is_array($this->item) or $this->num_items() == 0) {
       $this->show_empty_cart();
      return false;
    }

    foreach ($this->item as $item => $attr) {
	$ITEM = sprintf("Item%sFor%s",$attr["art"],$attr["euid"]);
	global $$ITEM;
	$num = $$ITEM;
	if ($num>0) {
		$db->query("Select MinimumOrderQty, prodcode from items where id='".$attr["art"]."'");
		$db->next_record();
		if ($db->f(0)>$num) echo "<script language=JavaScript> alert('".$db->f(1)." not updated.  You must order 0 or a minimum of ".$db->f(0)."'); </script>;\n"; 
		else $this->set_item($attr["art"], $num, $attr["euid"]);
        } else $this->set_item($attr["art"], 0, $attr["euid"]);
    }
  }

  function start() {
    global $sess;
    
    $sess->register("cart");
  }

  ##
  ## Dummy, to be overwritten by user.
  ##

  function show_cart_open()  { 
    return; 
  }

  function show_cart_close() { 
    return; 
  }

  function show_item($art, $num, $euid) {
    printf("%s units of %s<br>\n", $num, $art);
  }
  
  function show_empty_cart() {
    printf("Your shopping cart is empty.<br>\n");
  }
}
?>

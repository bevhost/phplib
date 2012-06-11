<?php
include('phplib/prepend.php');

page_open(array("sess"=>"hotspot_Session","auth"=>"hotspot_Auth","perm"=>"hotspot_Perm"));

echo "<script language=JavaScript src=/js/currency.js></script>\n";
echo "<script language=JavaScript src=/js/datefunc.js>
//Parts taken from ts_picker.js
//Script by Denis Gritcyuk: tspicker@yahoo.com
//Submitted to JavaScript Kit (http://javascriptkit.com)
//Visit http://javascriptkit.com for this script
</script>
<script language=JavaScript>
function SetFields(f) { 
    mailbox = f.mailbox.value;
    domain = f.domain[f.domain.selectedIndex].value;
    f.email.value = mailbox+'@'+domain;
    f.maildir.value = domain+'/'+mailbox+'/Maildir/';
    url = 'find.php?MailboxExists='+mailbox+'%40';
    ajax(url,f.domain);
}
function DoCustomChecks(f) {
        if (f.bulk.value) {
                if (f.address.value=='') f.address.value=' ';
                if (f.access.value=='') f.access.value=' ';
        }
        m = f.mailbox.value;
        e = f.domain;
        if (e.selectedIndex==0) {
                alert('Please select domain');
		e.focus();
                return false;
        }
        d = e.options[e.selectedIndex].value;
        if (m=='') {
                alert('Please enter a value for mailbox part of the email address.');
		f.mailbox.focus();
                return false;
        } else {
                f.email.value = m + '@' + d;
        }
        return true;
}
</script> \n";

include ("postfix.inc");

class my_postfix_mailboxform extends postfix_mailboxform {
	var $classname = "my_postfix_mailboxform";
}

$db = new DB_postfix;

$f = new my_postfix_mailboxform;

if ($submit) {
  switch ($submit) {

   case "Save":
    if ($id) $submit = "Edit";
    else $submit = "Add";
   case "Add":
   case "Edit":
    if (isset($auth)) {
     if ((!$bulk) and (!$f->validate($result))) {
        $cmd = $submit;
        echo "<font class=bigTextBold>$cmd postfix _mailbox</font>\n";
        $f->display();
        page_close();
        exit;
     }
     else
     {
	get_request_values("mbpref,clear,email,clear,crypt,bulk");
        echo "Saving....";
	$modified = date("Y-m-d H:m:s");
	if ($mbpref) {
		if (substr($email,0,strlen($mbpref))!=$mbpref) $email = $mbpref.$email;
	} else {
		if (!strpos($email,'@')) 
			if (!strpos($email,'-'))
				if ($domain!="accessplus.com.au")
					$email = $email . '@' . $domain;
	}
	if ($clear) $_POST["crypt"] = crypt($clear);
        if ($bulk) {
                $lines = explode("\n",$bulk);
                foreach ($lines as $line) {
                        unset($id);
                        $words = explode(":",$line);
                        $mailbox = $words[0];
			$domain = "accessplus.com.au";
			$email = "$mailbox@$domain";
			$maildir = "$domain/$mailbox/Maildir";
			$crypt = $words[1];
			$name = $words[7];
			$quota = -1;
                        $f->save_values();
                        $count++;
                }
                echo "$count records...";
        } else {
		$ssh = "";
        	if ($submit=='Add') $ssh = "/usr/bin/ssh vmail\@mail.accessplus.com.au 'mkdir -p /home/vmail/$maildir'";
		if ($submit=='Edit') {
			if ($OldMaildir!=$maildir) {
			    if ($OldMaildir) {
				if ($maildir) {
				   $ssh = "/usr/bin/ssh vmail\@mail.accessplus.com.au ";
				   $ssh .= "'mv /home/vmail/$OldMaildir /home/vmail/$maildir'";
				   $ssh = str_replace("/Maildir/","",$ssh);
				}
			    } else {
				if ($maildir) {
				   $ssh = "/usr/bin/ssh vmail\@mail.accessplus.com.au 'mkdir -p /home/vmail/$maildir'";
				}
			    }
			}
		}
                $f->save_values();
        	if ($ssh) {
			echo "$ssh<br>\n";
		        system($ssh);
		}
        }
        echo "<b>Done!</b><br>\n";
        if ($shell) echo "<pre>\n$shell\n</pre>\n";
	else { echo "<META HTTP-EQUIV=REFRESH CONTENT=\"20; URL=".$sess->self_url();
	       echo $sess->add_query(array("domain"=>$domain))."\">";
	}
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to postfix_mailbox.</a><br>\n";
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
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to postfix_mailbox.</a><br>\n";
        page_close();
        exit;

   case "Delete":
    if (isset($auth)) {
        echo "Deleting....";
        $f->save_values();
	$ssh = "/usr/bin/ssh vmail\@mail.accessplus.com.au 'rm -rf /home/vmail/$domain/$mailbox'";
	echo "$ssh<br>\n";
	system($ssh);
        echo "<b>Done!</b><br>\n";
    } else {
        echo "You are not logged in....";
        echo "<b>Aborted!</b><br>\n";
    }
        echo "<META HTTP-EQUIV=REFRESH CONTENT=\"2; URL=".$sess->self_url()."\">";
        echo "&nbsp<a href=\"".$sess->self_url()."\">Back to postfix_mailbox.</a><br>\n";
        page_close();
        exit;
  }
} else {
    if ($id) {
	$f->find_values($id);
    }
}
switch ($cmd) {
    case "View":
    case "Delete":
	$f->freeze();
    case "Add":

    case "Edit":
	$origcmd = $cmd;
	echo "<font class=bigTextBold>$cmd postfix _mailbox</font>\n";
	echo "<table><tr><td>";
	$f->display();

	echo "</td><td>";

	if ($origcmd!='Add') {
		$t = new table;
		$t->heading='on';
		$sql = "select email as `Email Aliases` from postfix_virtual where destination like '%$email%'";
		$db->query($sql);
		$t->show_result($db, "default");
	}
	echo "</td></tr></table><pre>";
	if ($maildir) {
        	system("/usr/bin/ssh vmail\@mail.accessplus.com.au 'du -h $maildir; echo \"\n\"; ls -lh ".$maildir."*'");
	}
	echo "</pre>";
	break;
    default:
	$cmd="Query";

class my_postfix_mailboxTable extends Table {
  var $classname = "postfix_mailboxTable";

  function table_row_add_extra($row, $row_key, $data, $class="") {
        global $sess, $auth, $perm;

        echo "<td><a href=\"".$sess->self_url().
                $sess->add_query(array("cmd"=>"View","id"=>$data["id"]))."\">view</a></td>";
        echo "<td><a href=\"".$sess->self_url().
                $sess->add_query(array("cmd"=>"Edit","id"=>$data["id"]))."\">edit</a></td>";
        echo "<td><a href=\"".$sess->self_url().
                $sess->add_query(array("cmd"=>"Delete","id"=>$data["id"]))."\">delete</a></td>";
  }
}

	$t = new my_postfix_mailboxTable;
	$t->heading = 'on';
	$t->add_extra = 'on';

        echo "<br><a href=\"".$sess->self_url()
		.$sess->add_query(array("cmd"=>"Add"))."\">Add postfix _mailbox</a>&nbsp\n";
        echo "&nbsp<a href=\"".$sess->url("/index.php")."\">Home</a>&nbsp\n";
	echo "<br><font class=bigTextBold>$cmd postfix _mailbox</font>\n";

	// These fields will be searchable and displayed in results.
	// Format is "RealFieldName"=>"Field Name Formatted For Display",
if ($perm->have_perm("admin")) {
	$t->fields = array(
			"email",
			"name",
			"lastlogin",
			"notes");
        $t->map_cols = array(
			"email"=>"email",
			"clear"=>"clear",
			"crypt"=>"crypt",
			"name"=>"name",
			"maildir"=>"maildir",
			"quota"=>"quota",
			"domain"=>"domain",
			"modified"=>"modified",
			"lastlogin"=>"Last Login",
                        "notes"=>"notes"
			);
} else {
        $t->fields = array(
                        "email",
                        "name",
                        "domain");
        $t->map_cols = array(
                        "email"=>"email",
                        "clear"=>"clear",
                        "name"=>"name",
                        "domain"=>"domain",
                        );
}
  // When we hit this page the first time,
  // there is no .
  if (!isset($q)) {
    $q = new postfix_mailbox_Sql_Query;     // We make one
    $q->conditions = 1;     // ... with a single condition (at first)
    $q->translate  = "on";  // ... column names are to be translated
    $q->container  = "on";  // ... with a nice container table
    $q->variable   = "on";  // ... # of conditions is variable
    $q->lang       = "en";  // ... in English, please

    $sess->register("q");   // and don't forget this!
  }

  if (!empty($rowcount)) {
        $q->start_row = $startingwith;
        $q->row_count = $rowcount;
  }

  get_request_values("x,domain");

  // When we hit that page a second time, the array named
  // by $base will be set and we must generate the $query.
  // Ah, and don\'t set $base to "q" when $q is your Sql_Query
  // object... :-)
  if ($x) {
    $query = $q->where("x", 1);
  }


  if (empty($query)) { 
	if ($domain) $query = "domain='".$domain."'";
	else $query="id!='0'"; 
  }
  $db->query("SELECT COUNT(*) as total from postfix_mailbox where ".$query);
  $db->next_record();
  if ($db->f("total") < ($q->start_row - $q->row_count))
      { $q->start_row = $db->f("total") - $q->row_count; }

  if ($q->start_row < 0) { $q->start_row = 0; }

  if (!$sortorder) $sortorder="id";
  $query .= " LIMIT ".$q->start_row.",".$q->row_count;

  // In any case we must display that form now. Note that the
  // "x" here and in the call to $q->where must match.
  // Tag everything as a CSS "query" class.
if ($perm->have_perm("admin")) {
  printf($q->form("x", $t->map_cols, "query"));
  printf("<hr>");
}

  // if (!$query) { $query="id!='0'"; }

  // Do we have a valid query string?
  if ($query) {
    // Show that condition
    printf("Query Condition = %s<br>\n", $query);

    // Do that query
    $db->query("select * from postfix_mailbox where ". $query);

    // Dump the results (tagged as CSS class default)
    printf("Query Results = %s<br>\n", $db->num_rows());
    $t->show_result($db, "default");
  }
} // switch $cmd
page_close();
?>

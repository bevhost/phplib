<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

function display($name) {
	$pos = strrpos($name,'/');
	if ($pos) return substr($name,$pos+1);
	else return $name;
}

function htmlMail($rcpt, $subj, $html, $hdrs="", $text="", $name="", $type="Application/Octet-Stream", $data="", 
							   $name2="", $display2="",
							   $name3="", $display3="",
							   $data2="", $data3="") {


    $DOCUMENT_ROOT=$_SERVER["DOCUMENT_ROOT"];
    if (!$DOCUMENT_ROOT) $DOCUMENT_ROOT=".";

    if ($text=="") $text = str_replace("&nbsp;"," ",strip_tags(iconv("UTF-8","ASCII//TRANSLIT",$html)));

    $crlf = "\n";
    $bdry = md5(uniqid("sjhfkjhslkjfhlkj"));
    $tbry = md5(uniqid("uywiouyioiuyiou"));
    $cid  = md5(uniqid("kjlksjfuosiflkjf"));
    $cid2  = md5(uniqid("kjhadkljhkjliflkjf"));
    $cid3  = md5(uniqid("iuyoiuyiuyiuiuyiuy"));
    $ContentExtras = "";

	if (empty($display)) $display = display($name);
	if (empty($display2)) $display2 = display($name2);
	if (empty($display3)) $display3 = display($name3);

   if ($data) {                     // $data passed instead of external file in "name"
    $ctyp = "multipart/mixed";
   } else {
    if ($name) {
        $file = $DOCUMENT_ROOT.'/'.$name;
        if (!$fhdl = fopen($file,"r"))  echo "Can't Open $file";;
        $data = chunk_split(base64_encode(fread($fhdl,5000000)));    		// Nearly 5MB.
        fclose($fhdl);
        $ctyp = "multipart/mixed";
        $ContentExtras .= "Content-Transfer-Encoding: base64".$crlf;
        $ContentExtras .= "Content-Disposition: attachment;".$crlf;
        $ContentExtras .= '      filename="'.$display.'"'.$crlf;
        $ContentExtras .= 'Content-ID:<'.$cid.'>'.$crlf;
        $html = str_replace($name,"cid:".$cid,$html);
        if (($name2) or ($data2)) {
	  if ($name2) {
            $file = $DOCUMENT_ROOT.'/'.$name2;
            if (!$fhdl = fopen($file,"r"))  echo "Can't Open $file";;
            $data2 = chunk_split(base64_encode(fread($fhdl,5000000)));    		// Nearly 5MB.
            fclose($fhdl);
	  } else {
            $data2 = chunk_split(base64_encode($data2));
	  }
            $html = str_replace($name2,"cid:".$cid2,$html);
            $ContentExtras2 = "Content-Transfer-Encoding: base64".$crlf;
            $ContentExtras2 .= "Content-Disposition: attachment;".$crlf;
            $ContentExtras2 .= '      filename="'.$display2.'"'.$crlf;
            $ContentExtras2 .= 'Content-ID:<'.$cid2.'>'.$crlf;
	}
        if (($name3) or ($data3)) {
	  if ($name3) {
            $file = $DOCUMENT_ROOT.'/'.$name3;
            if (!$fhdl = fopen($file,"r"))  echo "Can't Open $file";;
            $data3 = chunk_split(base64_encode(fread($fhdl,5000000)));    		// Nearly 5MB.
            fclose($fhdl);
	  } else {
            $data3 = chunk_split(base64_encode($data3));
	  }
            $html = str_replace($name3,"cid:".$cid3,$html);
            $ContentExtras3 = "Content-Transfer-Encoding: base64".$crlf;
            $ContentExtras3 .= "Content-Disposition: attachment;".$crlf;
            $ContentExtras3 .= '      filename="'.$display3.'"'.$crlf;
            $ContentExtras3 .= 'Content-ID:<'.$cid3.'>'.$crlf;
        }
    } else {
        $ctyp = "multipart/alternative";
        $tbry = $bdry;
        if ($html==$text) {
            $plain = "yes";
            $ctyp = "text/plain";
        }
    }
   }
    $hdrs .= $crlf;
    $hdrs .= "MIME-Version: 1.0".$crlf;
    $hdrs .= "Content-Type: ".$ctyp.";".$crlf;
    if (isset($plain)) {
        $hdrs .= '      charset="utf-8"'.$crlf;
        $hdrs .= 'Content-Transfer-Encoding: 7bit'.$crlf;
        $body = $text.$crlf;
    } else {
        $hdrs .= "      boundary=".$bdry.$crlf;
        $body = "This is a MIME encoded multipart message.".$crlf;
        $body .= "If you are reading this, you might want to".$crlf;
        $body .= "consider changing to a mail reader that understands".$crlf;
        $body .= "how to properly display multipart messages.".$crlf; 
        if ($name) {
            $body .= $crlf;
            $body .= "--".$bdry.$crlf;
            $body .= "Content-Type: multipart/alternative;".$crlf;
            $body .= "      boundary=".$tbry.$crlf;
        }
        $body .= $crlf;
        $body .= "--".$tbry.$crlf;

// Add Text Part

        $body .= "Content-Type: text/plain;".$crlf;
        $body .= '      charset="utf-8"'.$crlf;
        $body .= "Content-Transfer-Encoding: 7bit".$crlf;
        $body .= $crlf;
		$body .= $text.$crlf;
        $body .= "--".$tbry.$crlf;

// Add HTML Part

        $body .= "Content-Type: text/html;".$crlf;
        $body .= '      charset="utf-8"'.$crlf;
        $body .= "Content-Transfer-Encoding: 7bit".$crlf;
        $body .= $crlf;
		$body .= $html.$crlf;
        $body .= "--".$tbry."--".$crlf;
        $body .= $crlf;

// Add Attachments
		
		if ($name) {
            $body .= "--".$bdry.$crlf;
            $body .= "Content-Type: ".$type.";".$crlf;
            $body .= '      name="'.$display.'"'.$crlf;
            $body .= $ContentExtras;
            $body .= $crlf;
            $body .= $data.$crlf;
            $body .= $crlf;
            if ($data2) {
        //        $type = "application/x-shockwave-flash";
                $body .= "--".$bdry.$crlf;
                $body .= "Content-Type: ".$type.";".$crlf;
                $body .= '      name="'.$display2.'"'.$crlf;
                $body .= $ContentExtras2;
                $body .= $crlf;
                $body .= $data2.$crlf;
                $body .= $crlf;
            }
            if ($data3) {
        //        $type = "application/x-shockwave-flash";
                $body .= "--".$bdry.$crlf;
                $body .= "Content-Type: ".$type.";".$crlf;
                $body .= '      name="'.$display3.'"'.$crlf;
                $body .= $ContentExtras3;
                $body .= $crlf;
                $body .= $data3.$crlf;
                $body .= $crlf;
            }
            $body .= "--".$bdry."--".$crlf;
        }
        $body .= $crlf;
    }

#    echo "$hdrs\n$body";
    mail($rcpt, $subj, $body, $hdrs);

}

?>

<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

function display($name) {
	$pos = strrpos($name,'/');
	if ($pos) return substr($name,$pos+1);
	else return $name;
}

function htmlMail($rcpt, $subj, $html, $hdrs="", $text="", $name="", $type="Application/Octet-Stream", $data="", $name2="", $display2="") {

    global $DOCUMENT_ROOT;

    if (!$DOCUMENT_ROOT) $DOCUMENT_ROOT=".";

    if ($text=="") $text = strip_tags($html);

    $crlf = "\n";
    $bdry = md5(uniqid("sjhfkjhslkjfhlkj"));
    $tbry = md5(uniqid("uywiouyioiuyiou"));
    $cid  = md5(uniqid("kjlksjfuosiflkjf"));
    $cid2  = md5(uniqid("kjhadkljhkjliflkjf"));
    $ContentExtras = "";

	if (!$display) $display = display($name);
	if (!$display2) $display2 = display($name2);

   if ($data) {                     // $data passed instead of external file in "name"
    $ctyp = "multipart/mixed";
   } else {
    if ($name) {
        $file = $DOCUMENT_ROOT.'/'.$name;
        if (!$fhdl = fopen($file,"r"))  echo "Can't Open $file";;
        $data = chunk_split(base64_encode(fread($fhdl,2000000)));    		// Nearly 2MB.
        fclose($fhdl);
        $ctyp = "multipart/mixed";
        $ContentExtras .= "Content-Transfer-Encoding: base64".$crlf;
        $ContentExtras .= "Content-Disposition: attachment;".$crlf;
        $ContentExtras .= '      filename="'.$display.'"'.$crlf;
        $ContentExtras .= 'Content-ID:<'.$cid.'>'.$crlf;
        $html = str_replace($name,"cid:".$cid,$html);
        if ($name2) {
            $file = $DOCUMENT_ROOT.'/'.$name2;
            if (!$fhdl = fopen($file,"r"))  echo "Can't Open $file";;
            $data2 = chunk_split(base64_encode(fread($fhdl,2000000)));    		// Nearly 2MB.
            fclose($fhdl);
            $html = str_replace($name2,"cid:".$cid2,$html);
            $ContentExtras2 = "Content-Transfer-Encoding: base64".$crlf;
            $ContentExtras2 .= "Content-Disposition: attachment;".$crlf;
            $ContentExtras2 .= '      filename="'.$display2.'"'.$crlf;
            $ContentExtras2 .= 'Content-ID:<'.$cid2.'>'.$crlf;
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
    if ($plain) {
        $hdrs .= '      charset="iso-8859-1"'.$crlf;
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
        $body .= '      charset="iso-8859-1"'.$crlf;
        $body .= "Content-Transfer-Encoding: 7bit".$crlf;
        $body .= $crlf;
		$body .= $text.$crlf;
        $body .= "--".$tbry.$crlf;

// Add HTML Part

        $body .= "Content-Type: text/html;".$crlf;
        $body .= '      charset="iso-8859-1"'.$crlf;
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
            if ($name2) {
        //        $type = "application/x-shockwave-flash";
                $body .= "--".$bdry.$crlf;
                $body .= "Content-Type: ".$type.";".$crlf;
                $body .= '      name="'.$display2.'"'.$crlf;
                $body .= $ContentExtras2;
                $body .= $crlf;
                $body .= $data2.$crlf;
                $body .= $crlf;
                $body .= "--".$bdry."--".$crlf;
            }
        }
        $body .= $crlf;
    }

    mail($rcpt, $subj, $body, $hdrs);

}

?>

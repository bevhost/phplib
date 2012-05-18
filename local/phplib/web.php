<?php


function web($host,$path,$method,$data="",$password="",$debug="") {

		$cookiejar = $DOCUMENT_ROOT."/../tmp/cookies.txt";
		$query = ""; $sepCh = "?";
		while (list($k, $v) = each($data)) {
			  $query .= $sepCh . urlencode($k) . "=" . urlencode($v);
			  $sepCh = "&";
		}
		if (strtoupper($debug)=="OFF") $debug="";
		
		if (substr($method,0,4)=="CURL") $proto = "https://"; else $proto = "http://";

		$url = $proto.$host.$path;

		$answer = "";

		if ($debug) echo "$url$query\n";

        switch (strtoupper($method)) {
              case "SIMPLEGET" :
                //GET METHOD
                if ($fp = fopen($url.$query,"r")) {
                    while (!feof($fp)) {
						$line = fgets($fp,4096);
						$answer .= $line;
                    }
                }
                break;
              case "GET" :
                // GET METHOD
                $port = 80;
                $data = substr($query,1);

                // if php version 4.3 or better $port=443; change host to "ssl://".$host;
                $fp = fsockopen($host, $port, $errno, $errstr, $timeout = 30);

                if(!$fp){
                  echo "Error: $errstr ($errno)\n";
                }else{
                  fputs($fp, "GET $path$query HTTP/1.1\r\n");
                  fputs($fp, "Host: $host\r\n");
				  if ($password) fputs($fp, "Authorization: Basic ".base64_encode($password)."\r\n");
                  fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
//                  fputs($fp, "Content-length: ".strlen($data)."\r\n");
                  fputs($fp, "Connection: close\r\n\r\n");
//                  fputs($fp, $data . "\r\n\r\n");

                  while (!feof($fp)) {
					$line = fgets($fp,4096);
					$answer .= $line;
                  }
                  fclose($fp);
                }
                // END GET METHOD
                break;

              case "POST" :
                // POST METHOD
                $port = 443;
                $data = substr($query,1);

                // if php version 4.3 or better $port=443; change host to "ssl://".$host;
                $fp = fsockopen("ssl://".$host, $port, $errno, $errstr, $timeout = 30);

                if(!$fp){
                  echo "Error: $errstr ($errno)\n";
                }else{
                  fputs($fp, "POST $path HTTP/1.1\r\n");
                  fputs($fp, "Host: $host\r\n");
				  if ($password) fputs($fp, "Authorization: Basic ".base64_encode($password)."\r\n");
                  fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
                  fputs($fp, "Content-length: ".strlen($data)."\r\n");
                  fputs($fp, "Connection: close\r\n\r\n");
                  fputs($fp, $data . "\r\n\r\n");

                  while (!feof($fp)) {
					$line = fgets($fp,4096);
					$answer .= $line;
                  }
                  fclose($fp);
                }
                // END POST METHOD
                break;
	      case "CURLPOST" :
				// CURL METHOD
				$url = "https://".$host.$path;
				$data = substr($query,1);
				if (!$ch = curl_init()) {
					echo "Could not initialize cURL session.\n";
				}
				curl_setopt($ch, CURLOPT_URL, $url);
				if (!file_exists($cookiejar)) curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiejar);
				else curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiejar);
				curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiejar);
				curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiejar);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDSIZE, 0);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
				curl_setopt($ch, CURLOPT_SSLVERSION, 3);
				if ($previousUrl) curl_setopt($ch, CURLOPT_REFERER, $previousUrl);
				$output = curl_exec($ch);
				curl_close($ch);
				if($output == ''){
				   echo "cURL did not receive a response back.\n";
				}
				$answer = preg_replace("'Content-type: text/plain'si","",$output);
				$error_lines = split("\n", $error_message);
				$i=0;
				while($i <= sizeof($error_lines)) {            
				  $error_message_html .= "<p>" .$error_lines[$i];
				  $i++;
				}
				// END CURL METHOD
				break;
	     case "CURLGET" :
                // CURL METHOD
                $url = "https://".$host.$path.$query;
                if (!$ch = curl_init()) {
                    echo "Could not initialize cURL session.\n";
                    exit;
                }
                curl_setopt($ch, CURLOPT_URL, $url);
                if (!file_exists($cookiejar)) curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiejar);
                else curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiejar);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
				if ($previousUrl) curl_setopt($ch, CURLOPT_REFERER, $previousUrl);
                $output = curl_exec($ch);
                curl_close($ch);
                if($output == ''){
                   echo "cURL did not receive a response back.\n";
                }
                $answer = preg_replace("'Content-type: text/plain'si","",$output);
                $error_lines = split("\n", $error_message);
                $i=0;
                while($i <= sizeof($error_lines)) {
                  $error_message_html .= "<p>" .$error_lines[$i];
                  $i++;
                }
                // END CURL METHOD
                break;
        } //switch method

	$previousUrl = $url;
	
	return $answer;
}

?>

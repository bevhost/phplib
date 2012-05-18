// Title: Timestamp picker
// Description: See the demo at url
// URL: http://us.geocities.com/tspicker/
// Script featured on: http://javascriptkit.com/script/script2/timestamp.shtml
// Version: 1.0
// Date: 12-05-2001 (mm-dd-yyyy)
// Author: Denis Gritcyuk <denis@softcomplex.com>; <tspicker@yahoo.com>
// Notes: Permission given to use this script in any kind of applications if
//    header lines are left unchanged. Feel free to contact the author
//    for feature requests and/or donations

// Modified by David Beveridge, david@beveridge.id.au to support MySQL timestamp format.

var mode;
var DateStrMDY;
var NeatDateStrMDY;
var ErrorStr;

var arr_months = ["January", "February", "March", "April", "May", "June",
		"July", "August", "September", "October", "November", "December"];

function show_cal(f,e, str_datetime) {
        if (!str_datetime) str_datetime = document.forms[f].elements[e].value;
        str_target = "document.forms['" + f + "'].elements['" + e + "']";

	var week_days = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
	var n_weekstart = 1; // day week starts from (normally 0 or 1)

//	var dt_datetime = (str_datetime == null || str_datetime =="" ?  new Date() : str2dt(str_datetime));

	var dt_datetime = (str_datetime == null || str_datetime =="" ?  new Date() : new Date(strtotime(str_datetime)*1000));

	if (ErrorStr) {
		alert(ErrorStr);
		str_datetime = new Date();
	}

	var dt_prev_month = new Date(dt_datetime);
	dt_prev_month.setMonth(dt_datetime.getMonth()-1);
	var dt_next_month = new Date(dt_datetime);
	dt_next_month.setMonth(dt_datetime.getMonth()+1);
	var dt_firstday = new Date(dt_datetime);
	dt_firstday.setDate(1);
	dt_firstday.setDate(1-(7+dt_firstday.getDay()-n_weekstart)%7);
	var dt_lastday = new Date(dt_next_month);
	dt_lastday.setDate(0);

	// html generation (feel free to tune it for your particular application)
	// print calendar header

	if (mode=='datetime') timeStr = " '+document.cal.time.value);\">";
	else timeStr = "');\">";

	MonthSelect = "<select id=headMonth name=headMonth "
		 	+ "onChange=\"gotoMonth('"+f+"','"+e+"','"+dt_datetime.getDate()+"');\">\n";
	CurrMonth = dt_datetime.getMonth();
	var thisMonth = new Number (0);
	for (thisMonth=0;thisMonth<12;thisMonth++) {
		MonthSelect += "<option ";
		if (thisMonth == CurrMonth) MonthSelect += "selected ";
		MonthSelect += "value="+Number(thisMonth + 1)+">"+arr_months[thisMonth].substring(0,3)+"\n";
	}
	MonthSelect += "</select>\n";
	
	var str_buffer = new String (
		"<html>\n"+
		"<head>\n"+
		"	<title>Calendar</title>\n"+
		"<script language=javascript>\n"+
		"function show_help(vURL){\n"+
		"        helpWindow = window.open(vURL,'','height=400 width=500 toolbar=no scrollbars=yes resizable=yes');\n"+
		"}// showHelp\n"+
		"function gotoMonth(f,e,str_date) {\n"+
		"	window.opener.show_cal(f,e,document.hdr.headYear.value+'/'+document.hdr.headMonth.value +'/'+ str_date);\n"+
		"}\n"+
		"</script>\n"+
		"</head>\n"+
		"<body bgcolor=\"White\" onload='javascript:window.focus()'>\n"+
		"<table class=\"clsOTable\" cellspacing=\"0\" border=\"0\" width=\"100%\">\n"+
		"<tr><td bgcolor=\"#4682B4\">\n"+
		"<table cellspacing=\"1\" cellpadding=\"3\" border=\"0\" width=\"100%\">\n"+
		"<form name=hdr onsubmit=\"return gotoMonth('"+f+"','"+e+"','"+dt_datetime.getDate()+"');\">"+
		"<tr>\n<td bgcolor=\"#4682B4\"><a href=\"javascript:window.opener.show_cal("+
		"'"+f+"','"+e+"', '"+ dt2dtstr(dt_prev_month)+timeStr+
		"\n<img src=\"/image/prev.gif\" width=\"16\" height=\"16\" border=\"0\""+
		" alt=\"previous month\"></a></td>\n"+
		"	<td bgcolor=\"#4682B4\" colspan=\"5\">"+
		"<font color=\"white\" face=\"tahoma, verdana\" size=\"1\">\n"
		+MonthSelect+" "
		+"\n<input size='4' name='headYear' id='headYear' type='text' value='"
		+dt_datetime.getFullYear()+"' onblur=\"gotoMonth('"+f+"','"+e+"','"+dt_datetime.getDate()+"')\"></font></td>\n"+
		"	<td bgcolor=\"#4682B4\" align=\"right\"><a href=\"javascript:window.opener.show_cal("+
		"'"+f+"','"+e+"','"+dt2dtstr(dt_next_month)+timeStr+
		"<img src=\"/image/next.gif\" width=\"16\" height=\"16\" border=\"0\""+
		" alt=\"next month\"></a></td>\n</form></tr>\n"
	);

	var dt_current_day = new Date(dt_firstday);
	// print weekdays titles
	str_buffer += "<tr>\n";
	for (var n=0; n<7; n++)
		str_buffer += "	<td bgcolor=\"#87CEFA\">"+
		"<font color=\"white\" face=\"tahoma, verdana\" size=\"2\">"+
		week_days[(n_weekstart+n)%7]+"</font></td>\n";
	// print calendar table
	str_buffer += "</tr>\n";
	while (dt_current_day.getMonth() == dt_datetime.getMonth() ||
		dt_current_day.getMonth() == dt_firstday.getMonth()) {
		// print row heder
		str_buffer += "<tr>\n";
		for (var n_current_wday=0; n_current_wday<7; n_current_wday++) {
				if (dt_current_day.getDate() == dt_datetime.getDate() &&
					dt_current_day.getMonth() == dt_datetime.getMonth())
					// print current date
					str_buffer += "	<td bgcolor=\"#FFB6C1\" align=\"right\">";
				else if (dt_current_day.getDay() == 0 || dt_current_day.getDay() == 6)
					// weekend days
					str_buffer += "	<td bgcolor=\"#DBEAF5\" align=\"right\">";
				else
					// print working days of current month
					str_buffer += "	<td bgcolor=\"white\" align=\"right\">";

				if (mode=='datetime') timeStr = " '+document.cal.time.value; window.close();\">";
				else timeStr = "'; window.close();\">";
		
				if (dt_current_day.getMonth() == dt_datetime.getMonth())
					// print days of current month
					str_buffer += "<a href=\"javascript:window.opener."+str_target+
					".value='"+dt2dtstr(dt_current_day)+timeStr+
					"<font color=\"black\" face=\"tahoma, verdana\" size=\"2\">";
				else 
					// print days of other months
					str_buffer += "<a href=\"javascript:window.opener."+str_target+
					".value='"+dt2dtstr(dt_current_day)+timeStr+
					"<font color=\"gray\" face=\"tahoma, verdana\" size=\"2\">";
				str_buffer += dt_current_day.getDate()+"</font></a></td>\n";
				dt_current_day.setDate(dt_current_day.getDate()+1);
		}
		// print row footer
		str_buffer += "</tr>\n";
	}
	// print calendar footer
	if (mode=="datetime") str_buffer +=
		"<form name=\"cal\">\n<tr><td colspan=\"7\" bgcolor=\"#87CEFA\">"+
		"<font color=\"White\" face=\"tahoma, verdana\" size=\"2\">"+
		"Time: <input type=\"text\" name=\"time\" value=\""+dt2tmstr(dt_datetime)+
		"\" size=\"8\" maxlength=\"8\">&nbsp;<a href=\"javascript:show_help('helptime.php');\">Help</a></font></td></tr>\n</form>\n";
	str_buffer +=
		"</table>\n" +
		"</tr>\n</td>\n</table>\n" +
		"</body>\n" +
		"</html>\n";

	var vWinCal = window.open("", "Calendar", 
		"width=200,height=250,status=no,resizable=yes,top=200,left=200");
	vWinCal.opener = self;
	var calc_doc = vWinCal.document;
	calc_doc.write (str_buffer);
	calc_doc.close();
}
// datetime parsing and formatting routimes. modify them if you wish other datetime format
function str2dt (str_datetime) {
	mode = 'datetime';
	ErrorStr = '';
	if (String(Number(str_datetime))==str_datetime) {
		// digits only
		if (str_datetime.length==14) re_date = /^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/;
		else if (str_datetime.length==12) re_date = /^(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/;
		else if (str_datetime.length==8) { re_date = /^(\d\d\d\d)(\d\d)(\d\d)$/; mode='dateonly'; }
		else if (str_datetime.length==6) { re_date = /^(\d\d)(\d\d)(\d\d)$/; mode='dateonly'; }
		else ErrorStr = "Invalid All Digits Datetime format: "+ str_datetime +"\nTry YYMMDD, YYYYMMDD or YYYYMMDDHHMMSS.";
	} else {
		// digits with separators
		//if (str_datetime.length>10) re_date = /^(\d+)\-(\d+)\-(\d+)\s+(\d+)\:(\d+)\:(\d+)$/;
		if (str_datetime.length>10) re_date = /^(\d+).(\d+).(\d+)\s+(\d+).(\d+).(\d+)$/;
		else { re_date = /^(\d+).(\d+).(\d+)$/; mode='dateonly'; }
	}
	if (!re_date.exec(str_datetime)) {
	    if (mode=='dateonly')
		ErrorStr = "Invalid Datetime format: "+ str_datetime +"\nTry YYYY-MM-DD or YYYYMMDD.";
	    else
		ErrorStr = "Invalid Datetime format: "+ str_datetime +"\nTry YYYY-MM-DD HH:MM:SS or YYYYMMDDHHMMSS.";
	    return new Date();
	}
	DateStrMDY = String(RegExp.$2) + '-' + String(RegExp.$3) + '-' + String(RegExp.$1);
	NeatDateStrMDY = arr_months[Number(RegExp.$2)-1] + '-' + String(RegExp.$3) + '-' + String(RegExp.$1);
	if (Number(RegExp.$1) + Number(RegExp.$2) + Number(RegExp.$3) == 0) return new Date();
	if (mode=='datetime') return (new Date (RegExp.$1, RegExp.$2-1, RegExp.$3, RegExp.$4, RegExp.$5, RegExp.$6));
	if (mode=='dateonly') return (new Date (RegExp.$1, RegExp.$2-1, RegExp.$3));
	return alert("This shouldn't happen!!");
}
function str2tm (str_time) {
	ErrorStr = '';
	done = false;
	Fraction = 0; Days=0;
	if ((String(Number(str_time))==str_time) || (String(Number(str_time))== '0'+str_time)) {
		// digits only
		Hours=0; Minutes=0;
		if (str_time.length>6) re_time = /^(\d\d)(\d\d)(\d\d)\.(\d+)$/;
		else if (str_time.length==6) re_time = /^(\d\d)(\d\d)(\d\d)$/; 
		else if (str_time.length==4) re_time = /^(\d\d)(\d\d)$/;
		else if (str_time.length==2) re_time = /^(\d\d)$/;
		else re_time=/^(\d)$/;
		if (!re_time.exec(str_time)) {
			ErrorStr = "Invalid All Digits Time format: "+ str_time +"\nTry SS, MMSS, or HHMMSS";
			return false;
		}
		if (str_time.length<=2) Seconds=RegExp.$1;
		else if (str_time.length==4) { Minutes=RegExp.$1; Seconds=RegExp.$2; }
		else { Hours=RegExp.$1; Minutes=RegExp.$2; Seconds=RegExp.$3; }
		if (str_time.length>6) Fraction=RegExp.$4;
	} else {
		// digits with separators
		re_time = /^(\d*?)\s?(\d+):?(\d*):?(\d*)(\.?\d*)$/;
		if (!re_time.exec(str_time)) {
				ErrorStr = "Invalid Time format: " + str_time 
					 + "\nTry MM:SS, HH:MM:SS or HH:MM:SS.fraction";
				return (false);
		}
		Seconds = Number(RegExp.$4);
		Minutes = Number(RegExp.$3);
		Hours = Number(RegExp.$2);
		Days = Number(RegExp.$1);
		Fraction = Number("0" + RegExp.$5);
	}
	if (Seconds>59) ErrorStr = "Seconds must be less than 60";
	if (Minutes>59) ErrorStr = "Minutes must be less than 60";
	if (Days>33) ErrorStr = "Days must be less than 34";
	if (ErrorStr) return (false);
	else return (new Date (0,0,Days,Hours,Minutes,Seconds));
}
function old_dt2dtstr (dt_datetime) {
	return (new String (
			dt_datetime.getFullYear()+"-"+(dt_datetime.getMonth()+1)+"-"+dt_datetime.getDate()));
}
function dt2tmstr (dt_datetime) {
	return (new String (
			dt_datetime.getHours()+":"+dt_datetime.getMinutes()+":"+dt_datetime.getSeconds()));
}

function show_help(vURL){
        helpWindow = window.open(vURL,'','height=400 width=500 toolbar=no scrollbars=yes resizable=yes');
}// showHelp

function show_terms(vURL){
        helpWindow = window.open(vURL,'','height=400 width=680 toolbar=no scrollbars=yes resizable=yes');
}// showTerms




function isValidDate(dateStr, format) {
   if (format == null) { format = "MDY"; }
   format = format.toUpperCase();
   if (format.length != 3) { format = "MDY"; }
   if ( (format.indexOf("M") == -1) || (format.indexOf("D") == -1) || (format.indexOf("Y") == -1) ) { format = "MDY"; }
   if (format.substring(0, 1) == "Y") { // If the year is first
      var reg1 = /^\d{2}(\-|\/|\.)\d{1,2}\1\d{1,2}$/
      var reg2 = /^\d{4}(\-|\/|\.)\d{1,2}\1\d{1,2}$/
   } else if (format.substring(1, 2) == "Y") { // If the year is second
      var reg1 = /^\d{1,2}(\-|\/|\.)\d{2}\1\d{1,2}$/
      var reg2 = /^\d{1,2}(\-|\/|\.)\d{4}\1\d{1,2}$/
   } else { // The year must be third
      var reg1 = /^\d{1,2}(\-|\/|\.)\d{1,2}\1\d{2}$/
      var reg2 = /^\d{1,2}(\-|\/|\.)\d{1,2}\1\d{4}$/
   }
   // If it doesn't conform to the right format (with either a 2 digit year or 4 digit year), fail
   if ( (reg1.test(dateStr) == false) && (reg2.test(dateStr) == false) ) { return false; }
   var parts = dateStr.split(RegExp.$1); // Split into 3 parts based on what the divider was
   // Check to see if the 3 parts end up making a valid date
   if (format.substring(0, 1) == "M") { var mm = parts[0]; } else 
      if (format.substring(1, 2) == "M") { var mm = parts[1]; } else { var mm = parts[2]; }
   if (format.substring(0, 1) == "D") { var dd = parts[0]; } else 
      if (format.substring(1, 2) == "D") { var dd = parts[1]; } else { var dd = parts[2]; }
   if (format.substring(0, 1) == "Y") { var yy = parts[0]; } else 
      if (format.substring(1, 2) == "Y") { var yy = parts[1]; } else { var yy = parts[2]; }
   if (parseFloat(yy) <= 50) { yy = (parseFloat(yy) + 2000).toString(); }
   if (parseFloat(yy) <= 99) { yy = (parseFloat(yy) + 1900).toString(); }
   var dt = new Date(parseFloat(yy), parseFloat(mm)-1, parseFloat(dd), 0, 0, 0, 0);
   if (parseFloat(dd) != dt.getDate()) { return false; }
   if (parseFloat(mm)-1 != dt.getMonth()) { return false; }
   return true;
}

function dateValidator(vDataType,vElement,vElementValue,vAllowNull) {
   
   if ((vAllowNull=='YES') && (vElementValue.length==0)) return true;
   if ((vAllowNull=='YES') && (vElementValue=='0000-00-00')) return true;
   ErrorStr = "";
   if (vDataType=='time') {
        // check time   
	var tm_time = str2tm(vElementValue);
   } else {
	var dt_datetime = new Date(strtotime(vElementValue)*1000);
        if (ErrorStr) return(false);       
        if (Number(dt_datetime.getDate())<=0) ErrorStr="Date must be greater than zero";
        if (Number(dt_datetime.getMonth())<0) ErrorStr="Month must be greater than zero";
        if (Number(dt_datetime.getFullYear())<=0) ErrorStr="Year must be greater than zero";

        if (Number(dt_datetime.getHours())>23) ErrorStr="Hours must be less than 24";      
        if (Number(dt_datetime.getMinutes())>59) ErrorStr="Minutes must be less than 60";  
        if (Number(dt_datetime.getSeconds())>59) ErrorStr="Seconds must be less than 60";
        if (Number(dt_datetime.getDate())>31) ErrorStr="Date must be less than 32";
        if (Number(dt_datetime.getMonth())>12) ErrorStr="Month must be less than 13";      
        if (Number(dt_datetime.getFullYear())>2037) ErrorStr="Year must be less than 2037";    
        if ((Number(dt_datetime.getFullYear())<1900) && (Number(dt_datetime.getYear())>999)) 
		ErrorStr="Year must be more than 1900";
        if ((Number(dt_datetime.getFullYear()<1000)) && (Number(dt_datetime.getYear()>99))) 
		ErrorStr="Year must be 2 digits or 4 digits";
        if (ErrorStr) return(false);
   }
   if (ErrorStr) return(false);
   return(true);
}  


function strtotime(str, now) {
    // http://kevin.vanzonneveld.net
    // +   original by: Caio Ariede (http://caioariede.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: David
    // +   improved by: Caio Ariede (http://caioariede.com)
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Wagner B. Soares
    // %        note 1: Examples all have a fixed timestamp to prevent tests to fail because of variable time(zones)
    // *     example 1: strtotime('+1 day', 1129633200);
    // *     returns 1: 1129719600
    // *     example 2: strtotime('+1 week 2 days 4 hours 2 seconds', 1129633200);
    // *     returns 2: 1130425202
    // *     example 3: strtotime('last month', 1129633200);
    // *     returns 3: 1127041200
    // *     example 4: strtotime('2009-05-04 08:30:00');
    // *     returns 4: 1241418600
 
    var i, match, s, strTmp = '', parse = '';
 
    strTmp = str;
    strTmp = strTmp.replace(/\s{2,}|^\s|\s$/g, ' '); // unecessary spaces
    strTmp = strTmp.replace(/[\t\r\n]/g, ''); // unecessary chars
    strTmp = strTmp.replace(/yesterday/g, '-1 day');
    strTmp = strTmp.replace(/tomorrow/g, '+1 day');

    strTmp = strTmp.toLowerCase();
 
    if (strTmp == 'now' | strTmp == 'today') {
        return (new Date()).getTime()/1000; // Return seconds, not milli-seconds
    } else if (!isNaN(parse = Date.parse(strTmp))) {
        return (parse/1000);
    } else if (now) {
        now = new Date(now*1000); // Accept PHP-style seconds
    } else {
        now = new Date();
    }
 
 
    var __is =
    {
        day:
        {
            'sun': 0,
            'mon': 1,
            'tue': 2,
            'wed': 3,
            'thu': 4,
            'fri': 5,
            'sat': 6
        },
        mon:
        {
            'jan': 0,
            'feb': 1,
            'mar': 2,
            'apr': 3,
            'may': 4,
            'jun': 5,
            'jul': 6,
            'aug': 7,
            'sep': 8,
            'oct': 9,
            'nov': 10,
            'dec': 11
        }
    };
 
    var process = function (m) {
        var ago = (m[2] && m[2] == 'ago');
        var num = (num = m[0] == 'last' ? -1 : 1) * (ago ? -1 : 1);
 
        switch (m[0]) {
            case 'last':
            case 'next':
                switch (m[1].substring(0, 3)) {
                    case 'yea':
                        now.setFullYear(now.getFullYear() + num);
                        break;
                    case 'mon':
                        now.setMonth(now.getMonth() + num);
                        break;
                    case 'for':
                        now.setDate(now.getDate() + (num * 14));
                        break;
                    case 'wee':
                        now.setDate(now.getDate() + (num * 7));
                        break;
                    case 'day':
                        now.setDate(now.getDate() + num);
                        break;
                    case 'hou':
                        now.setHours(now.getHours() + num);
                        break;
                    case 'min':
                        now.setMinutes(now.getMinutes() + num);
                        break;
                    case 'sec':
                        now.setSeconds(now.getSeconds() + num);
                        break;
                    default:
                        var day;
                        if (typeof (day = __is.day[m[1].substring(0, 3)]) != 'undefined') {
                            var diff = day - now.getDay();
                            if (diff == 0) {
                                diff = 7 * num;
                            } else if (diff > 0) {
                                if (m[0] == 'last') {diff -= 7;}
                            } else {
                                if (m[0] == 'next') {diff += 74;}
                            }
                            now.setDate(now.getDate() + diff);
                        }
                }
                break;
 
            default:
                if (/\d+/.test(m[0])) {
                    num *= parseInt(m[0], 10);
 
                    switch (m[1].substring(0, 3)) {
                        case 'yea':
                            now.setFullYear(now.getFullYear() + num);
                            break;
                        case 'mon':
                            now.setMonth(now.getMonth() + num);
                            break;
                        case 'for':
                            now.setDate(now.getDate() + (num * 14));
                            break;
                        case 'wee':
                            now.setDate(now.getDate() + (num * 7));
                            break;
                        case 'day':
                            now.setDate(now.getDate() + num);
                            break;
                        case 'hou':
                            now.setHours(now.getHours() + num);
                            break;
                        case 'min':
                            now.setMinutes(now.getMinutes() + num);
                            break;
                        case 'sec':
                            now.setSeconds(now.getSeconds() + num);
                            break;
                    }
                } else {
                    return false;
                }
                break;
        }
        return true;
    };
 
    match = strTmp.match(/^(\d{2,4}-\d{2}-\d{2})(?:\s(\d{1,2}:\d{2}(:\d{2})?)?(?:\.(\d+))?)?$/);
    if (match != null) {
        if (!match[2]) {
            match[2] = '00:00:00';
        } else if (!match[3]) {
            match[2] += ':00';
        }
 
        s = match[1].split(/-/g);
 
        for (i in __is.mon) {
            if (__is.mon[i] == s[1] - 1) {
                s[1] = i;
            }
        }
        s[0] = parseInt(s[0], 10);
 
        s[0] = (s[0] >= 0 && s[0] <= 69) ? '20'+(s[0] < 10 ? '0'+s[0] : s[0]+'') : (s[0] >= 70 && s[0] <= 99) ? '19'+s[0] : s[0]+'';
        return parseInt(this.strtotime(s[2] + ' ' + s[1] + ' ' + s[0] + ' ' + match[2])+(match[4] ? match[4]/1000 : ''), 10);
    }
 
    var regex = '([+-]?\\d+\\s'+
        '(years?|months?|fortnights?|weeks?|days?|hours?|min|minutes?|sec|seconds?'+
        '|sun\.?|sunday|mon\.?|monday|tue\.?|tuesday|wed\.?|wednesday'+
        '|thu\.?|thursday|fri\.?|friday|sat\.?|saturday)'+
        '|(last|this|next)\\s'+
        '(years?|months?|fortnights?|weeks?|days?|hours?|min|minutes?|sec|seconds?'+
        '|sun\.?|sunday|mon\.?|monday|tue\.?|tuesday|wed\.?|wednesday'+
        '|thu\.?|thursday|fri\.?|friday|sat\.?|saturday))'+
        '(\\sago)?';
 
    match = strTmp.match(new RegExp(regex, 'g'));
    if (match == null) {
        return false;
    }
 
    for (i in match) {
	// if (/.+\s[a-z]+/.test(match[i])) 
if (i<match.length) 
        if (!process(match[i].split(' '))) {
            return false;
        }
    }
 
    return (now.getTime()/1000);
}


function dateCheck(el) {
	v = el.value;
	now = new Date();
	if (window.RegExp) {
	 // Look for YYYY MM DD * and convert spaces and dashes to slashes
	 var re = new RegExp("^([0-9]{4})[\ |\.|\-|/]([0-9]{2})[\ |\.|\-|/]([0-9]{2})(.*)$","");
	 m = re.exec(v)
	 if (m) {
		v = m[1]+"/"+m[2]+"/"+m[3];
		if (m[4]) v = v+" "+m[4];
	 }
	 if (true) {  // change to false to disable DMY to MDY conversion.
	  // Look for DD/MM/YYYY, D/M/YYYY, DD/M/YYYY, D/MM/YYYY
	  var re = new RegExp("^([0-9]{1,2})[\ |\.|\-|/]([0-9]{1,2})[\ |\.|\-|/]([0-9]{4})$","");
	  m = re.exec(v)
	  if (m) {
		if (m[2]>12 & m[1]>12) v = "";
		else {
			if (m[2]>12) v = m[3]+"/"+m[1]+"/"+m[2];
			else v = m[3]+"/"+m[2]+"/"+m[1];
		}
	  }
	  // Look for DD/MM/YY, D/M/YY, DD/M/YY, D/MM/YY
	  var re = new RegExp("^([0-9]{1,2})[\ |\.|\-|/]([0-9]{1,2})[\ |\.|\-|/]([0-9]{1,2})$","");
	  m = re.exec(v)
	  if (m) {
		if (m[2]>12 & m[1]>12) v = "";
		else {
			if (m[3]<50) c = "20"; else c = 19;
			if (m[3]<10) c = "200";
			if (m[2]>12) v = m[3]+"/"+m[1]+"/"+m[2];
			else v = c + m[3]+"/"+m[2]+"/"+m[1];
		}
	  }
	  // Look for DD/MM, D/M, DD/M, D/MM
	  var re = new RegExp("^([0-9]{1,2})[\ |\.|\-|/]([0-9]{1,2})$","");
	  m = re.exec(v)
	  if (m) {
		if (m[2]>12 & m[1]>12) v = "";
		else {
			if (m[2]>12) v = now.getYear()+"/"+m[1]+"/"+m[2];
			else v = now.getFullYear()+"/"+m[2]+"/"+m[1];
		}
	  }
	 }
	}
	

	dt = new Date();
	ms = Date.parse(v);
	if (ms) dt.setTime(ms);
	else {
		re = new RegExp("([0-9]{4})","");
		m = re.exec(v);
		if (!m) {
			ms = Date.parse(v + " " + now.getFullYear());
			if (ms) dt.setTime(ms);
			else dt.setTime(strtotime(v)*1000);
		}
	}

	if (str = dt2dtstr(dt)) el.value=str;
}

function dt2dtstr (dt_datetime) {
	re = new RegExp("^([a-z]+), ([0-9]+) ([a-z]+) ([0-9]+) (.*)$","i");
	m = re.exec(dt_datetime.toLocaleString());
	if (m) return m[1].substring(0,3)+", "+m[2]+" "+m[3].substring(0,3)+" "+m[4];
	else return false
}

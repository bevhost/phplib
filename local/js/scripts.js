
function showHelp(vURL){
        helpWindow = window.open(vURL,'','height=500 width=448 toolbar=no scrollbars=yes');
}// showHelp

function showImage(vURL){
        helpWindow = window.open(vURL,'','height=200 width=190 toolbar=no scrollbars=no');
}// Image Viewer

function showField(jsProdName,jsProdPrice,currency) {
  setDivHtml(jsProdName,symbols[currency]+formatValue(jsProdPrice * convRates[currency],"###,###,###,###.##")+" "+currencies[currency]);
} 


function showElement(e) {
        if (document.all) {
                document.all[e].style.visibility='visible';
                document.all[e].style.display='block';
        } else {
                el = document.getElementById(e);
                el.style.visibility='visible';
                el.style.display='block';
        }
}
function addtocart(f) {
        q = buildQuery(f);
        ajax("find.php?"+q,'Cart');
        hide("cart_popup");
        showElement("DeliveryDiv");
        return false;
}
function updatecart(el) {
        ajax("find.php?updatecart="+el.name+"&qty="+el.value,'Cart');
}
function showcart() {
        showElement("DeliveryDiv");
        ajax("find.php?cmd=ShowCart",'Cart');
}
function emptycart() {
        if (confirm("Are you sure you want to empty the shopping basket?")) {
           ajax("find.php?cmd=EmptyCart",'Cart');
        }
}
function showdelivery() {
        showElement("DeliveryDiv");
}
function login() {
        f = document.forms["minilogin"];
        url = "delivery.php?username="+f.username.value+"&password="+f.password.value+"&mode=mini";
        ajax(url,'Delivery');
}
function toggleDA() {
        if (document.all) {
                h = document.all["DeliveryFrame"].height;
                if (h==0) h=350; else h=0;
                document.all["DeliveryFrame"].height = h;
        } else {
                el = document.getElementById("DeliveryFrame");
                h = el.height;
                if (h==0) h=350; else h=0;
                el.height = h;
        }
}

function Seconds(Count,Units) {
	if (Units=="Seconds") { return Count; }
	if (Units=="Minutes") { return Count * 60; }
	if (Units=="Hours") { return Count * 3600; }
	if (Units=="Days") { return Count * 86400; }
	if (Units=="Months") { return Count * 86400 * 30; }
	if (Units=="Years") { return Count * 86400 * 365; }
}

function getDivHtml(fieldToGet) {
 if (document.all||document.layers||document.getElementById){
  if (document.all) {
   vItem = document.all[fieldToGet].innerHTML;
  } else if (document.layers){
   vItem = document.fieldToGet.innerHTML;
  } else if (document.getElementById){
   vItem = document.getElementById(fieldToGet).innerHTML;
  }
  return vItem;
 }
}
function setDivHtml(fieldToSet,vItem) { 
 if (document.all||document.layers||document.getElementById){
  if (document.all) {
   document.all[fieldToSet].innerHTML = vItem;
  } else if (document.layers){
   document.fieldToSet.innerHTML = vItem;
  } else if (document.getElementById){
   document.getElementById(fieldToSet).innerHTML = vItem;
  }
 }
}
function addDivHtml(fieldToSet,vItem) {
 if (document.all||document.layers||document.getElementById){
  if (document.all) {
   document.all[fieldToSet].innerHTML += vItem;
  } else if (document.layers){
   document.fieldToSet.innerHTML += vItem;
  } else if (document.getElementById){
   document.getElementById(fieldToSet).innerHTML += vItem;
  }
 }
}
function setFieldValue(fieldToSet,vItem) {
 if (document.all||document.layers||document.getElementById){
  if (document.all) {
   document.all[fieldToSet].value = vItem;
  } else if (document.layers){
   document.fieldToSet.value = vItem;
  } else if (document.getElementById){
   document.getElementById(fieldToSet).value = vItem;
  }
 }
}

function getFieldValue(e) {
 return _(e);
}
function _(e) {
 if (document.all) {
  v = document.all[e].value;
 } else if (document.getElementById) {
  v = document.getElementById(e).value;
 } else v=0;
 return v;
}

function isChecked(fieldToGet) {
 if (document.all) {
  vField = document.all[fieldToGet].checked;
 } else if (document.getElementById) {
  vField = document.getElementById(fieldToGet).checked;
 } else vField=0;
 return vField;
}

function showTotalPrice(){
 Price = _('Price') * _('qty') * (100 - _('Discount')) / 100;
 //showField("totalPrice",Price,selectedCurrency);
 //showField("ListPrice",_('Price'),selectedCurrency);
 setDivHtml("totalPrice","$"+formatValue(Price,"###,###,###,###.##"));
 //setDivHtml("discount",String(_('Discount'))+"%");
}

function formatDecimal(argvalue, addzero, decimaln) {
  var numOfDecimal = (decimaln == null) ? 2 : decimaln;
  var number = 1;
  number = Math.pow(10, numOfDecimal);
  argvalue = Math.round(parseFloat(argvalue) * number) / number;
  argvalue = "" + argvalue;
  if (argvalue.indexOf(".") == 0)
    argvalue = "0" + argvalue;
  if (addzero == true) {
    if (argvalue.indexOf(".") == -1)
      argvalue = argvalue + ".";
    while ((argvalue.indexOf(".") + 1) > (argvalue.length - numOfDecimal))
      argvalue = argvalue + "0";
  }
  return argvalue;
}

function formatValue(argvalue, format) {
  format = typeof(format) != 'undefined' ? format : "$##,###,###,###,###.##";
  var numOfDecimal = 0;
  if (format.indexOf(".") != -1) {
    numOfDecimal = format.substring(format.indexOf(".") + 1, format.length).length;
  }
  argvalue = formatDecimal(argvalue, true, numOfDecimal);
  argvalueBeforeDot = argvalue.substring(0, argvalue.indexOf("."));
  retValue = argvalue.substring(argvalue.indexOf("."), argvalue.length);
  strBeforeDot = format.substring(0, format.indexOf("."));
  for (var n = strBeforeDot.length - 1; n >= 0; n--) {
    oneformatchar = strBeforeDot.substring(n, n + 1);
    if (oneformatchar == "#") {
      if (argvalueBeforeDot.length > 0) {
        argvalueonechar = argvalueBeforeDot.substring(argvalueBeforeDot.length - 1, argvalueBeforeDot.length);
        retValue = argvalueonechar + retValue;
        argvalueBeforeDot = argvalueBeforeDot.substring(0, argvalueBeforeDot.length - 1);
      }
    }
    else {
      if (argvalueBeforeDot.length > 0 || n == 0)
        retValue = oneformatchar + retValue;
    }
  }
  return retValue;
}

function setpos(obj,target) {
        var curleft = 150;
        var curtop = -50;
        if (obj.offsetParent) {
                do {
                        curleft += obj.offsetLeft;
                        curtop += obj.offsetTop;
                } while (obj = obj.offsetParent);
                target.style.top = curtop + "px";
                target.style.left = curleft + "px";
        }
}

function hidepopup() {
 document.getElementById('popup').style.display='none';
}

if (typeof(XMLHttpRequest) == "undefined") {
  XMLHttpRequest = function() {
    try { return new ActiveXObject("Msxml2.XMLHTTP.6.0"); }
      catch(e) {}
    try { return new ActiveXObject("Msxml2.XMLHTTP.3.0"); }
      catch(e) {}
    try { return new ActiveXObject("Msxml2.XMLHTTP"); }
      catch(e) {}
    try { return new ActiveXObject("Microsoft.XMLHTTP"); }
      catch(e) { alert ("Your browser does not support AJAX!");}
    throw new Error("This browser does not support XMLHttpRequest.");
  };
}

function ajax(url,el) {
  el = typeof(el) != 'undefined' ? el : "popup";
  var xmlHttp;

  xmlHttp=new XMLHttpRequest();
  xmlHttp.onreadystatechange=function()
    {
    if(xmlHttp.readyState==4)
      {
        if (document.all) {
                document.all[el].innerHTML=xmlHttp.responseText;
                document.all[el].style.display='block';
                document.all[el].style.visibility='visible';
        } else {
                popup = document.getElementById(el);
                popup.innerHTML=xmlHttp.responseText;
                popup.style.display='block';
                popup.style.visibility='visible';
        }
      }
    }
  xmlHttp.open("GET",url,true);
  xmlHttp.send(null);
}


function old_ajax(url,el)
{
var xmlHttp;

data = typeof(el) != 'undefined' ? el.value : "";
popupForm = typeof(el) != 'undefined' ? el.form : false;

try
  // Firefox, Opera 8.0+, Safari
  { xmlHttp=new XMLHttpRequest(); }
catch (e)
  {
  // Internet Explorer
  try { xmlHttp=new ActiveXObject("Msxml2.XMLHTTP"); }
  catch (e)
    {
    try { xmlHttp=new ActiveXObject("Microsoft.XMLHTTP"); }
    catch (e)
      { alert("Your browser does not support AJAX!"); return false; }
    }
  }
  xmlHttp.onreadystatechange=function()
    {
    if(xmlHttp.readyState==4)
      {
	if (document.all) {
		document.all['popup'].innerHTML=xmlHttp.responseText;
        	document.all['popup'].style.display='block';
        	document.all['popup'].style.visibility='visible';
	} else {
        	popup = document.getElementById('popup');
		popup.innerHTML=xmlHttp.responseText;
        	popup.style.display='block';
        	popup.style.visibility='visible';
	}
        setpos(el,popup);
      }
    }
  xmlHttp.open("GET",url+data,true);
  xmlHttp.send(null);
}

function set(el,val) {
 hidepopup();
 popupForm.elements[el].value=val;
}

function ajaxSaveCell(element,index,table,key)
{
var xmlHttp;

try
  // Firefox, Opera 8.0+, Safari
  { xmlHttp=new XMLHttpRequest(); }
catch (e)
  {
  // Internet Explorer
  try { xmlHttp=new ActiveXObject('Msxml2.XMLHTTP'); }
  catch (e)
    {
    try { xmlHttp=new ActiveXObject('Microsoft.XMLHTTP'); }
    catch (e)
      { alert('Your browser does not support AJAX!'); return false; }
    }
  }
  xmlHttp.onreadystatechange=function()
    {
    if(xmlHttp.readyState==4)
      {
                element.value=xmlHttp.responseText;
      }
    }
  url = 'ipe.php?table='+table+'&col='+element.name+'&val='+escape(element.value)+'&key='+key+'&index='+escape(index);
  xmlHttp.open('GET',url,true);
  xmlHttp.send(null);
}

function enableSave(elem) {
  f = elem.form;
  for (e=0;e<f.elements.length;e++) {
    el = f.elements[e];
    if (el.name == 'submit') {
      el.className='ipeb';   /* in place editing - block class */
    }
  }
}

function ajaxreport(url,target)
{
var xmlHttp;

try
  // Firefox, Opera 8.0+, Safari
  { xmlHttp=new XMLHttpRequest(); }
catch (e)
  {
  // Internet Explorer
  try { xmlHttp=new ActiveXObject("Msxml2.XMLHTTP"); }
  catch (e)
    {
    try { xmlHttp=new ActiveXObject("Microsoft.XMLHTTP"); }
    catch (e)
      { alert("Your browser does not support AJAX!"); return false; }
    }
  }
  xmlHttp.onreadystatechange=function()
    {
    if(xmlHttp.readyState==4)
      {
        t = document.getElementById(target+"report");
        t.innerHTML=xmlHttp.responseText;
      }
    }
  xmlHttp.open("GET",url+"&report="+target,true);
  xmlHttp.send(null);
}

function hidereport(user,rep) {
        document.getElementById(rep+'report').innerHTML = "<a href=javascript:ajaxreport('userreports.php?username="+user+"','"+rep+"');>show</a>";
}

function dollarformat(thisone){
  if (thisone.value.charAt(0)=='$') { s=1; prefix=''; } else { s=0; prefix='$'; }
  wd='w'; count=0; tempnum=thisone.value;
  for (i=s;i<tempnum.length;i++) if (wd=='d') count++; else if (tempnum.charAt(i)=='.') wd='d'; 
  if (wd=='w') extra = '.00'; else if (count==0) extra = '00'; else if (count==1) extra = '0'; else extra = '';
  thisone.value=prefix+tempnum+extra;
}

function buildQuery(f)
{
   var query = "";
   for(var i=0; i<f.elements.length; i++)
   {
     var key = f.elements[i].name;
     var value = getElementValue(f.elements[i]);
     if(key && value)
     {
        if (query.length) query += "&";
        query += key +"="+ value;
     }
   }
   return query;
}

function getElementValue(e)
{
        if(e.length != null) var type = e[0].type;
        if((typeof(type) == 'undefined') || (type == 0)) var type = e.type;

        switch(type)
        {
                case 'undefined': return;

                case 'radio':
                        for(var x=0; x < e.length; x++)
                                if(e[x].checked == true)
                                        return e[x].value;

                case 'select-multiple':
                        var myArray = new Array();
                        for(var x=0; x < e.length; x++)
                                if(e[x].selected == true)
                                        myArray[myArray.length] = e[x].value;
                        return myArray;

                case 'checkbox': return e.checked;

                default: return e.value;
        }
}

function hide(el) {
 el = typeof(el) != 'undefined' ? el : "popup";
 document.getElementById(el).style.display='none';
}

function ajax_update_element(e,url)
{
var xmlHttp;
try
  // Firefox, Opera 8.0+, Safari
  { xmlHttp=new XMLHttpRequest(); }
catch (e)
  {
  // Internet Explorer
  try { xmlHttp=new ActiveXObject("Msxml2.XMLHTTP"); }
  catch (e)
    {
    try { xmlHttp=new ActiveXObject("Microsoft.XMLHTTP"); }
    catch (e)
      { alert("Your browser does not support AJAX!"); return false; }
    }
  }
  xmlHttp.onreadystatechange=function()
    {
    if(xmlHttp.readyState==4)
      {
        e.value=xmlHttp.responseText;
      }
    }
  xmlHttp.open("GET",url+e.value,true);
  xmlHttp.send(null);
}


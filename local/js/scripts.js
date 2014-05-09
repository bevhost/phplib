
function SetFunction(id,field,func) {
        if (func=='off') {
                v=field;
        } else {
                v=func+':'+field;
                $('#cb'+id).prop('checked',true);
        }
        setFieldValue('hf'+id,v);
        setDivHtml('span_'+id,v);
        $('#ColumnChooser span').popover('hide');
}//used to set sql groupby aggregation functions in column chooser

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
	el = document.getElementById(e);
	el.style.visibility='visible';
	el.style.display='block';
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
	el = document.getElementById("DeliveryFrame");
	h = el.height;
	if (h==0) h=350; else h=0;
	el.height = h;
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
  return document.getElementById(fieldToGet).innerHTML;
}
function setDivHtml(fieldToSet,vItem) { 
   document.getElementById(fieldToSet).innerHTML = vItem;
}
function addDivHtml(fieldToSet,vItem) {
   document.getElementById(fieldToSet).innerHTML += vItem;
}
function setFieldValue(fieldToSet,vItem) {
   document.getElementById(fieldToSet).value = vItem;
}

function getFieldValue(e) {
 return _(e);
}
function _(e) {
 return document.getElementById(e).value;
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

function ShowSelector(el,linkdesc,action) {
        myform = el.form.elements['form_name'].value;
        Selector = el.name;
        fld = Selector.replace("_Selector","");
        el.form.elements[fld].value='';
        old_ajax('/find.php?Frm='+myform+';Col='+Selector+';Desc='+linkdesc+';a='+action+';v=',el);
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
                document.all[el].style.display='inline';
                document.all[el].style.visibility='visible';
        } else {
                popup = document.getElementById(el);
                popup.innerHTML=xmlHttp.responseText;
                popup.style.display='inline';
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

function show(el) {
 el = typeof(el) != 'undefined' ? el : "popup";
 document.getElementById(el).style.display='block';
 document.getElementById(el).style.visibility='visible';
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

function dollarformat(thisone){
  if (thisone.value.charAt(0)=='$') { s=1; prefix=''; } else { s=0; prefix='$'; }
  wd='w'; count=0; tempnum=thisone.value;
  for (i=s;i<tempnum.length;i++) if (wd=='d') count++; else if (tempnum.charAt(i)=='.') wd='d'; 
  if (wd=='w') extra = '.00'; else if (count==0) extra = '00'; else if (count==1) extra = '0'; else extra = '';
  thisone.value=prefix+tempnum+extra;
}

function dollarvalue(fieldToGet){
  vField = document.getElementById(fieldToGet).value;
  if (vField.charAt(0)=='$') return Number(vField.substring(1,vField.length));
  else return Number(vField);
}

var currentField;
var enum_set_popup;
function enum_set_popup_hide() {
        document.getElementById("enum_set_popup").style.display='none';
}
function enum_set_move(obj,target) {
        var curleft = 50;
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
function enum_set_chooser(elem,set) {
        var e,f,i,p;
	enum_set_popup = document.getElementById("enum_set_popup");
	enum_set_inner_HTML = '<a onclick="this.offsetParent.style.display=\'none\';" id="close">x</a><ul>';
	for ( o in enum_set[set] ) {
		v = enum_set[set][o];
		enum_set_inner_HTML += " <li><input type=checkbox name='enum_sets[]' value='"+v+"' onclick=enum_set_update(); >"+v+"</li>\n";
	}
	enum_set_inner_HTML += '</ul>';
	enum_set_popup.innerHTML = enum_set_inner_HTML;
	enum_set_popup.style.display='block';
	enum_set_popup.style.visibility='visible';
        enum_set_values = elem.value.split(',');
        f = document.forms["enum_set_chooser_form"];
        for (p in enum_set_values) {
                for (i=0; i<f.elements.length; i++) {
                        e = f.elements[i];
                        if ((e.name=='enum_sets[]') && (e.value==enum_set_values[p])) {
                                e.checked='checked';
                        } 
                }
        }
        enum_set_move(elem,enum_set_popup);
        currentField = elem;
	show_debug("chooser: "+elem.value);
}
function enum_set_update() {
        var str = "";
        f = document.forms["enum_set_chooser_form"];
        for (i=0; i<f.elements.length; i++) {
                e = f.elements[i];
                if ((e.name=='enum_sets[]') && (e.checked)) {
                        if (str.length>0) {str += ",";}
                        str += e.value;
                }
        }
        currentField.value = str;
	currentField.onblur();
}
function show_debug(str) {
	if (true) return;
	popup = document.getElementById("popup");
	str += "<br>";
	popup.style.display='block';
	popup.style.visibility='visible';
	popup.innerHTML += str;
	popup.style.position = 'fixed';
	popup.style.padding = '10px';
	popup.style.top = '100px';
	popup.style.left = '800px';
}

function nextPage() {
	id = 'ips_starting_with';
        el = document.getElementById(id);
	el.value = String(Number(el.value)+Number(getFieldValue('ips_row_count')));
	el.form.submit.click();
}

function prevPage() {
	id = 'ips_starting_with';
        el = document.getElementById(id);
	v = Number(el.value)-Number(getFieldValue('ips_row_count'));
	if (v<0) v = 0;
	el.value = v;
	el.form.submit.click();
}

function Jump10Pages() {
	id = 'ips_starting_with';
        el = document.getElementById(id);
	el.value = String(Number(el.value)+(10*Number(getFieldValue('ips_row_count'))));
	el.form.submit.click();
}

function Back10Pages() {
	id = 'ips_starting_with';
        el = document.getElementById(id);
	v = Number(el.value)-(10*Number(getFieldValue('ips_row_count')));
	if (v<0) v = 0;
	el.value = v;
	el.form.submit.click();
}

function showSortedBy(colname) {
	id = 'ips_sort_order';
        el = document.getElementById(id);
	el.value = colname
	el.form.submit.click();
}
function custom_query(q) {
        id = 'ips_custom_query';
        el = document.getElementById(id);
        el.value = q
}
function export_results(q) {
        id = 'ips_export_results';
        el = document.getElementById(id);
        el.value = q
}

function checkall(t) {
  for (i=0; i<document.forms[t].elements.length; i++) {
	e = document.forms[t].elements[i];
        if (e.type=='checkbox') e.checked='checked';
  }
}
function uncheckall(t) {
  for (i=0; i<document.forms[t].elements.length; i++) {
	e = document.forms[t].elements[i];
        if (e.type=='checkbox') e.checked=false;
  }
}
function invert(t) {
  for (i=0; i<document.forms[t].elements.length; i++) {
	e = document.forms[t].elements[i];
        if (e.type=='checkbox') {
                if (e.checked) e.checked=false;
                else e.checked='checked';
        }
  }
}
function confirmsubmit(e,t,n) {
  if (e.selectedIndex==0) return false;
  count = 0;
  action = e.options[e.selectedIndex].value;
  words = action.split(" ");
  for (i=0; i<document.forms[t].elements.length; i++) {
	el = document.forms[t].elements[i];
        if (el.type=='checkbox') {
                if (el.checked) count++;
        }
  }
  if (count<1) {
	alert('No items are checked.  Please select rows first');
        e.selectedIndex=0;
        return false;
  }
  if (action=='Delete') {
        if (!confirm("OK to Delete "+count+" "+n)) {
                e.selectedIndex=0;
                return false;
        }
  }
  if (words[0]=='Change') {
	var old='';
	for (k=2;k<words.length-1;k++){if(old.length){old=old+' ';}old=old+words[k];}
	var newvalue = prompt('New '+words[1]+' value:',old);
	if (newvalue==old || !newvalue) return false;
	var el = document.createElement('input');
	el.type='hidden';
	el.name='new_value';
	el.value=newvalue;
	document.forms[t].appendChild(el);
  }
  if (words[0]=='Copy' || words[0]==='Move') {
	main_form = t;
	if(el=document.getElementById('zchdr')){
		el.innerHTML = words[0]+" "+count+" records to zone";
	}
	if(el=document.getElementById('ZoneChooser')){
		el.style.display='inline';
		el.style.visibility='visible';
	}
	return false;
  }
  e.form.submit();
}

function setloc(tab,col,loc) {
        document.forms[tab].elements[col].value = "="+loc.options[loc.selectedIndex].value;
}


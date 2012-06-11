<style>
A
{
}
.white
{
	font-weight: bold;
	font-family: tahoma, arial;
	color:white;
}
</style>
<?php 
$bgc = false;
if (array_key_exists("bgc",$_REQUEST)) $bgc=$_REQUEST["bgc"];
if (!$bgc) $bgc='#808080'; ?>
<body bgcolor=<?=$bgc?>>
	<img src=images/logo.jpg>
	<b class=white>Set Backgrond Colour</b>
	<table><tr><td bgcolor=red>
	<a class=white href=javascript:LotsMoreRed()>Lots More Red</a> <br>
	<a class=white href=javascript:MoreRed()>More Red</a> <br>
	<a class=white href=javascript:LessRed()>Less Red</a> <br>
	<a class=white href=javascript:LotsLessRed()>Lots Less Red</a> 
	</td><td bgcolor=green>
	<a class=white href=javascript:LotsMoreGreen()>Lots More Green</a> <br>
	<a class=white href=javascript:MoreGreen()>More Green</a> <br>
	<a class=white href=javascript:LessGreen()>Less Green</a> <br>
	<a class=white href=javascript:LotsLessGreen()>Lots Less Green</a> 
	</td><td bgcolor=blue>
	<a class=white href=javascript:LotsMoreBlue()>Lots More Blue</a> <br>
	<a class=white href=javascript:MoreBlue()>More Blue</a> <br>
	<a class=white href=javascript:LessBlue()>Less Blue</a> <br>
	<a class=white href=javascript:LotsLessBlue()>Lots Less Blue</a> 
	</td></tr></table>
	<form id=mf name=mf onSubmit=javascript:SetColor(this.value);><input id=bgc name=bgc></form>
	<a class=white href="javascript:t='<table width=100%>';c=new Array('00','11','22','33','44','55','66','77','88','99','AA','BB','CC','DD','EE','FF');for(i=0;i<16;i++){for(j=0;j<16;j++){t+='<tr>';for(k=0;k<16;k++){L=c[i]+c[j]+c[k];t+='<td bgcolor=#'+L+'>'+L+'</td>'}t+='</tr>'}}document.write(t+'</table>');void(document.close())">Show Color Chart</a>
</body>
<script>
var hD="0123456789ABCDEF";
function d2h(d) {
	var h = hD.substr(d&15,1);
	while(d>15) {d>>=4;h=hD.substr(d&15,1)+h;}
	if (h.length==1) { h = "0"+h;	}
	return h;
}
function h2d(h) {return parseInt(h,16);}
function SetColor(c) {
	document.bgColor = c;
	return false;
}
function MoreRed () {
	CurrentColor = document.bgColor;
	Red = CurrentColor.substring(1,3);
	Green = CurrentColor.substring(3,5);
	Blue = CurrentColor.substring(5,7);
	Value = h2d(Red)
	if (Value<255) { Value++; }
	Red = d2h(Value);
	document.bgColor = "#"+Red+Green+Blue;
	document.mf.bgc.value = "#"+Red+Green+Blue;
}
function MoreGreen () {
	CurrentColor = document.bgColor;
	Red = CurrentColor.substring(1,3);
	Green = CurrentColor.substring(3,5);
	Blue = CurrentColor.substring(5,7);
	Value = h2d(Green)
	if (Value<255) { Value++; }
	Green = d2h(Value);
	document.bgColor = "#"+Red+Green+Blue;
	document.mf.bgc.value = "#"+Red+Green+Blue;
}
function MoreBlue () {
	CurrentColor = document.bgColor;
	Red = CurrentColor.substring(1,3);
	Green = CurrentColor.substring(3,5);
	Blue = CurrentColor.substring(5,7);
	Value = h2d(Blue)
	if (Value<255) { Value++; }
	Blue = d2h(Value);
	document.bgColor = "#"+Red+Green+Blue;
	document.mf.bgc.value = "#"+Red+Green+Blue;
}
function LessRed () {
	CurrentColor = document.bgColor;
	Red = CurrentColor.substring(1,3);
	Green = CurrentColor.substring(3,5);
	Blue = CurrentColor.substring(5,7);
	Value = h2d(Red)
	if (Value>0) { Value--; }
	Red = d2h(Value);
	document.bgColor = "#"+Red+Green+Blue;
	document.mf.bgc.value = "#"+Red+Green+Blue;
}
function LessGreen () {
	CurrentColor = document.bgColor;
	Red = CurrentColor.substring(1,3);
	Green = CurrentColor.substring(3,5);
	Blue = CurrentColor.substring(5,7);
	Value = h2d(Green)
	if (Value>0) { Value--; }
	Green = d2h(Value);
	document.bgColor = "#"+Red+Green+Blue;
	document.mf.bgc.value = "#"+Red+Green+Blue;
}
function LessBlue () {
	CurrentColor = document.bgColor;
	Red = CurrentColor.substring(1,3);
	Green = CurrentColor.substring(3,5);
	Blue = CurrentColor.substring(5,7);
	Value = h2d(Blue)
	if (Value>0) { Value--; }
	Blue = d2h(Value);
	document.bgColor = "#"+Red+Green+Blue;
	document.mf.bgc.value = "#"+Red+Green+Blue;
}
function LotsMoreRed () {
	CurrentColor = document.bgColor;
	Red = CurrentColor.substring(1,3);
	Green = CurrentColor.substring(3,5);
	Blue = CurrentColor.substring(5,7);
	Value = h2d(Red)
	if (Value<244) { Value+=11; }
	Red = d2h(Value);
	document.bgColor = "#"+Red+Green+Blue;
	document.mf.bgc.value = "#"+Red+Green+Blue;
}
function LotsMoreGreen () {
	CurrentColor = document.bgColor;
	Red = CurrentColor.substring(1,3);
	Green = CurrentColor.substring(3,5);
	Blue = CurrentColor.substring(5,7);
	Value = h2d(Green)
	if (Value<244) { Value+=11; }
	Green = d2h(Value);
	document.bgColor = "#"+Red+Green+Blue;
	document.mf.bgc.value = "#"+Red+Green+Blue;
}
function LotsMoreBlue () {
	CurrentColor = document.bgColor;
	Red = CurrentColor.substring(1,3);
	Green = CurrentColor.substring(3,5);
	Blue = CurrentColor.substring(5,7);
	Value = h2d(Blue)
	if (Value<244) { Value+=11; }
	Blue = d2h(Value);
	document.bgColor = "#"+Red+Green+Blue;
	document.mf.bgc.value = "#"+Red+Green+Blue;
}
function LotsLessRed () {
	CurrentColor = document.bgColor;
	Red = CurrentColor.substring(1,3);
	Green = CurrentColor.substring(3,5);
	Blue = CurrentColor.substring(5,7);
	Value = h2d(Red)
	if (Value>10) { Value-=11; }
	Red = d2h(Value);
	document.bgColor = "#"+Red+Green+Blue;
	document.mf.bgc.value = "#"+Red+Green+Blue;
}
function LotsLessGreen () {
	CurrentColor = document.bgColor;
	Red = CurrentColor.substring(1,3);
	Green = CurrentColor.substring(3,5);
	Blue = CurrentColor.substring(5,7);
	Value = h2d(Green)
	if (Value>10) { Value-=11; }
	Green = d2h(Value);
	document.bgColor = "#"+Red+Green+Blue;
	document.mf.bgc.value = "#"+Red+Green+Blue;
}
function LotsLessBlue () {
	CurrentColor = document.bgColor;
	Red = CurrentColor.substring(1,3);
	Green = CurrentColor.substring(3,5);
	Blue = CurrentColor.substring(5,7);
	Value = h2d(Blue)
	if (Value>10) { Value-=11; }
	Blue = d2h(Value);
	document.bgColor = "#"+Red+Green+Blue;
	document.mf.bgc.value = "#"+Red+Green+Blue;
}
</script>

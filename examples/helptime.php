<html>
<body>
<P>
MySQL retrieves and displays <CODE>TIME</CODE> values in <CODE>'HH:MM:SS'</CODE>
format (or <CODE>'HHH:MM:SS'</CODE> format for large hours values).  <CODE>TIME</CODE>
values may range from <CODE>'-838:59:59'</CODE> to <CODE>'838:59:59'</CODE>.  The reason
the hours part may be so large is that the <CODE>TIME</CODE> type may be used not
only to represent a time of day (which must be less than 24 hours), but also
elapsed time or a time interval between two events (which may be much greater
than 24 hours, or even negative).  It doesn't make sense to specify more than 24 hours
of time when you are also providing a date.

</P>
<P>
You can specify <CODE>TIME</CODE> values in a variety of formats:

</P>

<UL>
<LI>

As a string in <CODE>'D HH:MM:SS.fraction'</CODE> format.  (Note that
MySQL doesn't yet store the fraction for the time column).  One
can also use one of the following ``relaxed'' syntax:

<CODE>HH:MM:SS.fraction</CODE>, <CODE>HH:MM:SS</CODE>, <CODE>HH:MM</CODE>, <CODE>D HH:MM:SS</CODE>,
<CODE>D HH:MM</CODE>, <CODE>D HH</CODE> or <CODE>SS</CODE>.  Here <CODE>D</CODE> is days between 0-33.

<LI>

As a string with no delimiters in <CODE>'HHMMSS'</CODE> format, provided that
it makes sense as a time.  For example, <CODE>'101112'</CODE> is understood as
<CODE>'10:11:12'</CODE>, but <CODE>'109712'</CODE> is illegal (it has a nonsensical
minute part) and becomes <CODE>'00:00:00'</CODE>.

<LI>

As a number in <CODE>HHMMSS</CODE> format, provided that it makes sense as a time.
For example, <CODE>101112</CODE> is understood as <CODE>'10:11:12'</CODE>.  The following
alternative formats are also understood: <CODE>SS</CODE>, <CODE>MMSS</CODE>,<CODE>HHMMSS</CODE>,
<CODE>HHMMSS.fraction</CODE>.  Note that MySQL doesn't yet store the
fraction part.

</UL>

<P>
For <CODE>TIME</CODE> values specified as strings that include a time part
delimiter, it is not necessary to specify two digits for hours, minutes, or
seconds values that are less than <CODE>10</CODE>.  <CODE>'8:3:2'</CODE> is the same as
<CODE>'08:03:02'</CODE>.

</P>
<P>
Be careful about assigning ``short'' <CODE>TIME</CODE> values to a <CODE>TIME</CODE>
column. Without semicolon, MySQL interprets values using the 
assumption that the rightmost digits represent seconds. (MySQL 
interprets <CODE>TIME</CODE> values as elapsed time rather than as time of 
day.) For example, you might think of <CODE>'1112'</CODE> and <CODE>1112</CODE> as 
meaning <CODE>'11:12:00'</CODE> (12 minutes after 11 o'clock), but
MySQL interprets them as <CODE>'00:11:12'</CODE> (11 minutes, 12 seconds).
Similarly, <CODE>'12'</CODE> and <CODE>12</CODE> are interpreted as <CODE>'00:00:12'</CODE>.
<CODE>TIME</CODE> values with semicolon, instead, are always treated as
time of the day. That is <CODE>'11:12'</CODE> will mean <CODE>'11:12:00'</CODE>,
not <CODE>'00:11:12'</CODE>.

</P>
<P>
Values that lie outside the <CODE>TIME</CODE> range
but are otherwise legal are clipped to the appropriate
endpoint of the range.  For example, <CODE>'-850:00:00'</CODE> and
<CODE>'850:00:00'</CODE> are converted to <CODE>'-838:59:59'</CODE> and
<CODE>'838:59:59'</CODE>.

</P>
<P>
Illegal <CODE>TIME</CODE> values are converted to <CODE>'00:00:00'</CODE>.  Note that
because <CODE>'00:00:00'</CODE> is itself a legal <CODE>TIME</CODE> value, there is no way
to tell, from a value of <CODE>'00:00:00'</CODE> stored in a table, whether the
original value was specified as <CODE>'00:00:00'</CODE> or whether it was illegal.

</P>
<hr>
<p align=center>
&nbsp;<a href=javascript:print();>Print</a>&nbsp;
&nbsp;<a href=javascript:close();>Close Window</a>&nbsp;
</p>
</body>
</html>

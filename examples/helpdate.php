<html>
<P>
All <CODE>TIMESTAMP</CODE> columns have the same storage size, regardless of
display size.  The most common display sizes are 6, 8, 12, and 14.  You can
specify an arbitrary display size at table creation time, but values of 0 or
greater than 14 are coerced to 14.  Odd-valued sizes in the range from 1 to
13 are coerced to the next higher even number.

</P>
<P>
You can specify <CODE>DATETIME</CODE>, <CODE>DATE</CODE>, and <CODE>TIMESTAMP</CODE> values using
any of a common set of formats:

</P>

<UL>
<LI>

As a string in either <CODE>'YYYY-MM-DD HH:MM:SS'</CODE> or <CODE>'YY-MM-DD
HH:MM:SS'</CODE> format.  A ``relaxed'' syntax is allowed--any punctuation
character may be used as the delimiter between date parts or time parts.
For example, <CODE>'98-12-31 11:30:45'</CODE>, <CODE>'98.12.31 11+30+45'</CODE>,
<CODE>'98/12/31 11*30*45'</CODE>, and <CODE>'98@12@31 11^30^45'</CODE> are
equivalent.

<LI>

As a string in either <CODE>'YYYY-MM-DD'</CODE> or <CODE>'YY-MM-DD'</CODE> format.
A ``relaxed'' syntax is allowed here, too.  For example, <CODE>'98-12-31'</CODE>,
<CODE>'98.12.31'</CODE>, <CODE>'98/12/31'</CODE>, and <CODE>'98@12@31'</CODE> are
equivalent.

<LI>

As a string with no delimiters in either <CODE>'YYYYMMDDHHMMSS'</CODE> or
<CODE>'YYMMDDHHMMSS'</CODE> format, provided that the string makes sense as a
date.  For example, <CODE>'19970523091528'</CODE> and <CODE>'970523091528'</CODE> are
interpreted as <CODE>'1997-05-23 09:15:28'</CODE>, but <CODE>'971122129015'</CODE> is
illegal (it has a nonsensical minute part) and becomes <CODE>'0000-00-00
00:00:00'</CODE>.

<LI>

As a string with no delimiters in either <CODE>'YYYYMMDD'</CODE> or <CODE>'YYMMDD'</CODE>
format, provided that the string makes sense as a date.  For example,
<CODE>'19970523'</CODE> and <CODE>'970523'</CODE> are interpreted as
<CODE>'1997-05-23'</CODE>, but <CODE>'971332'</CODE> is illegal (it has nonsensical month
and day parts) and becomes <CODE>'0000-00-00'</CODE>.

<LI>

As a number in either <CODE>YYYYMMDDHHMMSS</CODE> or <CODE>YYMMDDHHMMSS</CODE>
format, provided that the number makes sense as a date.  For example,
<CODE>19830905132800</CODE> and <CODE>830905132800</CODE> are interpreted as
<CODE>'1983-09-05 13:28:00'</CODE>.

<LI>

As a number in either <CODE>YYYYMMDD</CODE> or <CODE>YYMMDD</CODE>
format, provided that the number makes sense as a date.  For example,
<CODE>19830905</CODE> and <CODE>830905</CODE> are interpreted as <CODE>'1983-09-05'</CODE>.

</UL>

<P>
Illegal <CODE>DATETIME</CODE>, <CODE>DATE</CODE>, or <CODE>TIMESTAMP</CODE> values are converted
to the ``zero'' value of the appropriate type (<CODE>'0000-00-00 00:00:00'</CODE>,
<CODE>'0000-00-00'</CODE>, or <CODE>00000000000000</CODE>).

</P>
<P>
For values specified as strings that include date part delimiters, it is not
necessary to specify two digits for month or day values that are less than
<CODE>10</CODE>.  <CODE>'1979-6-9'</CODE> is the same as <CODE>'1979-06-09'</CODE>.  Similarly,
for values specified as strings that include time part delimiters, it is not
necessary to specify two digits for hour, month, or second values that are
less than <CODE>10</CODE>.  <CODE>'1979-10-30 1:2:3'</CODE> is the same as
<CODE>'1979-10-30 01:02:03'</CODE>.

</P>
<P>
Values specified as numbers should be 6, 8, 12, or 14 digits long.  If the
number is 8 or 14 digits long, it is assumed to be in <CODE>YYYYMMDD</CODE> or
<CODE>YYYYMMDDHHMMSS</CODE> format and that the year is given by the first 4
digits.  If the number is 6 or 12 digits long, it is assumed to be in
<CODE>YYMMDD</CODE> or <CODE>YYMMDDHHMMSS</CODE> format and that the year is given by the
first 2 digits.  Numbers that are not one of these lengths are interpreted
as though padded with leading zeros to the closest length.

</P>
<hr>
<p align=center>
&nbsp;<a href=javascript:print();>Print</a>&nbsp;
&nbsp;<a href=javascript:close();>Close Window</a>&nbsp;
</p>
</html>

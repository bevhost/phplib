<SCRIPT Language="JavaScript"> 


function ClearList( l )
{ 
    var i;
    for( i = 0; i < l.options.length; i++ )
    {
       if( l.options[i].value != -1 )
              l.options[i] = null;
    }


}

function UpdateMenu( f, sel_t )
{


        var field_names = new Array();
	var field_values = new Array();

	var table_names = new Array();
	

	<?
	  for( $i = 0; list( #i, $table ) = each( $table_list ); $i++ )
	  {
	    
	      echo "\ttable_names[$i] = \"$table\";\n";
	      print "field_names[\"$table\"] = new Array();\n";
	      print "field_values[\"$table\"] = new Array();\n";
	      $fields = $table_fields[$table];

	      for( $j = 0; list( $value, $name) = each( $fields ); $j++ )
	      {
	           
	           echo "\t\tfield_names[\"$table\"][$j] = \"$name\";\n";
		   echo "\t\tfield_values[\"$table\"][$j] = \"$value\";\n";
	      }   	   
	  }
	?>
	


        var opArray = sel_t.options;
	var index = sel_t.selectedIndex;
	var selOp = opArray[index];
        var Table = selOp.value;
	

	var re = /table/i;
	
	var foo = sel_t.name.replace( re, "field" );
	var sel_f = f.elements[foo];
	
	if( ! Table )
	{
	   Table = selOp.text;
	}
	
	if( Table == -1 )
	{
	  ClearList( sel_f );
	  history.go(0);
	  return;
	}

	ClearList( sel_f );

	var empty = new Option( '-', -1 );
	sel_f.options[0] = empty;

	for( var i = 1; i < field_values[Table].length; i++ )
	{
	    var temp = new Option( field_names[Table][i-1], field_values[Table][i-1] );
	    sel_f.options[i] = temp;
	}
	history.go(0);

}

function PutText( what )
{

	var f = what.form;


	if( what.options[what.selectedIndex].value == -1 )
	{
//	       alert( "return" );
	       return;
	}
	

//        alert( what.selectedIndex );
	var fields = what.options;

	var re = /field_sel2/i;	
	var foo = what.name.replace( re, "input" );
	var target = f.elements[foo];
	
	re = /field/i;
	foo = what.name.replace( re, "table" );
	var table = f.elements[foo];
	var tables = table.options;

	// target is a text box
	
        var theText = tables[table.selectedIndex].value + "." + fields[what.selectedIndex].value;
	
	target.value = theText;
}
	

}
</SCRIPT>




<?php

// Given a list of processids that have changed ("changes.txt", if we are lazy
// just list every processid) we generate a list of the edit actions made to
// that processid, and store these in the 'edits" table.

require_once('changes.php');

$filename = 'changes.txt';

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$processid = trim(fgets($file_handle));

	$operations = get_changes($processid);
	
	//print_r($operations);
	
	foreach ($operations as $op)
	{
		echo obj_to_sql($op, 'edits') . "\n";
	}
	
}

?>

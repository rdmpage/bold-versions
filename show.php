<?php

// Explore visualisations of changing BINs

require_once (dirname(__FILE__) . '/sqlite.php');
require_once (dirname(__FILE__) . '/changes.php');
require_once (dirname(__FILE__) . '/colour.php');

//----------------------------------------------------------------------------------------

if (0)
{
	// version history for a barcode
	
	$processid = 'GMMGN3306-14';
	//$processid = 'GMCDA679-16';
	//$processid = 'GMCCB776-17';
	//$processid = 'ASQSQ702-10'; // multiple bin moves
	//$processid = 'AACTA1277-20'; // Margarita Miklasevskaja to Margarita G Miklasevskaja
	
	//$processid = 'OPPUM2195-17'; // Poanes hobomok to Lon hobomok
	$processid = 'XAF587-05';
	
	//$processid = 'GBMLG0261-06';
	//$processid = 'DUTCH363-19';
	//$processid = 'GBMLG0277-06';
	
	//$processid = 'EPNG7021-12';
	
	$sql = 'SELECT * FROM version WHERE processid="' . $processid . '" ORDER BY valid_from';
	
	$data = db_get($sql);
	
	print_r($data);
	
	$last = null;
	
	foreach ($data as $current)
	{
		if ($last)
		{
			$operations = array();
			
			$op = '';
			
			// BIN
			
			if (isset($last->bin_uri) && isset($current->bin_uri))
			{
				if ($last->bin_uri != $current->bin_uri)
				{
					$op = 'replace bin';
				}
			}
			else
			{
				if (isset($last->bin_uri))
				{
					$op = 'delete bin';
				}
				elseif (isset($current->bin_uri))
				{
					$op = 'add bin';
				}
			}
			
			if ($op != '')
			{
				$operations[] = $op;
				$op = '';
			}
					
			// Identification
			if (isset($last->identification) && isset($current->identification))
			{
				if (strcmp($last->identification, $current->identification) !== 0)
				{
					$op = 'replace identification';
					
					if (possible_synonyms($last->identification, $current->identification))
					{
						$op = 'edit identification';
					}
				}
			}
			else
			{
				if (isset($last->identification))
				{
					$op = 'delete identification';
				}
				elseif (isset($current->identification))
				{
					$op = 'add identification';
				}
			}
			
			if ($op != '')
			{
				$operations[] = $op;
				$op = '';
			}
			
			// Identified by
			if (isset($last->identified_by) && isset($current->identified_by))
			{
				if ($last->identified_by != $current->identified_by)
				{
					$d = levenshtein($last->identified_by, $current->identified_by);
				
					if ($d <= 5) // 5 is enough to handle Paul Hebert => Paul D.N. Hebert
					{
						$op = 'edit identified by'; // BOLD cleaning up people names
					}
					else
					{
						$op = 'replace identified by';
					}
				}
			}
			else
			{
				if (isset($last->identified_by))
				{
					$op = 'delete identified_by';
				}
				elseif (isset($current->identified_by))
				{
					$op = 'add identified_by';
				}
			}
			
			if ($op != '')
			{
				$operations[] = $op;
				$op = '';
			}
			
			print_r($operations);		
		}
		
		$last = $current;
	
	}
}
else
{
	// version history for a bin
	
	// BOLD:ADJ8510 looks like a bin that splits in two
	
	$bin = 'BOLD:ADJ8510'; // https://bold-view-bf2dfe9b0db3.herokuapp.com/record/ABMMC14027-10
	
	//$bin = 'BOLD:AAB1543';
	
	//$bin = 'BOLD:ACA8528';
	
	//$bin = 'BOLD:AAH8705';
	//$bin = 'BOLD:AEU7731';
	
	//$bin = 'BOLD:AAF8217';
	
	
	
	/*
	BOLD:AAH8697 is listed in the description of 
	https://tb.plazi.org/GgServer/html/23F3367C3AD082ADE37550FFA9C68FDD/1
	 Heterogamus donstonei but this BIN is currently not associated with name in BOLD. 
	 Which BIN has the type specimen?
	*/
	$bin = 'BOLD:AAH8697'; // complex
	
	
	//$bin = 'BOLD:AAD7140'; // some barcodes fall in and out of the BIN
	
	//$bin = 'BOLD:AAC4712';
	
	/* 
	Morphologically identified specimens were placed in three well-differentiated 
	BINs for R. arria (BOLD:ABX0547, BOLD: ADD3784, BOLD:ADD3785) and 
	four BINs for R. bilix (BOLD:ACF3699, BOLD: ABX0491, BOLD:ADD1839, BOLD:ABX0493). 
	*/
	$bin = 'BOLD:ABX0491';
	//$bin = 'BOLD:ACF3699'; // now ABX0491
	//$bin = 'BOLD:ADD1839'; // no change
	//$bin = 'BOLD:ABX0493'; // no change
	
	//$bin = 'BOLD:ADD3785';
	
	/*
	https://cdnsciencepub.com/doi/10.1139/gen-2017-0096
	
	Table 1, ABZ6180, which is now merged into AAA5893
	AAA5893 is a big BIN that has subsumed several others over time, and is included in
	Table 2
	
	Some barcodes in AAA5893 have nice illustrations presumably from a paper, e.g. 
	BHTT083-09, and the term "Plate" means plate as in figure.
	
	*/
	$bin = 'BOLD:ABZ6180'; 
	$bin = 'BOLD:AAA5893';
	
	//$bin = 'BOLD:AAO3000';
	
	
	//$bin = 'BOLD:AAB3486';
	
	// get all barcodes that have ever been in the BIN
	$sql = 'SELECT v2.processid, v2.bin_uri, v2.valid_from, v2.valid_to
	FROM version as v1 
	INNER JOIN version AS v2 ON v1.processid=v2.processid
	WHERE v1.bin_uri="' . $bin . '";';
	
	$data = db_get($sql);
		
	//print_r($data);
	
	// need to process with care
	// there may be changes to a processid that aren't changes in BIN membership
	// but other changes (e.g., BIN taxonomy changes)
	
	// need way to visualise BIN membership changes over time...
	
	$steps = array();
	$items = array();
	
	foreach ($data as $row)
	{
		// get the unique time stamps
		if (!in_array($row->valid_from, $steps))
		{
			$steps[] = $row->valid_from;
		}
		if (isset($row->valid_to))
		{
			if (!in_array($row->valid_to, $steps))
			{
				$steps[] = $row->valid_to;
			}
		}
		
		// get the processids
		if (!isset($items[$row->processid]))
		{
			$items[$row->processid] = array();
		}
		
	}
	
	// sort the time stamps
	sort($steps);
	$steps = array_flip($steps);

	$num_steps = count($steps);
	
	// get histories
	foreach ($data as $row)
	{	
		$start = $steps[$row->valid_from];
		
		if (isset($row->valid_to))
		{
			$end = $steps[$row->valid_to];
		}
		else
		{
			$end = $num_steps;
		}
		
		for ($i = $start; $i < $end; $i++)
		{
			if (isset($row->bin_uri))
			{
				$items[$row->processid][$i] = $row->bin_uri;
			}
			else
			{
				$items[$row->processid][$i] = "none";
			}
		}
		
	}
	
	foreach ($items as &$item)
	{
		ksort($item);
	}
	
	//print_r($items);
	//print_r($steps);
	
	// convert to time slices
	
	/*
	$num_slices = count($time_slices);

    // Step 1: Build group contents at each time
    $groupsAtTime = [];
    foreach ($items as $item => $path) 
    {
        foreach ($path as $t => $group) 
        {
            $groupsAtTime[$t][$group][] = $item;
        }
    }
    */
    
    $clusters = array();
    
    $paths = array();
    
    $nodes = array();
    
    foreach ($items as $processid => $item)
    {
    	$path = '';
    	
    	$first = true;
    	foreach ($item as $time_slice => $bin)
    	{
    		// link to predecessor
    		if (!$first)
    		{
    			$path .= "->";
    		}
    		$first = false;
    		
    		$node_id = str_replace('-', '', $processid) . '_' . $time_slice;
    		
    		$nodes[] = "node [label=\"$processid\"] $node_id;";
    		
    		$path .= $node_id;
    		
    		if (!isset($clusters[$time_slice]))
    		{
    			$clusters[$time_slice] = array();    			
    		}
    		if (!isset($clusters[$time_slice][$bin]))
    		{
    			$clusters[$time_slice][$bin] = array();    			
    		}
    		$clusters[$time_slice][$bin][] = $node_id;
    	}
 		$path .= ";";
 		
 		$paths[] = $path;
    
    }
    
    //print_r($clusters);
    //print_r($paths);
    
    // List nodes in each cluster
    $cluster_names = array();
   	foreach ($clusters as $time_slice => $cluster)
    {
    	foreach ($cluster as $name => $members)
    	{
    		if (!in_array($name, $cluster_names))
    		{
    			$cluster_names[] = $name;
    		}
    	}
    }
    
    $cluster_colours = array();
    
    $num_clusters = count($cluster_names);
    for ($i = 0; $i < $num_clusters; $i++)
    {
		$h = $i * 137.6;
		$c = 80;
		$l = 80;
		
		$rgb = fromHCL($h, $c, $l);

		$browser = 'rgb(' . round($rgb[0], 0) . ',' . round($rgb[1], 0) . ',' . round($rgb[2], 0) . ')';
		
		$hex = array(
			str_pad(dechex(round($rgb[0], 0)), 2, '0', STR_PAD_LEFT),
			str_pad(dechex(round($rgb[1], 0)), 2, '0', STR_PAD_LEFT),
			str_pad(dechex(round($rgb[2], 0)), 2, '0', STR_PAD_LEFT)
			);
   		
   		$cluster_colours[$cluster_names[$i]] = '#' . join("", $hex);
   		
   		//print_r($rgb);
   		//print_r($hex);
    }
  
  	//print_r($cluster_names);
  	//print_r($cluster_colours);
  	
  	//exit();
    
    echo "digraph {\n";
    echo "rankdir=LR;\n";
 	echo "node [fontname=\"Helvetica,Arial,sans-serif\",style=\"filled\",shape=\"box\",fillcolor=\"white\"]\n";
	
	echo join("\n", $nodes) . "\n";
    
    $cluster_number = 0;
    foreach ($clusters as $time_slice => $cluster)
    {
    	
    	foreach ($cluster as $name => $members)
    	{
    		echo "subgraph cluster_" . $cluster_number++ . " {\n";

			echo "   label=\"" . $name . "\";\n";
			echo "   fontname=\"bold\";\n";
			
			if ($name == "none")
			{
				echo "   style=\"invis\";\n";
			}
			else
			{
    			echo "   style=\"filled,rounded\";\n";
    		}   		
			echo "   color=\"" . $cluster_colours[$name] . "\";\n";
    		
    		echo join(" ", $members) . ";\n";
    		
    		echo "}\n";
    		
    	}
    	
    }
    
    echo join("\n", $paths) . "\n";
    
    echo "}\n";
}

?>

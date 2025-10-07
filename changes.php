<?php

// 

require_once (dirname(__FILE__) . '/sqlite.php');
require_once (dirname(__FILE__) . '/colour.php');

//----------------------------------------------------------------------------------------
/* 
doi:10.1186/1471-2105-14-16

The stemming (equivalent) in Taxamatch equates 
-a, -is -us, -ys, -es, -um, -as and -os when 
they occur at the end of a species epithet 
(or infraspecies) by changing them all to -a. 
Thus (for example) the epithets “nitidus”, “nitidum”, 
“nitidus” and “nitida” will all be considered 
equivalent following this process.  

To this I've added -se and -sis, -ue and -uis (and more)
*/
function stem_epithet($epithet)
{
	$stem = $epithet;
	$matched = '';
	
	// 6
    // -ulatum
    if ($matched == '') {
        if (preg_match('/ulatum$/', $epithet)) {
            $matched = 'ulatum';
        }
    }

	
	// 4
	
    // -atum
    if ($matched == '') {
        if (preg_match('/atum$/', $epithet)) {
            $matched = 'atum';
        }
    }
	
	// 3
	
    // -ata
    if ($matched == '') {
        if (preg_match('/ata$/', $epithet)) {
            $matched = 'ata';
        }
    }	
	
    // -lis
    if ($matched == '') {
        if (preg_match('/lis$/', $epithet)) {
            $matched = 'lis';
        }
    }
	
    // -sis
    if ($matched == '') {
        if (preg_match('/sis$/', $epithet)) {
            $matched = 'sis';
        }
    }
    
    // -uis
    if ($matched == '') {
        if (preg_match('/uis$/', $epithet)) {
            $matched = 'uis';
        }
    }
    
	
	// 2

    // -se
    if ($matched == '') {
        if (preg_match('/se$/', $epithet)) {
            $matched = 'se';
        }
    } 
       
    // -ue
    if ($matched == '') {
        if (preg_match('/ue$/', $epithet)) {
            $matched = 'ue';
        }
    }
    // -is
    if ($matched == '') {
        if (preg_match('/is$/', $epithet)) {
            $matched = 'is';
        }
    }
    // -us
    if ($matched == '') {
        if (preg_match('/us$/', $epithet)) {
            $matched = 'us';
        }
    }
    // -ys
    if ($matched == '') {
        if (preg_match('/ys$/', $epithet)) {
            $matched = 'ys';
        }
    }
    // -es
    if ($matched == '') {
        if (preg_match('/es$/', $epithet)) {
            $matched = 'es';
        }
    }
    // -um
    if ($matched == '') {
        if (preg_match('/um$/', $epithet)) {
            $matched = 'um';
        }
    }
    // -as
    if ($matched == '') {
        if (preg_match('/as$/', $epithet)) {
            $matched = 'as';
        }
    }
    // -os
    if ($matched == '') {
        if (preg_match('/os$/', $epithet)) {
            $matched = 'os';
        }
    }

    // -le
    if ($matched == '') {
        if (preg_match('/le$/', $epithet)) {
            $matched = 'le';
        }
    }

    // stem
    if ($matched != '') {
        $pattern = '/' . $matched . '$/';
        $stem = preg_replace($pattern, 'a', $epithet);
    } else {
        /* Tony's algorithm doesn't handle ii and i */
        // -ii -i 
        if (preg_match('/ii$/', $epithet)) {
            $stem = preg_replace('/ii$/', 'i', $epithet);
        }
    }
    
    //echo "-- stem=$stem\n";

    return $stem;
}

//----------------------------------------------------------------------------------------
// Test whether two taxonomic names are likely to be synonyms based on shared epithets
function possible_synonyms($name1, $name2)
{
	$synomyms = false;
	
	// do names have epithets?
	$parts1 = explode(' ', $name1);
	$parts2 = explode(' ', $name2);
	
	$num_parts1 = count($parts1);
	$num_parts2 = count($parts2);

	if ($num_parts1 >= 2 && $num_parts2 >= 2)
	{
		//echo "-- " . stem_epithet($parts1[$num_parts1 - 1]) . ' vs. ' . stem_epithet($parts2[$num_parts2 - 1]) . "\n";
	
		// are stemmed epithets the same?
		if (strcmp(stem_epithet($parts1[$num_parts1 - 1]), stem_epithet($parts2[$num_parts2 - 1])) == 0)
		{
			$synomyms = true;
		}
	}
	
	
	return $synomyms;
}


//----------------------------------------------------------------------------------------
// Create an edit operation, by default this has the barcode id (processid) and
// the date of the operation
function new_operation($processid, $date)
{
	$op = new stdclass;
	$op->processid = $processid;
	$op->action = null;
	$op->from = null;
	$op->to = null;
	$op->date = $date;
	
	return $op;
}

//----------------------------------------------------------------------------------------
// Get summary of changes for a barcode, we return this as a list of "operations" 
function get_changes($processid)
{
	$operations = array();
	
	$sql = 'SELECT * FROM version WHERE processid="' . $processid . '" ORDER BY valid_from';
	
	$data = db_get($sql);
	
	//print_r($data);
	
	$last = null; // previous state of barcode (i.e., in a dated release of the database)
	
	foreach ($data as $current)
	{
		if ($last)
		{
			$op = new_operation($processid, $current->valid_from);
			
			// BIN
			if (isset($last->bin_uri) && isset($current->bin_uri))
			{
				if ($last->bin_uri != $current->bin_uri)
				{
					$op->action = 'replace bin';
					$op->from = $last->bin_uri;
					$op->to = $current->bin_uri;
				}
			}
			else
			{
				// 
				if (isset($last->bin_uri))
				{
					$op->action = 'delete bin';
					$op->from = $last->bin_uri;
				}
				elseif (isset($current->bin_uri))
				{
					$op->action = 'add bin';
					$op->to = $current->bin_uri;					
				}
			}
			
			if ($op->action)
			{
				$operations[] = $op;
				$op = new_operation($processid, $current->valid_from);
			}
					
			// Identification
			if (isset($last->identification) && isset($current->identification))
			{
				if (strcmp($last->identification, $current->identification) !== 0)
				{
					$op->action = 'replace identification';

					$op->from = $last->identification;
					$op->to = $current->identification;					
					
					// if names are plausibly synonyms, change edit actio
					if (possible_synonyms($last->identification, $current->identification))
					{
						$op->action = 'edit identification';
					}
				}
			}
			else
			{
				if (isset($last->identification))
				{
					$op->action = 'delete identification';
					$op->from = $last->identification;
				}
				elseif (isset($current->identification))
				{
					$op->action = 'add identification';
					$op->to = $current->identification;
				}
			}
			
			if ($op->action)
			{
				$operations[] = $op;
				$op = new_operation($processid, $current->valid_from);
			}
			
			// Identified by
			if (isset($last->identified_by) && isset($current->identified_by))
			{
				if ($last->identified_by != $current->identified_by)
				{
					$d = levenshtein($last->identified_by, $current->identified_by);
				
					if ($d <= 5) // 5 is enough to handle Paul Hebert => Paul D.N. Hebert
					{
						$op->action = 'edit identified_by'; // BOLD cleaning up people names
					}
					else
					{
						$op->action = 'replace identified_by';
					}
					$op->from = $last->identified_by;
					$op->to = $current->identified_by;					
				}
			}
			else
			{
				if (isset($last->identified_by))
				{
					$op->action = 'delete identified_by';
					$op->from = $last->identified_by;
				}
				elseif (isset($current->identified_by))
				{
					$op->action = 'add identified_by';
					$op->to = $current->identified_by;	
				}
			}
			
			if ($op->action)
			{
				$operations[] = $op;
				$op = new_operation($processid, $current->valid_from);
			}	
				
		}
		
		$last = $current;
	
	}
	
	return $operations;
}
	
if (0)
{
	
	$processid = 'AACTA071-20';
	$processid = 'OPPUM2195-17';
	$processid = 'AACTA1277-20';
	$processid = 'XAF587-05';
	$processid = 'AACTA2287-20';
	
	$operations = get_changes($processid);
	
	print_r($operations);
}

?>

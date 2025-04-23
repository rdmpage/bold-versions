<?php

// Parse BOLD file and generate dump of of core data to track versions

ini_set('memory_limit', '-1');

require_once (dirname(__FILE__) . '/sqlite.php');

//----------------------------------------------------------------------------------------
// Convert NCBI style date (e.g., "07-OCT-2015") to Y-m-d
function parse_ncbi_date($date_string)
{
	$date = '';
	
	if (false != strtotime($date_string))
	{
		// format without leading zeros
		$date = date("Y-m-d", strtotime($date_string));
	}	
	
	return $date;
}

//----------------------------------------------------------------------------------------

$headings = array();

$debug = false;
$debug = true;


$tablename = 'version';

$row_count = 0;

$filename = "/Volumes/LaCie/BOLD-data-packages/iBOLD.31-Dec-2016/iBOLD.31-Dec-2016.tsv"; // Curated version of iBOL

//$filename = "/Volumes/LaCie/BOLD-data-packages/BOLD_Public.30-Mar-2022/BOLD_Public.30-Mar-2022.tsv"; // 0
//$filename = "/Volumes/LaCie/BOLD-data-packages/BOLD_Public.06-Jul-2022/BOLD_Public.06-Jul-2022.tsv"; // 1
//$filename = "/Volumes/LaCie/BOLD-data-packages/BOLD_Public.28-Sep-2022/BOLD_Public.28-Sep-2022.tsv"; // 2
//$filename = "/Volumes/LaCie/BOLD-data-packages/BOLD_Public.30-Dec-2022/BOLD_Public.30-Dec-2022.tsv"; // 3

//$filename = "/Volumes/LaCie/BOLD-data-packages/BOLD_Public.31-Mar-2023/BOLD_Public.31-Mar-2023.tsv"; // 0
//$filename = "/Volumes/LaCie/BOLD-data-packages/BOLD_Public.30-Jun-2023/BOLD_Public.30-Jun-2023.tsv"; // 1
//$filename = "/Volumes/LaCie/BOLD-data-packages/BOLD_Public.29-Sep-2023/BOLD_Public.29-Sep-2023.tsv"; // 2
//$filename = "/Volumes/LaCie/BOLD-data-packages/BOLD_Public.29-Dec-2023/BOLD_Public.29-Dec-2023.tsv"; // 3

//$filename = "/Volumes/LaCie/BOLD-data-packages/BOLD_Public.29-Mar-2024/BOLD_Public.29-Mar-2024.tsv"; // 0
//$filename = "/Volumes/LaCie/BOLD-data-packages/BOLD_Public.19-Jul-2024/BOLD_Public.19-Jul-2024.tsv"; // 1
//$filename = "/Volumes/LaCie/BOLD-data-packages/BOLD_Public.06-Sep-2024/BOLD_Public.06-Sep-2024.tsv"; // 2 - this was used to make bold-viewer

$version = '';
$date = '';

if (preg_match('/\.(\d+-[A-Z]\w+-\d+)\.tsv/', $filename, $m))
{
	$version = $m[1];
	// convert version to ISO date
	$date = parse_ncbi_date($version);
}
else
{
	echo "Bad version\n";
	exit();
}

// taxonomy	ranks (need this if we have to construct identification)					
$taxon_keys = array(
	"kingdom",
	"phylum",
	"class",
	"order",
	"family",
	"subfamily",
	"tribe",
	"genus",
	"species",
	"subspecies",
);

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
		
	$row = explode("\t",$line);
	
	$go = is_array($row) && count($row) > 1;
	
	if ($go)
	{
		if ($row_count == 0)
		{
			$headings = $row;	
			print_r($headings);
			//exit();
		}
		else
		{
			$obj = new stdclass;
		
			foreach ($row as $k => $v)
			{
				if (trim($v) != '' && $v != "None")
				{
					$obj->{$headings[$k]} = $v;
				}
			}
		
			if (0)
			{
				print_r($obj);	
			}
			
			
			$record = new stdclass;
			$record->processid = $obj->processid;
			
			if (isset($obj->marker_code))
			{
				$record->marker_code = $obj->marker_code;
			}			
			
			if (isset($obj->bin_uri))
			{
				$record->bin_uri = $obj->bin_uri;
			}
							
			// iBOL
			if (isset($obj->taxon_name))
			{
				$record->identification = $obj->taxon_name;
			}
			
			if (!isset($record->identification))
			{
				// 2024-07-19
				
				// construct an identification from lowest taxonomic rank :(
				foreach ($taxon_keys as $rank)
				{
					if (isset($obj->{$rank}))
					{
						$record->identification = $obj->{$rank};
					}
				}
			
			}
			
			// 28-Sep-2022
			if (isset($obj->taxon))
			{
				$record->identification = $obj->taxon;
			}				

			if (isset($obj->identification))
			{
				$record->identification = $obj->identification;
			}
			
			if (isset($obj->identified_by))
			{
				$record->identified_by = $obj->identified_by;
			}			

			if (isset($obj->identification_method))
			{
				$record->identification_method = $obj->identification_method;
			}

			/*
			if (isset($obj->insdc_acs))
			{
				$record->insdc_acs = $obj->insdc_acs;
			}
			*/
			
			if (0)
			{
				print_r($record);
			}
			else			
			{			
				// From 2023-06-30 we may have records that have no sequences, ignore these
				if (isset($record->marker_code))
				{
					$record->hash = md5(json_encode($record));
					
					// Do we have a record with these values that is currently active?
					
					$sql = 'SELECT * FROM version WHERE processid="' . $record->processid . '" AND hash="' . $record->hash . '" AND valid_to IS NULL';
					
					$data = db_get($sql);
					
					if (count($data) == 1)
					{
						// same as an existing, active record, no change
						//echo "No change\n";
					}
					else
					{
						// look currently active version of this record
						$sql = 'SELECT * FROM version WHERE processid="' . $record->processid . '" AND marker_code="'. $record->marker_code . '" AND valid_to IS NULL LIMIT 1';
						
						$data = db_get($sql);
						
						if (count($data) == 1)
						{
							// make active version (if we have one) inactive by updating valid_to
							echo "Update record\n";
							
							$old_record = $data[0];
							
							// have record already, but  with different values
							$sql = 'UPDATE version SET valid_to="' . $date . '" WHERE processid="' . $old_record->processid . '" AND hash="' . $old_record->hash . '" AND valid_to IS NULL';
							
							db_put($sql);
						}
						
						//echo "Insert record\n";

						// insert new record which is valid from this date and hence becomes the active record
						$record->valid_from = $date;
						
						$sql = obj_to_sql($record, 'version');
						db_put($sql);
					}
					
					if ($record->processid == 'ASQSQ702-10')
					{
						print_r($record);
						exit();
					}
				}
			}
		}
	}
	
	$row_count++;
	
	if ($row_count % 1000 == 0)
	{
		echo "[$row_count]\n";
	}	
	
	/*
	if ($row_count > 10000)
	{
		exit();
	}
	*/
}	


?>

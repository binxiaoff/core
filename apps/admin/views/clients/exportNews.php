<?
header("Content-Type: application/csv-tab-delimited-table"); 
header("Content-disposition: filename=newsletter.csv");
header("Pragma: no-cache");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0, public");
header("Expires: 0");

$i = 0;

while($i < mysql_num_fields($this->requete_result))
{
	$meta = mysql_fetch_field($this->requete_result,$i);
	
	if($i != 0)
	{
		echo ';';
	}
	
	echo '"'.$meta->name.'"';
	
	$i++;
}

echo "\n";

while($row = mysql_fetch_array($this->requete_result,MYSQL_NUM))
{
	$first = true;
	
	foreach($row as $value)
	{
		if($first == true)
		{
			$first = false;
		}
		else
		{
			echo ';';
		}
		
		echo '"'.utf8_decode($value).'"';
	}
	
	echo "\n";
}
?>
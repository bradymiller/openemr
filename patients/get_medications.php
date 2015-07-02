<?php
 // Copyright (C) 2011 Cassian LUP <cassi.lup@gmail.com>
 //
 // This program is free software; you can redistribute it and/or
 // modify it under the terms of the GNU General Public License
 // as published by the Free Software Foundation; either version 2
 // of the License, or (at your option) any later version.

        require_once("verify_session.php");

	$sql = "SELECT * FROM lists WHERE pid = ? AND type = 'medication' ORDER BY begdate";
	
	$res = sqlStatement($sql, array($pid) );

	if(sqlNumRows($res)>0)
  	{
  		?>
  		<table class="class1">
  			<tr class="header">
  				<th><?php echo xlt('Drug'); ?></th>
  				<th><?php echo xlt('Start Date'); ?></th>
  				<th><?php echo xlt('End Date'); ?></th>
  				<th><?php echo xlt('Referrer'); ?></th>
  			</tr>
  		<?php
  		$even=false;
  		while ($row = sqlFetchArray($res)) {
  			if ($even) {
  				$class="class1_even";
  				$even=false;
  			} else {
  				$class="class1_odd";
  				$even=true;
  			}
  			echo "<tr class='". attr($class)."'>";
  			echo "<td>". attr($row['title'])."</td>";
  			echo "<td>". attr($row['begdate'])."</td>";
  			echo "<td>". attr($row['enddate'])."</td>";
  			echo "<td>". attr($row['referredby'])."</td>";
  			echo "</tr>";
  		}
		echo "</table>";
  	}
	else
	{
		echo xlt("No Results");
	}
?>

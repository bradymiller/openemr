<?php
 // Copyright (C) 2011 Cassian LUP <cassi.lup@gmail.com>
 //
 // This program is free software; you can redistribute it and/or
 // modify it under the terms of the GNU General Public License
 // as published by the Free Software Foundation; either version 2
 // of the License, or (at your option) any later version.


        require_once("verify_session.php");


	$query = "SELECT e.pc_eid, e.pc_aid, e.pc_title, e.pc_eventDate, " .
	  "e.pc_startTime, e.pc_hometext, u.fname, u.lname, u.mname, " .
	  "c.pc_catname " .
	  "FROM openemr_postcalendar_events AS e, users AS u, " .
	  "openemr_postcalendar_categories AS c WHERE " .
	  "e.pc_pid = ? AND e.pc_eventDate >= CURRENT_DATE AND " .
	  "u.id = e.pc_aid AND e.pc_catid = c.pc_catid " .
	  "ORDER BY e.pc_eventDate, e.pc_startTime";
		
	//echo $query;
  	
  	$res = sqlStatement($query, array($pid) );
  	
  	//echo "test";
  	if(sqlNumRows($res)>0)
  	{
  		$count = 0;
			 
			 while($row = sqlFetchArray($res)) {
			  $count++;
			  $dayname = xl(date("l", strtotime($row['pc_eventDate'])));
			  $dispampm = "am";
			  $disphour = substr($row['pc_startTime'], 0, 2) + 0;
			  $dispmin  = substr($row['pc_startTime'], 3, 2);
			  if ($disphour >= 12) {
			   $dispampm = "pm";
			   if ($disphour > 12) $disphour -= 12;
			  }
			  if ($row['pc_hometext'] != "") {
				$etitle = 'Comments'.": ".$row['pc_hometext']."\r\n";
			  } else {
				$etitle = "Patient Scheduled via Portal";
			  }
              if ($GLOBALS['portal_onsite_appt_modify'] && $GLOBALS['portal_delete_change_appt']) {   # add a global to allow delete/change portal_delete_change_appt
                echo "<a href='add_edit_event_user.php?eid=" . attr($row['pc_eid']) .
		  "' class='edit_event iframe' title='" . text($etitle) . "'>";
              }
			  echo "<b>" . attr($dayname . ", " . $row['pc_eventDate']) . "</b><br>";
			  echo attr("$disphour:$dispmin " . $dispampm . " " . $row['pc_catname']) . "<br>\n";
			  echo attr($row['fname'] . " " . $row['lname']);
                          if ($GLOBALS['portal_onsite_appt_modify'] && $GLOBALS['portal_delete_change_appt']) {  # add a global to allow delete/change
                            echo "</a><br>\n";
                          }
                          else {
                            echo "<br>\n";
                          }
			 }
			 if (isset($res) && $res != null) {
				if ( $count < 1 ) { echo "&nbsp;&nbsp;" . xlt('None'); }
			 }
	} else { // if no appts
		echo xlt('No Appointments');
	}
?>

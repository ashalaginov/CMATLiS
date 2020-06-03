<?php

/**
 * \file checkTests.php
 * \brief  Completed Tests data for AJAX area.
 * \details  Provides information about recently completed 5 tests in descendent order for displaying on the page.  
 * \author Andrey Shalaginov <andrii.shalaginov@hig.no>
 * \date September-December 2012
 * \version   1.0
 */
require_once("config.inc.php");

///Call configuration check class
$configinitialization = new ConfigCheck();
$configinitialization->mySql_connection();
$configinitialization->pathesCheck();
$configinitialization->readyCheck();

$content = "";

$query = "SELECT * FROM $tests_results_table 
				LEFT JOIN $app_management_table ON $tests_results_table.id_app = $app_management_table.id_app 
				ORDER BY `id_test` DESC LIMIT 0, 5 ";
$result = mysql_query($query);

while ($row = mysql_fetch_array($result)) {
    $list_tests[] = $row;
}

if (isset($list_tests) & count($list_tests) !== FALSE) {
    echo "<table border='0' align='center' width='800px'>
				<tr class='tr_top_stat'>
					<td><b>ID Test</b></td>
					<td><b>App ID</b></td> 
					<td><b>App Label</b></td> 
					<td><b>AVD Name</b></td>
					<td><b>Finished</b></td>
					<td><b>Duration (s)</b></td>
					<td><b>Errors </b></td>
				</tr>";

    foreach ($list_tests as $i => $tmp_test) {
        $tr_class = fmod($i, 2) ? 'row_class1' : 'row_class2';
        echo "<tr class=" . $tr_class . " >
						<td>" . $tmp_test['id_test'] . "</td>
						<td>" . $tmp_test['id_app'] . "</td> 
						<td>" . $tmp_test['app_label'] . "</td>
						<td>" . $tmp_test['avd_name'] . "</td>
						<td>" . $tmp_test['time'] . "</td>
						<td>" . $tmp_test['duration'] . "</td>
						<td>" . $tmp_test['errors'] . "</td>
					</tr>";
    };
    echo "</table>";
}else
    echo "<br><br><br><br><i>No Elements!</i>";

mysql_close();
?>
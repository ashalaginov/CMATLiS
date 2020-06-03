<?php

/**
 * \file config.inc.php
 * \brief Various statistics regarding permissions
 * \author Andrey Shalaginov <andrii.shalaginov@hig.no>
 * \date September-December 2012
 * \version   1.0
 */
require_once("config.inc.php");

//Initialization
//Call configuration check class
$configinitialization = new ConfigCheck();
$configinitialization->mySql_connection();
$configinitialization->pathesCheck();
$configinitialization->readyCheck();
$output = $configinitialization->output;

//Get data about Analysis from the DB
$permissions_array = array();
$perm_risks = array();

$perm_risks['android.permission.READ_EXTERNAL_STORAGE'] = 1;
$perm_risks['android.permission.WRITE_EXTERNAL_STORAGE'] = 1;

$perm_risks['android.permission.READ_SMS'] = 2;
$perm_risks['android.permission.SEND_SMS'] = 2;
$perm_risks['android.permission.RECEIVE_SMS'] = 2;
$perm_risks['android.permission.READ_CONTACTS'] = 2;
$perm_risks['android.permission.WRITE_CONTACTS'] = 2;

$perm_risks['android.permission.WRITE_SECURE_SETTINGS'] = 3;
$perm_risks['android.permission.AUTHENTICATE_ACCOUNTS'] = 3;
$perm_risks['android.permission.PROCESS_OUTGOING_CALLS'] = 3;
$perm_risks['android.permission.READ_LOGS'] = 3;

$perm_risks['com.android.vending.BILLING'] = 4;
$perm_risks['android.permission.ADD_SYSTEM_SERVICE'] = 4;

$low_count = 0;
$medium_count = 0;
$high_count = 0;
$very_high_count = 0;
$critical_count = 0;

//Extract processed apps data
$query = "SELECT * FROM $app_management_table ";
$res = mysql_query($query);

while ($row = mysql_fetch_assoc($res)) {
    $per_app_permissions_array = array();

    if ($row['permissions'] != '') {
        $tmp = unserialize($row['permissions']);
        foreach ($tmp as $key => $item) {

            if (isset($permissions_array[$item]) && !isset($per_app_permissions_array[$item])) {

                $permissions_array[$item]++;
            } else {
                $permissions_array[$item] = 1;
            }
            $per_app_permissions_array[$item] = 1;

            if (isset($perm_risks[$item])) {

                if ($perm_risks[$item] == 4)
                    $critical_count++;
                elseif ($perm_risks[$item] == 3)
                    $very_high_count++;
                elseif ($perm_risks[$item] == 2)
                    $high_count++;
                elseif ($perm_risks[$item] == 1)
                    $medium_count++;
            }else
                $low_count++;
        }
    }
}

echo "<br><b>TOP 20 used permissions</b> ";
//Build statistics
arsort($permissions_array, SORT_NUMERIC);
echo "<table>";
$i = 0;
foreach ($permissions_array as $key => $val) {
    echo "<tr><td>" . $key . "</td><td>" . $val . "</td></tr>";
    if (++$i == 20)
        break;
}
echo "</table>";

echo "<br><b>Amount of used permissions in each category</b>";
echo "<br>low: " . $low_count;
echo "<br>medium: " . $medium_count;
echo "<br>high: " . $high_count;
echo "<br>very high: " . $very_high_count;
echo "<br>critical: " . $critical_count;

echo "<br><br><b>Amount of processed apps with with average level of permissions within each category</b>";
$query = "SELECT * FROM $anaysis_results_table WHERE `ml_threats_analysis` LIKE '%low%';";
$res = mysql_query($query);
$num_rows = mysql_num_rows($res);
echo "<br>low avarage level of permissions: $num_rows\n";

$query = "SELECT * FROM $anaysis_results_table WHERE `ml_threats_analysis` LIKE '%medium%';";
$res = mysql_query($query);
$num_rows = mysql_num_rows($res);
echo "<br>medium avarage level of permissions: $num_rows\n";

$query = "SELECT * FROM $anaysis_results_table WHERE `ml_threats_analysis` LIKE '%high%';";
$res = mysql_query($query);
$num_rows = mysql_num_rows($res);
echo "<br>high avarage level of permissions: $num_rows\n";

$query = "SELECT * FROM $anaysis_results_table WHERE `ml_threats_analysis` LIKE '%very%';";
$res = mysql_query($query);
$num_rows = mysql_num_rows($res);
echo "<br>very high avarage level of permissions: $num_rows\n";

$query = "SELECT * FROM $anaysis_results_table WHERE `ml_threats_analysis` LIKE '%dangerous%';";
$res = mysql_query($query);
$num_rows = mysql_num_rows($res);
echo "<br>critical avarage level of permissions: $num_rows\n";

echo "<br><br><b>Various stored data</b>";
$query = "SELECT * FROM $anaysis_results_table WHERE `data_structure_analysis`<>'empty';";
$res = mysql_query($query);
$num_rows = mysql_num_rows($res);
echo "<br>data_structure_analysis: $num_rows\n";

$query = "SELECT * FROM $anaysis_results_table WHERE `databases_analysis`<>'';";
$res = mysql_query($query);
$num_rows = mysql_num_rows($res);
echo "<br>databases_analysis: $num_rows\n";

$query = "SELECT * FROM $anaysis_results_table WHERE `shared_prefs_analysis`<>'';";
$res = mysql_query($query);
$num_rows = mysql_num_rows($res);
echo "<br>shared_prefs_analysis: $num_rows\n";
?>


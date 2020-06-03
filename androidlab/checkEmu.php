<?php

/**
 * \file checkEmu.php
 * \brief  Emulator data for AJAX area.
 * \details  Provides information about launched AVDs and different states of launch process.
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

$devices = shell_exec($android_sdk_home . "/" . $android_adb . " devices ");
preg_match_all('/(emulator-.*)[\s](offline|device)/', $devices, $parsed_data);

if (isset($parsed_data[2][0])) {
    if ($parsed_data[2][0] == 'offline')
        $content.= "Wait Please! <font color='#CD0000'>The instance of Android Emulator is running, but Not connected or Not responding!</font>";
    elseif ($parsed_data[2][0] == 'device') {
        $query = "(SELECT `value` FROM " . $config_table . "	WHERE `item`='launchedAVD');";
        $res = mysql_query($query);
        $row = mysql_fetch_row($res);

        $content.= "<font color='#00CD00'>You can RUN the tests on the next page, AVD <b>" . $row[0] . "</b> is online (" . shell_exec($android_sdk_home . "/" . $android_adb . " devices | sed -n 2p ") . ") </font>";
    }
}else
    $content.= "<font color='grey'>No Instances of Android Emulator launched!</font>";

if (strlen(shell_exec("ps -A|grep emulator")) <= 1)
    $content.= "<br>(No Emulator processes in memory!)";
else
    $content.= "<br>(There is a process of Android Emulator in memory)";

echo $content;

mysql_close();
?>

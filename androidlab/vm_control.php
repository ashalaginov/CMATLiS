<?php

/**
 * \file vm_control.php
 * \brief  Android Virtual Devices (AVD) Control 
 * \details  Allows user to check what Target Platforms are installed and available on the system.
 * Additionally, it is possible to create AVD and see other that are available.
 * \author Andrey Shalaginov <andrii.shalaginov@hig.no>
 * \date September-December 2012
 * \version   1.0
 * \bug Add support for choosing ABI and Skin for particular avaliable Target Platform
 * \warning  There is only possibility to chose Target Platform without any options for AVD to be created.
 */
require_once("config.inc.php");

/**
 * Class VmControl 
 * \brief Management of Android Virtual Devices and Platforms
 */
class VmControl {

    ///Output content, that will be presented on each page below the submenu block (warnings, messages, errors)
    public $output = "";

    /**
     * Constructior, check all configuration
     */
    public function __construct() {
        ///Call configuration check class
        $configinitialization = new ConfigCheck();
        $configinitialization->mySql_connection();
        $configinitialization->pathesCheck();
        $configinitialization->readyCheck();
        $this->output = $configinitialization->output;
    }

    /**
     * Destructor, clase MySQL conntection
     */
    function __destruct() {
        mysql_close();
    }

    /**
     * Extract from Android SDK available Targets (platforms) and parse their details, insert into DB
     */
    public function recheck_targets() {
        global $android_sdk_home, $android_android, $vm_targets_table;

        $targets_num = shell_exec($android_sdk_home . "/" . $android_android . " list targets|grep 'Name:' | wc -l");
        $targets = shell_exec($android_sdk_home . "/" . $android_android . " list targets");
        preg_match_all('/id: (\d) or "(.*)"\n[\s]*Name: (.*)\n[\s]*Type: (.*)\n[\s]*API level: (.*)\n[\s]*Revision: (.*)\n[\s]*Skins: (.*)\n[\s]*ABIs : (.*)/', $targets, $parsed_data);
        array_shift($parsed_data);

        //Transpose array of getting each target dfetails in each raw
        array_unshift($parsed_data, null);
        $targets_array = call_user_func_array("array_map", $parsed_data);

        //Clear the table
        $query = "TRUNCATE TABLE `" . $vm_targets_table . "`; ";
        if (mysql_query($query) === FALSE)
            $this->output.="<font color='#CD0000'>Impossible to empty Table " . $vm_targets_table . "</font> <br>";
        //Make query to insert obtained data into DB
        foreach ($targets_array as $key => $targets_array_item) {
            $query = "INSERT INTO $vm_targets_table 
			(`id_target`,
			`pseudonim`, 
			`name`, 
			`type`, 
			`api_level`, 
			`revision`, 
			`skins`, 
			`ABIs`) 
			VALUES('" . intval($targets_array_item[0]) . "',
			'" . mysql_real_escape_string($targets_array_item[1]) . "',
			'" . mysql_real_escape_string($targets_array_item[2]) . "',
			'" . mysql_real_escape_string($targets_array_item[3]) . "',
			'" . intval($targets_array_item[4]) . "', 
			'" . intval($targets_array_item[5]) . "', 
			'" . mysql_real_escape_string($targets_array_item[6]) . "',
			'" . mysql_real_escape_string($targets_array_item[7]) . "'); ";

            //Check whether it is possible to execute query
            if (mysql_query($query) === FALSE)
                $this->output.="<font color='#CD0000'>Impossible to execute INSERT/UPDATE query!</font> <br>";
        }
    }

    /**
     * Extract from Android SDK created AVDs and parse their details, insert into DB
     */
    public function recheck_avds() {
        global $android_sdk_home, $android_android, $vm_avds_table;

        $avds = shell_exec($android_sdk_home . "/" . $android_android . " list avd");
        $avds_num = shell_exec($android_sdk_home . "/" . $android_android . " list avd|grep 'Name:' | wc -l");

        preg_match_all('/Name: (.*)\n[\s]*Path: (.*)\n[\s]*Target: (.*)[(]API level(.*)[)]\n[\s]*ABI: (.*)\n[\s]*Skin: (.*)/', $avds, $parsed_data);
        array_shift($parsed_data);

        //Transpose array
        array_unshift($parsed_data, null);
        $avds_array = call_user_func_array("array_map", $parsed_data);

        //Clear table 
        $query = "TRUNCATE TABLE `" . $vm_avds_table . "`; ";
        if (mysql_query($query) === FALSE)
            $output.="<font color='#CD0000'>Impossible to empty Table " . $vm_avds_table . "</font> <br>";
        //Make query to insert obtained data into DB
        foreach ($avds_array as $key => $avds_array_item) {
            $query = "INSERT INTO $vm_avds_table 
			(`avd_name`,
			`path`, 
			`target`, 
			`api_level`, 
			`ABI`, 
			`skins`) 
			VALUES('" . mysql_real_escape_string($avds_array_item[0]) . "',
			'" . mysql_real_escape_string($avds_array_item[1]) . "',
			'" . mysql_real_escape_string($avds_array_item[2]) . "',
			'" . intval($avds_array_item[3]) . "', 
			'" . mysql_real_escape_string($avds_array_item[4]) . "',
			'" . mysql_real_escape_string($avds_array_item[5]) . "'); ";

            //Check whether it is possible to execute query
            if (mysql_query($query) === FALSE)
                $this->output.="<font color='#CD0000'>Impossible to execute INSERT/UPDATE query!</font> <br>";
        }
    }

    /**
     * The function provides list of available Android Virtual Devices and Taget Platforms, removing 
     * @return array Layouts of AVD and Targets tables
     */
    public function VmInfo() {
        global $android_sdk_home, $android_android, $vm_targets_table, $vm_avds_table;
        if (isset($_GET['vm_info'])) {
            //Re Check all available Targets and Android Virtual Devices (takes time)
            if (isset($_POST['vm_recheck'])) {
                //TODO:	android update avd
                //Perform rechecking process
                $this->output.=$this->recheck_targets();
                $this->output.=$this->recheck_avds();

                //Delete Selected AVDs from disk and from DB
            } elseif (isset($_POST['delete_avds']) && isset($_POST['avds_selected'])) {
                $count = 0;
                foreach ($_POST['avds_selected'] as $key => $avd_delete) {
                    //Perform deletion process from DB 
                    $query = "DELETE FROM $vm_avds_table WHERE `avd_name`='" . mysql_real_escape_string($avd_delete) . "'";
                    if (mysql_query($query) === FALSE)
                        $this->output.="<font color='#CD0000'>Impossible to execute DELETE '" . $avd_delete . "' AVD query!</font><br>";
                    else {
                        //Check wether it was succesfull
                        if (mysql_affected_rows() <= 0)
                            $this->output.="<font color='#CD0000'>Cannot find AVD with id='" . $avd_delete . "'!</font><br>";
                        else {
                            $count++;
                            $this->output.="<pre>" . shell_exec($android_sdk_home . "/" . $android_android . " delete avd -n " . $avd_delete) . "</pre>";
                        }
                    }
                }
                $this->output.=($count > 0) ? "AVDs were deleted from DB succesfully, total: " . $count . " AVDs <br>" : "";
            }


            //Read available Targets (Platforms) from DB
            $query = "SELECT * FROM $vm_targets_table ORDER by `id_target` ASC ";
            $res = mysql_query($query);
            while ($row = mysql_fetch_assoc($res))
                $available_targets[] = $row;
            $layout['available_targets'] = $available_targets;

            //Read available Android Virtual Devices
            $query = "SELECT * FROM $vm_avds_table ORDER by `avd_name` ASC ";
            $res = mysql_query($query);
            while ($row = mysql_fetch_assoc($res))
                $available_avds[] = $row;


            $layout['available_avds'] = $available_avds;
            return $layout;
        }
    }

    /**
     * The function provides options for creating of new Android Virtual Device (AVD)
     * @return array Layout of available Targets table
     */
    public function VmCreate() {
        // Page for AVD Creation
        global $android_sdk_home, $android_android, $vm_targets_table;
        if (isset($_GET['create_vm'])) {

            //Extract available Targets from the DB
            $query = "SELECT * FROM $vm_targets_table ORDER by `id_target` ASC ";
            $res = mysql_query($query);
            while ($row = mysql_fetch_assoc($res))
                $available_targets[] = $row;

            //TODO: add skinsm ABI select support
            //Perform Create AVD process
            if (isset($_POST['selected_target']) && isset($_POST['avd_name']) && strlen($_POST['avd_name']) > 1) {
                $shell_out = shell_exec("echo 'no' | " . $android_sdk_home . "/" . $android_android . " -s create avd -n " . $_POST['avd_name'] . " -t " . intval($_POST['selected_target']));
                if (strpos($shell_out, "Created AVD '" . $_POST['avd_name'] . "' based on Android") != FALSE) {
                    $this->output.="<pre>" . $shell_out . "</pre><br>The AVD '" . $_POST['avd_name'] . "' was successfully created";
                    $this->output.=$this->recheck_avds();
                }else
                    $this->output.="<font color='#CD0000'>Impossible to create AVD '" . $_POST['avd_name'] . "'!</font><br>";
            }
            $layout['available_targets'] = $available_targets;
            return $layout;
        }
    }

}

///Create VmControll class object
$vmcontrol = new VmControl();
///Print information about available Target Platforms and Virtual Devices
$layout = $vmcontrol->VmInfo();
$available_targets = $layout['available_targets'];
$available_avds = $layout['available_avds'];
///Create Virtual Device
$layout = $vmcontrol->VmCreate();
$available_targets = isset($layout) ? $layout['available_targets'] : $available_targets;
///Display system errors
$output = $vmcontrol->output;

///Building the layout of vm_control.php
require_once("templates/header.html");
require_once("templates/vm_control.template.html");
require_once("templates/footer.html");
?>

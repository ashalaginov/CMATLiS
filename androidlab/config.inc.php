<?php

/**
 * \file config.inc.php
 * \brief     Configuration file
 * \details   This file is used to define important environmental varibales. 
 * Additionally there are several performed check in orer to avoid errors and improper usage.
 * \author Andrey Shalaginov <andrii.shalaginov@hig.no>
 * \date September-December 2012
 * \version   1.0
 * \warning   Ensure that defined belowe section with important variables are correct and 
 * database scheme is exported from the corresponding file
 */
//------------------------------------------------------------------------------
//CHANGE THIS DATA FOR PROPER SYSTEM OPERATION!
/// MySQL DB name 
$db_name = "mobileLaboratory";
///MySQL DB user 
$db_user = "mobileLaboratory";
///MySQL DB password
$db_password = "mobileLaboratory";
///MySQL server address
$db_host = "localhost";
///Table in the DB for storing info about processed apps
$app_management_table = "processedApps";
///Table for storing info about installed Platform Tartgets
$vm_targets_table = "androidVMtargets";
///Table for storing created and existing Android Virtual Devices (AVD)
$vm_avds_table = "androidAVDs";
/// Table for data, captured during dynami test cycle
$tests_results_table = "performedTests";
/// Table for storing extracted data after analysis of test data
$anaysis_results_table = "performedAnalysis";
///Extracted security features table
$features_table = "appsFeatures";
///Various configuration variables
$config_table = "config";

//specific folder details (tools and system required storage)
///Folder, where SDK is installed
$android_sdk_home = "/home/andymir/android-sdk-linux";
///Folder, where AVD and other user configuration are sstored
$android_user_config = "/home/andymir/.android/";
///Emulator SubFolder
$android_emulator = "tools/emulator";
///Android script SubFolder
$android_android = "tools/android";
///Android Debug Bridge
$android_adb = "platform-tools/adb";
///Android Asset Packaging Tool (aapt)
$android_aapt = "platform-tools/aapt";
///Storage for uploaded APK files
$tmp_folder = "tmpUpload/";
///Storage for processed APK files
$permanent_folder = "apkFiles/";
///Folder for storing data extracted during test cycle
$test_data_folder = "testData/";
//------------------------------------------------------------------------------
//Variables
///Maximal amount of APK files processed at once in App Management sub-system
$max_processed_apps_at_once = 500;

//FUNCTIONS
/** Time measurement at current moment
  @return current time in second,msc format
 */
function getTime() {
    $a = explode(' ', microtime());
    return(double) $a[0] + $a[1];
}

/** Detect maximum allowed filesize to upload on the server
  @return maximal amount of megabytes, allowed to apload
 */
function detectMaxAllowedFileSize() {
    $max_upload = (int) (ini_get('upload_max_filesize'));
    $max_post = (int) (ini_get('post_max_size'));
    $memory_limit = (int) (ini_get('memory_limit'));
    return min($max_upload, $max_post, $memory_limit);
}

/** Define categories (manus) on the system
  @return associated array of Menu/SubMenu items and corresponding urls
 */
function fillCategoriesInfo() {
    $categories[0]['name'] = "App Management";
    $categories[0]['url'] = "/app_management.php";
    $categories[0]['sub'][0]['name'] = "Upload App";
    $categories[0]['sub'][0]['url'] = "/app_management.php?upload";
    $categories[0]['sub'][1]['name'] = "List of Apps";
    $categories[0]['sub'][1]['url'] = "/app_management.php?list";

    $categories[1]['name'] = "VM Control";
    $categories[1]['url'] = "/vm_control.php";
    $categories[1]['sub'][0]['name'] = "VM Status";
    $categories[1]['sub'][0]['url'] = "/vm_control.php?vm_info";
    $categories[1]['sub'][1]['name'] = "Create VM";
    $categories[1]['sub'][1]['url'] = "/vm_control.php?create_vm";

    $categories[2]['name'] = "Test Cycle";
    $categories[2]['url'] = "/test_cycle.php";
    $categories[2]['sub'][0]['name'] = "Test SETUP";
    $categories[2]['sub'][0]['url'] = "/test_cycle.php?test_conf";
    $categories[2]['sub'][1]['name'] = "Launch Tests";
    $categories[2]['sub'][1]['url'] = "/test_cycle.php?testing";
    $categories[2]['sub'][2]['name'] = "Completed Tests List";
    $categories[2]['sub'][2]['url'] = "/test_cycle.php?test_results";

    $categories[3]['name'] = "Analysis";
    $categories[3]['url'] = "/analysis.php";
    $categories[3]['sub'][0]['name'] = "Analysis SETUP";
    $categories[3]['sub'][0]['url'] = "/analysis.php?select_analysis";
    $categories[3]['sub'][1]['name'] = "Completed Analysis List";
    $categories[3]['sub'][1]['url'] = "/analysis.php?analysis_list";

    return $categories;
}

/** Print Array elements as text of lines
  @param $arr array
  @return string with separated by \n lines
 */
function printArray($arr) {
    $string = "";
    foreach ($arr as $key => $item) {
        $string.=$item . "<br>";
    }
    echo $string;
}

/** Generate Main Menu
  @param $categories list of Categories
  @return formatted menu layout
 */
function mainMenu($categories) {
    $menu = "";
    foreach ($categories as $key => $catName) {
        $active_page = ($catName['url'] == $_SERVER["PHP_SELF"]) ? "active_menu" : "";
        $menu.="<a href='" . $catName['url'] . "'><div class='menu_item " . $active_page . "'>" . $catName['name'] . "</div></a>";
    }

    return $menu;
}

/** Generate submenu from subcategories on category page
  @param $categories list of Categories
  @return formatted submenu layout for current category
 */
function subMenu($categories) {
    $bottom_menu_items = "";

    foreach ($categories as $key => $catName) {
        $i = 0;
        if ($catName['url'] == $_SERVER["PHP_SELF"]) {
            foreach ($catName['sub'] as $key1 => $subCatName) {
                //Extract sub categories for current category and form sub menu
                $active_page = ($subCatName['url'] == $_SERVER["REQUEST_URI"]) ? "active_menu" : "";
                $bottom_menu_items.="
						<a href='" . $subCatName['url'] . "'>
							<div class='sub_menu_item " . $active_page . "'>" . $subCatName['name'] . "</div>
						</a>";
                $i++;
            }
            //Fill blank subtabs
            $bottom_menu_blank = "";
            $k = 0;
            while ($k < $key && $k + $i < 4) {
                $bottom_menu_blank.="<div class='sub_menu_item sub_menu_blank'></div>";
                $k++;
            }
        }
    }
    return "<div id='sub_menu_block'>" . $bottom_menu_blank . $bottom_menu_items . "</div>";
}

///Define categories and subcategories with urls
$categories = fillCategoriesInfo();
///Amount of bytes, allowed to upload on the server
$upload_mb = detectMaxAllowedFileSize() * 1024 * 1024;
///Start point of page exectution, measures time
$Start = getTime();

/**
 * Class ConfigCheck 
 * \brief Functionality for initialization and providing check of all defined parameters
 */
class ConfigCheck {

    ///Output content, that will be presented on each page below the submenu block (warnings, messages, errors)
    public $output = "";

    /**
     * Setup connection to MySQL server and try to select main DB
     */
    public function mySql_connection() {
        global $db_host, $db_user, $db_password, $db_name;
        //Make MySQL Conntection
        if (mysql_connect($db_host, $db_user, $db_password) === FALSE)
            $this->output.="Cannot Create Connection to DB<br>";
        if (mysql_select_db($db_name) === FALSE)
            $this->output.="Cannot Select the Table into DB<br>";
    }

    /*
     * Checkk all entered pathes for existence  of files/directories
     */

    public function pathesCheck() {
        global $android_sdk_home, $vm_avds_table, $android_sdk_home, $android_emulator, $android_aapt, $android_adb, $tmp_folder, $permanent_folder;

        //Direcories & Files
        if (is_dir($android_sdk_home) === FALSE)
            $this->output.="Android SDK Folder does not found<br>";
        if (is_file($android_sdk_home . "/" . $android_emulator) === FALSE)
            $this->output.="Android Emulator does not found<br>";
        if (is_file($android_sdk_home . "/" . $android_aapt) === FALSE)
            $this->output.="Android AAPT does not found<br>";
        if (is_file($android_sdk_home . "/" . $android_adb) === FALSE)
            $this->output.="Android ADB does not found<br>";
        if (is_dir($tmp_folder) === FALSE)
            $this->output.="Directory for temporal APK storage does not exist<br>";
        if (is_dir($permanent_folder) === FALSE)
            $this->output.="Directory for permanent APK storage does not exist<br>";

        //Check owner of Android SDK folder
        //$filename=$android_sdk_home;
        //$fileowner=posix_getpwuid(fileowner($filename));
        //if(exec('whoami')!==$fileowner['name'])
        //	$output.="The owners of Web Server and Android SDK folder are different. Please change owner of SDK folder<br>";		
        //Check if there is any errors
    }

    /*
     * Check if there was an error - then stop, otherwise succesfull continue execution.
     */

    public function readyCheck() {
        global $vm_avds_table;
        //IMPORTANT ERRORS 
        if (strlen($this->output) > 1) {
            $this->output = "<font color='#CD0000'>" . $this->output . "</font>";
            echo "<center>" . $this->output . "</center>";
            die();
        }

        //UNIMPORTANT ERRORS
        //Check if AVDs exist - select available info from DB table
        $query = "SELECT * FROM $vm_avds_table ";
        ///Get data about AVD from the DB
        $res = mysql_query($query);
        while ($row = mysql_fetch_assoc($res)) {
            if (is_dir($row['path']) == FALSE)
                $this->output.="AVD '" . $row['avd_name'] . "' was no found! Please ReCheck all Targets and AVDs on AM Managements page!<br>";
        }
        //Check if programs are installed on the system
        if (strpos(shell_exec("whereis tree"), "bin") == FALSE)
            $this->output.="Please, install 'tree' tool for successful running of Analysis<br>";
        if (strpos(shell_exec("whereis screen"), "bin") == FALSE)
            $this->output.="Please, install 'screen' tool for successful running of Analysis<br>";
        if (strpos(shell_exec("whereis tshark"), "bin") == FALSE)
            $this->output.="Please, install 'tshark' tool for successful Traffic Analysis<br>";
        if (strpos(shell_exec("whereis sqlite3"), "bin") == FALSE)
            $this->output.="Please, install 'tshark' tool for successful Traffic Analysis<br>";

        if (!class_exists('SQLite3')) {
            $this->output.="Please, install 'SQLite3' support for php<br>";
        }


        if (strlen($this->output) > 1) {
            $this->output = "<font color='#CD0000'>" . $this->output . "</font>";
        }
    }

}

?>

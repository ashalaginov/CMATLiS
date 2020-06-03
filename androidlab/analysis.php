<?php

/**
 * \file analysis.php
 * \brief  Analysis of applicaiton   
 * \details  This file provides functionality for comprehensive analysis of collected static
 * and dynamic test data. Additionally, it provides estimation of threats, which application can posses to the user. 
 * \author Andrey Shalaginov <andrii.shalaginov@hig.no>
 * \date February-May 2013
 * \version   1.1
 */
require_once("config.inc.php");

/**
 * Class Analysis 
 * \brief Functionality for analysis of avalilable static and dynamic tests data. 
 */
class Analysis {

    //entropy
    function entropy($string) {
        $h = 0;
        $size = strlen($string);
        foreach (count_chars($string, 1) as $v) {
            $p = $v / $size;
            $h -= $p * log($p) / log(2);
        }
        return $h;
    }

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
     * Make formatted list of available tests and perform analysis of selected test data
     * @return formatted list of available tests data
     */
    public function selectAnalysis() {
        if (isset($_GET['select_analysis'])) {
            global $tests_results_table, $app_management_table, $vm_avds_table, $anaysis_results_table, $test_data_folder, $permanent_folder, $features_table;

            //Grep Tests data
            $query = "SELECT * FROM $tests_results_table
					LEFT JOIN $app_management_table ON $tests_results_table.id_app = $app_management_table.id_app 
					LEFT JOIN $vm_avds_table ON $tests_results_table.avd_name = $vm_avds_table.avd_name 
					ORDER BY `id_test` DESC; ";
            $result = mysql_query($query);
            while ($row = mysql_fetch_assoc($result)) {
                $list_tests[] = $row;
            }


            if (isset($_POST['analyseSelectedTests'])) {
                $count = 0;
                foreach ($_POST['selectedTestToAnalyse'] as $key => $item) {
                    $query = "SELECT * FROM $tests_results_table 
						LEFT JOIN $app_management_table ON $tests_results_table.id_app = $app_management_table.id_app 
						WHERE `id_test`='" . intval($item) . "' ORDER BY `id_test` DESC; ";
                    $result = mysql_query($query);
                    $row = mysql_fetch_assoc($result);

                    //----------------------------------------------------------
                    //ANALYSIS
                    //
                    //permissions
                    $permissions = ($row['permissions'] != '') ? unserialize($row['permissions']) : "";
                    $permissions_number = count($permissions);
                    $permissions_highest = 0;
                    $permissions_avg = 0;

                    if ($permissions_number > 0) {

                        //Level of risk because of allowing each perticular permission
                        //0 - low, 1 - medium, 2-high, 3-dangerous, 4- critical
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

                        $risk_queue = array();
                        if ($permissions != '') {
                            foreach ($permissions as $key => $perm) {
                                if (isset($perm_risks[$perm])) {
                                    //check the highest
                                    if ($permissions_highest < $perm_risks[$perm])
                                        $permissions_highest = $perm_risks[$perm];
                                    $risk_queue[$perm] = $perm_risks[$perm];
                                } else {
                                    $risk_queue[$perm] = 0;
                                }
                            }
                        }
                        $permissions_avg = array_sum($risk_queue) / count($risk_queue);
                    }

                    $shared_prefs = 0;
                    $shared_prefs_size = 0;
                    $databases = 0;
                    $databases_size = 0;
                    $pull_data_size = 0;
                    $files = 0;
                    $files_size = 0;
                    if ($row['pull_data'] == 1) {
                        //whole data folder
                        $folder_name = $row['folder_name'] . "/data";
                        $size = explode("\t", exec("du -hk " . $folder_name), 2);
                        $pull_data_size = $size[0];

                        //shared_prefs
                        $folder_name = $row['folder_name'] . "/data/shared_prefs";
                        if (is_dir($folder_name)) {
                            $size = explode("\t", exec("du -hk " . $folder_name), 2);
                            $size[0] == $folder_name ? $size[0] : 0;
                            $shared_prefs_size = $size[0];
                            $shared_prefs = shell_exec("find " . $folder_name . " |wc -l");
                        }
                        //databases
                        $folder_name = $row['folder_name'] . "/data/databases";
                        if (is_dir($folder_name)) {
                            $size = explode("\t", exec("du -hk " . $folder_name), 2);
                            $size[0] == $folder_name ? $size[0] : 0;
                            $databases_size = $size[0];
                            $databases = shell_exec("find " . $folder_name . " |wc -l");
                        }
                        //files
                        $folder_name = $row['folder_name'] . "/data/files";
                        if (is_dir($folder_name)) {
                            $size = explode("\t", exec("du -hk " . $folder_name), 2);
                            $size[0] == $folder_name ? $size[0] : 0;
                            $files_size = $size[0];
                            $files = shell_exec("find " . $folder_name . " |wc -l");
                        }
                    }

                    //Resources analysis in MB
                    $cpu_usage_peak = 0;
                    $cpu_usage_avg = 0;
                    $cpu_usage_stdev = 0;
                    $thr_usage_peak = 0;
                    $thr_usage_avg = 0;
                    $thr_usage_stdev = 0;
                    $vss_usage_peak = 0;
                    $vss_usage_avg = 0;
                    $vss_usage_stdev = 0;
                    $rss_usage_peak = 0;
                    $rss_usage_avg = 0;
                    $rss_usage_stdev = 0;
                    $resources_data = unserialize($row['read_cpu_usage']);

                    print_r($resources_data);
                    echo "--------------";
                    if (count($resources_data) > 1) {
                        //Mean and highest
                        foreach ($resources_data as $key => $item) {
                            if (intval($item['CPU']) > $cpu_usage_peak)
                                $cpu_usage_peak = intval($item['CPU']);
                            $cpu_usage_avg+=intval($item['CPU']);
                            if (intval($item['THR']) > $thr_usage_peak)
                                $thr_usage_peak = intval($item['THR']);
                            $thr_usage_avg+=intval($item['THR']);
                            if ($item['VSS'] > $vss_usage_peak)
                                $vss_usage_peak = $item['VSS'];
                            $vss_usage_avg+=$item['VSS'];
                            if ($item['RSS'] > $rss_usage_peak)
                                $rss_usage_peak = $item['RSS'];
                            $rss_usage_avg+=$item['RSS'];
                        }

                        $vss_usage_peak = $vss_usage_peak / 1024 / 1024;
                        $rss_usage_peak = $rss_usage_peak / 1024 / 1024;

                        $cpu_usage_avg = $cpu_usage_avg / count($resources_data);
                        $thr_usage_avg = $thr_usage_avg / count($resources_data);
                        $vss_usage_avg = $vss_usage_avg / count($resources_data) / 1024 / 1024;
                        $rss_usage_avg = $rss_usage_avg / count($resources_data) / 1024 / 1024;

                        //stdev calculation
                        foreach ($resources_data as $key => $item) {
                            $cpu_usage_stdev+=pow(intval($item['CPU']) - $cpu_usage_avg, 2);
                            $thr_usage_stdev+=pow(intval($item['THR']) - $thr_usage_avg, 2);
                            $vss_usage_stdev+=pow(($item['VSS'] / 1024 / 1024 - $vss_usage_avg), 2);
                            $rss_usage_stdev+=pow(($item['RSS'] / 1024 / 1024 - $rss_usage_avg), 2);
                        }
                        $cpu_usage_stdev = sqrt($cpu_usage_stdev / count($resources_data));
                        $thr_usage_stdev = sqrt($thr_usage_stdev / count($resources_data));
                        $vss_usage_stdev = sqrt($vss_usage_stdev / count($resources_data));
                        $rss_usage_stdev = sqrt($rss_usage_stdev / count($resources_data));
                    }

                    //Working with package
                    $package_entropy = 0;
                    $package_number_files = 0;
                    $manifest_size = 0;
                    $res_folder_size = 0;
                    $assets_folder_size = 0;
                    $classes_dex_size = 0;
                    $classes_dex_entropy = 0;
                    $execution_time = 0;

                    $apk_file = $permanent_folder . $row['md5_name'] . ".apk";
                    echo shell_exec("mkdir tmp/");
                    shell_exec("unzip " . $apk_file . " -d tmp/");
                    //entropy package
                    shell_exec("tar -cf tmp.tar tmp/");
                    $package_entropy = $this->entropy(file_get_contents("tmp.tar"));
                    shell_exec("rm tmp.tar");
                    //total files
                    $package_number_files = shell_exec("find tmp/ |wc -l");
                    //manifest size
                    $manifest_size = filesize("tmp/AndroidManifest.xml");
                    //res size
                    if (is_dir("tmp/res")) {
                        $size = explode("\t", exec("du -hk " . "tmp/res"), 2);
                        $size[0] == "tmp/res" ? $size[0] : 0;
                        $res_folder_size = $size[0];
                    }
                    //assets size
                    if (is_dir("tmp/assets")) {
                        $size = explode("\t", exec("du -hk " . "tmp/assets"), 2);
                        $size[0] == "tmp/assets" ? $size[0] : 0;
                        $assets_folder_size = $size[0] . "<br>";
                    }
                    //classes_dex size
                    $classes_dex_size = filesize("tmp/classes.dex");
                    //entropy dex
                    $classes_dex_entropy = $this->entropy(file_get_contents("tmp/classes.dex"));
                    echo shell_exec("rm -rf tmp/");

                    $query = "INSERT INTO $features_table 
						(`id_app`, 
						`id_test`, 
						`sdkVersion`, 
						`targetSdkVersion`,
						`app_label_length`, 
						`package_name_length`,
						`filesize`,
						`permissions_highest`,
						`permissions_avg`,
						`permissions_number`,
						`pull_data_size`,
						`log_launch_size`,
						`cpu_usage_peak`,
						`cpu_usage_avg`,
						`cpu_usage_stdev`,
						`thr_usage_peak`,
						`thr_usage_avg`,
						`thr_usage_stdev`,
						`vss_usage_peak`,
						`vss_usage_avg`,
						`vss_usage_stdev`,
						`rss_usage_peak`, 
						`rss_usage_avg`,
						`rss_usage_stdev`,
						`shared_prefs`,
						`shared_prefs_size`,
						`databases`,
						`databases_size`,
						`files`,
						`files_size`,
						`package_entropy`,
						`package_number_files`,
						`manifest_size`,
						`res_folder_size`,
						`assets_folder_size`,
						`classes_dex_size`,
						`classes_dex_entropy`,
						`execution_time`)
 
      
						VALUES(
						'" . $row['id_app'] . "',
						'" . $row['id_test'] . "',
						'" . $row['sdkVersion'] . "',
						'" . $row['targetSdkVersion'] . "',
						'" . strlen($row['app_label']) . "',
						'" . strlen($row['package_name']) . "',
						'" . $row['filesize'] . "',
						'" . $permissions_highest . "',
						'" . $permissions_avg . "',
						'" . $permissions_number . "',
						'" . $pull_data_size . "',
						'" . strlen($row['log_launch']) . "',
						'" . $cpu_usage_peak . "',
						'" . $cpu_usage_avg . "',
						'" . $cpu_usage_stdev . "',
						'" . $thr_usage_peak . "',
						'" . $thr_usage_avg . "',
						'" . $thr_usage_stdev . "',
						'" . $vss_usage_peak . "',
						'" . $vss_usage_avg . "',
						'" . $vss_usage_stdev . "',
						'" . $rss_usage_peak . "',
						'" . $rss_usage_avg . "',
						'" . $rss_usage_stdev . "',
						'" . $shared_prefs . "',
						'" . $shared_prefs_size . "',
						'" . $databases . "',
						'" . $databases_size . "',
						'" . $files . "',
						'" . $files_size . "',
						'" . $package_entropy . "',
						'" . $package_number_files . "',
						'" . $manifest_size . "',
						'" . $res_folder_size . "',
						'" . $assets_folder_size . "',
						'" . $classes_dex_size . "',
						'" . $classes_dex_entropy . "',
						'" . $row['duration'] . "'
						); ";

                    //echo $query;
                    if (mysql_query($query) === FALSE)
                        $this->output.="<font color='#CD0000'>Impossible to execute analysis INSERT/UPDATE query!</font> <br>";
                    //----------------------------------------------------------

                    $analyses_data = $this->analysisFunction($row);

                    $query = "INSERT INTO $anaysis_results_table 
						(`id_test`, 
						`id_app`, 
						`app_label`, 
						`folder_name`,
						`screenshot`, 
						`resources_usage`,
						`data_structure_analysis`,
						`databases_analysis`,
						`ml_threats_analysis`,
						`shared_prefs_analysis`)
						VALUES(
						'" . $row['id_test'] . "',
						'" . $row['id_app'] . "',
						'" . $row['app_label'] . "',
                                                '" . (isset($analyses_data['folder_name']) ? mysql_real_escape_string($analyses_data['folder_name']) : "") . "',
						'" . (isset($analyses_data['screenshot']) ? intval($analyses_data['screenshot']) : "") . "',
						'" . (isset($analyses_data['resources_usage']) ? mysql_real_escape_string(serialize($analyses_data['resources_usage'])) : "") . "',
                                                '" . (isset($analyses_data['data_structure_analysis']) ? mysql_real_escape_string($analyses_data['data_structure_analysis']) : "") . "',
                                                '" . (isset($analyses_data['databases_analysis']) ? mysql_real_escape_string($analyses_data['databases_analysis']) : "") . "',       
                                                '" . (isset($analyses_data['ml_threats_analysis']) ? mysql_real_escape_string($analyses_data['ml_threats_analysis']) : "") . "',
                                                '" . (isset($analyses_data['shared_prefs_analysis']) ? mysql_real_escape_string($analyses_data['shared_prefs_analysis']) : "") . "'); ";
                    //Check whether it is possible to execute query
                    if (mysql_query($query) === FALSE)
                        $this->output.="<font color='#CD0000'>Impossible to execute INSERT/UPDATE query!</font> <br>";
                    else
                        $count++;
                }
                $this->output.=($count > 0) ? "Performed Analysis over test data were successfull finished, total: " . $count . " apps test data <br>" : "";
            }
            return $list_tests;
        }
    }

    /**
     * Print list of performed analysis and delete selected analysis (if necessary)
     * @return formatted list of performed analysis
     */
    function analysisList() {
        if (isset($_GET['analysis_list'])) {
            global $anaysis_results_table, $app_management_table;
            //Delete selected test results if necessary and corresponding data folder
            if (isset($_POST['deteleSelectedAnalysis']) && isset($_POST['selectedAnalysisToDelete'])) {
                $count = 0;
                foreach ($_POST['selectedAnalysisToDelete'] as $key => $analysis_delete) {
                    //Check whether an app in the DB
                    $query = "SELECT * FROM $anaysis_results_table WHERE `id_analysis`='" . intval($analysis_delete) . "'";
                    $res = mysql_query($query);
                    if ($res == FALSE)
                        $this->output.="<font color='#CD0000'>Impossible to execute SELECT '" . intval($analysis_delete) . "' test query!</font> <br>";
                    else
                        $row = mysql_fetch_assoc($res);

                    //Perform deletion process from DB and disk
                    if ($row != FALSE && count($row) > 0) {
                        $query = "DELETE FROM $anaysis_results_table WHERE `id_analysis`='" . $row['id_analysis'] . "'";
                        if (mysql_query($query) == FALSE)
                            $this->output.="<font color='#CD0000'>Impossible to execute DELETE '" . $row['id_analysis'] . "' analysis data query!</font><br>";
                        else
                            $count++;
                    }else
                        $this->output.="<font color='#CD0000'>Cannot find analysis with id='" . intval($analysis_delete) . "'!</font><br>";
                }
                $this->output.=($count > 0) ? "Performed analysis data were deleted from DB succesfully, total: " . $count . " items <br>" : "";
            }


            //Grep Analysis data
            $query = "SELECT * FROM $anaysis_results_table
                LEFT JOIN $app_management_table ON $anaysis_results_table.id_app = $app_management_table.id_app 
                ORDER BY `id_analysis` DESC; ";
            $result = mysql_query($query);
            while ($row = mysql_fetch_assoc($result)) {

                if (strlen($row['resources_usage']) > 1) {
                    $plot_data = unserialize($row['resources_usage']);
                    //if (count($plot_data) > 1) {
                    $line_CPU = "[[";
                    $line_THR = "[[";
                    $line_VSS = "[[";
                    $line_RSS = "[[";
                    foreach ($plot_data as $key => $item) {
                        //Interpolate missing data
                        if (!isset($item['CPU']))
                            $item['CPU'] = $previous_element['CPU'];

                        if ((!isset($item['THR'])))
                            $item['THR'] = $previous_element['THR'];
                        if ((!isset($item['VSS'])))
                            $item['VSS'] = $previous_element['VSS'];
                        if ((!isset($item['RSS'])))
                            $item['RSS'] = $previous_element['RSS'];

                        $line_CPU.="[" . $key . "," . intval($item['CPU']) . "],";
                        $line_THR.="[" . $key . "," . intval($item['THR']) . "],";
                        $line_VSS.="[" . $key . "," . floor($item['VSS'] / 1024) . "],";
                        $line_RSS.="[" . $key . "," . floor($item['RSS'] / 1024) . "],";
                        $previous_element = $item;
                    }

                    $row['line_CPU'] = $line_CPU . "]]";
                    $row['line_THR'] = $line_THR . "]]";
                    $row['line_VSS'] = $line_VSS . "]]";
                    $row['line_RSS'] = $line_RSS . "]]";
                }
                $list_analysis[] = $row;
            }
            return $list_analysis;
        }
    }

    /**
     * Perfrm Analysis with selectied options
     * @return Result of analysis
     */
    function analysisFunction($available_info) {
        global $test_data_folder;
        //Check if screenshots were collected
        //TODO: Check if exists
        if ($available_info['screenshot'] != FALSE) {
            //TODO: Add more screens
            $screenshot = $available_info['screenshot'];
        }

        //Scan temp directory for uploaded files
        if (isset($available_info['folder_name'])) {
            $folder_name = $available_info['folder_name'];
        }

        if (isset($_POST['resources_usage_analysis'])) {
            //Check if any statistics was collected
            if (isset($available_info['read_cpu_usage'])) {
                $resources_usage = unserialize($available_info['read_cpu_usage']);
                if (count($resources_usage) == FALSE)
                    unset($resources_usage);
            }
        }

        if (isset($_POST['data_structure_analysis'])) {
            //Check Data structure
            if (is_dir($test_data_folder . $folder_name . "/data")) {
                $data_structure_analysis = shell_exec("tree " . $test_data_folder . $folder_name . "/data");
            }else
                $data_structure_analysis = "empty";
        }

        //Check databases
        if (isset($_POST['databases_analysis'])) {

            //Read DB directory
            if (is_dir($test_data_folder . $folder_name . "/data/databases")) {
                $dh = opendir($test_data_folder . $folder_name . "/data/databases");
                while (false !== ($tmp_filename = readdir($dh))) {
                    //Exclude '..' and '.' directories
                    if ($tmp_filename !== "." && $tmp_filename !== ".." && pathinfo($tmp_filename, PATHINFO_EXTENSION) === 'db') {
                        $dataBases[] = $tmp_filename;
                        //Open connection to he DB
                        $db = new SQLite3($test_data_folder . $folder_name . "/data/databases/" . $tmp_filename);
                        //Select Available Tables
                        $table_names = $db->querySingle("SELECT name FROM sqlite_master WHERE type = 'table'", true);
                        //Print tables Content
                        foreach ($table_names as $key => $item) {
                            $results = $db->query("SELECT * FROM '" . $item . "'");
                            while ($row = $results->fetchArray()) {
                                foreach ($row as $key => $item_child)
                                    $databases_analysis[$tmp_filename][$item] = $item_child;
                            }
                        }
                    }
                }
            }
        }

        //shared_prefs_analysis
        if (isset($_POST['shared_prefs_analysis'])) {

            //Read SharedPrefs directory
            if (is_dir($test_data_folder . $folder_name . "/data/shared_prefs")) {
                $dh = opendir($test_data_folder . $folder_name . "/data/shared_prefs");
                while (false !== ($tmp_filename = readdir($dh))) {
                    //Exclude '..' and '.' directories                    
                    if ($tmp_filename !== "." && $tmp_filename !== ".." && pathinfo($tmp_filename, PATHINFO_EXTENSION) == 'xml') {
                        $dataBases[] = $tmp_filename;
                        if (file_exists($test_data_folder . $folder_name . "/data/shared_prefs/" . $tmp_filename)) {
                            $xml = simplexml_load_file($test_data_folder . $folder_name . "/data/shared_prefs/" . $tmp_filename);
                            $shared_prefs_analysis[$tmp_filename] = json_decode(json_encode($xml), 1);
                        }
                    }
                }
            }
        }


        //ml_threats_analysis
        if (isset($_POST['ml_threats_analysis'])) {
            $permissions = ($available_info['permissions'] != '') ? unserialize($available_info['permissions']) : "";

            //Level of risk because of allowing each perticular permission
            //0 - low, 1 - medium, 2-high, 3-dangerous, 4- critical
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

            $risk_queue = array();
            if ($permissions != '') {
                foreach ($permissions as $key => $perm) {

                    if (isset($perm_risks[$perm])) {

                        $risk_queue[$perm] = $perm_risks[$perm];
                    } else {
                        $risk_queue[$perm] = 0;
                    }
                }
            }
            switch (round(array_sum($risk_queue) / count($risk_queue))) {
                case 0:
                    $ml_threats_analysis['permissions'] = "low impact from permissions";
                    //Majority voting risk
                    $ml_threats_analysis["total"] = 0;
                    break;
                case 1:
                    $ml_threats_analysis['permissions'] = "medium impact from permissions";
                    //Majority voting risk
                    $ml_threats_analysis["total"] = 1;
                    break;
                case 2:
                    $ml_threats_analysis['permissions'] = "high impact from permissions";
                    //Majority voting risk
                    $ml_threats_analysis["total"] = 2;
                    break;
                case 3:
                    $ml_threats_analysis['permissions'] = "very high impact from permissions";
                    //Majority voting risk
                    $ml_threats_analysis["total"] = 3;
                    break;
                case 3:
                    $ml_threats_analysis['permissions'] = "critical impact from permissions";
                    //Majority voting risk
                    $ml_threats_analysis["total"] = 4;
                    break;
                default:
                    $ml_threats_analysis['permissions'] = "low impact from permissions";
                    //Majority voting risk
                    $ml_threats_analysis["total"] = 0;
            }
        }
        //Add isset
        $verdict['screenshot'] = $screenshot;
        $verdict['resources_usage'] = $resources_usage;
        $verdict['folder_name'] = $folder_name;
        $verdict['data_structure_analysis'] = $data_structure_analysis;
        $verdict['shared_prefs_analysis'] = (count($shared_prefs_analysis) == 0 || !isset($shared_prefs_analysis)) ? "" : serialize($shared_prefs_analysis);
        $verdict['ml_threats_analysis'] = (count($ml_threats_analysis) == 0 || !isset($ml_threats_analysis)) ? "" : serialize($ml_threats_analysis);
        $verdict['databases_analysis'] = (count($databases_analysis) == 0 || !isset($databases_analysis)) ? "" : serialize($databases_analysis);

        return $verdict;
    }

}

///Create object of the class Analysis
$analysis = new Analysis();
///Print List of Available Test Data and Perform Analysis of selected test data
$list_tests = $analysis->selectAnalysis();
///Get data from the DB and Print List of Executed Analysis List
$list_analysis = $analysis->analysisList();
///System messages
$output = $analysis->output;



//Building the layout
require_once("templates/header.html");
require_once("templates/analysis.template.html");
require_once("templates/footer.html");
?>




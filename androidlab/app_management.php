<?php

/**
 * \file app_management.php
 * \brief  Management of Android Applications 
 * \details  This file provide functionality for uploading and processign aploaded Android APK packages (applications).
 * After processing, all information are stored in the database for future analysis. 
 * At the moment, there is only one file can be uploaded at the moment. 
 * If you need to process bunch of APK files, please put it into the folder, defined by $tmp_folder in config.ini.php
 * \author Andrey Shalaginov <andrii.shalaginov@hig.no>
 * \date September-December 2012
 * \version   1.0
 * \warning   There could be restrictions in Web Server configuraitons on size of uploaded file
 */
require_once("config.inc.php");

/**
 * Class AppManagement 
 * \brief Management of apploaded applications
 */
class AppManagement {

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

    /** Parse and extract statistical data from the APK file
     * @param $file_name Name of uploaded, but not processed file
     * @param $new_name Name of processed file in permanent folder
     * @param $count Amount of previously succesfully processed files
     * @return result of extracting data from APK file
     */
    public function statisticsApkReading($file_name, $new_name, $count) {
        global $android_sdk_home, $android_aapt, $app_management_table, $tmp_folder;

        //Read application label
        $app_label = shell_exec($android_sdk_home . "/" . $android_aapt . " d badging " . $new_name . " |grep \"application: label=\" |cut -d\' -f2");

        //MD5 of application package	
        $md5_name = md5_file($tmp_folder . $file_name);

        //Read versionName
        $version = shell_exec($android_sdk_home . "/" . $android_aapt . " d badging " . $new_name . " |grep \"versionName=\"|cut -d\' -f6");

        //Read sdkVersion
        $sdkVersion = shell_exec($android_sdk_home . "/" . $android_aapt . " d badging " . $new_name . " |grep \"sdkVersion:\"|cut -d\' -f2 ");
        $sdkVersion = (strlen($sdkVersion) < 1) ? 1 : $sdkVersion;

        //Read targetSdkVersion
        $targetSdkVersion = shell_exec($android_sdk_home . "/" . $android_aapt . " d badging " . $new_name . " |grep \"targetSdkVersion:\"|cut -d\' -f2 ");
        $targetSdkVersion = (strlen($targetSdkVersion) < 1) ? $sdkVersion : $targetSdkVersion;

        //Read package name
        $package_name = trim(shell_exec($android_sdk_home . "/" . $android_aapt . " d badging " . $new_name . " |grep \"package: name=\"|cut -d\' -f2 "));

        //Read file structure
        $package_structure = explode("\n", shell_exec($android_sdk_home . "/" . $android_aapt . " list -v " . $new_name . " "));
        $package_structure = serialize($package_structure);

        //Read launchable activity name
        $launchable_activity = trim(shell_exec($android_sdk_home . "/" . $android_aapt . " d badging " . $new_name . " |grep \"launchable-activity: name=\" |cut -d\' -f2 "));

        //Read & extract permissions
        $permissions = explode("\n", shell_exec($android_sdk_home . "/" . $android_aapt . " d permissions " . $new_name . "  "));
        $permissions = array_slice($permissions, 1, -1);
        foreach ($permissions as $key => $tmp_permission) {
            $permissions[$key] = str_replace("uses-permission: ", "", $tmp_permission);
        }
        $permissions = serialize($permissions);

        //Read version code
        $versionCode = shell_exec($android_sdk_home . "/" . $android_aapt . " d badging " . $new_name . " |grep \"versionCode=\" | cut -d\' -f4");

        //package filesize
        $filesize = filesize($new_name);

        //Read native-code
        $native_code = shell_exec($android_sdk_home . "/" . $android_aapt . " d badging " . $new_name . " |grep \"native-code:\" | cut -d\' -f2");

        //Read locales
        $locales = shell_exec($android_sdk_home . "/" . $android_aapt . " d badging " . $new_name . " |grep \"locales:\" ");

        //Read supports-screens
        $supports_screens = shell_exec($android_sdk_home . "/" . $android_aapt . " d badging " . $new_name . " |grep \"supports-screens:\" ");

        //Read densities
        $densities = shell_exec($android_sdk_home . "/" . $android_aapt . " d badging " . $new_name . " |grep \"densities:\" ");

        //Make query to insert obtained data into DB
        $query = "INSERT INTO $app_management_table 
		(`app_label`,
		`md5_name`, 
		`version`, 
		`sdkVersion`, 
		`targetSdkVersion`, 
		`package_name`, 
		`package_structure`, 
		`launchable_activity`, 
		`permissions`, 
		`versionCode`,
		`filesize`, 
		`native_code`,
		`locales`, 
		`supports_screens`,
		`densities`) 
		VALUES('" . mysql_real_escape_string($app_label) . "',
		'$md5_name', 
		'" . mysql_real_escape_string($version) . "', 
		'" . intval($sdkVersion) . "', 
		'" . intval($targetSdkVersion) . "', 
		'" . mysql_real_escape_string($package_name) . "', 
		'" . mysql_real_escape_string($package_structure) . "', 
		'" . mysql_real_escape_string($launchable_activity) . "', 
		'" . mysql_real_escape_string($permissions) . "', 
		'" . mysql_real_escape_string($versionCode) . "', 
		'" . intval($filesize) . "', 
		'" . mysql_real_escape_string($native_code) . "', 
		'" . mysql_real_escape_string($locales) . "', 
		'" . mysql_real_escape_string($supports_screens) . "', 
		'" . mysql_real_escape_string($densities) . "')";

        //Check whether it is possible to execute query
        if (mysql_query($query) === FALSE) {
            $this->output = "<font color='#CD0000'>Impossible to execute INSERT query!</font> <br>";
        } else {
            unlink($tmp_folder . $file_name);
            $count++;
        }

        return $count;
    }

    /**
     * Display in temporary storage and already processed applicaitons in DB; delete and process selected apps.
     */
    public function uploadApp() {
        global $tmp_folder, $upload_mb;

        if (isset($_GET['upload']) && isset($_FILES['filename'])) {

            //Check whether file is uploaded
            if (is_uploaded_file($_FILES['filename']['tmp_name'])) {
                //Check whether file is 'apk'
                if (pathinfo($_FILES['filename']['name'], PATHINFO_EXTENSION) === 'apk') {
                    //Move to Laboratory temporary storage 
                    if (move_uploaded_file($_FILES['filename']['tmp_name'], $tmp_folder . $_FILES['filename']['name'])) {
                        $this->output.="File '" . $_FILES['filename']['name'] .
                                "' (size " . floor($_FILES['filename']['size'] / 1024) . "KB) was succesfully uploaded <br>";
                    } else {
                        $this->output.= "<font color='#CD0000'>File not moved to destination folder. Check permissions. </font>f <br>";
                    }
                }else
                    $this->output.= "<font color='#CD0000'>Uploaded file is not an APK! </font><br>";
                //File can exceed maximum size allowed by server
            }elseif (isset($_FILES['filename']['error'])) {
                if ($_FILES['filename']['error'] == 2)
                    $this->output.= "<font color='#CD0000'>Uploaded file exceed maximal size, allowed by the server: " . $upload_mb / (1024 * 1024) . "MB!</font><br>";
            }
        }
    }

    /**
     * If there is an uploaded file - move to temporary folder.
     * Generate list of uploaded/processed apps.
     * @return list of uploaded and already processed applications
     */
    public function appList() {
        global $tmp_folder, $android_sdk_home, $android_aapt, $permanent_folder,
        $max_processed_apps_at_once, $app_management_table, $tests_results_table, $anaysis_results_table;
        if (isset($_GET['list'])) {
            //Delete selected (not processed) files
            if (isset($_POST['delete']) && isset($_POST['uploaded_app'])) {
                foreach ($_POST['uploaded_app'] as $key => $file_name_delete)
                    if (file_exists($tmp_folder . $file_name_delete))
                        unlink($tmp_folder . $file_name_delete);
                $this->output.="Uploaded files were deleted succesfully!";
            }
            //PROCESS selected (not processed) files
            elseif (isset($_POST['process']) && isset($_POST['uploaded_app'])) {
                $key_count = 0; //
                $count = 0; //
                foreach ($_POST['uploaded_app'] as $key => $file_name) {
                    //Check if files exists in the tmp folder
                    if (file_exists($tmp_folder . $file_name)) {
                        $new_name = $permanent_folder . md5_file($tmp_folder . $file_name) . ".apk";
                        //Check if the same package already exists

                        if (file_exists($new_name)) {
                            $othis->utput.="<font color='#CD0000'>File " . $file_name . ".apk already processed. </font><br>";
                            //Delete this file
                            unlink($tmp_folder . $file_name);
                        } else {
                            //move file to APK folder with rename to md5
                            copy($tmp_folder . $file_name, $new_name);

                            //Check if the APK file conteins AndroidManifes.xml configuration file
                            $manifest_present = shell_exec($android_sdk_home . "/" . $android_aapt . " list " . $new_name . " |grep AndroidManifest.xml ");
                            if (strlen($manifest_present) < 1) {
                                $this->output.= "<font color='#CD0000'> Package " . $file_name . " does not have the AndroidManifest file </font><br>";
                                unlink($new_name);
                            } else {
                                //Reading various static information from the APK package
                                $count = $this->statisticsApkReading($file_name, $new_name, $count);
                                //Process not more than defined number of apps at once
                                $key_count++;
                                if ($key_count >= $max_processed_apps_at_once)
                                    break;
                            }
                        }
                    }else
                        $this->output.="<font color='#CD0000'>File " . $tmp_folder . $file_name . ".apk does not exists. </font><br>";
                }
                $this->output.=($count > 0) ? "Information was added to the DB, total: " . $count . " apps <br>" : "Information was not added to the DB<br>";

                // Delete processed apps from disk and DB
            }elseif (isset($_POST['deleteProcessed']) && isset($_POST['selected_apps'])) {
                $count = 0;
                foreach ($_POST['selected_apps'] as $key => $app_delete) {
                    //Check whether an app in the DB
                    $query = "SELECT `md5_name` FROM $app_management_table WHERE `id_app`='" . intval($app_delete) . "'";
                    $res = mysql_query($query);
                    if ($res == FALSE)
                        $this->output.="<font color='#CD0000'>Impossible to execute SELECT '" . intval($app_delete) . "' app query!</font> <br>";
                    else
                        $row = mysql_fetch_row($res);

                    //Perform deletion process from DB and disk
                    if ($row != FALSE && count($row) > 0) {

                        //DELETE DATA FROM ANALYSIS, TEST AND APP TABLES in order to exclude missing data
                        $query = "DELETE FROM $app_management_table WHERE `id_app`='" . intval($app_delete) . "';";
                        $query1 = "DELETE FROM $tests_results_table WHERE `id_app`='" . intval($app_delete) . "';";
                        $query2 = "DELETE FROM $anaysis_results_table WHERE `id_app`='" . intval($app_delete) . "';";
                        if (mysql_query($query) == FALSE || mysql_query($query1) == FALSE || mysql_query($query2) == FALSE)
                            $othis->utput.="<font color='#CD0000'>Impossible to execute DELETE '" . $row[0] . "' app query!</font><br>";
                        else {
                            unlink($permanent_folder . $row[0] . ".apk");
                            $count++;
                        }
                    }else
                        $this->output.="<font color='#CD0000'>Cannot find app with id='" . intval($app_delete) . "'!</font><br>";
                }
                $this->output.=($count > 0) ? "Processed apps were deleted from disk and DB succesfully, total: " . $count . " apps <br>" : "";
            }

            $files = "";
            //Scan temp directory for uploaded files
            $dh = opendir($tmp_folder);
            while (false !== ($tmp_filename = readdir($dh))) {
                //Exclude '..' and '.' directories
                if ($tmp_filename !== "." && $tmp_filename !== "..")
                    $files[] = $tmp_filename;
            }
            sort($files);

            $layout['files'] = $files;
            $list_processed = "";
            //Read processed apps from DB with ordering by defined attributes
            $query = "SELECT * FROM $app_management_table ORDER by `app_label` ASC, `version` ASC, `sdkVersion` ASC ";
            $res = mysql_query($query);
            while ($row = mysql_fetch_assoc($res))
                $list_processed[] = $row;
        }
        $layout['list_processed'] = $list_processed;
        return $layout;
    }

}

///Create AppManagement class object
$appmanagement = new AppManagement();
///Upload APK file in temporary storage
$appmanagement->uploadApp();
///Display available APK files and process varoius user actions
$layout = $appmanagement->appList();
$files = $layout['files'];
$list_processed = $layout['list_processed'];
///Display system errors
$output = $appmanagement->output;


//Building the layout
require_once("templates/header.html");
require_once("templates/app_management.template.html");
require_once("templates/footer.html");
?>



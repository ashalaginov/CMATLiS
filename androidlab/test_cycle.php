<?php

/**
 * \file test_cycle.php
 * \brief Dynamic Test Cycle 
 * \details  Provides flexible configurable test environment based on Android SDK Emulator.
 * User can configure Emulator and Android Debugging Bridge (which are used further during test cycle) 
 * and launch one of available Android Virtual Devices (AVD).
 * Also, it is possible to select which options should be executed and logged during the test.
 * During running the test, infromation about executed test is automatically updated every 10 seconds on the screen.
 * \author Andrey Shalaginov <andrii.shalaginov@hig.no>
 * \date February-May 2013
 * \version   1.1
 */
/**
 * TODO:
 * INSTALL_FAILED_DEXOPT 
 * sudo chmod -R 777 /tmp/android-unknown/
 * sudo chmod -R 777 /tmp/.android/
 * kill-strart on cron
 * Create SDcard
 * xinit
 * sudo dpkg-reconfigure x11-common
 * xhost+
 * rm .Xauthority
 */
require_once("config.inc.php");

/**
 * Class TestCycle 
 * \brief Management of Running Emulators and Test Configuration
 */
class TestCycle {

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
     * Launch emulator with defined parameters chosen through the web panel
     */
    private function startAVD() {
        global $config_table, $vm_avds_table, $android_sdk_home, $android_adb, $android_user_config, $android_emulator, $vm_avds_table;
        shell_exec($android_sdk_home . "/" . $android_adb . " kill-server");
        sleep(10);
        shell_exec($android_sdk_home . "/" . $android_adb . " devices");
        //Check if there are anny emulators in the memory - otherwise do no show the Launch button
        if (strlen(shell_exec($android_sdk_home . "/" . $android_adb . " devices | sed -n 2p")) <= 1 && strlen(shell_exec("ps -A|grep emulator")) <= 1) {
            //IMPORTANT to create the link
            if (is_dir('/tmp/.android'))
                shell_exec("rm /tmp/.android");
            shell_exec("ln -s " . $android_user_config . " /tmp/");

            //Insert into DB Started Emulator ID
            //Check if AVD is in the runing loop
            if (isset($_POST['avd_to_run'])) {
                $startAVDname = $_POST['avd_to_run'];
                $query = "INSERT INTO $config_table 
				(`item`,`value`) VALUES
				('launchedAVD','" . mysql_real_escape_string($startAVDname) . "')
				ON DUPLICATE KEY UPDATE `value`= '" . mysql_real_escape_string($startAVDname) . "'; ";
                //Check whether it is possible to execute query
                if (mysql_query($query) === FALSE)
                    $this->output.="<font color='#CD0000'>Impossible to execute INSERT/UPDATE query!</font> <br>";

                //Insert into DB Started Emulator API	
                $query = "INSERT INTO $config_table 
				(`item`,`value`) VALUES
				('launchedAVD_API', (SELECT `api_level` FROM " . $vm_avds_table . "	WHERE `avd_name`='" . mysql_real_escape_string($startAVDname) . "'))
				ON DUPLICATE KEY UPDATE `value`= (SELECT `api_level` FROM " . $vm_avds_table . "	WHERE `avd_name`='" . mysql_real_escape_string($startAVDname) . "'); ";
                //Check whether it is possible to execute query
                if (mysql_query($query) === FALSE)
                    $this->output.="<font color='#CD0000'>Impossible to execute INSERT/UPDATE query!</font> <br>";
            }else {
                $query = "SELECT * FROM $config_table WHERE `item`='launchedAVD';";
                $res = mysql_query($query);
                $row = mysql_fetch_assoc($res);
                $startAVDname = $row['value'];
            }

            //Read test configuration from DB 
            $query = "SELECT `value` FROM $config_table WHERE item='testConfiguration'";
            $res = mysql_query($query);
            $row = mysql_fetch_row($res);

            if ($row != FALSE && count($row) > 0) {
                $config_array = unserialize($row[0]);
            }

            //Launch Android Emulator in an detached screen as background process 
            //-snapstorage <file>            file that contains all state snapshots (default <datadir>/snapshots.img)
            //-no-snapstorage                do not mount a snapshot storage file (this disables all snapshot functionality)
            //-snapshot <name>               name of snapshot within storage file for auto-start and auto-save (default 'default-boot')
            //-no-snapshot                   perform a full boot and do not do not auto-save, but qemu vmload and vmsave operate on snapstorage
            //-no-snapshot-save              do not auto-save to snapshot on exit: abandon changed state
            //-no-snapshot-load              do not auto-start from snapshot: perform a full boot
            //-snapshot-list                 show a list of available snapshots
            //-no-snapshot-update-time       do not do try to correct snapshot time on restore

            print_r($config_array);

            $avd_parameters = "";
            if (isset($config_array['disable_audio']))
                $avd_parameters.="-noaudio ";
            if (isset($config_array['enable_gpu']))
                $avd_parameters.="-gpu on ";
            if (isset($config_array['disable_boot_anim']))
                $avd_parameters.="-no-boot-anim ";
            if (isset($config_array['disable_window']))
                $avd_parameters.="-no-window ";
            if (isset($config_array['traffic'])) {
                $avd_parameters.="-tcpdump traffic.cap ";
                echo shell_exec("rm " . $android_sdk_home . "/tools/traffic.cap");
            }
            if (isset($config_array['use_profile'])) //IMPORTANT TO UNLOCK DEVICE which producec userdata
                $avd_parameters.="-initdata /home/andymir/websites/androidlab/userdata-qemu.img ";
            //INCREASE time of AVD load, but erase all data, more natural environment
            $avd_parameters.="-no-snapshot -wipe-data ";

            shell_exec("export DISPLAY=:0; screen -d -m " . $android_sdk_home . "/" . $android_emulator . " -avd " . $startAVDname . " " . $avd_parameters);
            //echo $android_sdk_home."/".$android_emulator." -avd ".$_POST['avd_to_run']." ".$avd_parameters;
            $this->output.="Android Emulator was launched successfully! It will be ready in a few minutes!</font>";
        }else
            $this->output.="<font color='#CD0000'>There is an Android Emulator entity in the memory. Try to stop first!</font>";
    }

    /**
     * Stop runnning emulator and existing instances
     */
    private function stopAVD() {
        global $android_sdk_home, $android_adb, $config_table;
        //Ensure that adb server is running
        shell_exec($android_sdk_home . "/" . $android_adb . " devices");
        sleep(2);

        //Kill all running emulators
        echo shell_exec($android_sdk_home . "/" . $android_adb . " emu kill");
        echo shell_exec("killall emulator64-x86");
        sleep(5);
        echo shell_exec("killall emulator64-x86");
        echo shell_exec("killall emulator");
        sleep(5);
        echo shell_exec("killall emulator");
        echo shell_exec("killall adb");
        sleep(5);
        echo shell_exec("killall adb");

        $this->output.="Android Emulator was stopped successfully!";
    }

    /**
     * Manage configuration of Test Case (Settings and Launch)
     * @return array Layout
     */
    public function testConf() {
        if (isset($_GET['test_conf'])) {
            global $config_table, $vm_avds_table, $android_sdk_home, $android_adb, $android_user_config, $android_emulator, $vm_avds_table;
            //Save selected configuration to the DB
            $config_array['testConf'] = 'testConf';
            if (isset($_POST['save_config'])) {
                foreach ($_POST['selected_conf_options'] as $key => $item)
                    $config_array[$key] = $item;
                //Make query to insert obtained data into DB
                $query = "INSERT INTO $config_table 
				(`item`,`value`) 
				VALUES('testConfiguration','" . mysql_real_escape_string(serialize($config_array)) . "') 
				ON DUPLICATE KEY UPDATE `value`= '" . mysql_real_escape_string(serialize($config_array)) . "'; ";
                //Check whether it is possible to execute query
                if (mysql_query($query) === FALSE)
                    $this->output.="<font color='#CD0000'>Impossible to execute INSERT/UPDATE query!</font> <br>";
            }

            //Read test configuration from DB 
            $query = "SELECT `value` FROM $config_table WHERE item='testConfiguration'";
            $res = mysql_query($query);
            $row = mysql_fetch_row($res);

            if ($row != FALSE && count($row) > 0) {
                $config_array = unserialize($row[0]);
            }

            $layout['available_avds'] = $available_avds;
            $layout['config_array'] = $config_array;

            if (isset($_POST['stop_emulator'])) {
                $this->stopAVD();
            }

            return $layout;
        }
    }

    /**
     * Perform Test cycle for each selected application
     * @param array Layout
     */
    public function testing() {

        if (isset($_GET['testing'])) {
            global $android_sdk_home, $android_adb, $vm_avds_table, $config_table, $app_management_table, $permanent_folder, $android_adb, $tests_results_table, $test_data_folder;

            //Check whether any devices are online 
            //Read available Android Virtual Devices
            $query = "SELECT * FROM $vm_avds_table ORDER by `avd_name` ASC ";
            $res = mysql_query($query);
            while ($row = mysql_fetch_assoc($res))
                $available_avds[] = $row; {

                $this->output.="The instance is connected, but may be in the boot process and can cause some delays (up to several minutes).";
                if (isset($_POST['selectedAppsToTest'])) {

                    //Read test configuration from DB 
                    $query = "SELECT `value` FROM $config_table WHERE item='testConfiguration'";
                    $res = mysql_query($query);
                    $row = mysql_fetch_row($res);
                    if ($row != FALSE && count($row) > 0)
                        $config_array = unserialize($row[0]);
                    else
                        $this->output.="<font color='#CD0000'>Impossible to extract Test configuration from the DB!</font> <br>";

                    $count = 0;
                    //Perform test cycle with each selected app and pre-defined parameters
                    foreach ($_POST['selectedAppsToTest'] as $key => $app_id) {

                        //Check whether an app in the DB
                        $query = "SELECT * FROM $app_management_table WHERE `id_app`='" . intval($app_id) . "'";
                        $res = mysql_query($query);
                        if ($res == FALSE)
                            $this->output.="<font color='#CD0000'>Impossible to execute SELECT '" . intval($app_id) . "' app query!</font> <br>";
                        else
                            $selected_app_details = mysql_fetch_assoc($res);

                        //Perform testing process 
                        if ($selected_app_details != FALSE && count($selected_app_details) > 0) {

                            //Check if API of AVD is higher than minSDK version of Application
                            $query = "SELECT * FROM $config_table WHERE `item`='launchedAVD_API';";
                            $res = mysql_query($query);
                            $row = mysql_fetch_assoc($res);
                            if (intval($selected_app_details['sdkVersion']) > intval($row['value'])) {
                                $this->output.="<br><font color='#CD0000'>Impossible to process correctly Application '" . $selected_app_details['app_label'] . "', minimal required API is v" . $selected_app_details['sdkVersion'] . ", but running AVD has API v" . intval($row['value']) . "!</font>";
                            } elseif (strlen($selected_app_details['launchable_activity']) < 1) {
                                $this->output.="<br><font color='#CD0000'>Application '" . $selected_app_details['app_label'] . "' does not have launchable activity and it is impossible to launch it!</font>";
                            } else {
                                //Start AVD		
                                $this->startAVD();
                                while ($devices = shell_exec($android_sdk_home . "/" . $android_adb . " devices ")) {
                                    preg_match_all('/(emulator-.*)[\s](offline|device)/', $devices, $parsed_data);
                                    if ($parsed_data[2][0] == 'device')
                                        break;
                                    else {
                                        sleep(30);
                                    }
                                }

                                $test_app_process_echo = "";
                                //Start duration counting
                                $duration_start = getTime();
                                //Count errors during test cycle
                                $detected_errors = 0;
                                $apk_file = $permanent_folder . $selected_app_details['md5_name'] . ".apk";
                                $package_name = trim($selected_app_details['package_name']);
                                $launchable_activity_name = trim($selected_app_details['launchable_activity']);

                                //---------------------------------------------------------------------------------------------------------------
                                //INSTALL App
                                //uninstall app initially if installed
                                //If AVD is not fully booted, then quit and abort test cycle	
                                $uninstall_result = shell_exec($android_sdk_home . "/" . $android_adb . " uninstall " . $package_name);
                                while (strpos($uninstall_result, "Error: Could not access the Package Manager.  Is the system running?") !== FALSE) {
                                    sleep(20);
                                    $uninstall_result = shell_exec($android_sdk_home . "/" . $android_adb . " uninstall " . $package_name);
                                }

                                $test_app_process_echo.= "
                                    -----------------------------------------------------------------------<br>Initial Uninstall!<br>" . $uninstall_result;

                                //Clear log buffer
                                shell_exec($android_sdk_home . "/" . $android_adb . " logcat -c ");
                                //install
                                $test_app_process_echo.= "
                                -----------------------------------------------------------------------<br>Install!<br>" . shell_exec($android_sdk_home . "/" . $android_adb . " install " . $apk_file);
                                //-----------------------------------------------------------------------<br>Install!<br>" . shell_exec($android_sdk_home . "/" . $android_adb . " wait-for-device install " . $apk_file);
                                //Due to bug in Android SDK r21, we should filter the log output
                                if (isset($config_array['log_install'])) {
                                    $parsed_data = shell_exec($android_sdk_home . "/" . $android_adb . " logcat -d  ");
                                    $parsed_data = preg_replace('/((.*)Unexpected value from nativeGetEnabledTags: 0\r\n)/', '', $parsed_data);
                                    $log_install = $parsed_data;
                                }
                                //CREATE folder for EACH TEST			
                                $folder_name = $test_data_folder . "Test_" . date('Y_m_d_H_i_s');
                                echo shell_exec("mkdir " . $folder_name);

                                //---------------------------------------------------------------------------------------------------------------
                                //RUN/STOP App
                                //Clear log buffer
                                shell_exec($android_sdk_home . "/" . $android_adb . " logcat -c ");
                                //launch
                                $test_app_process_echo.= "
								-----------------------------------------------------------------------<br>Start App!<br>";
                                $string_tmp = shell_exec($android_sdk_home . "/" . $android_adb . " shell am start -n " . $package_name . "/" . $launchable_activity_name);

                                //Cut noisy output of am start
                                $test_app_process_echo.= substr($string_tmp, 0, strpos($string_tmp, "usage: am [start"));

                                //Start tracing if necessary
                                if (isset($config_array['trace'])) {
                                    //echo shell_exec($android_sdk_home . "/" . $android_adb . " shell rm /data/misc/trace.txt");

                                    shell_exec("screen -d -m " . $android_sdk_home . "/" . $android_adb . " shell strace -e trace=all -p$(" . $android_sdk_home . "/" . $android_adb . " shell ps | grep " . $package_name . " | awk '{ print $2 }') -tt -f -o /data/misc/trace.txt 2>&1");
                                }

                                print "<pre>";

                                //Read cpu/memory consumption
                                if (isset($config_array['read_cpu_usage'])) {
                                    $read_cpu_usage = array();
                                    //Need to detect which column is CPU,THR and VSS
                                    $tmp_testeg = shell_exec($android_sdk_home . "/" . $android_adb . " shell top -n 1 | grep CPU");
                                    print_r($tmp_testeg);
                                    for ($i = 0; $i < 20; $i++) {
                                        if (strpos($tmp_testeg, "PCY") !== FALSE && strpos($tmp_testeg, "PR") !== FALSE) //Android >4.0
                                            $tmp = shell_exec($android_sdk_home . "/" . $android_adb . " shell top -n 1  | grep " . $package_name . " | awk '{print$3,$5,$6,$7}'");
                                        elseif (strpos($tmp_testeg, "PCY") !== FALSE && strpos($tmp_testeg, "PR") === FALSE) //Android 2.3.3
                                            $tmp = shell_exec($android_sdk_home . "/" . $android_adb . " shell top -n 1  | grep " . $package_name . " | awk '{print$2,$4,$5,$6}'");
                                        elseif (strpos($tmp_testeg, "PCY") === FALSE && strpos($tmp_testeg, "PR") === FALSE) //Android 2.2
                                            $tmp = shell_exec($android_sdk_home . "/" . $android_adb . " shell top -n 1  | grep " . $package_name . " | awk '{print$2,$4,$5,$6}'");

                                        print_r($tmp);
                                        if ($i == 0)
                                            $start_execution_time = getTime();
                                        if (strlen($tmp) > 1) {
                                            $tmp = explode(" ", $tmp);
                                            $read_cpu_usage[number_format((getTime() - $start_execution_time), 2)]['CPU'] = $tmp[0];
                                            $read_cpu_usage[number_format((getTime() - $start_execution_time), 2)]['THR'] = $tmp[1];
                                            $read_cpu_usage[number_format((getTime() - $start_execution_time), 2)]['VSS'] = $tmp[2];
                                            $read_cpu_usage[number_format((getTime() - $start_execution_time), 2)]['RSS'] = $tmp[3];
                                        }
                                    }
                                }else
                                    sleep(10);

                                print "</pre>";

                                //adb shell top -n 1 | grep com.android.keychain
                                //Adoird 4.0
                                //PID  PR  CPU% S  #THR     VSS     RSS      PCY UID      Name
                                //1201  0   0%  S    10     174272K  17912K  bg system   com.android.keychain
                                //Android 2.3.3
                                //   PID CPU% S  #THR     VSS     RSS PCY UID      Name
                                //Android 2.2
                                //  PID CPU% S  #THR     VSS     RSS UID      Name
                                // 563   7% S    35 191320K  32160K system   system_server
                                //adb shell ps -p -c  
                                //-c show CPU (may not be available prior to Android 4.x) involved 
                                //USER     PID   PPID  VSIZE  RSS   CPU PRIO  NICE  RTPRI SCHED   WCHAN    PC         NAME
                                //echo $(adb shell ps | grep com.android.phone | awk '{ system("adb shell cat /proc/" $2 "/stat");}' | awk '{print $14+$15;}')
                                //SCREENING if necessary
                                if (isset($config_array['screenshot'])) {
                                    $test_app_process_echo.= "
									-----------------------------------------------------------------------<br>Screening!<br>";
                                    $test_app_process_echo.= shell_exec($android_sdk_home . "/" . $android_adb . " shell screencap /data/local/" . $selected_app_details['md5_name'] . ".png ");
                                    $test_app_process_echo.= shell_exec($android_sdk_home . "/" . $android_adb . " pull /data/local/" . $selected_app_details['md5_name'] . ".png " . $folder_name . "/screenshot.png ");
                                    if (file_exists($folder_name . "/screenshot.png"))
                                        $screenshot = true;
                                }

                                //Stop App
                                $test_app_process_echo.= "
								-----------------------------------------------------------------------<br>Stop!<br>" . shell_exec($android_sdk_home . "/" . $android_adb . " force-stop " . $package_name);
                                //Due to bug in Android SDK r21, we should filter the log output
                                if (isset($config_array['log_launch'])) {
                                    $parsed_data = shell_exec($android_sdk_home . "/" . $android_adb . " logcat -d  ");
                                    $parsed_data = preg_replace('/((.*)Unexpected value from nativeGetEnabledTags: 0\r\n)/', '', $parsed_data);
                                    $log_launch = $parsed_data;
                                }
                                //Ensure that app was stopped completely
                                shell_exec($android_sdk_home . "/" . $android_adb . " force-stop " . $package_name);
                                shell_exec($android_sdk_home . "/" . $android_adb . " shell kill $(" . $android_sdk_home . "/" . $android_adb . " shell ps | grep " . $package_name . " | awk '{ print $2 }')");
                                $android_sdk_home . "/" . $android_adb . " shell kill $(" . $android_sdk_home . "/" . $android_adb . " shell ps | grep " . $package_name . " | awk '{ print $2 }')";
                                //Stop Browser if was opened
                                shell_exec($android_sdk_home . "/" . $android_adb . " shell kill `" . $android_sdk_home . "/" . $android_adb . " shell ps | grep browser | awk '{ print $2 }'`");


                                //---------------------------------------------------------------------------------------------------------------
                                //UI TESTS
                                //TODO: ADD resources usa capturing
                                if (isset($config_array['ui_tester'])) {
                                    sleep(5);
                                    //Clear log buffer
                                    shell_exec($android_sdk_home . "/" . $android_adb . " logcat -c ");
                                    //Due to bug in Android SDK r21, we should filter the log output
                                    $test_app_process_echo.= "
									-----------------------------------------------------------------------<br>UI Tests<br>" . shell_exec($android_sdk_home . "/" . $android_adb . "  shell monkey -p " . $package_name . " -v 500 --throttle 50 --pct-touch 70");
                                    //Create  log
                                    if (isset($config_array['log_test'])) {
                                        $parsed_data = shell_exec($android_sdk_home . "/" . $android_adb . " logcat -d  ");
                                        $parsed_data = preg_replace('/((.*)Unexpected value from nativeGetEnabledTags: 0\r\n)/', '', $parsed_data);
                                        $log_test = $parsed_data;
                                    }
                                }

                                //Finish Tracing if necessary
                                if (isset($config_array['trace'])) {
                                    shell_exec($android_sdk_home . "/" . $android_adb . " pull /data/misc/trace.txt " . $folder_name . "/");
                                }


                                //---------------------------------------------------------------------------------------------------------------
                                //PULL App data from Android Emulator
                                if ($config_array['pull_data']) {
                                    $test_app_process_echo.= "
									-----------------------------------------------------------------------<br>Pull Data!<br>" . shell_exec($android_sdk_home . "/" . $android_adb . " pull /data/data/" . $package_name . "  " . $folder_name . "/data/ ");
                                    //check wether it exists
                                    if (is_dir($folder_name . "/data/"))
                                        $pull_data = true;
                                }

                                //---------------------------------------------------------------------------------------------------------------
                                //BUGREPORT
                                if (isset($config_array['bugreport'])) {
                                    shell_exec($android_sdk_home . "/" . $android_adb . " bugreport > " . $folder_name . "/bugreport");
                                }

                                //---------------------------------------------------------------------------------------------------------------
                                //UNINSTALL App	
                                //Ensure that app was stopped completely
                                shell_exec($android_sdk_home . "/" . $android_adb . " force-stop " . $package_name);
                                shell_exec($android_sdk_home . "/" . $android_adb . " shell kill $(" . $android_sdk_home . "/" . $android_adb . " shell ps | grep " . $package_name . " | awk '{ print $2 }')");
                                $android_sdk_home . "/" . $android_adb . " shell kill $(" . $android_sdk_home . "/" . $android_adb . " shell ps | grep " . $package_name . " | awk '{ print $2 }')";
                                //Stop Browser if was opened
                                shell_exec($android_sdk_home . "/" . $android_adb . " shell kill `" . $android_sdk_home . "/" . $android_adb . " shell ps | grep browser | awk '{ print $2 }'`");

                                //Clear log buffer				
                                shell_exec($android_sdk_home . "/" . $android_adb . " logcat -c ");
                                $test_app_process_echo.= "
								-----------------------------------------------------------------------<br>Final Uninstall!<br>" . shell_exec($android_sdk_home . "/" . $android_adb . " uninstall " . $package_name);
                                //Due to bug in Android SDK r21, we should filter the log output
                                if (isset($config_array['log_uninstall'])) {
                                    $parsed_data = shell_exec($android_sdk_home . "/" . $android_adb . " logcat -d  ");
                                    $parsed_data = preg_replace('/((.*)Unexpected value from nativeGetEnabledTags: 0\r\n)/', '', $parsed_data);
                                    $log_uninstall = $parsed_data;
                                }

                                //Check if there are any errors			
                                if (strpos($test_app_process_echo, "Bad component name:") !== FALSE)
                                    $detected_errors++;
                                if (strpos($test_app_process_echo, "Error: Activity class") !== FALSE)
                                    $detected_errors++;
                                if (strpos($test_app_process_echo, "screencap: not found") !== FALSE)
                                    $detected_errors++;
                                if (strpos($test_app_process_echo, "INSTALL_FAILED_OLDER_SDK") !== FALSE)
                                    $detected_errors++;


                                //Process traffic data
                                shell_exec("mv traffic.cap " . $folder_name . "/");
                                shell_exec("tshark -r " . $folder_name . "/traffic.cap -Tpsml  > " . $folder_name . "/traffic.xml");

                                //TODO: analysis of Sensitive data in traffic/folders
                                //strings emulator.cap  | grep andrii    
                                //Make query to insert obtained data into DB
                                $query = "INSERT INTO $tests_results_table 
								(`avd_name`,
								`id_app`,
								`test_configuration`,
								`log_install`, 
								`log_launch`, 
								`log_test`, 
								`log_uninstall`, 
								`screenshot`, 
								`pull_data`, 
								`read_cpu_usage`,
								`duration`, 
								`test_app_process_echo`, 
								`bugreport`, 
								`folder_name`,
								`errors`) 
								VALUES((SELECT `value` FROM " . $config_table . "	WHERE `item`='launchedAVD'),
								'" . mysql_real_escape_string($selected_app_details['id_app']) . "', 
								'" . mysql_real_escape_string(serialize($config_array)) . "', 
								'" . (isset($log_install) ? mysql_real_escape_string($log_install) : "") . "', 
								'" . (isset($log_launch) ? mysql_real_escape_string($log_launch) : "") . "', 
								'" . (isset($log_test) ? mysql_real_escape_string($log_test) : "") . "', 
								'" . (isset($log_uninstall) ? mysql_real_escape_string($log_uninstall) : "") . "', 
								'" . (isset($screenshot) ? true : false) . "', 
								'" . (isset($pull_data) ? true : false) . "', 
								'" . (isset($read_cpu_usage) ? mysql_real_escape_string(serialize($read_cpu_usage)) : "") . "',
								'" . number_format((getTime() - $duration_start), 2) . "', 
								'" . (isset($test_app_process_echo) ? mysql_real_escape_string($test_app_process_echo) : "") . "', 
								'" . (isset($config_array['bugreport']) ? true : false) . "', 
								'" . mysql_real_escape_string($folder_name) . "',
								'" . $detected_errors . "');";

                                //Check whether it is possible to execute query
                                if (mysql_query($query) === FALSE) {
                                    $this->output = "<font color='#CD0000'>Impossible to execute INSERT query!</font> <br>";
                                }else
                                    $count++;
                                $this->stopAVD();
                            }
                        }else
                            $this->output.="<font color='#CD0000'>Cannot find app with id='" . intval($app_id) . "'!</font><br>";
                    }
                    $this->output.=($count > 0) ? " <br>Selected Applications were tested, total: " . $count . " apps" : "";
                }
            }
            $list_processed = "";
            //Read processed apps from DB with ordering by defined attributes
            $query = "SELECT * FROM $app_management_table ORDER by `app_label` ASC, `version` ASC, `sdkVersion` ASC ";
            $res = mysql_query($query);
            while ($row = mysql_fetch_assoc($res))
                $list_processed[] = $row;

            $layout['list_processed'] = $list_processed;
            $layout['available_avds'] = $available_avds;
            return $layout;
        }
    }

    /**
     * Show list with Tests Data for each application
     * @return array Layout
     */
    public function testResults() {
        if (isset($_GET['test_results'])) {
            global $tests_results_table, $app_management_table, $vm_avds_table, $anaysis_results_table;

            //Delete selected test results and corresponding data folder
            if (isset($_POST['deleteSelectedTests']) && isset($_POST['selectedTestToDelete'])) {
                $count = 0;
                foreach ($_POST['selectedTestToDelete'] as $key => $test_delete) {
                    //Check whether an app in the DB
                    $query = "SELECT * FROM $tests_results_table WHERE `id_test`='" . intval($test_delete) . "'";
                    $res = mysql_query($query);
                    if ($res == FALSE)
                        $this->output.="<font color='#CD0000'>Impossible to execute SELECT '" . intval($test_delete) . "' test query!</font> <br>";
                    else
                        $row = mysql_fetch_assoc($res);

                    //Perform deletion process from DB and disk
                    if ($row != FALSE && count($row) > 0) {
                        //DELETE DATA FROM ANALYSIS,AND TEST in order to exclude missing data
                        $query = "DELETE FROM $tests_results_table WHERE `id_test`='" . $row['id_test'] . "';";
                        $query1 = "DELETE FROM $anaysis_results_table WHERE `id_test`='" . $row['id_test'] . "';";

                        if (mysql_query($query) == FALSE || mysql_query($query1) == FALSE)
                            $this->output.="<font color='#CD0000'>Impossible to execute DELETE '" . $row['id_test'] . "' test query!</font><br>";
                        else {
                            echo shell_exec("rm -rf " . $row['folder_name']);
                            $count++;
                        }
                    }else
                        $this->output.="<font color='#CD0000'>Cannot find test with id='" . intval($test_delete) . "'!</font><br>";
                }
                $this->output.=($count > 0) ? "Performed tests were deleted from disk and DB succesfully, total: " . $count . " test <br>" : "";
            }

            //Grep Tests data
            $query = "SELECT * FROM $tests_results_table
					LEFT JOIN $app_management_table ON $tests_results_table.id_app = $app_management_table.id_app 
					LEFT JOIN $vm_avds_table ON $tests_results_table.avd_name = $vm_avds_table.avd_name 
					ORDER BY `id_test` DESC; ";
            $result = mysql_query($query);
            while ($row = mysql_fetch_assoc($result)) {
                $list_tests[] = $row;
            }

            $layout['list_tests'] = $list_tests;
            return $layout;
        }
    }

}

///Create TestCycle class object
$testcycle = new TestCycle();
///Print information about available Target Platforms and Virtual Devices (launch stop it).
$layout = $testcycle->testConf();
$available_avds = $layout['available_avds'];
$config_array = $layout['config_array'];

//Perform Testing process
$layout = $testcycle->testing();
$list_processed = $layout['list_processed'];
$available_avds = $layout['available_avds'];
//Check List of available Tests Data
$layout = $testcycle->testResults();
$list_tests = $layout['list_tests'];

///Display system errors
$output = $testcycle->output;

//Building the layout of test_conf.php
require_once("templates/header.html");
require_once("templates/test_cycle.template.html");
require_once("templates/footer.html");
?>
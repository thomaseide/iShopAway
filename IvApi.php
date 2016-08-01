<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

set_time_limit(0);
define("ANDROID_APIKEY", "AIzaSyDyNjtn8w3Jiul8MPqNYZthOge-H4c6-RE");
date_default_timezone_set('UTC');

class IvApi extends CI_Controller {

    function IvApi() {
        parent::__construct();
        $this->result = array();
        $this->msg = '';

        if ($_SERVER['HTTP_HOST'] == "localhost") {
            $this->dire_path = $_SERVER['DOCUMENT_ROOT'] . "/invitedApp/";
        } else {
            $this->dire_path = $_SERVER['DOCUMENT_ROOT'] . "/invitedApp/";
        }

        $this->is_debug = isset($_POST['is_debug']) ? $_POST['is_debug'] : 0;
        //echo date("Y-m-d H:i:s");
        //echo $this->get_timeago("2015-03-30 04:46:04");exit;
        if ($this->is_debug == 1) {
            //  $this->print_debug();
        }
    }

    private function change_date_format($localtime) {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $localtime);
        return $date->format('Y-m-d H:i:s');
    }

    public function login() {
        $email = $this->input->post('email', TRUE);
        $password = $this->input->post('password', TRUE);
        $device_token = $this->input->post('device_token', TRUE);
        $device_type = $this->input->post('device_type', TRUE);
        $device_id = $this->input->post('device_id', TRUE);


        ###############  post parameter #######
        $email = $this->is_require($email);
        $password = $this->is_require($password);
        $device_token = isset($device_token) ? $device_token : '';
        $device_type = isset($device_type) ? $device_type : '0';
        $device_id = isset($device_id) ? $device_id : ''; //for android
        #######################################
        $where = array(
            'email' => $email
        );
        $user_tbl = 'tbl_user';

        $t = $this->Basic->db_select('*', $user_tbl, $where, array(), '', '', array(1, 0), 'row_array');

        //echo "<pre>";print_r($t);
        if (empty($t)) {
            $this->_sendResponse(9); // email invalid
        }


        if ($t['is_active'] == 0) {
            $this->msg = "Your account is deleted by you on " . $t['date'];
            $this->_sendResponse(19); // deactive invalid
        }


        $where = array(
            'email' => $email,
            'password' => md5($password),
        );

        $user_info = $this->Basic->db_select("*", $user_tbl, $where, array(), '', '', '', 'row_array', 0);
        if (empty($user_info)) {

            if ($t['temp_pass'] == $password) {
                $user_info = $t;
            } else {
                $this->_sendResponse(10); // password invalid
            }
        }
        if ($user_info['role'] == 2) {

            $business_other_info = $this->Basic->db_select("stock_limit", "tbl_business_stock", array("business_id" => $user_info['user_id']), array(), 'day asc', '', '', 'all', 0);
            $user_info['stocks_info'] = $business_other_info;
        }


        $user_token = $this->get_random_string();

        $token_arr = array(
            "device_token" => $device_token,
            "device_type" => $device_type,
            "user_token" => $user_token,
            "device_id" => $device_id,
            "user_id" => $user_info['user_id'],
        );
        $this->manage_token($token_arr);
        $business_info = array();
        if ($t['role'] == 2) {
            $bid = $t['user_id'];
//            $business_info = $this->Basic->db_select('*,DATE_FORMAT(start_time,"%H:%i")', 'tbl_business', array('created_by' => $t['user_id']), array(), '', '', array(1, 0), 'row_array');
            $q = "select *,DATE_FORMAT(start_time,'%H:%i') as start_time,DATE_FORMAT(end_time,'%H:%i') as end_time from tbl_business_time_temp where business_id = $bid";
            $business_info = $this->Basic->select_custom($q);
            $business_info = $business_info[0];
            $q = ("select DATE_FORMAT(start_time,'%H:%i') as start_time,DATE_FORMAT(end_time,'%H:%i') as end_time from tbl_business_time where business_id=$bid order by day asc");
            $opening_hours = $this->Basic->select_custom($q);
            $business_info['opening_hours'] = $opening_hours;
        }

        $user_info['user_token'] = $user_token;


        $this->result = array_merge($user_info, $business_info);


        $status_code = 1;

        //echo "<pre>";print_r($this->result);
        $this->_sendResponse($status_code);
    }

    public function signup() {
        $email = $this->input->post('email', TRUE);
        $password = $this->input->post('password', TRUE);
        $display_name = $this->input->post('display_name', TRUE);
        $first_name = $this->input->post('first_name', TRUE);
        $last_name = $this->input->post('last_name', TRUE);
        $latitude = $this->input->post('latitude', TRUE);
        $longitude = $this->input->post('longitude', TRUE);
        $gender = $this->input->post('gender', TRUE);
        $device_token = $this->input->post('device_token', TRUE);
        $device_type = $this->input->post('device_type', TRUE);
        $device_id = $this->input->post('device_id', TRUE);

        ###############  post parameter ########################################

        $role = 3;
        $email = urldecode($this->is_require($email));
        $password = $this->is_require($password);
        $display_name = urldecode(isset($display_name) ? $display_name : '');
        $first_name = isset($first_name) ? $first_name : '';
        $last_name = isset($last_name) ? $last_name : '';
        $profile_pic = "";
        $latitude = isset($latitude) ? $latitude : '0';
        $longitude = isset($longitude) ? $longitude : '0';
        $gender = isset($gender) ? $gender : 0;

        $device_token = isset($device_token) ? $device_token : '';
        $device_type = isset($device_type) ? $device_type : '0';
        $device_id = isset($device_id) ? $device_id : '';

        ######################################################################## 

        if (!empty($_FILES)) {
            foreach ($_FILES as $k => $v) {

                $user_token = $this->get_random_string();
                $tuid = time() . $user_token;
                $fn = $this->file_upload($v, $tuid);
                if ($fn == "") {
                    $this->_sendResponse(5); //file can not upload
                } else {
                    $profile_pic = $fn;
                }
                break;
            }
        }

        $t = $this->Basic->db_select('count(*) as cnt', 'tbl_user', array('email' => $email), '', '', '', array(1, 0), 'row');

        if ($t->cnt >= 1) {
            $this->_sendResponse(8); //email already exists
        }

        $user_token = $this->get_random_string();
        $verification_code = $this->get_unique_code('tbl_user', 'verification_code');
        $ins = array(
            'role' => $role,
            'email' => $email,
            'password' => md5($password),
            'display_name' => $display_name,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'profile_pic' => $profile_pic,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'gender' => $gender,
            'verification_code' => $verification_code,
            'login_through' => 1, //email login
            'is_active' => 1,
            'is_verify' => 1
        );
        $temp = $this->Basic->insert_entry('tbl_user', $ins, TRUE);
        if ($temp['stat'] == 1) {
            $user_id = $temp['last_id'];
            $token_arr = array(
                "device_token" => $device_token,
                "device_type" => $device_type,
                "user_token" => $user_token,
                "device_id" => $device_id,
                "user_id" => $user_id,
            );
            $this->manage_token($token_arr);

            $user_info = $this->Basic->db_select('*', 'tbl_user', array("user_id" => $user_id), array(), '', '', array(1, 0), 'row_array');
            $user_info['user_token'] = $user_token;
            $this->result = $user_info;
            $this->result['is_new_user'] = 1;
            $status_code = 1;
        } else {
            $status_code = 0;
        }
        //echo "<pre>";print_r($result);exit;
        $this->_sendResponse($status_code);
    }

    public function send_mail() {
        $user_id = $this->is_require($_POST, 'user_id');
        $user_token = $this->is_require($_POST, 'user_token');
        $this->Is_authorised($user_id, $user_token);

        $email = $this->is_require($_POST, 'email');
        $email_message = isset($_POST['email_message']) ? $_POST['email_message'] : '';
        $email_subject = isset($_POST['email_subject']) ? $_POST['email_subject'] : '';
        $arr = array(
            'subject' => $email_subject,
            'message_body' => $email_message,
            'to' => $email,
        );
        $this->send_email($arr);
    }

    public function test_email() {


        $email_message = "fdg gfgj ghfdgfhg fjkggh gkbh jg nhkgf nfghfkhj gfh";
        $email = "ashish.jksol@gmail.com";
        $arr = array(
            'subject' => "In Moment : Verify The Account",
            'message_body' => $email_message,
            'to' => $email,
        );
        $this->send_email($arr);
    }

    public function forgot_password_new() {
        /*
         *  send the reset password link to user by email with secret token that is update for user record.
         */
        $ct = date('Y-m-d H:i:s'); // current time
        $dt = $this->is_require($_POST, 'localtime'); // in mail display time text 
        ###############  post parameter #######
        $email = $this->is_require($_POST, 'email');
        #######################################
//        $q = "select user_id,email,name,password from tbl_user where email like '$email' limit 1";
//        $info = $this->Basic->select_custom($q);
        $where = array(
            "email" => $email,
        );
        $info = $this->Basic->db_select('user_id,email,display_name,password,thirdparty_id', "tbl_user", $where, array(), '', '', array(1, 0), 'row_array');

        if (empty($info)) {
            $this->_sendResponse(9); //email invalid
        }

        if ($info['thirdparty_id'] != "") {
            $this->_sendResponse(18); //email not exits
        }

        $secret_token = $this->get_random_string(4);
        $this->Basic->update_entry("tbl_user", array('temp_pass' => $secret_token,), array('user_id' => $info['user_id']));
        $ua = $this->get_useragent();

        $email_message = $this->load->view('email/forgot_password_new', $data = array('dt' => $dt, 'name' => $info['display_name'], 'temp_password' => $secret_token), TRUE);


        $arr = array(
            'subject' => "EZQ : Forgot Password",
            'message_body' => $email_message,
            'to' => $email,
        );
        $this->send_email($arr);
        $this->_sendResponse(1);
    }

    public function reset_password_new() {
        /*
         *  if user forgot the password than after callling forgot password api we call reset password api.
         */
        ###############  post parameter #######   
        $user_id = $this->is_require($_POST, 'user_id');
        $user_token = $this->is_require($_POST, 'user_token');
        $this->Is_authorised($user_id, $user_token);
        $temp_password = $this->is_require($_POST, 'temp_password');
        $new_password = $this->is_require($_POST, 'new_password');
        #######################################

        $user_tbl = "tbl_user";

        $where = array(
            'temp_pass' => $temp_password,
            'user_id' => $user_id,
        );
        $info = $this->Basic->db_select('*', $user_tbl, $where, array(), '', '', array(1, 0), 'row_aray');

        if (!empty($info)) {

            $status_code = 1;
            $this->Basic->update_entry($user_tbl, array('password' => md5($new_password), 'temp_pass' => ''), array('user_id' => $info[0]['user_id']));
            $this->msg = "password reset successfully";
        } else {
            $status_code = 16;
            $this->msg = "Temporary Password Invalid";
        }
        $this->_sendResponse($status_code);
    }

    public function change_password() {
        $user_id = $this->is_require($_POST, 'user_id');
        $user_token = $this->is_require($_POST, 'user_token');
        $this->Is_authorised($user_id, $user_token);
        $current_password = $this->is_require($_POST, 'current_password');
        $new_password = $this->is_require($_POST, 'new_password');
        $where = array(
            "user_id" => $user_id,
        );
        $info = $this->Basic->db_select('password', "tbl_user", $where, array(), '', '', array(1, 0), 'row_array');
        if ($info['password'] != md5($current_password)) {
            $this->_sendResponse(10);
        }
        $status_code = 1;
        $this->Basic->update_entry('tbl_user', array('password' => md5($new_password)), array('user_id' => $user_id));
        $this->msg = "password change successfully";
        $this->_sendResponse($status_code);
    }

    public function login_by_thirdparty() {

        $thirdparty_id = $this->input->post('thirdparty_id');
        $email = $this->input->post('email', TRUE);
        $display_name = $this->input->post('display_name', TRUE);
        $first_name = $this->input->post('first_name', TRUE);
        $last_name = $this->input->post('last_name', TRUE);
        $latitude = $this->input->post('latitude', TRUE);
        $longitude = $this->input->post('longitude', TRUE);
        $gender = $this->input->post('gender', TRUE);
        $device_token = $this->input->post('device_token', TRUE);
        $device_type = $this->input->post('device_type', TRUE);
        $device_id = $this->input->post('device_id', TRUE);
        $dob = $this->input->post('dob', TRUE);



        $is_new_user = 1;
        $thirdparty_id = $this->is_require($thirdparty_id);
        $display_name = $this->is_require($display_name);
        $dob = isset($dob) ? $dob : '';
        $gender = isset($gender) ? $gender : 0;
        $device_token = isset($device_token) ? $device_token : '';
        $device_type = isset($device_type) ? $device_type : '0';
        $device_id = isset($device_id) ? $device_id : '';
        $login_through = 2; //defult facebook
        $user_token = $this->get_random_string(10);
        $profile_pic = '';
        $role = 3;
        if ($login_through == '2') {
            $this->param_str = "?width=200&height=200";
            $profile_pic = "http://graph.facebook.com/" . $thirdparty_id . "/picture" . $this->param_str;
        }



        $info = array(
            'display_name' => $display_name,
            'first_name' => isset($first_name) ? $first_name : '',
            'last_name' => isset($last_name) ? $last_name : '',
            'email' => isset($email) ? $email : '',
            'latitude' => isset($latitude) ? $latitude : '0',
            'longitude' => isset($longitude) ? $longitude : '0',
            'profile_pic' => $profile_pic,
            'thirdparty_id' => $thirdparty_id,
            'login_through' => $login_through,
            'role' => $role,
            'dob' => $dob,
            'gender' => $gender,
            'is_active' => 1,
            'is_verify' => 1,
        );

        $wh = array(
            "thirdparty_id" => $thirdparty_id,
            "login_through" => $login_through,
        );
        $t = $this->Basic->db_select('*', 'tbl_user', $wh, array(), '', '', array(1, 0), 'row_array');



        if (empty($t)) {//insert record
            $temp = $this->Basic->insert_entry('tbl_user', $info, TRUE);
            //echo "<pre>";print_r($temp);exit;
            if (empty($temp)) {

                $this->_sendResponse(0);
            }
            $user_id = $temp['last_id'];
        } else {
            if ($t['is_active'] == 0) {
                $this->msg = "Your account is deleted by you on " . $t['date'];
                $this->_sendResponse(19); // deactive invalid
            }
            $is_new_user = 0;
            $user_id = $t['user_id'];
            $wh = array("user_id" => $user_id);
            /*
             * remove the not updated parameter here
             */
            unset($info['profile_pic']);
            //unset($info['display_name']);
            $this->Basic->update_entry('tbl_user', $info, $wh);
        }
        $token_arr = array(
            "device_token" => $device_token,
            "device_type" => $device_type,
            "user_token" => $user_token,
            "device_id" => $device_id,
            "user_id" => $user_id,
        );
        $this->manage_token($token_arr);


        $user_info = $this->Basic->db_select('*', 'tbl_user', $wh, array(), '', '', array(1, 0), 'row_array');
        if (!empty($user_info)) {
            $user_info['user_token'] = $user_token;
            $this->result = $user_info;
            $this->result['is_new_user'] = $is_new_user;
            $this->result['user_token'] = $user_token;
            $status_code = 1;
            $this->msg = "Login successfully";
        } else {
            $status_code = 0;
        }
        $this->_sendResponse($status_code);
    }

    private function manage_token($arr) {
        ###############  post parameter #######

        if ($arr['device_type'] == 1) {
            $udid = $arr['device_token'];
            $wh_call = "device_token";
        } else {
            $udid = $arr['device_id'];
            $wh_call = "device_id";
        }

        $wh = array(
            $wh_call => $udid,
            "device_type" => $arr['device_type'],
        );
        $info = $this->Basic->db_select('count(*) as cnt', 'tbl_token', $wh, array(), '', '', array(1, 0), 'row_array');
        if ($info['cnt'] <= 0) {
            $inn = array(
                "device_token" => $arr['device_token'],
                "device_type" => $arr['device_type'],
                "user_token" => $arr['user_token'],
                "device_id" => $arr['device_id'],
                "user_id" => $arr['user_id'],
            );
            $this->Basic->insert_entry("tbl_token", $inn);
        } else {
            $up = array(
                'user_token' => $arr['user_token'],
                'user_id' => $arr['user_id'],
            );

            $this->Basic->update_entry("tbl_token", $up, $wh);
        }
    }

    public function add_event() {
        $user_id = $this->input->post('user_id', TRUE);
        $user_token = $this->input->post('user_token', TRUE);
        $description = $this->input->post('description', TRUE);
        $date_of_event = $this->input->post('date_of_event', TRUE);
        $event_title = $this->input->post('event_title', TRUE);
        $address = $this->input->post('address', TRUE);
        $event_cat_id = $this->input->post('event_cat_id', TRUE);
        $link = $this->input->post('link', TRUE);
        $youtube_link = $this->input->post('youtube_link', TRUE);
        $longitude = $this->input->post('longitude', TRUE);
        $latitude = $this->input->post('latitude', TRUE);

        $event_code = $this->get_random_string(8);


        $user_id = $this->is_require($user_id);
        $user_token = $this->is_require($user_token);
        $this->Is_authorised($user_id, $user_token);
        $event_title = isset($event_title) ? $event_title : '';
        $description = isset($description) ? $description : ''; //in Y-m-d format
        $address = isset($address) ? $address : ''; //in Y-m-d format
        $date_of_event = isset($date_of_event) ? $date_of_event : '0000-00-00'; //in Y-m-d format
        ########################################################################
        $images = array(
            "image1" => "",
            "image2" => "",
            "image3" => "",
            "thumb_image1" => "",
            "thumb_image2" => "",
            "thumb_image3" => ""
        );
        $total_images_count = 0;
        ########################################################################

        if (!empty($_FILES)) {
            foreach ($_FILES as $k => $v) {
                $fn_id = time() . "_" . $this->get_random_string();
                $fn = $this->file_upload($v, $fn_id, 0);
                if ($fn == "") {
                    $this->_sendResponse(5); //file can not upload
                }
                switch ($k) {
                    case "image1":
                        $total_images_count++;
                        $images["image1"] = $fn;
                        break;
                    case "image2":
                        $total_images_count++;
                        $images["image2"] = $fn;
                        break;
                    case "image3":
                        $total_images_count++;
                        $images["image3"] = $fn;
                        break;

                    case "thumb_image1":

                        $images["thumb_image1"] = $fn;
                        break;
                    case "thumb_image2":

                        $images["thumb_image2"] = $fn;
                        break;
                    case "thumb_image3":

                        $images["thumb_image3"] = $fn;
                        break;


                    default:
                        break;
                }
            }
        }



        $info = array(
            'image1' => $images['image1'],
            'image2' => $images['image2'],
            'image3' => $images['image3'],
            'thumb_image1' => $images['thumb_image1'],
            'thumb_image2' => $images['thumb_image2'],
            'thumb_image3' => $images['thumb_image3'],
            'longitude' => $longitude,
            'latitude' => $latitude,
            'link' => $link,
            'youtube_link' => $youtube_link,
            'event_cat_id' => $event_cat_id,
            "event_code" => $event_code,
            "event_title" => $event_title,
            "description" => $description,
            "address" => $address,
            "date_of_event" => $date_of_event,
            "created_by" => $user_id,
            "created_date" => date("Y-m-d H:i:s"),
        );

        $temp = $this->Basic->insert_entry('tbl_event', $info, TRUE);


        if (isset($_POST['hashtags'])) {
            $hashtags = explode(',', $_POST['hashtags']);
            foreach ($hashtags as $key) {
                $data = array(
                    "event_id" => $temp['last_id'],
                    "hashtag" => trim($key)
                );
                $this->Basic->insert_entry('tbl_event_tag', $data);
            }
        }

        $status_code = 1;
        $feed_info = $this->Basic->db_select('*', 'tbl_event', array("event_id" => $temp['last_id']), array(), '', '', array(1, 0), 'row_array');
        $this->result['info'] = $feed_info;
        $this->msg = "Event successfully uploded";
        $this->_sendResponse($status_code);
    }

    public function edit_event() {
        $info = array();
        $user_id = $this->input->post('user_id', TRUE);
        $user_token = $this->input->post('user_token', TRUE);
        $event_id = $this->input->post('event_id', TRUE);
        $user_id = $this->is_require($user_id);
        $user_token = $this->is_require($user_token);
        $event_id = $this->is_require($event_id);
        $this->Is_authorised($user_id, $user_token);
        ########################################################################
        $info['event_id'] = $event_id;

        if (isset($_POST['link'])) {
            $info['link'] = $_POST['link'];
        }
        if (isset($_POST['youtube_link'])) {
            $info['youtube_link'] = $_POST['youtube_link'];
        }
        if (isset($_POST['hashtags'])) {

            $wh = array(
                'event_id' => $event_id,
            );
            $this->Basic->delete_entry('tbl_event_tag', $wh);
            $hashtags = explode(',', $_POST['hashtags']);
            foreach ($hashtags as $key) {
                $data = array(
                    "event_id" => $temp['last_id'],
                    "hashtag" => trim($key)
                );
                $this->Basic->insert_entry('tbl_event_tag', $data);
            }
        }
        if (isset($_POST['event_cat_id'])) {
            $info['event_cat_id'] = $_POST['event_cat_id'];
        }
        if (isset($_POST['event_title'])) {
            $info['event_title'] = $_POST['event_title'];
        }
        if (isset($_POST['description'])) {
            $info['description'] = $_POST['description'];
        }
        if (isset($_POST['address'])) {
            $info['address'] = $_POST['address'];
        }
        if (isset($_POST['date_of_event'])) {
            $info['date_of_event'] = $_POST['date_of_event'];
        }

        if (!empty($_FILES)) {
            foreach ($_FILES as $k => $v) {
                $fn_id = time() . "_" . $this->get_random_string();
                $fn = $this->file_upload($v, $fn_id, 0);
                if ($fn == "") {
                    $this->_sendResponse(5); //file can not upload
                }
                switch ($k) {
                    case "image1":

                        $info["image1"] = $fn;
                        break;
                    case "image2":

                        $info["image2"] = $fn;
                        break;
                    case "image3":

                        $info["image3"] = $fn;
                        break;

                    case "thumb_image1":

                        $info["thumb_image1"] = $fn;
                        break;
                    case "thumb_image2":

                        $info["thumb_image2"] = $fn;
                        break;
                    case "thumb_image3":

                        $info["thumb_image3"] = $fn;
                        break;
                    default:
                        break;
                }
            }
        }

        $wh = array(
            "event_id" => $event_id,
        );

        $this->Basic->update_entry('tbl_event', $info, $wh);
        $event_info = $this->Basic->db_select('*', 'tbl_event', $wh, array(), '', '', array(1, 0), 'row_array');
        if (!empty($event_info)) {
            $this->result = $event_info;
            $status_code = 1;
            $this->msg = "Event successfully updated";
        } else {
            $status_code = 0;
        }
        $this->_sendResponse($status_code);
    }

    public function delete_event() {
        $user_id = $this->input->post('user_id', TRUE);
        $user_token = $this->input->post('user_token', TRUE);
        $event_id = $this->input->post('event_id', TRUE);
        $user_id = $this->is_require($user_id);
        $user_token = $this->is_require($user_token);
        $event_id = $this->is_require($event_id);
        $this->Is_authorised($user_id, $user_token);
        $wh = array(
            "event_id" => $event_id,
        );

        $info = array(
            'is_deleted' => 1
        );

        $this->Basic->update_entry('tbl_event', $info, $wh);
        $this->msg = "Event deleted successfully.";
        $this->_sendResponse(1);
    }

    public function list_my_event() {
        $user_id = $this->input->post('user_id', TRUE);
        $user_token = $this->input->post('user_token', TRUE);
        $user_id = $this->is_require($user_id);
        $user_token = $this->is_require($user_token);
        $this->Is_authorised($user_id, $user_token);
        $q = "Select t1.*,IFNULL(t2.cat_name,'') as cat_name,IFNULL(group_concat(t3.hashtag),'') as hashtags from tbl_event t1 "
                . "left join tbl_category t2 on t1.event_cat_id = t2.cat_id "
                . "left join tbl_event_tag t3 on t1.event_id = t3.event_id "
                . " where t1.is_deleted = 0 and t1.created_by != $user_id"
                . " order by event_id desc ";
        $info = $this->Basic->select_custom($q);
        if (!empty($info)) {
            foreach ($info as $k => $v) {
                $event_id = $v['event_id'];
                $q = "select IFNULL(COUNT(join_id),0) as total_join_user from tbl_join_event where event_id = $event_id";
                $count = $this->Basic->select_custom($q);
                $info[$k]['share_url'] = base_url() . "i/" . $v['event_code'];
                $info[$k]['total_join_user'] = isset($count) ? $count[0]['total_join_user'] : 0;
            }
        }
        $this->result['info'] = $info;
        $this->msg = "Get event list successfully.";
        $this->_sendResponse(1);
    }

    public function list_events() {
        $user_id = $this->input->post('user_id', TRUE);
        $user_token = $this->input->post('user_token', TRUE);
        $user_id = $this->is_require($user_id);
        $user_token = $this->is_require($user_token);
        $this->Is_authorised($user_id, $user_token);
        $latitude = isset($_POST['latitude']) ? $_POST['latitude'] : 0;
        $longitude = isset($_POST['longitude']) ? $_POST['longitude'] : 0;
        $radius = isset($_POST['radius']) ? $_POST['radius'] : 0;
        $having = "having 1=1";

        if ($radius > 0) {
            $radius = round($radius / 1.60934);
            $having.=" AND distance <= $radius ";
        }

        $q = "Select t1.*,IFNULL(t2.cat_name,'') as cat_name,IFNULL(group_concat(t3.hashtag),'') as hashtags,"
                . "(((acos(sin(($latitude*pi()/180)) * sin((t1.`latitude`*pi()/180))+cos(($latitude*pi()/180)) * cos((t1.`latitude`*pi()/180))
             * cos((($longitude - t1.`longitude`)*pi()/180))))*180/pi())*60*1.1515) AS `distance` "
                . "from tbl_event t1 "
                . "left join tbl_category t2 on t1.event_cat_id = t2.cat_id "
                . "left join tbl_event_tag t3 on t1.event_id = t3.event_id "
                . " where t1.is_deleted = 0 and t1.created_by != $user_id"
                . " $having "
                . " order by event_id desc ";
        $info = $this->Basic->select_custom($q);
        if (!empty($info)) {
            foreach ($info as $k => $v) {
                $event_id = $v['event_id'];
                $q = "select IFNULL(COUNT(join_id),0) as total_join_user from tbl_join_event where event_id = $event_id";
                $count = $this->Basic->select_custom($q);
                $info[$k]['share_url'] = base_url() . "i/" . $v['event_code'];
                $info[$k]['total_join_user'] = isset($count) ? $count[0]['total_join_user'] : 0;
            }
        }
        $this->result['info'] = $info;
        $this->msg = "Get event list successfully.";
        $this->_sendResponse(1);
    }
    public function list_joined_events() {
        $user_id = $this->input->post('user_id', TRUE);
        $user_token = $this->input->post('user_token', TRUE);
        $user_id = $this->is_require($user_id);
        $user_token = $this->is_require($user_token);
        $user_data = $this->Is_authorised($user_id, $user_token);
        $latitude = isset($_POST['latitude']) ? $_POST['latitude'] : 0;
        $longitude = isset($_POST['longitude']) ? $_POST['longitude'] : 0;
        $radius = isset($_POST['radius']) ? $_POST['radius'] : 0;
        $having = "having 1=1";
        $email = $user_data['email'];

        if ($radius > 0) {
            $radius = round($radius / 1.60934);
            $having.=" AND distance <= $radius ";
        }

        $q = "Select t1.*,IFNULL(t2.cat_name,'') as cat_name,IFNULL(group_concat(t3.hashtag),'') as hashtags,"
                . "(((acos(sin(($latitude*pi()/180)) * sin((t1.`latitude`*pi()/180))+cos(($latitude*pi()/180)) * cos((t1.`latitude`*pi()/180))
             * cos((($longitude - t1.`longitude`)*pi()/180))))*180/pi())*60*1.1515) AS `distance` "
                . "from tbl_event t1 "
                . "left join tbl_category t2 on t1.event_cat_id = t2.cat_id "
                . "left join tbl_event_tag t3 on t1.event_id = t3.event_id "
                . " join tbl_join_event t4 on t1.event_id = t4.event_id and t4.email = '$email' "
                . " where t1.is_deleted = 0 "
                . " $having "
                . " order by event_id desc ";
        $info = $this->Basic->select_custom($q);
        if (!empty($info)) {
            foreach ($info as $k => $v) {
                $event_id = $v['event_id'];
                $q = "select IFNULL(COUNT(join_id),0) as total_join_user from tbl_join_event where event_id = $event_id";
                $count = $this->Basic->select_custom($q);
                $info[$k]['share_url'] = base_url() . "i/" . $v['event_code'];
                $info[$k]['total_join_user'] = isset($count) ? $count[0]['total_join_user'] : 0;
            }
        }
        $this->result['info'] = $info;
        $this->msg = "Get event list successfully.";
        $this->_sendResponse(1);
    }

    public function visit_event($event_code) {
        if (!empty($event_code)) {
            $q1 = "Update tbl_event set `view_count` = `view_count` + 1 where event_code = '$event_code'";
            $this->Basic->query_custom($q1);
            $q = "Select * from tbl_event where is_deleted = '0' and event_code = '$event_code' order by event_id desc";
            $info = $this->Basic->select_custom($q);
            if (!empty($info)) {
                $data['info'] = $info[0];
                $this->load->view('event_view', $data);
            } else {
                echo "404";
            }
        } else {
            echo "404";
        }
    }

    public function list_join_users() {
        $user_id = $this->input->post('user_id', TRUE);
        $user_token = $this->input->post('user_token', TRUE);
        $event_id = $this->input->post('event_id', TRUE);
        $user_id = $this->is_require($user_id);
        $user_token = $this->is_require($user_token);
        $this->Is_authorised($user_id, $user_token);
        $q = "Select * from tbl_join_event where event_id = '$event_id'";
        $info = $this->Basic->select_custom($q);
        $this->result['info'] = $info;
        $this->msg = "Get user list successfully.";
        $this->_sendResponse(1);
    }

    public function join_event() {
        $event_code = $this->input->post('event_code', TRUE);
        $user_name = $this->input->post('user_name', TRUE);
        $email = $this->input->post('email', TRUE);
        $phone = $this->input->post('phone', TRUE);

        $company = $this->input->post('company', TRUE);
        $info = array(
            "event_id" => $event_code,
            "user_name" => $user_name,
            "email" => $email,
            "phone" => $phone,
            "company" => $company,
            "created_date" => date("Y-m-d H:i:s"),
        );

        $temp = $this->Basic->insert_entry('tbl_join_event', $info, TRUE);
        return true;
    }

    public function add_category() {
        ###############  post parameter #######
        $user_id = $this->input->post('user_id', TRUE);
        $user_token = $this->input->post('user_token', TRUE);
        $user_id = $this->is_require($user_id);
        $user_token = $this->is_require($user_token);
        $this->Is_authorised($user_id, $user_token);
        $cat_name = $this->input->post('cat_name', TRUE);
        $parent_id = $this->input->post('parent_id', TRUE);
        #######################################
        $q = "select * from tbl_category where cat_name='$cat_name' and parent_id ='$parent_id' limit 1";
        $c_exits = $this->Basic->select_custom($q);
        if (!empty($c_exits)) {
            $this->msg = "category already exist";
            $this->_sendResponse(2);
        }

        $inn = array(
            'cat_name' => $cat_name,
            'parent_id' => $parent_id,
        );

        $temp = $this->Basic->insert_entry("tbl_category", $inn, TRUE);
        if ($temp['stat'] == 1) {
            $this->result['cat_id'] = $temp['last_id'];
        } else {
            $this->_sendResponse(0);
        }
        $this->_sendResponse(1);
    }

    public function list_category() {


        $q = " select * from tbl_category where 1=1 and parent_id = 0 order by cat_id asc";
        $info = $this->Basic->select_custom($q);

        foreach ($info as $k => $v) {
            $cat_id = $v['cat_id'];
            $q = " select * from tbl_category where 1=1 and parent_id = $cat_id order by cat_name asc ";
            $cinfo = $this->Basic->select_custom($q);
            $info[$k]['sub_category'] = $cinfo;
            foreach ($info[$k]['sub_category'] as $m => $p) {
//                print_r($p);
                $cat_id = $p['cat_id'];
                $q = " select * from tbl_category where 1=1 and parent_id = $cat_id order by cat_name asc ";
                $tinfo = $this->Basic->select_custom($q);
                $info[$k]['sub_category'][$m]['sub_category'] = $tinfo;
            }
        }


        $this->result['info'] = $info;

        $status_code = 1;
        $this->msg = "You have successfully get the category  list.";
        $this->_sendResponse($status_code);
    }

    public function list_sub_category() {

        $parent_id = $this->input->post('parent_id', TRUE);

        $q = " select * from tbl_category where 1=1 and parent_id = $parent_id order by cat_id asc";
        $cinfo = $this->Basic->select_custom($q);

        $this->result['info'] = $cinfo;

        $status_code = 1;
        $this->msg = "You have successfully get the category  list.";
        $this->_sendResponse($status_code);
    }

    public function delete_category() {

        $user_id = $this->input->post('user_id', TRUE);
        $user_token = $this->input->post('user_token', TRUE);
        $user_id = $this->is_require($user_id);
        $user_token = $this->is_require($user_token);
        $this->Is_authorised($user_id, $user_token);
        $cat_id = $this->input->post('cat_id', TRUE);

        $this->Is_authorised($user_id, $user_token);

        $inn = array(
            "cat_id" => $cat_id,
        );
        $this->Basic->delete_entry('tbl_category', $inn);
        $this->_sendResponse(1);
    }

    public function logout() {


        $udid = $this->is_require($_POST, 'udid');
        $device_type = $this->is_require($_POST, 'device_type');
        $col = "device_token";
        if ($device_type == 2) {
            $col = "device_id";
        }
        $wh = array(
            $col => $udid,
        );

        $this->Basic->delete_entry('tbl_token', $wh);

        $this->msg = "Logout successfully";

        $status_code = 1;

        $this->_sendResponse($status_code);
    }

    ################ private functions ###########################

    private function create_mutual_chat($user_id, $other_user_id, $localtime, $item_name) {

        $cht_info = array();
        $cht_request_info = array();
        $dictionary = array();



        $user_info = $this->Basic->db_select('user_id,display_name,thirdparty_id,profile_pic', 'tbl_user', array("user_id" => $user_id), array(), '', '', array(1, 0), 'row_array');


        $chat_created_to = $other_user_id;

        $random_str = $this->get_random_string(28);
        $chat_random_id = $user_id . "_" . "_" . $random_str;
        $q = "select chat_id,is_active from tbl_chat where (chat_created_by = $user_id and chat_created_to = $chat_created_to ) or (chat_created_by = $chat_created_to and chat_created_to = $user_id) limit 1  ";
        $info_cu = $this->Basic->select_custom($q);
        //echo "<pre>";print_r($info);exit;
        if (empty($info_cu)) {
            $info = array(
                "chat_created_to" => $chat_created_to,
                "chat_random_id" => $chat_random_id,
                "chat_created_by" => $user_id,
                "localtime" => $localtime,
            );
            $temp = $this->Basic->insert_entry('tbl_chat', $info, TRUE);
            $chat_id = $temp['last_id'];
            $this->mutual_push($chat_id, $user_id, $other_user_id, $user_info, $item_name);
        } else if ($info_cu[0]['is_active'] == 0) {
            $chat_id = $info_cu[0]['chat_id'];
            $this->Basic->update_entry('tbl_chat', array("is_active" => 1), array("chat_id" => $info_cu[0]['chat_id']));
            $this->mutual_push($chat_id, $user_id, $other_user_id, $user_info, $item_name);
        } else {
            $chat_id = $info_cu[0]['chat_id'];
        }

        $cht_info = $this->Basic->db_select('*', 'tbl_chat', array("chat_id" => $chat_id), array(), '', '', array(1, 0), 'row_array');
        ####################################################

        return $cht_info;
    }

    private function mutual_push($chat_id, $user_id, $other_user_id, $user_info, $item_name = "") {
        $wh = array(
            "user_id" => $other_user_id,
        );
        $token_info = $this->Basic->db_select('*', 'tbl_token', $wh, array(), '', '', array(), 'all');

        $push_message = "The date has been accepted by " . $user_info['display_name'];
        $push_message = $user_info['display_name'] . " has sold $item_name to you.";
        $dictionary['m_type'] = "CHAT_CREATE";
        $dictionary['id'] = $chat_id;
        $dictionary['name'] = $user_info['display_name'];
        $dictionary['s_id'] = $user_id;
        $dictionary['profile_pic'] = $user_info['profile_pic'];
        $dictionary['fid'] = $user_info['thirdparty_id'];

        $arr = array(
            "alert" => $push_message,
            "user_info" => $dictionary,
            //"dt" => $other_user_info['device_token'],
            "badge" => 1,
            "sound" => "",
            "user_id" => $other_user_id,
        );

        foreach ($token_info as $tinfo) {
            $arr['dt'] = $tinfo['device_token'];
            if ($tinfo['device_type'] == 1) {
                $this->send_ios_push($arr);
            } else if ($tinfo['device_type'] == 2) {
                $this->send_android_push($arr);
            }
        }
    }

    private function is_require($v) {
        $v = trim($v);
        if (!isset($v) || $v == '') {
            $this->msg = "parameter missing or it should not null";
            $this->_sendResponse(0);
        } else {
            return $v;
        }
    }

    private function Is_authorised($user_id, $user_token, $is_admin = array()) {

        $q = "select t1.auto_id,t2.role,t2.display_name,t2.email,t2.thirdparty_id,t2.profile_pic from tbl_token t1 join tbl_user t2 on t1.user_id = t2.user_id where t1.user_id = $user_id and t1.user_token = '$user_token' limit 1";
        $info = $this->Basic->select_custom($q);
        if (empty($info)) {
            $this->_sendResponse(2);
        } else {

            if (!empty($is_admin)) {
                if (!in_array($info[0]['role'], $is_admin)) {

                    $this->msg = "Only admin can do this";
                    $this->_sendResponse(2);
                }
            }
        }

        return $info[0];
    }

    private function get_random_string($length = 10) {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $token = "";
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < $length; $i++) {
            $n = rand(0, $alphaLength);
            $token.= $alphabet[$n];
        }
        return $token;
    }

    protected function get_useragent() {
        $ua = 'web';
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $str = $_SERVER['HTTP_USER_AGENT'];
            if (stripos($str, 'iPhone') !== FALSE) {
                $ua = 'iPhone';
            } else if (stripos($str, 'android') !== FALSE) {
                $ua = 'Android';
            } else {
                $ua = 'web';
            }
        }
        return $ua;
    }

    private function send_push($arr) {
        if ($arr['dt'] == '1') {
            $this->load->library('apn');
            $this->apn->payloadMethod = 'enhance';
            $this->apn->connectToPush();
            $this->apn->setData(array('someKey' => true));
            $send_result = $this->apn->sendMessage($arr['device_token'], $arr['alert'], $arr['badge'], $arr['sound']);
            if ($send_result)
                echo "send";
            else
                echo "not send";
        }else {
            $this->load->library('android');
            $send_result = $this->android->send_notification($arr);
            if ($send_result)
                echo "send";
            else
                echo "not send";
        }
    }

    function send_email($p) {

        $this->load->library('email');

        //$p['message_body']="benzatine contact by ".$p['from_name']." with email ".$p['from']."<br /><br />";
        $config = array(
            'protocol' => 'sendmail',
            'smtp_host' => 'ssl://inmomenthosting.info',
            'smtp_port' => '25',
            'smtp_timeout' => '7',
            'smtp_user' => "info@inmomenthosting.info",
            'smtp_pass' => "ser_1234",
            'charset' => 'utf-8',
            'newline' => "\r\n",
            'wordwrap' => TRUE,
            'mailtype' => 'html',
            'validation' => TRUE,
            'priority' => 2,
        );


        $this->email->initialize($config);
        $this->email->from("info@inmomenthosting.info", "EZQ");
        $this->email->to($p['to']);
        $this->email->subject($p['subject']);
        $this->email->message($p['message_body']);
        $this->email->send();

        if ($this->is_debug == 1) {
            echo "<br>";
            echo $this->email->print_debugger();
            exit;
        }
    }

    function invite() {


        $this->load->library('email');
        $subject = $this->is_require($_POST, 'subject');
        $message_body = $this->is_require($_POST, 'message');
        $to = $this->is_require($_POST, 'to');
        //$p['message_body']="benzatine contact by ".$p['from_name']." with email ".$p['from']."<br /><br />";
        $config = array(
            'protocol' => 'sendmail',
            'smtp_host' => 'ssl://inmomenthosting.info',
            'smtp_port' => '25',
            'smtp_timeout' => '7',
            'smtp_user' => "info@inmomenthosting.info",
            'smtp_pass' => "ser_1234",
            'charset' => 'utf-8',
            'newline' => "\r\n",
            'wordwrap' => TRUE,
            'mailtype' => 'html',
            'validation' => TRUE,
            'priority' => 1,
        );
        //echo "<pre>";print_r($config);
        $this->email->initialize($config);
        $this->email->from("info@inmomenthosting.info", "In Moment");
        //$this->email->from("jckhunt4@gmail.com","Deal On GOGO Network");
        $this->email->to($to);
        $this->email->subject($subject);
        $this->email->message($message_body);
        @$this->email->send();
        if ($this->is_debug == 1) {
            echo "<br>";
            echo $this->email->print_debugger();
            exit;
        }
    }

    private function _sendResponse($status_code = 200) {
        if ($this->msg == '') {
            $this->msg = $this->_getStatusCodeMessage($status_code);
        }
        $this->result['msg'] = $this->msg;
        $this->result['status_code'] = $status_code;
        echo json_encode($this->result);
        die();
    }

    private function _getStatusCodeMessage($status) {

        $codes = Array(
            1 => 'OK',
            4 => 'Bad Request',
            2 => 'You must be authorized to view this page.',
            3 => 'The requested URL  was not found.',
            0 => 'internal server error',
            5 => 'file not upload',
            6 => 'At least one image required',
            7 => 'You need to create chat first',
            8 => 'Email Already Exists',
            9 => 'Email invalid',
            10 => 'password invalid',
            11 => 'user is blocked',
            12 => 'user is not verified',
            13 => 'secret token not valid',
            14 => 'At least one video required',
            15 => 'The business email already exists',
            16 => 'Temporary Password Invalid',
            17 => 'You have already add as favorite.',
        );
        return (isset($codes[$status])) ? $codes[$status] : '';
    }

    private function update_user_coin($uid, $coin) {

        $q = "UPDATE tbl_user set `coin`=`coin`+$coin where user_id = $uid";
        $this->Basic->query_custom($q);
    }

    private function get_timeago($ptime) {


        $ptime = strtotime($ptime);
        $estimate_time = time() - $ptime;

        if ($estimate_time < 1) {
            return 'less than 1 second ago';
        }

        $condition = array(
            12 * 30 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60 => 'month',
            24 * 60 * 60 => 'day',
            60 * 60 => 'hour',
            60 => 'minute',
            1 => 'second'
        );

        foreach ($condition as $secs => $str) {
            $d = $estimate_time / $secs;

            if ($d >= 1) {
                $r = round($d);
                return 'about ' . $r . ' ' . $str . ( $r > 1 ? 's' : '' ) . ' ago';
            }
        }
    }

    private function get_feed_comments($fid) {

        $q = " select t1.*,t2.display_name,t2.first_name,t2.last_name,t2.profile_pic from tbl_comments t1 join tbl_user t2 on t1.comment_by = t2.user_id where t1.feed_id = $fid and comment_parent_id = 0 order by comment_id asc";
        $info = $this->Basic->select_custom($q);
        foreach ($info as $k => $v) {
            $info[$k]['time_ago'] = $this->get_timeago($v['comment_date']);
            $info[$k]['child_comment'] = $this->get_feed_child_comments($fid, $v['comment_id']);
        }
        return $info;
    }

    private function get_feed_child_comments($fid, $pid) {
        $q = " select t1.*,t2.display_name,t2.first_name,t2.last_name,t2.profile_pic from tbl_comments t1 join tbl_user t2 on t1.comment_by = t2.user_id where t1.feed_id = $fid and t1.comment_parent_id = $pid and t1.feed_id = $fid ";
        $info = $this->Basic->select_custom($q);
        foreach ($info as $k => $v) {
            $info[$k]['time_ago'] = $this->get_timeago($v['comment_date']);
        }
        return $info;
    }

    private function android_inaapp_varification($public_key_base64) {
        $key = "-----BEGIN PUBLIC KEY-----\n" .
                chunk_split($public_key_base64, 64, "\n") .
                '-----END PUBLIC KEY-----';
        //using PHP to create an RSA key
        $key = openssl_get_publickey($key);
        //$signature should be in binary format, but it comes as BASE64. 
        //So, I'll convert it.
        $signature = base64_decode($signature);
        //using PHP's native support to verify the signature
        $result = openssl_verify(
                $signed_data, $signature, $key, OPENSSL_ALGO_SHA1);
        if (0 === $result) {
            return false;
        } else if (1 !== $result) {
            return false;
        } else {
            return true;
        }
    }

    private function file_upload($arr, $uid, $is_user_folder = 1, $is_thumb = 0) {

        $ret = "";
        if ($arr['error'] == 0) {

            $temp = explode('.', $arr['name']);
            $extention = end($temp);
            //$r_char = $this->get_random_string(6);
            if ($is_thumb == 1) {
                $thumb_px = '_thumb';
            } else {
                $thumb_px = '';
            }

            if ($is_user_folder == 2) {
                $up_folder = "feed";
                $file_name = $uid . $thumb_px . '.' . $extention;
            } else if ($is_user_folder == 0) {
                $up_folder = "chat";
                $file_name = $uid . $thumb_px . '.' . $extention;
            } else {
                $up_folder = "user";
                $file_name = $uid . $thumb_px . '.' . $extention;
            }

            $path = $this->dire_path . "uploads/" . $up_folder . '/';
            $file_path_url = base_url() . 'uploads/' . $up_folder . '/' . $file_name;
            $file_path = $path . $file_name;

            if (move_uploaded_file($arr["tmp_name"], $file_path) > 0) {
                $ret = $file_path_url;
            } else {
                $ret = "";
            }
        }

        return $ret;
    }

    private function get_folder($id) {
        $fol_name = ceil($id / 1000);
        $path = $this->dire_path . "uploads/" . $fol_name;
        if (!file_exists($path)) {
            mkdir($path);
        }
        return $fol_name;
    }

    private function get_folder_name($id) {
        return ceil($id / 1000);
    }

    private function isReceiptValid($purchase) {

        $data = array('receipt-data' => $purchase);
        $encodedData = json_encode($data);

        if (APNS_DEVELOPMENT == "sandbox") {
            $url = "https://sandbox.itunes.apple.com/verifyReceipt";
        } else {
            $url = "https://buy.itunes.apple.com/verifyReceipt";
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);
        $encodedResponse = curl_exec($ch);
        curl_close($ch);
        //echo "<pre>";print_r($encodedResponse);
        if ($encodedResponse == false) {
            //logError($purchase, "Payment could not be verified (no response data).");
            return false;
        }

        $response = json_decode($encodedResponse);
        //echo "<pre>";print_r($response);exit;
        if ($response->{'status'} != 0) {
            //logError($purchase, "Payment could not be verified (status != 0).");
            return false;
        }

        //$purchase->storeReceipt = $response->{'receipt'};
        return $encodedResponse;
    }

    private function send_android_push($push_arr, $is_de = 0) {
//        print_r($push_arr);
//        exit;
//        foreach ($push_arr as $pa) {
//            $uids_str.=$ua['user_id'] . ",";
//        }
//        $uids_str = rtrim($uids_str, ",");
//        exit;
        if (!empty($push_arr['dt'])) {
            $registrationIds = array($push_arr['dt']);
            $headers = array("Content-Type:" . "application/json", "Authorization:" . "key=" . ANDROID_APIKEY);
            $dic = isset($push_arr['user_info']) ? $push_arr['user_info'] : array();
            //$push_arr['alert']=  $this->set_message($push_arr['alert']);
            $dic['message'] = $push_arr['alert'];
            $dic = json_encode($dic);



            $data = array(
                'data' => array(
                    'alert' => $dic,
                    'badge' => (int) $push_arr['badge'],
                    'sound' => "",
                ),
                'registration_ids' => $registrationIds
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, "https://android.googleapis.com/gcm/send");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            @$response = curl_exec($ch);
            if ($is_de == 1 || $this->is_debug == 1) {
                //echo "json string". $push_arr['alert'];
                echo "<pre>";
                print_r($data);
                echo "<pre>";
                print_r($response);
            }
            //echo "<pre>";print_r($response);exit;
            curl_close($ch);
            $this->update_badge($push_arr['user_id']);
        }
    }

    private function send_android_push2($push_arr, $is_de = 0) {

        $device_token_str = array();
        foreach ($push_arr['android'] as $k => $pa) {
            $device_token_str[$k] = $pa['device_token'];
        }
//        $device_token_str = rtrim($device_token_str, ",");

        if (!empty($device_token_str)) {
            foreach ($device_token_str as $device_token_str) {
                $registrationIds = array($device_token_str);

                $headers = array("Content-Type:" . "application/json", "Authorization:" . "key=" . ANDROID_APIKEY);
                $dic = isset($push_arr['user_info']) ? $push_arr['user_info'] : array();
                //$push_arr['alert']=  $this->set_message($push_arr['alert']);
                $dic['message'] = $push_arr['alert'];
                $dic = json_encode($dic);



                $data = array(
                    'data' => array(
                        'alert' => $dic,
                        'badge' => (int) $push_arr['badge'],
                        'sound' => "",
                    ),
                    'registration_ids' => $registrationIds
                );

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_URL, "https://android.googleapis.com/gcm/send");
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                @$response = curl_exec($ch);
                if ($is_de == 1 || $this->is_debug == 1) {
                    //echo "json string". $push_arr['alert'];
                    echo "<pre>";
                    print_r($data);
                    echo "<pre>";
                    print_r($response);
                }
//            echo "<pre>";
//            print_r($registrationIds);
//            print_r($response);
            }
//            exit;
            curl_close($ch);
            $this->update_badge($push_arr['user_id']);
        }
    }

    function push_testing() {

        $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : 1;
        $token = isset($_POST['token']) ? $_POST['token'] : '3245772689de2e080952698d277598e116901b3880fcfe3f3e18c4c95dc1e071';
        $user_info = $this->Basic->db_select('*', 'tbl_user', array("user_id" => $user_id), array(), '', '', array(1, 0), 'row_array');
//        $token_info = $this->Basic->db_select('*', 'tbl_token', array("user_id" => $user_id), array(), '', '', array(), 'all');

        $token_info = array();
        $token_info['device_type'] = 1;
        $dic = array(
            "m_type" => "PUSH_TESTING",
        );
//        if (!empty($user_info)) {
        $arr = array(
            'alert' => "hi this is testing ",
            'badge' => (int) 1,
            'sound' => "",
            'user_id' => $user_id,
            'user_info' => $dic,
        );

        $android = array();
        $ios = array();

        foreach ($token_info as $tinfo) {
            if (!empty($tinfo['device_token'])) {

                if ($tinfo['device_type'] == 1) {
                    $ios[] = $tinfo['device_token'];
                } else if ($tinfo['device_type'] == 2) {
                    $android[] = $tinfo['device_token'];
                }
            }
        }
        $ios['device_token'] = "$token";
        $arr['android'] = $android;
        $arr['ios'] = $ios;

        $this->send_ios_push($arr);
//            $this->send_android_push($arr);

        echo "<pre>";
        print_r($token_info);
        echo "<pre>";
        print_r($user_info);
        exit;
//        } else {
//            echo "The use not avilable";
//        }
    }

    function send_ios_push($push_arr, $is_dev = 0) {

        $this->load->library('Apn');
        $this->apn->payloadMethod = 'enhance'; // you can turn on this method for debuggin purpose
        $this->apn->connectToPush();
        $dic = isset($push_arr['user_info']) ? $push_arr['user_info'] : array();
        // adding custom variables to the notification
        $this->apn->setData($dic);

        foreach ($push_arr['ios'] as $v) {

            $send_result = $this->apn->sendMessage($v, $push_arr['alert'], 1, 'sms_sound1.mp3');
        }
//        if ($send_result)
//            log_message('debug', 'Sending successful');
//        else
//            log_message('error', $this->apn->error);

        $this->apn->disconnectPush();

        echo 'API Error Message: ' . $this->apn->error . "<br>";
        echo 'send_result:' . $send_result . "<br>";
//        echo $this->config->item('PassPhrase', 'apn');
//        echo $this->config->item('PermissionFile', 'apn');
//        exit;
    }

    function write_log($msg = "") {
        $fp = fopen("gogo.log", "a+");
        fwrite($fp, date("Y-m-d h:i:s") . "  :" . $msg . "\r\n");
        fclose($fp);
    }

    private function sizeofvar($var) {
        $start_memory = memory_get_usage();
        $tmp = unserialize(serialize($var));
        return memory_get_usage() - $start_memory;
    }

    private function update_badge($id) {
        $q = "update tbl_user set `badge`=`badge`+1 where user_id = $id";
        $this->Basic->query_custom($q);
    }

    protected function get_unique_code($tbl, $col, $length = 15) {
        static $loop = 0;
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $token = "";
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < $length; $i++) {
            $n = rand(0, $alphaLength);
            $token.= $alphabet[$n];
        }
        $t = $this->Basic->db_select($col, $tbl, array($col => $token, $col . ' !=' => ''), '', '', '', array(1, 0), 'row');
        if (!empty($t) && $loop < 100) {
            $loop++;
            $token = $this->get_unique_code($tbl, $col);
        }
        return $token;
    }

    /* Location: ./application/controllers/welcome.php */
}

?>
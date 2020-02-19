<?php
use WHMCS\Database\Capsule;

function xtreamvpn_MetaData()
{
    return array(
        'DisplayName' => 'Xtream VPN',
        'APIVersion' => '2.0'
    );
}
function xtreamvpn_ConfigOptions()
{
    return array(
        "apiurl" =>array(
          "FriendlyName" => "API URL",
          "Type" => "text",
          "Size" => "40",
          "Description" => "XtreamVPN API URL"
        ),
        "apiusername" =>array(
          "FriendlyName" => "API Username",
          "Type" => "text",
          "Size" => "40",
          "Description" => "XtreamVPN API Username"
        ),
        "usage_limit" => array (
          "FriendlyName" => "Usage Limit",
          "Type" => "text",
          "Size" => "25",
          "Description" => ""
        )
    );
}

function xtreamvpn_AdminServicesTabFields($params){
    $username = $params["username"];
    $serviceid = $params["serviceid"];

    $collected = collect_usage($params);
    if(!is_array($collected)){
            $fieldsarray = array(
                '# of Logins' => '0',
                'Accumalated Hours Online' =>  '0' ,
                'Last Login' =>  '0',
                'Status' => ''
            );
    }else{
        $fieldsarray = array(
           '# of Logins' => $collected['data']['logins'],
           'Last Login' =>  $collected['data']['logintime'] ,
           'Duration' =>  $collected['data']['duration'] ,
           'Status' => $collected['data']['status']
        );
    }
    return $fieldsarray;
}

function xtreamvpn_ClientArea($params){
    $username = $params["username"];
    $serviceid = $params["serviceid"];
    $collected  = xtreamvpn_sendData($params);

    return array(
        'templatefile' => 'clientarea',
        'vars' => array(
            'logins' => isset($collected['data']['logins']) ? $collected['data']['logins'] : 0,
            'logintime' => isset($collected['data']['logintime']) ? $collected['data']['logintime'] : 0,
            'duration' =>  isset($collected['data']['duration']) ? $collected['data']['duration']: 0 ,
            'status' =>    isset($collected['data']['status']) ? $collected['data']['status']: 0,
            'username' => $params['username'],
            'password' => $params['password']
        ),
    );
}

function xtreamvpn_Renew($params)
{
  $params['method'] = 'SuspendAccount';
    $params['group'] = 'reseller';
    $billingcycle = $params['model']['billingcycle'];
    switch ($billingcycle) {
        case"One Time":
            $suscriptionlength = 1;
            break;
        case"Free Account":
            $suscriptionlength = 1;
            break;
        case"Monthly":
            $suscriptionlength = 1;
            break;
        case"Quarterly":
            $suscriptionlength = 3;
            break;
        case"SemiAnnually":
            $suscriptionlength = 6;
            break;
        case"Annually":
            $suscriptionlength = 12;
            break;
        case"Biennially":
            $suscriptionlength = 24;
            break;
        case"Triennially":
            $suscriptionlength = 36;
    }
    $params['credit'] = $params["configoption3"] == 0 ? 0 : $suscriptionlength;
    $return = xtreamvpn_sendData($params);
    if(isset($return['error'])){
        return $return['error'];
    }
    return 'success';
}
function xtreamvpn_username($email){
  global $CONFIG;
    $emaillen = strlen($email);
    $username_exists = Capsule::table("tblhosting")->where('username',$email)->count();
    $suffix = 0;
    while( $username_exists > 0 ){
        $suffix++;
        $email = substr( $email, 0, $emaillen ) . $suffix;
        $username_exists = Capsule::table("tblhosting")->where('username',$email)->count();        
    }
    return $email;
}

function xtreamvpn_CreateAccount($params){
    $username = $params['username'];
    $email = $params['clientsdetails']['email'];
    if( !$username ){
        $user = explode('@',$email);
        $username = xtreamvpn_username( $user[0] );
        $password = xtream_rand_string(8);
        Capsule::table('tblhosting')->where("id",$params['serviceid'])->update([
            "username" => $username,
            "password" => encrypt($password)
        ]);
        $params['password'] = $password;
    }
     $billingcycle = $params['model']['billingcycle'];
    switch ($billingcycle) {
        case"One Time":
            $suscriptionlength = 1;
            break;
        case"Free Account":
            $suscriptionlength = 1;
            break;
        case"Monthly":
            $suscriptionlength = 1;
            break;
        case"Quarterly":
            $suscriptionlength = 3;
            break;
        case"SemiAnnually":
            $suscriptionlength = 6;
            break;
        case"Annually":
            $suscriptionlength = 12;
            break;
        case"Biennially":
            $suscriptionlength = 24;
            break;
        case"Triennially":
            $suscriptionlength = 36;
    }
    $params['username'] = $username;
    $params['method'] = 'createAccount';
    $params['credit'] = $params["configoption3"] == 0 ? -1 : $suscriptionlength;
    $return = xtreamvpn_sendData($params);
    logActivity(json_encode([$return]));
    if($return['error']){
        return $return['error'];
    }
    return 'success';
}

function xtreamvpn_SuspendAccount($params){
    $params['method'] = 'SuspendAccount';
    $return = xtreamvpn_sendData($params);
    return $return;
}

function xtreamvpn_UnsuspendAccount($params){
    $params['method'] = 'UnsuspendAccount';
    $return = xtreamvpn_sendData($params);
    if($return['error']){
        return $return['error'];
    }
    return 'success';
}

function xtreamvpn_TerminateAccount($params){
    $params['method'] = 'deleteAccount';
    $return = xtreamvpn_sendData($params);
    logActivity(json_encode([$return]));
    if($return['error']){
        return $return['error'];
    }
    return 'success';
}

function xtreamvpn_ChangePassword($params){
    $params['method'] = 'ChangePassword';
    $return = xtreamvpn_sendData($params);
    if($return['error']){
        return $return['error'];
    }
    return 'success';
}

function xtreamvpn_ChangePackage($params){
    $params['method'] = 'ChangePackage';
    $params['group'] = 'normal';
    $return = xtreamvpn_sendData($params);
    if($return['error']){
        return $return['error'];
    }
    return 'success';
}

function xtreamvpn_update_ip_address($params){
    $params['method'] = 'updateIp';
    $return = xtreamvpn_sendData($params);
    if($return['error']){
        return $return['error'];
    }
    return 'success';
}

function xtreamvpn_AdminCustomButtonArray(){
    $buttonarray = array(
        "Update IP Address" => "update_ip_address"
    );
    return $buttonarray;
}

function date_range($nextduedate, $billingcycle) {
    $year = substr( $nextduedate, 0, 4 );
    $month = substr( $nextduedate, 5, 2 );
    $day = substr( $nextduedate, 8, 2 );

      if( $billingcycle == "Monthly" ){
        $new_time = mktime( 0, 0, 0, $month - 1, $day, $year );
      } elseif( $billingcycle == "Quarterly" ){
        $new_time = mktime( 0, 0, 0, $month - 3, $day, $year );
      } elseif( $billingcycle == "Semi-Annually" ){
        $new_time = mktime( 0, 0, 0, $month - 6, $day, $year );
      } elseif( $billingcycle == "Annually" ){
        $new_time = mktime( 0, 0, 0, $month, $day, $year - 1 );
      } elseif( $billingcycle == "Biennially" ){
        $new_time = mktime( 0, 0, 0, $month, $day, $year - 2 );
      }
      $startdate = date( "Y-m-d", $new_time );
      $enddate = "";

      if( date( "Ymd", $new_time ) >= date( "Ymd" ) ){
        if( $billingcycle == "Monthly" ){
          $new_time = mktime( 0, 0, 0, $month - 2, $day, $year );
        } elseif( $billingcycle == "Quarterly" ){
          $new_time = mktime( 0, 0, 0, $month - 6, $day, $year );
        } elseif( $billingcycle == "Semi-Annually" ){
          $new_time = mktime( 0, 0, 0, $month - 12, $day, $year );
        } elseif( $billingcycle == "Annually" ){
          $new_time = mktime( 0, 0, 0, $month, $day, $year - 2 );
        } elseif( $billingcycle == "Biennially" ){
          $new_time = mktime( 0, 0, 0, $month, $day, $year - 4 );
        }
        $startdate = date( "Y-m-d", $new_time );
        if( $billingcycle == "Monthly" ){
          $new_time = mktime( 0, 0, 0, $month - 1, $day, $year );
        } elseif( $billingcycle == "Quarterly" ){
          $new_time = mktime( 0, 0, 0, $month - 3, $day, $year );
        } elseif( $billingcycle == "Semi-Annually" ){
          $new_time = mktime( 0, 0, 0, $month - 6, $day, $year );
        } elseif( $billingcycle == "Annually" ){
          $new_time = mktime( 0, 0, 0, $month, $day, $year - 1 );
        } elseif( $billingcycle == "Biennially" ){
          $new_time = mktime( 0, 0, 0, $month, $day, $year - 2 );
        }
        $enddate = date( "Y-m-d", $new_time );
      }
      return array(
        "startdate" => $startdate,
        "enddate" => $enddate
      );
}

function collect_usage($params){
      $username = $params["username"];
      $serviceid = $params["serviceid"];

      $sqlhost = $params["serverip"];
      $sqlusername = $params["serverusername"];
      $sqlpassword = $params["serverpassword"];
      $sqldbname = $params["serveraccesshash"];

      $result = Capsule::table("tblhosting")->where("id",$serviceid)->first();
      // $date_range = date_range( $data->nextduedate, $result->billingcycle );

      // $startdate = $date_range["startdate"];
      // $enddate = $date_range["enddate"];
      $params['method'] = 'getUsage';
      $return = xtreamvpn_sendData($params);
      return $return;
}

function xtreamvpn_sendData($params)
{
    $postdata = json_encode($params);
    $ch = curl_init($params["configoption1"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result,true);
}

function secs_to_h($secs){
    $units = array(
        "week"   => 7*24*3600,
        "day"    => 24*3600,
        "hour"   => 3600,
        "minute" => 60
    );
    if ( $secs == 0 ) return "0 seconds";
    if ( $secs < 60 ) return "{$secs} seconds";
    $s = "";

    foreach ( $units as $name => $divisor ) {
    if ( $quot = intval($secs / $divisor) ) {
            $s .= $quot." ".$name;
            $s .= (abs($quot) > 1 ? "s" : "") . ", ";
            $secs -= $quot * $divisor;
        }
    }
    return substr($s, 0, -2);
}

function byte_size($bytes){
    $size = $bytes / 1024;
    if( $size < 1024 ) {
        $size = number_format( $size, 2 );
        $size .= ' KB';
    } 
    else {
        if( $size / 1024 < 1024 ) {
            $size = number_format($size / 1024, 2);
            $size .= ' MB';
        } 
        else if ( $size / 1024 / 1024 < 1024 ) {
            $size = number_format($size / 1024 / 1024, 2);
            $size .= ' GB';
        }
    }
    return $size;
}
function xtream_rand_string( $length ) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    return substr(str_shuffle($chars),0,$length);
}
<?php

$sec = _x('sec', 'second short');
$min = _x('min', 'minute short');
$hor = _x('h', 'hour short');
$day = _x('d', 'day short');

function time2str($time) {

    global $sec, $min, $hor, $day;
    $str = null;

    $time = floor($time);
    if (!$time) {
        return "0 $sec";
    }

    $d = $time/86400;
    $d = floor($d);
    if ($d){
        $str .= "$d $day, ";
        $time = $time % 86400;
    }

    $h = $time/3600;
    $h = floor($h);
    if ($h){
        $str .= "$h $hor, ";
        $time = $time % 3600;
    }

    $m = $time/60;
    $m = floor($m);
    if ($m){
        $str .= "$m $min, ";
        $time = $time % 60;
    }

    if ($time) {
        $str .= "$time $sec, ";
    }

    $str = preg_replace('/, $/','',$str);
    return $str;
}

function time2strclock($time) {

	$time = floor($time);
	if (!$time) {
		return "00:00:00";
    }
	$str["days"] = $str["hour"] = $str["min"] = $str["sec"] = "00";

	$d = $time/86400;
	$d = floor($d);
	if ($d){
		if ($d < 10)
			$d = "0" . $d;
		$str["days"] = "$d";
		$time = $time % 86400;
	}

	$h = $time/3600;
	$h = floor($h);
	if ($h){
		if ($h < 10)
			$h = "0" . $h;
		$str["hour"] = "$h";
		$time = $time % 3600;
	}

	$m = $time/60;
	$m = floor($m);
	if ($m){
		if ($m < 10)
			$m = "0" . $m;
		$str["min"] = "$m";
		$time = $time % 60;
	}

	if ($time){
		if ($time < 10)
			$time = "0" . $time;
	}
	else {
		$time = "00";
    }

	$str["sec"] = "$time";
	if ($str["days"] != "00") {
		$ret = "$str[days]:$str[hour]:$str[min]:$str[sec]";
    }
	else {
		$ret = "$str[hour]:$str[min]:$str[sec]";
    }

	return $ret;
}

function date2timediv($date,$now) {
	list($day,$time)=explode(' ',$date);
	$day = explode('-',$day);
	$time = explode(':',$time);
	$timest = mktime($time[0],$time[1],$time[2],$day[1],$day[2],$day[0]);
	if (!$now)
		$now = time();
	return ($now - $timest);
}

function bytes2str($bytes) {
    $bytes=floor($bytes);
    if ($bytes > 536870912)
        $str = sprintf("%5.2f GBs", $bytes/1073741824);
    else if ($bytes > 524288)
        $str = sprintf("%5.2f MBs", $bytes/1048576);
    else
        $str = sprintf("%5.2f KBs", $bytes/1024);

    return $str;
}

function IsValidMAC($mac) {
	
	if (preg_match('/([a-fA-F0-9]{2}[-:]){5}[0-9A-Fa-f]{2}|([0-9A-Fa-f]{4}\.){2}[0-9A-Fa-f]{4}/', $mac) == 1) {
	return TRUE;
	}
	else {
		return FALSE;
	}
}

function chars($value, $double_encode = TRUE) {
    
    return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8', $double_encode);
}

/**
 * Show Error Messages on profile.php or operators.php
 *
 * @param int $operid Operator id
 * @param string $action - name=" save, addnew, delete ... "
 * @param array $msg multidimensional array with messages data
 * @param string $msg['msg_alias'] for empty Alias
 * @param string $msg['msg_password'] for not equal passwords
 */
function show_error_message($id_name, $id_value, $name_value, $msg, $action) {
    
    if (isset($action)) {
        
        $form = "<form name=\"myform\" method=\"post\" action=\"".chars($action)."\">";
    }
    else {

        $form = "<form name=\"myform\" method=\"post\">";
    }

    if (isset($id_name, $id_value)) {
        $form .= "<input type=\"hidden\" name=\"".chars($id_name)."\" value=\"".chars($id_value)."\">";
    }
    if (isset($name_value)) {
        $form .= "<input type=\"hidden\" name=\"".chars($name_value)."\" value=\"".chars($name_value)."\">";
    }
    
    foreach ($msg as $key => $value) {
        
        $form .= "<input type=\"hidden\" name=\"".chars($key)."\" value=\"".chars($value)."\">";
    }
    
    $form .= "<script language=\"JavaScript\">document.myform.submit(); </script> </form>";

    echo $form;
}

?>

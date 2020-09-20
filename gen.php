<?php
require_once "config.php";
$places = array();
$addresses = array();
$combinations = array();
function myUrlEncode($string) {
    $entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
    $replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
    return str_replace($entities, $replacements, urlencode($string));
}
function getAddr($id){
	global $conn;
	if($query = $conn->prepare("SELECT `place` FROM `places` WHERE `id` = ?")){
		$query->bind_param("i", $id);
		$query->execute();
		if($query->error)
			die("Error getting address " . $query->error);
		$query->bind_result($addr);
		$query->fetch();
		$query->close();
		return myUrlEncode($addr);
	}
	else
		die("Error in query getting address " . $conn->error);
}
foreach($_POST as $k => $v){
	$a = getAddr($k);
	$addresses[$k] = $a;
	$places[] = $k;
	$combinations[] = $k;
}
$combinations[] = $home;
$addresses[$home] = getAddr($home);
$times = array();
require_once 'Combinatorics.php';
$combinatorics = new Math_Combinatorics;
foreach($combinatorics->combinations($combinations, 2) as $c){
	sort($c);
	if($query = $conn->prepare("SELECT `time` FROM `times` WHERE `place1` = ? AND `place2` = ? AND timestamp > NOW() - INTERVAL ? DAY")){
		$query->bind_param("sss", $c[0], $c[1], $maxAge);
		$query->execute();
		if($query->error)
			die("Error getting times " . $query->error);
		$query->bind_result($time);
		$query->fetch();
		$times[$c[0] ." ". $c[1]] = [$c[0], $c[1], $time];
		$query->close();
	}
	else
		die("Error in query getting times " . $conn->error);
}
foreach($times as $k => $v)
	if($v[2] === null){
		$p1 = $addresses[$v[0]];
		$p2 = $addresses[$v[1]];
		$json = file_get_contents("https://maps.googleapis.com/maps/api/distancematrix/json?origins=$p1&destinations=$p2&key=$key");
		$times[$k][2] = json_decode($json, true)["rows"][0]["elements"][0]["duration"]["value"];
		if($query = $conn->prepare("INSERT INTO `times` (`place1`, `place2`, `time`) VALUES (?, ?, ?)")){
			$query->bind_param("iii", $v[0], $v[1], $times[$k][2]);
			$query->execute();
			if($query->error)
				die("Error setting time " . $query->error);
			$query->close();
		}
		else
			die("Error in query setting time " . $conn->error);
	}
function calc($perm){
	global $shortestTime, $shortestPath, $times, $home;
	$pair = [$home, $perm[0]];
	sort($pair);
	$r = $times[$pair[0]." " .$pair[1]][2];
	$pair = [$home, end($perm)];
	sort($pair);
	$r += $times[$pair[0]. " " .$pair[1]][2];
	for($i = 0; $i<count($perm); $i++){
		$pair = [$perm[$i], $perm[$i+1]];
		sort($pair);
		$r += $times[$pair[0]." ".$pair[1]][2];
		if($shortestTime > 0 && $r >= $shortestTime)
			return;
	}
	$shortestTime = $r;
	$shortestPath = $perm;
}
$shortestTime = -1;
$shortestPath = null;
foreach($combinatorics->permutations($places, count($places)) as $p)
	calc($p);
$url = "https://www.google.com/maps/dir/?api=1" . "&destination=" . $addresses[$home] . "&waypoints=";
foreach($shortestPath as $k => $v)
	$url = $url . $addresses[$v] ."|";
$url = substr($url, 0, -1);
echo "<script>window.location.replace('$url')</script>";

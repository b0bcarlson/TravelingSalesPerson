<?php
$database = "tsp";
$sqlusername = "tsp";
$sqlpassword = "";

//ID of row in `places` that represents the ending or home location
$home = 8;
//Max number of days that a time is valid for. After this amount of days the route will be recalculated
//Generally this number can be higher to reduce the number of calls to the Maps API but may need to be lower if there is changing of routes due to construction
$maxAge = 30;
//Google Maps Distance Matrix API key
$key = "";

$conn = new mysqli("127.0.0.1", $sqlusername, $sqlpassword, $database);
if($conn->connect_error){
        die("Connection failed: " . $conn->connect_error);
}


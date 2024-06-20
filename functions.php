<?php
// functions.php

// Function to get client IP address
function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

// Function to get user agent
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'];
}

// Function to generate a unique device identifier
function generateDeviceID() {
    $ip = getClientIP();
    $ua = getUserAgent();
    $deviceID = md5($ip . $ua); // Hash IP and user agent to generate a device ID
    return $deviceID;
}

// Function to check if device identifier exists in session
function isDeviceIDExists() {
    return isset($_SESSION['device_id']);
}

// Function to retrieve stored device identifier from session
function getStoredDeviceID() {
    return $_SESSION['device_id'];
}

// Function to store device identifier in session
function storeDeviceID($deviceID) {
    $_SESSION['device_id'] = $deviceID;
}
?>

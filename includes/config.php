<?php
session_start();
$conn = new mysqli("localhost","u716023235_pms","02X/nWXPJjJ+","u716023235_pms");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
?>
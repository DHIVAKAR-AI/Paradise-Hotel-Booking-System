<?php
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect("127.0.0.1", "root", "", "sshotels", 3307);
if (!$conn) {
    $conn = @mysqli_connect("127.0.0.1", "root", "", "sshotels", 3306);
}
if (!$conn) {
    $conn = @mysqli_connect("localhost", "root", "", "sshotels");
}
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
<?php

require('include/ost-config.php');

// $servername = "dissertum.com";
// $username = "dissertu_tq";
// $password = "p5S*nR_T>.Lwr\Wa";
// $dbname = "dissertu_tiquetes";

// Create connection
$conn = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$sql = "UPDATE ost_thread_entry SET quality_item_id=" . $_GET['qid'] . " WHERE id=" . $_GET['rid'] . ' AND thread_id=' . $_GET['tid']  . " AND quality_item_id IS NULL";

if ($conn->query($sql) === TRUE) {
    echo "Gracias por responder";
} else {
    echo "Error al responder: " . $conn->error;
}

$conn->close();
?>
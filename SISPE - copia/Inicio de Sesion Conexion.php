<?php
$connection = new mysqli("localhost", "root", "", "divina_comida");
if($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}
?>
<?php
$server = 'localhost'; 
$user = 'root';
$password = '';
$database = 'rwdd2308';

$connection = mysqli_connect($server, $user, $password, $database);

if($connection == false){
    die('Connection Failed!' . mysqli_connect_error());
}

?>
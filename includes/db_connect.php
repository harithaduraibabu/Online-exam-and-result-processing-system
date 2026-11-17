<?php
// db_connect.php - Database connection configuration

// Configuration variables (Update these when moving to live host)
$host = 'localhost';
$username = 'root';
$password = ''; // Default XAMPP password is empty
$dbname = 'exam-portal'; // Updated to match user's DB name
$port = 3307; // Updated port number

// Establish connection
$conn = new mysqli($host, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql_create_db = "CREATE DATABASE IF NOT EXISTS `$dbname`";
if ($conn->query($sql_create_db) === TRUE) {
    $conn->select_db($dbname);
} else {
    die("Error creating database: " . $conn->error);
}

// Set charset to utf8mb4 for emoji and special char support
$conn->set_charset("utf8mb4");

// Define session and global variables
session_start();
date_default_timezone_set('Asia/Kolkata');

// Base URL for migration ease
$base_url = "http://localhost/exam-portal/";
?>
<?php
// ===============================
// Database Configuration (PDO)
// ===============================
$host = 'localhost';           // Server name
$dbname = 'dbs';    // Database name (set according to your DB name)
$username = 'root';            // XAMPP default
$password = '';                // XAMPP default is empty

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    // Set PDO error mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optional: enable default associative fetch mode
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Display clear connection error
    die("<strong>Database connection failed:</strong> " . $e->getMessage());
}
?>

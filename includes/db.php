<?php
/**
 * CampusConnect - Database Connection Handler
 * Uses PHP Data Objects (PDO) for secure, prepared SQL execution.
 * Standard configuration for local XAMPP environment.
 */

$host = 'localhost';
$db_name = 'campusconnect';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db_name};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // If database does not exist yet or MySQL is stopped, display friendly instruction
    die("
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 10px; background-color: #f8fafc; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
        <h2 style='color: #e11d48; margin-top: 0;'>Database Connection Error</h2>
        <p style='color: #475569; line-height: 1.6;'>Could not connect to the MySQL database <strong>'{$db_name}'</strong>.</p>
        <p style='color: #475569; line-height: 1.6;'><strong>Common fix in XAMPP:</strong></p>
        <ol style='color: #334155; line-height: 1.8;'>
            <li>Open your <strong>XAMPP Control Panel</strong> and click <strong>Start</strong> next to Apache and MySQL.</li>
            <li>Open <a href='http://localhost/phpmyadmin' target='_blank' style='color: #2563eb;'>http://localhost/phpmyadmin</a> in your browser.</li>
            <li>Create a new database named <code>campusconnect</code>.</li>
            <li>Import the file <code>database/campusconnect.sql</code> located in this project.</li>
        </ol>
        <p style='font-size: 12px; color: #94a3b8;'>Error Details: " . htmlspecialchars($e->getMessage()) . "</p>
    </div>
    ");
}
?>

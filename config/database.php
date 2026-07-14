<?php

$host     = 'localhost';
$db_name  = 'nikah_randevu'; 
$username = 'root';                
$password = '';                    

try {
   
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Bağlantı başarılı ise (Lokalde test ederken açabilirsin, sonra sil)
    // echo "Bağlantı başarılı!"; 
} catch (PDOException $e) {
    
    die("Veri tabanı bağlantı hatası: " . $e->getMessage());
}
?>
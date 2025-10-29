<?php
try {
    $db = new PDO("mysql:host=localhost;dbname=uyeler;charset=utf8", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    echo "Veritabanı bağlantı hatası: " . $e->getMessage();
    exit;
}

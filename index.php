<?php
session_start();
require_once "db.php";

// GİRİŞ YAPMAMIŞ KULLANICIYI GİRİŞ SAYFASINA YÖNLENDİRİR.
if(!isset($_SESSION['user'])){ 
    header("Location: giris.php"); 
    exit; 
}

$currentUser = $_SESSION['user']; // KULLANICI ADI DEĞİŞKENE ATANIR.

// ÇIKIŞ İŞLEMİ YAPAN KULLANICI GİRİŞ SAYFASINA YÖNLENDİRİLİR.
if(isset($_GET['cikis'])){ 
    session_destroy(); 
    header("Location: giris.php"); 
    exit; 
}

// SAYFA HER GÜNCELLENDİĞİNDE KULLANICI VERİLERİNİ SORGULAR.
$stmt = $db->prepare("SELECT * FROM uye WHERE uye_isim=?");
$stmt->execute([$currentUser]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$userRole = $user['rol'] ?? 'user'; // ROL TANIMLANMAMIŞ İSE VARSAYILAN OLARAK 'user' ATANIR.
$sayfa = $_GET['sayfa'] ?? 'anasayfa';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sosyal Medya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background:#f8f9fa; padding-top: 75px; }
        .nav-profile-img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; }
        .main-container { max-width: 600px; margin: auto; }
        .badge-kurucu { background-color: #dc3545; } /* Kurucu için kırmızı rozet */
        .badge-yetkili { background-color: #0d6efd; } /* Yetkili için mavi rozet */
    </style>
</head>
<body>

<nav class="navbar navbar-expand navbar-dark bg-primary shadow-sm fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">SOSYAL MEDYA</a>
        
        <div class="ms-auto">
            <div class="dropdown">
                <div data-bs-toggle="dropdown" style="cursor:pointer;">
                    <img src="uploads/<?php echo $user['profil_foto'] ?: 'default.png'; ?>?t=<?php echo time(); ?>" class="nav-profile-img shadow-sm">
                </div>
                
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li class="px-3 py-2 border-bottom mb-1">
                        <small class="text-muted fw-bold">@<?php echo htmlspecialchars($currentUser); ?></small>
                        <br>
                        <?php if($userRole === 'kurucu'): ?>
                            <span class="badge badge-kurucu" style="font-size: 10px;">KURUCU</span>
                        <?php elseif($userRole === 'yetkili'): ?>
                            <span class="badge badge-yetkili" style="font-size: 10px;">YETKİLİ</span>
                        <?php endif; ?>
                    </li>
                    
                    <li><a class="dropdown-item" href="index.php?sayfa=profil"><i class="bi bi-person me-2"></i>Profil</a></li>
                    
                    <?php if($userRole === 'kurucu' || $userRole === 'yetkili'): ?>
                        <li>
                            <a class="dropdown-item text-primary fw-bold" href="index.php?sayfa=admin">
                                <i class="bi bi-shield-lock me-2"></i>Yönetici Paneli
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="index.php?cikis=1"><i class="bi bi-box-arrow-right me-2"></i>Çıkış</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container main-container">
    <?php 
    if ($sayfa == 'profil') {
        include "profil_icerik.php";
    } elseif ($sayfa == 'admin') {
        // YETKİ KONTROL EDİLİR.
        if ($userRole === 'kurucu' || $userRole === 'yetkili') {
            if (file_exists("admin_paneli.php")) {
                include "admin_paneli.php"; // YETKİLİ PANELİNİ YÜKLER.
            } else {
                echo "<div class='alert alert-warning'>Yönetici paneli dosyası bulunamadı.</div>"; // PANEL YOK İSE HATA VERİR.
            }
        } else {
            echo "<div class='alert alert-danger'>Bu sayfaya erişim yetkiniz bulunmamaktadır.</div>"; // YETKİSİ OLMAYANLAR İÇİN UYARI.
        }
    } else {
        include "anasayfa_icerik.php";
    }
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
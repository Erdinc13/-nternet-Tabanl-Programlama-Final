<?php
session_start();
require_once "db.php";

$hata = "";

// Giriş işlemi
if(isset($_POST['giris'])){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    if(!empty($username) && !empty($password)){
        $stmt = $db->prepare("SELECT * FROM uye WHERE uye_isim=? AND uye_sifre=?");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if($user){
            $_SESSION['user'] = $user['uye_isim'];
            header("Location: index.php");
            exit;
        } else {
            $hata = "Kullanıcı adı veya şifre yanlış!";
        }
    } else {
        $hata = "Lütfen tüm alanları doldurun!";
    }
}

// Kayıt işlemi
if(isset($_POST['kayit'])){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $password2 = trim($_POST['password2']);
    if(!empty($username) && !empty($password) && !empty($password2)){
        if($password !== $password2){
            $hata = "Şifreler eşleşmiyor!";
        } else {
            // Kullanıcı adı zaten var mı?
            $stmt = $db->prepare("SELECT * FROM uye WHERE uye_isim=?");
            $stmt->execute([$username]);
            if($stmt->fetch()){
                $hata = "Bu kullanıcı adı zaten alınmış!";
            } else {
                $stmt = $db->prepare("INSERT INTO uye (uye_isim, uye_sifre) VALUES (?, ?)");
                $stmt->execute([$username, $password]);
                $_SESSION['user'] = $username;
                header("Location: index.php");
                exit;
            }
        }
    } else {
        $hata = "Lütfen tüm alanları doldurun!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Giriş / Kayıt</title>
<style>
body { font-family: Arial; background:#f2f2f2; margin:0; }
.container { width:400px; margin:80px auto; background:white; padding:20px; border-radius:5px; box-shadow:0 0 5px rgba(0,0,0,0.2); text-align:center; }
input[type="text"], input[type="password"] { width:90%; padding:10px; margin:5px 0; border-radius:5px; border:1px solid #ccc; }
button { width:95%; padding:10px; margin-top:10px; border:none; border-radius:5px; background:#007bff; color:white; cursor:pointer; }
.secin-btn { display:inline-block; width:45%; padding:5px; margin:5px; border:1px solid black; background:black; color:white; cursor:pointer; border-radius:3px; }
.hata { color:red; font-size:12px; margin-top:5px; text-align:left; }
</style>
</head>
<body>

<div class="container">
    <h2>Sosyal Medya</h2>

    <!-- Seçim Butonları -->
    <form method="post" id="secimForm">
        <button type="button" class="secin-btn" onclick="showGiris()">Giriş Yap</button>
        <button type="button" class="secin-btn" onclick="showKayit()">Kayıt Ol</button>
    </form>

    <!-- Giriş Formu -->
    <form method="post" id="girisForm" style="display:block;">
        <input type="text" name="username" placeholder="Kullanıcı Adı" required><br>
        <input type="password" name="password" placeholder="Şifre" required><br>
        <button type="submit" name="giris">Giriş Yap</button>
        <div class="hata"><?php echo $hata; ?></div>
    </form>

    <!-- Kayıt Formu -->
    <form method="post" id="kayitForm" style="display:none;">
        <input type="text" name="username" placeholder="Kullanıcı Adı" required><br>
        <input type="password" name="password" placeholder="Şifre" required><br>
        <input type="password" name="password2" placeholder="Şifre Tekrar" required><br>
        <button type="submit" name="kayit">Kayıt Ol</button>
        <div class="hata"><?php echo $hata; ?></div>
    </form>
</div>

<script>
function showGiris(){
    document.getElementById('girisForm').style.display = 'block';
    document.getElementById('kayitForm').style.display = 'none';
}
function showKayit(){
    document.getElementById('girisForm').style.display = 'none';
    document.getElementById('kayitForm').style.display = 'block';
}
</script>

</body>
</html>

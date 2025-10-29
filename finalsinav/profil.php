<?php
session_start();
require_once "db.php";

if(!isset($_SESSION['user'])){
    header("Location: giris.php");
    exit;
}

$currentUser = $_SESSION['user'];

// Kullanıcı bilgilerini çek
$stmt = $db->prepare("SELECT * FROM uye WHERE uye_isim=?");
$stmt->execute([$currentUser]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$profil_foto = $user['profil_foto'] ?: 'default.png';
$success = $error = "";

// Fotoğraf yükleme/güncelleme
if(isset($_POST['foto_yukle']) && isset($_FILES['profil_foto'])){
    $file = $_FILES['profil_foto'];

    if($file['error'] === 0){
        $uploadDir = 'uploads/';
        if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = time().'_'.uniqid().'.'.$ext;

        if(move_uploaded_file($file['tmp_name'], $uploadDir.$filename)){
            $stmt = $db->prepare("UPDATE uye SET profil_foto=? WHERE uye_isim=?");
            $stmt->execute([$filename, $currentUser]);
            $profil_foto = $filename;
            $success = "Profil fotoğrafı başarıyla yüklendi!";
        } else {
            $error = "Dosya yüklenemedi! uploads klasörünü kontrol edin ve yazılabilir olduğundan emin olun.";
        }
    } else {
        $error = "Dosya yüklenirken hata oluştu. Hata kodu: ".$file['error'];
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Profil</title>
<style>
body { font-family: Arial; background:#f2f2f2; margin:0; }
.header {
    background:#007bff;
    color:white;
    padding:10px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    position: fixed;
    width: 100%;
    top: 0;
    box-sizing: border-box;
    z-index: 100;
}
.header a { color:white; text-decoration:none; font-weight:bold; }
.hamburger {
    cursor:pointer;
    display:flex;
    flex-direction:column;
    gap:4px;
}
.hamburger div {
    width:25px; height:3px; background:white;
}
.menu {
    display:none;
    position:absolute;
    right:20px;
    top:50px;
    background:white;
    border:1px solid #ccc;
    border-radius:5px;
    box-shadow:0 2px 5px rgba(0,0,0,0.2);
}
.menu a {
    display:block; padding:10px 20px; color:black; text-decoration:none;
}
.menu a:hover { background:#f2f2f2; }

.container {
    width:400px;
    margin:120px auto 0;
    text-align:center;
    background:white;
    padding:20px;
    border-radius:10px;
    box-shadow:0 0 5px rgba(0,0,0,0.2);
}
img { width:120px; height:120px; border-radius:50%; margin-bottom:10px; cursor:pointer; }
button { padding:10px 15px; border:none; border-radius:5px; background:#007bff; color:white; cursor:pointer; margin-top:10px;}
.error { color:red; margin-bottom:10px; }
.success { color:green; margin-bottom:10px; }
input[type=file] { display:none; }
</style>
</head>
<body>

<div class="header">
    <a href="index.php">Sosyal Medya</a>
    <div class="hamburger" onclick="document.getElementById('menu').classList.toggle('show');">
        <div></div><div></div><div></div>
    </div>
    <div id="menu" class="menu">
        <a href="profil.php">Profil</a>
        <a href="#">Ayarlar</a>
        <a href="index.php?cikis=1">Çıkış</a>
    </div>
</div>

<div class="container">
    <h2>Profil</h2>

    <?php if($error) echo "<div class='error'>$error</div>"; ?>
    <?php if($success) echo "<div class='success'>$success</div>"; ?>

    <img src="uploads/<?php echo $profil_foto; ?>" alt="Profil Fotoğrafı" onclick="document.getElementById('profilFile').click();">

    <form method="post" enctype="multipart/form-data">
        <input type="file" name="profil_foto" id="profilFile" accept="image/*" onchange="this.form.submit();">
        <button type="button" onclick="document.getElementById('profilFile').click();">
            <?php echo ($profil_foto === 'default.png') ? 'Profil Fotoğrafı Ekle' : 'Profil Fotoğrafını Güncelle'; ?>
        </button>
        <input type="submit" name="foto_yukle" style="display:none;">
    </form>
</div>

<script>
window.onclick = function(event){
    if(!event.target.matches('.hamburger') && !event.target.closest('.menu')){
        document.getElementById('menu').classList.remove('show');
    }
};
</script>

</body>
</html>

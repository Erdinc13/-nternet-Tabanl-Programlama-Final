<?php
session_start();
require_once "db.php";

if(!isset($_SESSION['user'])){
    header("Location: giris.php");
    exit;
}

$currentUser = $_SESSION['user'];

// Çıkış
if(isset($_GET['cikis'])){
    session_destroy();
    header("Location: giris.php");
    exit;
}

// Gönderi paylaşma
if(isset($_POST['paylas'])){
    $postText = trim($_POST['post']);
    if(!empty($postText)){
        $stmt = $db->prepare("INSERT INTO posts (user, post, silindi, tarih) VALUES (?, ?, 'aktif', NOW())");
        $stmt->execute([$currentUser, $postText]);
    }
}

// Gönderi düzenleme
if(isset($_POST['guncelle'])){
    $yeni_post = trim($_POST['yeni_post']);
    $post_no = $_POST['post_no'];
    if(!empty($yeni_post)){
        $stmt = $db->prepare("UPDATE posts SET post=?, tarih=NOW() WHERE post_no=? AND user=?");
        $stmt->execute([$yeni_post, $post_no, $currentUser]);
    }
}

// Gönderi silme
if(isset($_GET['sil'])){
    $post_no = $_GET['sil'];
    $stmt = $db->prepare("UPDATE posts SET silindi='silinmiş' WHERE post_no=? AND user=?");
    $stmt->execute([$post_no, $currentUser]);
}

// Gönderileri çek (aktif olanlar) ve kullanıcı profil fotoğrafı
$posts = $db->query("
    SELECT posts.*, uye.profil_foto FROM posts
    JOIN uye ON posts.user = uye.uye_isim
    WHERE silindi='aktif'
    ORDER BY tarih DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Ana Sayfa</title>
<style>
body { font-family: Arial; margin:0; background:#f2f2f2; }
.header { background:#007bff; color:white; padding:10px 20px; display:flex; justify-content:space-between; align-items:center; position:relative; }
.header a { color:white; text-decoration:none; }
.container { width:600px; margin:20px auto; }
textarea { width:100%; padding:10px; margin-bottom:10px; border-radius:5px; border:1px solid #ccc; }
button { padding:8px 12px; border:none; border-radius:5px; cursor:pointer; }
button.paylas { background:#007bff; color:white; width:100%; }
.iptal-btn { background: #ccc; color: #333; border:none; padding:5px 10px; margin-left:5px; border-radius:3px; cursor:pointer; }
.post { background:white; padding:10px; margin-bottom:10px; border-radius:5px; box-shadow:0 0 3px rgba(0,0,0,0.1); position:relative; }
.duzenle-form textarea { width:70%; }
.duzenle-form button { margin-top:5px; }
.duzenle-btn, .sil-btn { position:absolute; right:10px; top:10px; cursor:pointer; background:none; border:none; color:#007bff; }
.sil-btn { color:red; text-decoration:underline; display:none; }
#menu { display:none; position:absolute; right:0; background:white; color:black; box-shadow:0 0 5px rgba(0,0,0,0.3); border-radius:5px; }
#menu a { display:block; padding:10px; text-decoration:none; color:black; }
#hamburger { cursor:pointer; font-size:24px; color:white; }
.post-header { display:flex; align-items:center; }
.post-header img { width:40px; height:40px; border-radius:50%; margin-right:10px; }
</style>
</head>
<body>

<div class="header">
    <div><a href="index.php">Sosyal Medya</a></div>
    <div style="position:relative;">
        <div id="hamburger">&#9776;</div>
        <div id="menu">
            <a href="profil.php">Profil</a>
            <a href="#">Ayarlar</a>
            <a href="index.php?cikis=1">Çıkış Yap</a>
        </div>
    </div>
</div>

<div class="container">
    <form method="post">
        <textarea name="post" placeholder="Ne paylaşmak istersin?" required></textarea>
        <button type="submit" class="paylas" name="paylas">Paylaş</button>
    </form>

    <?php foreach($posts as $post): ?>
        <div class="post" id="post-<?php echo $post['post_no']; ?>">
            <div class="post-header">
                <img src="uploads/<?php echo $post['profil_foto']; ?>" alt="Profil Fotoğrafı">
                <strong><?php echo htmlspecialchars($post['user']); ?></strong>
                <small style="margin-left:10px;"><?php echo $post['tarih']; ?></small>
            </div>
            <p id="postText-<?php echo $post['post_no']; ?>"><?php echo htmlspecialchars($post['post']); ?></p>

            <?php if($post['user']==$currentUser): ?>
                <button class="duzenle-btn" id="duzenleBtn-<?php echo $post['post_no']; ?>" onclick="duzenlePost(<?php echo $post['post_no']; ?>)">Düzenle</button>
                <a class="sil-btn" id="silBtn-<?php echo $post['post_no']; ?>" href="?sil=<?php echo $post['post_no']; ?>">Sil</a>

                <form method="post" class="duzenle-form" id="duzenleForm-<?php echo $post['post_no']; ?>" style="display:none;">
                    <textarea name="yeni_post" id="textarea-<?php echo $post['post_no']; ?>"><?php echo htmlspecialchars($post['post']); ?></textarea>
                    <input type="hidden" name="post_no" value="<?php echo $post['post_no']; ?>">
                    <button type="submit" name="guncelle" id="guncelleBtn-<?php echo $post['post_no']; ?>" disabled>Güncelle</button>
                    <button type="button" class="iptal-btn" onclick="iptalDuzenle(<?php echo $post['post_no']; ?>)">İptal</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
const hamburger = document.getElementById('hamburger');
const menu = document.getElementById('menu');

hamburger.addEventListener('click', () => {
    menu.style.display = (menu.style.display==='block') ? 'none' : 'block';
});

document.addEventListener('click', (e) => {
    if(!hamburger.contains(e.target) && !menu.contains(e.target)){
        menu.style.display = 'none';
    }
});

function duzenlePost(postNo){
    const form = document.getElementById('duzenleForm-' + postNo);
    const postText = document.getElementById('postText-' + postNo);
    const textarea = document.getElementById('textarea-' + postNo);
    const guncelleBtn = document.getElementById('guncelleBtn-' + postNo);
    const duzenleBtn = document.getElementById('duzenleBtn-' + postNo);
    const silBtn = document.getElementById('silBtn-' + postNo);

    form.style.display = 'block';
    postText.style.display = 'none';
    const originalText = postText.textContent;
    textarea.value = originalText;
    guncelleBtn.disabled = true;

    duzenleBtn.style.display = 'none';
    silBtn.style.display = 'inline';

    textarea.oninput = function(){
        guncelleBtn.disabled = (textarea.value.trim() === originalText.trim());
    };
}

function iptalDuzenle(postNo){
    const form = document.getElementById('duzenleForm-' + postNo);
    const postText = document.getElementById('postText-' + postNo);
    const textarea = document.getElementById('textarea-' + postNo);
    const guncelleBtn = document.getElementById('guncelleBtn-' + postNo);
    const duzenleBtn = document.getElementById('duzenleBtn-' + postNo);
    const silBtn = document.getElementById('silBtn-' + postNo);

    form.style.display = 'none';
    postText.style.display = 'block';
    textarea.value = postText.textContent;
    guncelleBtn.disabled = true;

    silBtn.style.display = 'none';
    duzenleBtn.style.display = 'inline';
}
</script>

</body>
</html>

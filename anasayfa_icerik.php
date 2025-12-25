<?php
// KULLANICININ YETKİ SEVİYESİNİ ve PROFİL FOTOĞRAFINI VERİTABANINDAN ÇEKER.
$currentUser = $_SESSION['user'];
$checkRole = $db->prepare("SELECT rol, profil_foto FROM uye WHERE uye_isim = ?");
$checkRole->execute([$currentUser]);
$uInfo = $checkRole->fetch();
$userRole = $uInfo['rol'] ?? 'user';// ROL TANIMLI DEĞİL İSE 'user' VARSAYILIR.
$userPP = $uInfo['profil_foto'] ?? 'default.png';// FOTOĞRAF YOKSA VARSAYILAN ATANIR.

// POST SİLME
if(isset($_GET['sil_no'])){
    $post_no = $_GET['sil_no'];
	// YETKİLİLER TÜM GÖNDERİLERİ, USERLER İSE SADECE KENDİ GÖNDERİLERİNİ SİLEBİLİR.
    if($userRole === 'kurucu' || $userRole === 'yetkili'){
        $db->prepare("UPDATE posts SET silindi='pasif' WHERE post_no=?")->execute([$post_no]);
    } else {
        $db->prepare("UPDATE posts SET silindi='pasif' WHERE post_no=? AND user=?")->execute([$post_no, $currentUser]);
    }
    
    // SİLME İŞLEMİ SONRASINDA DÖNÜLECEK SAYFAYI BELİRLER.
    if(isset($_GET['sayfa']) && $_GET['sayfa'] == 'profil'){
        $u_param = isset($_GET['u']) ? "&u=".urlencode($_GET['u']) : "";
        header("Location: index.php?sayfa=profil" . $u_param);
    } else {
        header("Location: index.php");
    }
    exit;
}

// GÖNDERİ DÜZENLEME
if(isset($_POST['post_guncelle'])){
    $yeni_metin = $_POST['duzenlenen_metin']; 
    $p_id = $_POST['post_id'];
    $redirect = (isset($_GET['sayfa']) && $_GET['sayfa'] == 'profil') ? "index.php?sayfa=profil&u=".urlencode($_GET['u']) : "index.php";
	
	// YETKİLİLER TÜM GÖNDERİLERİ, KULLANICILAR SADECE KENDİ GÖNDERİLERİNİ DÜZENLEYEBİLİR.
    // DÜZENLEME SONRASI 'tarih=NOW()' İLE GÖNDERİ EN ÜSTE ÇIKARTILIR.
    if($userRole === 'kurucu' || $userRole === 'yetkili'){
        $db->prepare("UPDATE posts SET post=?, duzenlendi=1, tarih=NOW() WHERE post_no=?")->execute([$yeni_metin, $p_id]);
    } else {
        $db->prepare("UPDATE posts SET post=?, duzenlendi=1, tarih=NOW() WHERE post_no=? AND user=?")->execute([$yeni_metin, $p_id, $currentUser]);
    }
    header("Location: " . $redirect); exit;
}

// GÖNDERİ PAYLAŞMA
if(isset($_POST['paylas'])){
    $postText = trim($_POST['post']);
    $postResim = null;
	// RESİM DOSYASI SEÇİLMİŞ İSE ve HATA YOKSA YÜKLEME BAŞLATILIR.
    if(isset($_FILES['post_resim']) && $_FILES['post_resim']['error'] === 0){
        $uploadDir = 'uploads/' . $currentUser . '/post/';
        if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
		// DOSYA İSMİ OLUŞTURMA
		$fn = time() . '_' . uniqid() . '.' . strtolower(pathinfo($_FILES['post_resim']['name'], PATHINFO_EXTENSION));
        if(move_uploaded_file($_FILES['post_resim']['tmp_name'], $uploadDir . $fn)) $postResim = $currentUser . '/post/' . $fn;
    }
	
	// GÖNDERİ BOŞ DEĞİLSE VERİTABANINA EKLE.
    if(!empty($postText) || $postResim){
        $db->prepare("INSERT INTO posts (user, post, post_resim, silindi, tarih) VALUES (?, ?, ?, 'aktif', NOW())")->execute([$currentUser, $postText, $postResim]);
        header("Location: index.php"); exit;
    }
}

// AKTİF GÖNDERİLERİ LİSTELEME (TARİHE GÖRE YÜKSEKTE)
$posts = $db->query("SELECT posts.*, uye.profil_foto FROM posts JOIN uye ON posts.user = uye.uye_isim WHERE posts.silindi='aktif' ORDER BY posts.tarih DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card mb-4 shadow-sm border-0" style="border-radius: 15px;">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="d-flex align-items-start">
                <div class="dropdown me-3">
                    <img src="uploads/<?php echo $userPP; ?>" style="width: 48px; height: 48px; object-fit: cover; border-radius: 50%;" class="border" data-bs-toggle="dropdown" style="cursor:pointer;">
                    <ul class="dropdown-menu shadow border-0">
                        <li><a class="dropdown-item fw-bold" href="index.php?sayfa=profil">Profilim</a></li>
                    </ul>
                </div>
                <div class="flex-grow-1">
                    <textarea name="post" id="mainPostText" oninput="checkBtn('main')" class="form-control border-0 shadow-none p-0" style="resize:none; font-size: 1.1rem; min-height:60px;" placeholder="Neler oluyor?"></textarea>
                    <div id="mainFileInfo" class="small text-muted mt-1"></div>
                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                        <input type="file" name="post_resim" id="mainFileInp" class="d-none" onchange="fileSelected('main')">
                        <button type="button" class="btn btn-light rounded-pill text-primary" onclick="document.getElementById('mainFileInp').click()"><i class="bi bi-image"></i></button>
                        <button type="submit" name="paylas" id="mainPaylasBtn" class="btn btn-primary px-4 rounded-pill fw-bold" disabled style="opacity: 0.5;">Paylaş</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php foreach($posts as $p): ?>
<div class="card mb-3 shadow-sm border-0" style="border-radius: 15px;">
    <div class="card-body">
        <div class="d-flex align-items-center mb-2">
            <a href="index.php?sayfa=profil&u=<?php echo urlencode($p['user']); ?>">
                <img src="uploads/<?php echo ($p['profil_foto'] ?: 'default.png'); ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 50%;" class="me-2 border">
            </a>
            <div>
                <a href="index.php?sayfa=profil&u=<?php echo urlencode($p['user']); ?>" class="text-decoration-none text-dark fw-bold"><?php echo htmlspecialchars($p['user']); ?></a>
                <small class="text-muted d-block" style="font-size: 11px;">
                    <?php echo $p['tarih']; ?>
                    <?php if($p['duzenlendi'] == 1): ?>
                        <span class="ms-1 text-primary fw-bold" style="font-size: 10px;"><i class="bi bi-pencil-fill"></i> DÜZENLENDİ</span>
                    <?php endif; ?>
                </small>
            </div>
            <?php if($p['user'] === $currentUser || $userRole === 'kurucu' || $userRole === 'yetkili'): ?>
            <div class="dropdown ms-auto">
                <button class="btn btn-link text-dark p-0 shadow-none" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                    <li><button type="button" class="dropdown-item" onclick='duzenleHazirla(<?php echo $p['post_no']; ?>, <?php echo json_encode($p['post']); ?>)'>Düzenle</button></li>
                    <li><button type="button" class="dropdown-item text-danger" onclick="silmeOnayi(<?php echo $p['post_no']; ?>)">Sil</button></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <p class="mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars($p['post']); ?></p>
        <?php if($p['post_resim']): ?><img src="uploads/<?php echo $p['post_resim']; ?>" class="img-fluid mt-2 rounded border w-100" style="max-height: 500px; object-fit: contain;"><?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<div class="modal fade" id="duzenleModali" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow" style="border-radius: 20px;"><form method="post"><div class="modal-header border-0 pb-0"><h5 class="fw-bold">Düzenle</h5><button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="post_id" id="editPostId"><textarea name="duzenlenen_metin" id="editPostText" class="form-control border-0 bg-light shadow-none" style="resize:none; min-height:150px; border-radius:15px;"></textarea></div><div class="modal-footer border-0 pt-0"><button type="submit" name="post_guncelle" class="btn btn-primary rounded-pill px-4 fw-bold">Kaydet</button></div></form></div></div></div>
<div class="modal fade" id="silmeModali" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content border-0 shadow" style="border-radius: 15px;"><div class="modal-body text-center p-4"><h5 class="fw-bold mb-3 text-danger">Silinsin mi?</h5><div class="d-grid gap-2"><a id="kesinSilBtn" href="#" class="btn btn-danger rounded-pill fw-bold">Evet, Sil</a><button type="button" class="btn btn-light rounded-pill fw-bold" data-bs-dismiss="modal">Vazgeç</button></div></div></div></div></div>

<script>

// DOSYA SEÇİLDİĞİNDE ADINI GÖSTERİR.
function fileSelected(t) {
    const inp = document.getElementById(t+'FileInp'), info = document.getElementById(t+'FileInfo');
    info.innerHTML = inp.files.length > 0 ? `<i class="bi bi-file-earmark-image"></i> ${inp.files[0].name}` : '';
    checkBtn(t);
}

// METİN YAZILMAMIŞ yada DOSYA SEÇİLMEMİŞ İSE BUTONU PASİF TUTAR.
function checkBtn(t) {
    const txt = document.getElementById(t+'PostText').value.trim(), file = document.getElementById(t+'FileInp').files.length, btn = document.getElementById(t+'PaylasBtn');
    btn.disabled = !(txt.length > 0 || file > 0); btn.style.opacity = btn.disabled ? "0.5" : "1";
}

// DÜZENLE METNİNİ AÇAR VE MEVCUT METNİ DOLDURUR.
function duzenleHazirla(id, metin) {
    document.getElementById('editPostId').value = id;
    document.getElementById('editPostText').value = metin;
    new bootstrap.Modal(document.getElementById('duzenleModali')).show();
}

// SİLME ONAYI
function silmeOnayi(id) {
    const urlParams = new URLSearchParams(window.location.search);
    const sayfa = urlParams.get('sayfa');
    const user = urlParams.get('u');
    let silUrl = 'index.php?sil_no=' + id;
    if(sayfa === 'profil') {
        silUrl += '&sayfa=profil';
        if(user) silUrl += '&u=' + encodeURIComponent(user);
    }
    document.getElementById('kesinSilBtn').href = silUrl;
    new bootstrap.Modal(document.getElementById('silmeModali')).show();
}
</script>
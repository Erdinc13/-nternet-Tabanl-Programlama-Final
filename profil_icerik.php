<?php
// 1. KULLANICI VE ROL KONTROLÜ
$viewUser = isset($_GET['u']) ? $_GET['u'] : $currentUser;
$stmt = $db->prepare("SELECT * FROM uye WHERE uye_isim = ?");
$stmt->execute([$viewUser]);
$profileData = $stmt->fetch(PDO::FETCH_ASSOC);

//KULLANICI VERİ TABANINDA VAR MI KONTROL EDER.
if (!$profileData) {
    echo "<div class='alert alert-danger'>Kullanıcı bulunamadı!</div>";
    return;
}

//KULLANICI BİLGİLERİNİ ÇEKER.
$currentUser = $_SESSION['user'];
$checkRole = $db->prepare("SELECT rol, profil_foto FROM uye WHERE uye_isim = ?");
$checkRole->execute([$currentUser]);
$uInfo = $checkRole->fetch();
$userRole = $uInfo['rol'] ?? 'user';
$userPP = $uInfo['profil_foto'] ?? 'default.png';

//GÖRÜNTÜLENEN PROFİL KULLANICIYA MI AİT KONTROL EDER.
$isMyProfile = ($viewUser === $currentUser);

// --- 2. PROFİL FOTOĞRAFI İŞLEMLERİ ---

if ($isMyProfile && isset($_FILES['new_profile_foto'])) { //KENDİ PROFİLİNDE ve DOSYA SEÇMİŞ 
    $uploadDir = 'uploads/' . $currentUser . '/profile/'; //KULLANICI KLASÖR YOLU OLUŞTURUR
    if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }//KLASÖR YOKSA OLUŞTURUR.
    
    $ext = strtolower(pathinfo($_FILES['new_profile_foto']['name'], PATHINFO_EXTENSION));
    $newFileName = $currentUser . "_profile_" . time() . "." . $ext;
    $dbSavePath = $currentUser . '/profile/' . $newFileName;
    
    if (move_uploaded_file($_FILES['new_profile_foto']['tmp_name'], 'uploads/' . $dbSavePath)) {
        $db->prepare("UPDATE uye SET profil_foto = ? WHERE uye_isim = ?")->execute([$dbSavePath, $currentUser]); //VERİTABANI GÜNCELLEMESİ
        header("Location: index.php?sayfa=profil&u=" . urlencode($currentUser)); //SAYFAYI YENİLER
        exit;
    }
}

// KULLANICI FOTOĞRAFINI KALDIRIR
if ($isMyProfile && isset($_GET['foto_kaldir'])) {
	//VERİTABANINDA PROFİL FOTOĞRAFI 'NULL' ATANIR, VARSAYILAN FOTOĞRAFA DÖNER.
    $db->prepare("UPDATE uye SET profil_foto = NULL WHERE uye_isim = ?")->execute([$currentUser]);
    header("Location: index.php?sayfa=profil&u=" . urlencode($currentUser));
    exit;
}

// GÖNDERİ SİLME
if(isset($_GET['sil_no'])){
    $post_no = $_GET['sil_no'];
	// EĞER 'KURUCU' veya 'YETKİLİ' DEĞİL İSE SADECE KENDİ POSTLARINI SİLEBİLİR.
    if($userRole === 'kurucu' || $userRole === 'yetkili'){
        $db->prepare("UPDATE posts SET silindi='pasif' WHERE post_no=?")->execute([$post_no]);
    } else {
        $db->prepare("UPDATE posts SET silindi='pasif' WHERE post_no=? AND user=?")->execute([$post_no, $currentUser]);
    }
    header("Location: index.php?sayfa=profil&u=" . urlencode($viewUser)); exit;
}

// GÖNDERİ GÜNCELLEME
if(isset($_POST['post_guncelle'])){
    $yeni_metin = $_POST['duzenlenen_metin']; 
    $p_id = $_POST['post_id'];
	// METNİ GÜNCELLER, DÜZENLENDİ SATIRINI '1' YAPAR, TARİHİ GÜNCELLER.
    $db->prepare("UPDATE posts SET post=?, duzenlendi=1, tarih=NOW() WHERE post_no=?")->execute([$yeni_metin, $p_id]);
    header("Location: index.php?sayfa=profil&u=" . urlencode($viewUser)); exit;
}

// YENİ GÖNDERİ PAYLAŞMA
if(isset($_POST['paylas'])){
    $postText = trim($_POST['post']);
    $postResim = null;
	// EĞER GÖNDERİ İLE RESİM YÜKLENMİŞSE
    if(isset($_FILES['post_resim']) && $_FILES['post_resim']['error'] === 0){
        $postDir = 'uploads/' . $currentUser . '/post/';
        if(!is_dir($postDir)) mkdir($postDir, 0777, true);
        $fn = time() . '_' . uniqid() . '.' . strtolower(pathinfo($_FILES['post_resim']['name'], PATHINFO_EXTENSION));
        if(move_uploaded_file($_FILES['post_resim']['tmp_name'], $postDir . $fn)) { $postResim = $currentUser . '/post/' . $fn; }
    }
	// METİN VEYA RESİMDEN EN AZ BİRİ VARSA GÖNDERİYİ KAYDEDER.
    if(!empty($postText) || $postResim){
        $db->prepare("INSERT INTO posts (user, post, post_resim, silindi, tarih) VALUES (?, ?, ?, 'aktif', NOW())")->execute([$currentUser, $postText, $postResim]);
        header("Location: index.php?sayfa=profil&u=" . urlencode($viewUser)); exit;
    }
}

// KULLANICIYA AİT AKTİF GÖNDERİLERİ VERİTABANINDA LİSTELER.
$posts = $db->prepare("SELECT posts.*, uye.profil_foto FROM posts JOIN uye ON posts.user = uye.uye_isim WHERE posts.user = ? AND posts.silindi='aktif' ORDER BY posts.tarih DESC");
$posts->execute([$viewUser]);
$userPosts = $posts->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card border-0 shadow-sm mb-4" style="border-radius: 20px; overflow: hidden;">
    <div style="height: 120px; background: linear-gradient(45deg, #0d6efd, #0dcaf0);"></div>
    <div class="card-body text-center" style="margin-top: -65px;">
        <div class="d-inline-block position-relative">
            <form id="profileFotoForm" method="post" enctype="multipart/form-data">
                <input type="file" name="new_profile_foto" id="profileInput" class="d-none" accept="image/*" onchange="this.form.submit()">
                
                <div class="dropdown">
                    <div class="position-relative d-inline-block" <?php if($isMyProfile): ?> data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;" <?php endif; ?>>
                        <img src="uploads/<?php echo ($profileData['profil_foto'] ?: 'default.png'); ?>?t=<?php echo time(); ?>" 
                             class="rounded-circle border border-4 border-white shadow" 
                             style="width: 130px; height: 130px; object-fit: cover; background: white;">
                        
                        <?php if($isMyProfile): ?>
                        <div class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow" 
                             style="width: 32px; height: 32px; border: 2px solid white;">
                            <i class="bi bi-camera-fill" style="font-size: 0.9rem;"></i>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if($isMyProfile): ?>
                    <ul class="dropdown-menu dropdown-menu-center shadow border-0 mt-2" style="border-radius: 12px; min-width: 180px;">
                        <li><button type="button" class="dropdown-item py-2 fw-bold" onclick="document.getElementById('profileInput').click()"><i class="bi bi-upload me-2 text-primary"></i> Fotoğraf Yükle</button></li>
                        <?php if($profileData['profil_foto'] && $profileData['profil_foto'] !== 'default.png'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 fw-bold text-danger" href="index.php?sayfa=profil&u=<?php echo urlencode($currentUser); ?>&foto_kaldir=1"><i class="bi bi-trash me-2"></i> Fotoğrafı Kaldır</a></li>
                        <?php endif; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <h3 class="fw-bold mt-3 mb-0"><?php echo htmlspecialchars($profileData['uye_isim']); ?></h3>
        <p class="text-muted small mb-2">@<?php echo htmlspecialchars($profileData['uye_isim']); ?></p>
        <div class="badge <?php echo $profileData['rol'] == 'kurucu' ? 'bg-danger' : 'bg-primary'; ?> rounded-pill px-3"><?php echo strtoupper($profileData['rol']); ?></div>
    </div>
</div>

<?php if($isMyProfile): ?>
<div class="card mb-4 shadow-sm border-0" style="border-radius: 15px;">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="d-flex align-items-start">
                <img src="uploads/<?php echo ($userPP ?: 'default.png'); ?>" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;" class="me-3 border shadow-sm">
                <div class="flex-grow-1">
                    <textarea name="post" id="profPostText" oninput="checkBtn('prof')" class="form-control border-0 shadow-none p-0" style="resize:none; font-size: 1.1rem; min-height:60px;" placeholder="Profilinde neler oluyor?"></textarea>
                    <div id="profFileInfo" class="small text-muted mt-1"></div>
                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                        <div>
                            <input type="file" name="post_resim" id="profFileInp" class="d-none" onchange="fileSelected('prof')">
                            <button type="button" class="btn btn-light rounded-pill text-primary shadow-none" onclick="document.getElementById('profFileInp').click()"><i class="bi bi-image"></i></button>
                        </div>
                        <button type="submit" name="paylas" id="profPaylasBtn" class="btn btn-primary px-4 rounded-pill fw-bold" disabled style="opacity: 0.5;">Paylaş</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php foreach($userPosts as $p): ?>
<div class="card mb-3 shadow-sm border-0" style="border-radius: 15px;">
    <div class="card-body">
        <div class="d-flex align-items-center mb-2">
            <img src="uploads/<?php echo ($p['profil_foto'] ?: 'default.png'); ?>" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;" class="me-2 border">
            <div>
                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($p['user']); ?></h6>
                <small class="text-muted d-block" style="font-size: 11px;">
                    <?php echo $p['tarih']; ?>
                    <?php if(isset($p['duzenlendi']) && $p['duzenlendi'] == 1): ?>
                        <span class="ms-1 text-primary fw-bold" style="font-size: 10px;"><i class="bi bi-pencil-fill"></i> DÜZENLENDİ</span>
                    <?php endif; ?>
                </small>
            </div>
            <?php if($p['user'] === $currentUser || $userRole === 'kurucu' || $userRole === 'yetkili'): ?>
            <div class="dropdown ms-auto">
                <button class="btn btn-link text-dark p-0 shadow-none" data-bs-toggle="dropdown"><i class="bi bi-three-dots" style="font-size: 1.3rem;"></i></button>
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

// DOSYA SEÇİLDİĞİNDE İKON ve DOSYA ADINI GÖSTERİR.
function fileSelected(t) {
    const inp = document.getElementById(t+'FileInp'), info = document.getElementById(t+'FileInfo');
    info.innerHTML = inp.files.length > 0 ? `<i class="bi bi-file-earmark-image"></i> ${inp.files[0].name}` : '';
    checkBtn(t);
}

// FORM ALANINDA DOSYA veya METİN VAR MI KONTROL EDER.
function checkBtn(t) {
    const txt = document.getElementById(t+'PostText').value.trim(), file = document.getElementById(t+'FileInp').files.length, btn = document.getElementById(t+'PaylasBtn');
    // EĞER VARSA BUTONU AKTİF EDER.
	btn.disabled = !(txt.length > 0 || file > 0); btn.style.opacity = btn.disabled ? "0.5" : "1";
}

// DÜZENLEME BUTONUNDA FORMUN İÇİNİ MEVCUT POST İLE DOLDURUR.
function duzenleHazirla(id, metin) {
    document.getElementById('editPostId').value = id;
    document.getElementById('editPostText').value = metin;
    new bootstrap.Modal(document.getElementById('duzenleModali')).show();
}

// SİLME BUTONUNA BASILDIĞINDA ONAY LİNKİ AYARLAR
function silmeOnayi(id) {
    const urlParams = new URLSearchParams(window.location.search);
    const user = urlParams.get('u');
	// GERİ DÖNÜŞÜN DOĞRU YERE YAPILMASINI SAĞLAR.
    let silUrl = 'index.php?sil_no=' + id + '&sayfa=profil';
    if(user) silUrl += '&u=' + encodeURIComponent(user);
    document.getElementById('kesinSilBtn').href = silUrl;
    new bootstrap.Modal(document.getElementById('silmeModali')).show();
}
</script>
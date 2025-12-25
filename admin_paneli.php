<?php
// SADECE YETKİLİ KİŞİLERİN PANELİ GÖRMESİNİ İZİN VERİR.
if (!isset($userRole) || ($userRole !== 'yetkili' && $userRole !== 'kurucu')) {
    exit("Bu sayfaya erişim yetkiniz yok!");
}

// --- 1. ÜYE SİLME İŞLEMİ (Hiyerarşik Koruma) ---
if (isset($_GET['uye_sil'])) {
    $silinecek_id = $_GET['uye_sil'];
    
	// SİLİNMEK İSTENİN KULLANICI BİLGİLERİNİ GETİRİR.
    $stmt = $db->prepare("SELECT uye_isim, rol FROM uye WHERE id = ?");
    $stmt->execute([$silinecek_id]);
    $u = $stmt->fetch();
    
    if ($u) {
        // KURUCU SİLİNEMEZ.
        if ($u['rol'] === 'kurucu') {
            header("Location: index.php?sayfa=admin&hata=kurucu_dokunulmaz");
		// YETKİLİ KENDİNİ SİLEMEZ.
        } elseif ($u['uye_isim'] === $currentUser) {
            header("Location: index.php?sayfa=admin&hata=kendi_hesabini_silemez");
        // KULLANICI SİLİNİR.
		} else {
            $db->prepare("DELETE FROM uye WHERE id = ?")->execute([$silinecek_id]);
            header("Location: index.php?sayfa=admin&mesaj=silindi");
        }
        exit;
    }
}

// KULLANICILARA YETKİ VERME ve GERİ ALMA(SADECE KURUCULAR YAPABİLİR).
if (isset($_GET['yetki_degis'])) {
    $u_id = $_GET['yetki_degis'];
    $yeni_rol = $_GET['yeni_rol'];
    
	// SADECE KURUCU KULLANABİLİR.
    if ($userRole !== 'kurucu') {
        header("Location: index.php?sayfa=admin&hata=yetkin_yok");
    // KULLANICI ROLÜ GÜNCELLENİR, KURUCU HARİÇ
	} else {
        $db->prepare("UPDATE uye SET rol = ? WHERE id = ? AND rol != 'kurucu'")->execute([$yeni_rol, $u_id]);
        header("Location: index.php?sayfa=admin&mesaj=guncellendi");
    }
    exit;
}

// ÜYELERİ ROL SIRASINA GÖRE LİSTELER (KURUCU > YETKİLİ > ÜYE)
// AYNI ROLE SAHİP KULLANICILAR HESAP OLUŞUM TARİHİNE GÖRE SIRALANIR.
$uyeler = $db->query("SELECT * FROM uye ORDER BY CASE rol WHEN 'kurucu' THEN 1 WHEN 'yetkili' THEN 2 ELSE 3 END ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .admin-user-link { transition: 0.2s; }
    .admin-user-link:hover { color: #0d6efd !important; text-decoration: underline !important; }
</style>

<div class="card border-0 shadow-sm" style="border-radius:15px;">
    <div class="card-body p-4">
        <h4 class="fw-bold mb-4"><i class="bi bi-shield-lock text-primary me-2"></i>Yönetici Paneli</h4>
        
        <?php if(isset($_GET['mesaj'])): ?>
            <div class="alert alert-success small p-2">İşlem başarıyla tamamlandı.</div>
        <?php endif; ?>
        <?php if(isset($_GET['hata'])): ?>
            <div class="alert alert-danger small p-2">Hata: Erişim engellendi veya geçersiz işlem!</div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Kullanıcı</th>
                        <th>Rol</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($uyeler as $u): ?>
                    <tr>
                        <td>
                            <img src="uploads/<?php echo ($u['profil_foto'] ?: 'default.png'); ?>" style="width:38px; height:38px; border-radius:50%; object-fit:cover;" class="me-2 border shadow-sm">
                            
                            <a href="index.php?sayfa=profil&u=<?php echo urlencode($u['uye_isim']); ?>" class="text-decoration-none text-dark fw-bold admin-user-link">
                                <?php echo htmlspecialchars($u['uye_isim']); ?>
                            </a>
                        </td>
                        <td>
                            <?php if($u['rol'] === 'kurucu'): ?>
                                <span class="badge bg-danger">KURUCU</span>
                            <?php elseif($u['rol'] === 'yetkili'): ?>
                                <span class="badge bg-primary">YETKİLİ</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">USER</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if($u['rol'] !== 'kurucu' && $u['uye_isim'] !== $currentUser): ?>
                                
                                <?php if($userRole === 'kurucu'): ?>
                                    <?php if($u['rol'] === 'user'): ?>
                                        <a href="index.php?sayfa=admin&yetki_degis=<?php echo $u['id']; ?>&yeni_rol=yetkili" class="btn btn-sm btn-outline-primary me-1" title="Yetkili Yap">
                                            <i class="bi bi-chevron-up"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="index.php?sayfa=admin&yetki_degis=<?php echo $u['id']; ?>&yeni_rol=user" class="btn btn-sm btn-outline-warning me-1" title="Yetkiyi Al">
                                            <i class="bi bi-chevron-down"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <a href="index.php?sayfa=admin&uye_sil=<?php echo $u['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">
                                    <i class="bi bi-trash"></i>
                                </a>

                            <?php elseif($u['rol'] === 'kurucu'): ?>
                                <small class="text-muted"><i class="bi bi-patch-check-fill text-danger"></i> Korumalı</small>
                            <?php else: ?>
                                <small class="text-muted">Siz</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
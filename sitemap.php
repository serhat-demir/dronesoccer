<?php
    ini_set('session.gc_maxlifetime', 86400);
    session_set_cookie_params(86400);
    session_start();

    if (!isset($_SESSION['user_id'])) {
        // Redirect to login page if the user is not authenticated
        header('Location: login.php');
        exit;
    }
    
    $current_user_id = $_SESSION['user_id'];
    
    // Check if user is admin
    include 'db.php';
    $admin_check = $db->prepare("SELECT is_admin FROM kullanicilar WHERE id = ?");
    $admin_check->execute([$current_user_id]);
    $user_data = $admin_check->fetch(PDO::FETCH_ASSOC);
    $is_admin = $user_data && $user_data['is_admin'] > 0; // Both level 1 and 2 can access admin panel
    $is_super_admin = $user_data && $user_data['is_admin'] == 2;
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drone Soccer Türkiye - Site Haritası</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body class="mx-5 px-5 mt-5">
    <h1 class="text-center display-3 font-weight-bold text-primary border-bottom border-primary">Site Haritası</h1>

    <div class="row mt-2">
        <div class="col-md-6">
            <a href="index.php" class="btn btn-primary btn-lg btn-block py-3 font-weight-bold" target="_blank">Ana Sayfa</a>
            <a href="takimlar.php" class="btn btn-primary btn-lg btn-block py-3 font-weight-bold" target="_blank">Takımlar</a>
            <a href="gruplar.php" class="btn btn-primary btn-lg btn-block py-3 font-weight-bold" target="_blank">Gruplar</a>
            <a href="dronesoccerturkiye-scoreboard-kilavuz.pdf" class="btn btn-primary btn-lg btn-block py-3 font-weight-bold" target="_blank">Scoreboard Kullanımı</a>
            <?php if ($is_admin): ?>
                <a href="admin.php" class="btn btn-danger btn-lg btn-block py-3 font-weight-bold" target="_blank">Yönetici Paneli</a>
            <?php endif; ?>
            <?php if ($is_super_admin): ?>
                <a href="guide.php" class="btn btn-warning btn-lg btn-block py-3 font-weight-bold" target="_blank">Yetki Sistemi</a>
            <?php endif; ?>
        </div>

        <div class="col-md-6">
            <a href="mac-kayitlari.php" class="btn btn-primary btn-lg btn-block py-3 font-weight-bold" target="_blank">Maç Kayıtları</a>
            <a href="grup-siralama.php" class="btn btn-primary btn-lg btn-block py-3 font-weight-bold" target="_blank">Grup Sıralama</a>
            <a href="eslestirme.php" class="btn btn-primary btn-lg btn-block py-3 font-weight-bold" target="_blank">Eleme Maçları (Çeyrek Final)</a>
            <a href="final-four.php" class="btn btn-primary btn-lg btn-block py-3 font-weight-bold" target="_blank">Final Four</a>
        </div>
    </div>
</body>
</html>
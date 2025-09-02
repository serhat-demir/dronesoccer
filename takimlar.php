<?php
    include 'db.php';
    ini_set('session.gc_maxlifetime', 86400);
    session_set_cookie_params(86400);

    session_start();

    if (!isset($_SESSION['user_id'])) {
        // Redirect to login page if the user is not authenticated
        header('Location: login.php');
        exit;
    }
    
    $current_user_id = $_SESSION['user_id'];

    // Takım Ekleme
    if (isset($_POST['takim_ekle'])) {
        $takim_adi = trim($_POST['takim_adi'] ?? '');
        if (empty($takim_adi)) {
            echo '
                <div class="alert alert-danger">
                    Takım adı boş olamaz.
                </div>
            ';
        } else {
            $kontrol = $db->prepare("SELECT * FROM takimlar WHERE takim_adi = :takim_adi AND kullanici_id = :kullanici_id");
            $kontrol->bindParam(':takim_adi', $takim_adi);
            $kontrol->bindParam(':kullanici_id', $current_user_id);
            $kontrol->execute();

            if ($kontrol->rowCount() > 0) {
                echo '
                    <div class="alert alert-danger">
                        Bu isimde bir takım zaten mevcut.
                    </div>
                ';
            } else {
                $ekle = $db->prepare("INSERT INTO takimlar (takim_adi, kullanici_id) VALUES(:takim_adi, :kullanici_id)");
                $ekle->bindParam(':takim_adi', $takim_adi);
                $ekle->bindParam(':kullanici_id', $current_user_id);
                $ekle->execute();

                if ($ekle->rowCount() > 0) {
                    echo '
                        <div class="alert alert-success">
                            Takım eklendi.
                        </div>
                    ';
                } else {
                    echo '
                        <div class="alert alert-danger">
                            Takım eklenemedi.
                        </div>
                    ';
                }
            }
        }
    }

    // Takım Silme
    if (isset($_POST['takim_sil'])) {
        $takim_id = intval($_POST['takim_id'] ?? 0);

        if ($takim_id <= 0) {
            echo '
                <div class="alert alert-danger">
                    Geçersiz takım ID.
                </div>
            ';
        } else {
            $sil = $db->prepare("DELETE FROM takimlar WHERE id = :takim_id AND kullanici_id = :kullanici_id");
            $sil->bindParam(":takim_id", $takim_id);
            $sil->bindParam(":kullanici_id", $current_user_id);
            $sil->execute();

            if ($sil->rowCount() > 0) {
                echo '
                    <div class="alert alert-success">
                        Takım silindi.
                    </div>
                ';
            } else {
                echo '
                    <div class="alert alert-danger">
                        Takım silinemedi.
                    </div>
                ';
            }
        }
    }

    // Takım Düzenleme
    if (isset($_POST['takim_duzenle'])) {
        $takim_id = intval($_POST['takim_id'] ?? 0);
        $yeni_takim_adi = trim($_POST['yeni_takim_adi'] ?? '');

        if ($takim_id > 0 && !empty($yeni_takim_adi)) {
            $kontrol = $db->prepare("SELECT * FROM takimlar WHERE takim_adi = :takim_adi AND id != :id AND kullanici_id = :kullanici_id");
            $kontrol->bindParam(':takim_adi', $yeni_takim_adi);
            $kontrol->bindParam(':id', $takim_id);
            $kontrol->bindParam(':kullanici_id', $current_user_id);
            $kontrol->execute();

            if ($kontrol->rowCount() > 0) {
                echo '
                    <div class="alert alert-danger">
                        Bu isimde bir takım zaten mevcut.
                    </div>
                ';
            } else {
                $duzenle = $db->prepare("UPDATE takimlar SET takim_adi = :yeni_takim_adi WHERE id = :id AND kullanici_id = :kullanici_id");
                $duzenle->bindParam(':yeni_takim_adi', $yeni_takim_adi);
                $duzenle->bindParam(':id', $takim_id);
                $duzenle->bindParam(':kullanici_id', $current_user_id);
                $duzenle->execute();

                if ($duzenle->rowCount() > 0) {
                    echo '
                        <div class="alert alert-success">
                            Takım adı güncellendi.
                        </div>
                    ';
                } else {
                    echo '
                        <div class="alert alert-danger">
                            Takım adı güncellenemedi.
                        </div>
                    ';
                }
            }
        } else {
            echo '
                <div class="alert alert-danger">
                    Geçersiz takım ID veya takım adı.
                </div>
            ';
        }
    }
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drone Soccer Türkiye - Takım Yönetimi</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <div class="row">
        <div class="col-md-4">
            <h1 class="mt-2">Takım Ekle</h1>

            <form action="" method="post">
                <div class="form-group mb-2">
                    <label for="takim_adi">Takım Adı</label>
                    <input type="text" name="takim_adi" id="takimAdi" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-success" name="takim_ekle">Kaydet</button>
            </form>
        </div>

        <div class="col-md-8">
            <h1 class="mt-2">Takımlar</h1>

            <table class="table table-bordered table-striped">
                <tr>
                    <th>Sıra</th>
                    <th>Takım Adı</th>
                    <th>Düzenle</th>
                    <th>Sil</th>
                </tr>

                <?php
                    $takimlar = $db->prepare("SELECT * FROM takimlar WHERE kullanici_id = ?");
                    $takimlar->execute([$current_user_id]);
                    $takimlar = $takimlar->fetchAll();
                    $sira = 1;
                    foreach ($takimlar as $takim) {
                        echo '
                            <tr>
                                <td class="font-weight-bold">'.$sira.'</td>
                                <td>'. htmlspecialchars($takim['takim_adi']) .'</td>
                                <td>
                                    <form action="" method="post" class="d-inline">
                                        <input type="hidden" name="takim_id" value="'. $takim['id'] .'">
                                        <div class="form-group mb-0 d-flex">
                                            <input type="text" name="yeni_takim_adi" class="form-control" placeholder="Yeni isim" required>
                                            <button type="submit" class="btn btn-primary ml-2" name="takim_duzenle">Düzenle</button>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <form action="" method="post" class="d-inline">
                                        <input type="hidden" name="takim_id" value="'. $takim['id'] .'">
                                        <button type="submit" class="btn btn-danger" name="takim_sil">Sil</button>
                                    </form>
                                </td>
                            </tr>
                        ';
                        $sira++;
                    }
                ?>
            </table>
        </div>
    </div>

    <script>
        window.onload = (() => {
            document.getElementById('takimAdi').focus();
        });
    </script>

    <script src="js/routing.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>

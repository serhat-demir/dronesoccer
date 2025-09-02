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

    if (isset($_POST['grup_ekle'])) {
        $grup_adi = $_POST['grup_adi'] ?? '';

        if (empty($grup_adi)) {
            echo '
                <div class="alert alert-danger">
                    Grup adı boş olamaz.
                </div>
            ';
        } else {
            $kontrol = $db->prepare("SELECT * FROM gruplar WHERE grup_adi = :grup_adi AND kullanici_id = :kullanici_id");
            $kontrol->bindParam(':grup_adi', $grup_adi);
            $kontrol->bindParam(':kullanici_id', $current_user_id);
            $kontrol->execute();

            if ($kontrol->rowCount() > 0) {
                echo '
                    <div class="alert alert-danger">
                        Bu isimde bir grup zaten mevcut.
                    </div>
                ';
            } else {
                $ekle = $db->prepare("INSERT INTO gruplar (grup_adi, kullanici_id) VALUES(:grup_adi, :kullanici_id)");
                $ekle->bindParam(':grup_adi', $grup_adi);
                $ekle->bindParam(':kullanici_id', $current_user_id);
                $ekle->execute();

                if ($ekle->rowCount() > 0) {
                    echo '
                        <div class="alert alert-success">
                            Grup eklendi.
                        </div>
                    ';
                } else {
                    echo '
                        <div class="alert alert-danger">
                            Grup eklenemedi.
                        </div>
                    ';
                }
            }
        }
    }

    if (isset($_POST['grup_duzenle'])) {
        $grup_id = intval($_POST['grup_id'] ?? 0);
        $grup_adi = $_POST['grup_adi'] ?? '';

        if (empty($grup_adi)) {
            echo '
                <div class="alert alert-danger">
                    Grup adı boş olamaz.
                </div>
            ';
        } else {
            $kontrol = $db->prepare("SELECT * FROM gruplar WHERE grup_adi = :grup_adi AND id != :grup_id AND kullanici_id = :kullanici_id");
            $kontrol->bindParam(':grup_adi', $grup_adi);
            $kontrol->bindParam(':grup_id', $grup_id);
            $kontrol->bindParam(':kullanici_id', $current_user_id);
            $kontrol->execute();

            if ($kontrol->rowCount() > 0) {
                echo '
                    <div class="alert alert-danger">
                        Bu isimde bir grup zaten mevcut.
                    </div>
                ';
            } else {
                $duzenle = $db->prepare("UPDATE gruplar SET grup_adi = :grup_adi WHERE id = :grup_id AND kullanici_id = :kullanici_id");
                $duzenle->bindParam(':grup_adi', $grup_adi);
                $duzenle->bindParam(':grup_id', $grup_id);
                $duzenle->bindParam(':kullanici_id', $current_user_id);
                $duzenle->execute();

                if ($duzenle->rowCount() > 0) {
                    echo '
                        <div class="alert alert-success">
                            Grup düzenlendi.
                        </div>
                    ';
                } else {
                    echo '
                        <div class="alert alert-danger">
                            Grup düzenlenemedi.
                        </div>
                    ';
                }
            }
        }
    }

    if (isset($_POST['grup_sil'])) {
        $grup_id = intval($_POST['grup_id'] ?? 0);

        if ($grup_id <= 0) {
            echo '
                <div class="alert alert-danger">
                    Geçersiz grup ID.
                </div>
            ';
        } else {
            $guncelle = $db->prepare("UPDATE takimlar SET takim_grup = NULL WHERE takim_grup = :grup_id AND kullanici_id = :kullanici_id");
            $guncelle->bindParam(":grup_id", $grup_id);
            $guncelle->bindParam(":kullanici_id", $current_user_id);
            $guncelle->execute();

            $sil = $db->prepare("DELETE FROM gruplar WHERE id = :grup_id AND kullanici_id = :kullanici_id");
            $sil->bindParam(":grup_id", $grup_id);
            $sil->bindParam(":kullanici_id", $current_user_id);
            $sil->execute();

            if ($sil->rowCount() > 0) {
                echo '
                    <div class="alert alert-success">
                        Grup silindi.
                    </div>
                ';
            } else {
                echo '
                    <div class="alert alert-danger">
                        Grup silinemedi.
                    </div>
                ';
            }
        }
    }

    if (isset($_POST['takim_ekle'])) {
        $takim_id = intval($_POST['takim_id'] ?? 0);
        $grup_id = intval($_POST['grup_id'] ?? 0);

        if ($takim_id <= 0 || $grup_id <= 0) {
            echo '
                <div class="alert alert-danger">
                    Geçersiz takım veya grup ID.
                </div>
            ';
        } else {
            $kontrol = $db->prepare("SELECT * FROM takimlar WHERE id = :takim_id AND takim_grup = :grup_id AND kullanici_id = :kullanici_id");
            $kontrol->bindParam(':takim_id', $takim_id);
            $kontrol->bindParam(':grup_id', $grup_id);
            $kontrol->bindParam(':kullanici_id', $current_user_id);
            $kontrol->execute();

            if ($kontrol->rowCount() > 0) {
                echo '
                    <div class="alert alert-danger">
                        Bu takım zaten bu grupta.
                    </div>
                ';
            } else {
                $ekle = $db->prepare("UPDATE takimlar SET takim_grup = :grup_id WHERE id = :takim_id AND kullanici_id = :kullanici_id");
                $ekle->bindParam(':grup_id', $grup_id);
                $ekle->bindParam(':takim_id', $takim_id);
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

    if (isset($_POST['takim_cikar'])) {
        $takim_id = intval($_POST['takim_id'] ?? 0);
        $grup_id = intval($_POST['grup_id'] ?? 0);

        if ($takim_id <= 0 || $grup_id <= 0) {
            echo '
                <div class="alert alert-danger">
                    Geçersiz takım veya grup ID.
                </div>
            ';
        } else {
            $cikar = $db->prepare("UPDATE takimlar SET takim_grup = 0 WHERE id = :takim_id AND takim_grup = :grup_id AND kullanici_id = :kullanici_id");
            $cikar->bindParam(':takim_id', $takim_id);
            $cikar->bindParam(':grup_id', $grup_id);
            $cikar->bindParam(':kullanici_id', $current_user_id);
            $cikar->execute();

            if ($cikar->rowCount() > 0) {
                echo '
                    <div class="alert alert-success">
                        Takım gruptan çıkarıldı.
                    </div>
                ';
            } else {
                echo '
                    <div class="alert alert-danger">
                        Takım çıkarılamadı.
                    </div>
                ';
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drone Soccer Türkiye - Grup Yönetimi</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <div class="row">
        <div class="col-md-4">
            <h1 class="mt-2">Grup Ekle</h1>
            <form action="" method="post">
                <div class="form-group mb-2">
                    <label for="grup_adi">Grup Adı</label>
                    <input type="text" name="grup_adi" class="form-control" required>
                </div>

                <div class="text-right">
                    <button type="submit" class="btn btn-success" name="grup_ekle">Kaydet</button>
                </div>
            </form>
        </div>

        <div class="col-md-8">
            <h1 class="mt-2">Grup Listesi</h1>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Adı</th>
                        <th>Takımlar</th>
                        <th>Takım Ekle</th>
                        <th>Düzenle</th>
                        <th>Sil</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $gruplar = $db->prepare("SELECT * FROM gruplar WHERE kullanici_id = ?");
                        $gruplar->execute([$current_user_id]);
                        $gruplar = $gruplar->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($gruplar as $grup) {
                            $takimlar = $db->prepare("SELECT * FROM takimlar WHERE takim_grup = ? AND kullanici_id = ?");
                            $takimlar->execute([$grup['id'], $current_user_id]);
                            $takimlar = $takimlar->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <tr>
                                <td><?= $grup['grup_adi'] ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-primary" onclick="toggleDropdown(<?= $grup['id'] ?>)">Takımlar</button>
                                        <div id="dropdownMenu<?= $grup['id'] ?>" class="dropdown-menu" style="display: none;">
                                            <?php
                                                if (count($takimlar) > 0) {
                                                    foreach ($takimlar as $takim) {
                                                        echo '<div class="dropdown-item d-flex justify-content-between">
                                                                <span>' . htmlspecialchars($takim['takim_adi']) . '</span>
                                                                <form action="" method="post" style="display:inline;">
                                                                    <input type="hidden" name="takim_id" value="' . $takim['id'] . '">
                                                                    <input type="hidden" name="grup_id" value="' . $grup['id'] . '">
                                                                    <button type="submit" class="btn btn-danger btn-sm" name="takim_cikar">Çıkar</button>
                                                                </form>
                                                            </div>';
                                                    }
                                                } else {
                                                    echo '<a class="dropdown-item" href="#">Bu grupta takım yok.</a>';
                                                }
                                            ?>
                                        </div>
                                    </div>

                                    <script>
                                        function toggleDropdown(grupId) {
                                            const dropdownMenu = document.getElementById('dropdownMenu' + grupId);
                                            if (dropdownMenu.style.display === "none" || dropdownMenu.style.display === "") {
                                                dropdownMenu.style.display = "block";
                                            } else {
                                                dropdownMenu.style.display = "none";
                                            }
                                        }
                                    </script>
                                </td>


                                <td>
                                    <form action="gruplar.php" method="post">
                                        <div class="form-group mb-0 d-flex">
                                            <select name="takim_id" class="form-control">
                                                <?php
                                                    $takimlar = $db->prepare("SELECT * FROM takimlar WHERE (takim_grup IS NULL OR takim_grup = '0') AND kullanici_id = ?");
                                                    $takimlar->execute([$current_user_id]);
                                                    $takimlar = $takimlar->fetchAll(PDO::FETCH_ASSOC);
                                                    foreach ($takimlar as $takim) {
                                                        ?>
                                                        <option value="<?= $takim['id'] ?>"><?= $takim['takim_adi'] ?></option>
                                                        <?php
                                                    }
                                                ?>
                                            </select>

                                            <input type="hidden" name="grup_id" value="<?= $grup['id'] ?>">
                                            <button type="submit" class="btn btn-primary ml-2" name="takim_ekle">Ekle</button>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <form action="" method="post">
                                        <div class="form-group mb-0 d-flex">
                                            <input type="text" name="grup_adi" class="form-control mr-2" value="<?= $grup['grup_adi'] ?>" required>
                                            <input type="hidden" name="grup_id" value="<?= $grup['id'] ?>">
                                            <button type="submit" class="btn btn-primary btn-sm" name="grup_duzenle">Düzenle</button>
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <form action="" method="post">
                                        <input type="hidden" name="grup_id" value="<?= $grup['id'] ?>">
                                        <button type="submit" class="btn btn-danger" name="grup_sil">Sil</button>
                                    </form>
                                </td>
                            </tr>
                            <?php
                        }
                    ?>
                </tbody>
        </div>
    </div>

    <script src="js/routing.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>

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

    if (isset($_POST['macKayitTemizle'])) {
        $temizle = $db->prepare("DELETE FROM scoreboard WHERE kullanici_id = ?");
        $temizle->execute([$current_user_id]);
    }

    if (isset($_POST['updateRecords'])) {
        foreach ($_POST['matches'] as $matchId => $matchData) {
            $t1Puan = 0;
            $t2Puan = 0;

            // set the winner by looking into the scores + pens
            if (($matchData['takim1_gol'] + $matchData['takim1_pen']) > ($matchData['takim2_gol'] + $matchData['takim2_pen'])) {
                $matchData['kazanan_takim_id'] = $matchData['takim1_id'];
                $t1Puan = 3;
            } elseif (($matchData['takim1_gol'] + $matchData['takim1_pen']) < ($matchData['takim2_gol'] + $matchData['takim2_pen'])) {
                $matchData['kazanan_takim_id'] = $matchData['takim2_id'];
                $t2Puan = 3;
            } else {
                $matchData['kazanan_takim_id'] = 0; // Beraberlik durumu
                $t1Puan = $t2Puan = 1;
            }

            $update = $db->prepare("UPDATE scoreboard SET takim1_gol = ?, takim1_pen = ?, takim1_puan = ?, takim2_gol = ?, takim2_pen = ?, takim2_puan = ?, kazanan_takim_id = ? WHERE id = ? AND kullanici_id = ?");
            $update->execute([
                $matchData['takim1_gol'],
                $matchData['takim1_pen'],
                $t1Puan,
                $matchData['takim2_gol'],
                $matchData['takim2_pen'],
                $t2Puan,
                $matchData['kazanan_takim_id'], 
                $matchId,
                $current_user_id
            ]);
        }
    }

    $createMatchError = ''; // Initialize an error message variable

    $filterAsama = isset($_POST['filterAsama']) ? $_POST['filterAsama'] : '';

    if (isset($_POST['createMatch'])) {
        if (!isset($_POST['takim1_id']) || !isset($_POST['takim2_id'])) {
            $createMatchError = 'Takım bilgileri eksik.';
        } elseif ($_POST['takim1_id'] == $_POST['takim2_id']) {
            $createMatchError = 'Aynı takımla maç kaydedemezsiniz.';
        } else {
            $insert = $db->prepare("INSERT INTO scoreboard (takim1_id, takim2_id, takim1_gol, takim1_pen, takim2_gol, takim2_pen, kazanan_takim_id, asama, kullanici_id) VALUES (?, ?, 0, 0, 0, 0, 0, ?, ?)");
            $insert->execute([
                $_POST['takim1_id'],
                $_POST['takim2_id'],
                $_POST['asama'],
                $current_user_id
            ]);
        }
    }

    if (isset($_POST['deleteMatch'])) {
        $delete = $db->prepare("DELETE FROM scoreboard WHERE id = ? AND kullanici_id = ?");
        $delete->execute([$_POST['match_id'], $current_user_id]);
    }

    $query = "SELECT sc.id, t1.takim_adi AS takim1_ad, t1.id AS takim1_id, t2.takim_adi AS takim2_ad, t2.id AS takim2_id, sc.takim1_gol, sc.takim1_pen, sc.takim1_puan, sc.takim2_gol, sc.takim2_pen, sc.takim2_puan, sc.kazanan_takim_id, sc.asama FROM scoreboard sc INNER JOIN takimlar t1 ON sc.takim1_id = t1.id INNER JOIN takimlar t2 ON sc.takim2_id = t2.id WHERE sc.kullanici_id = ?";
    if ($filterAsama) {
        $query .= " AND sc.asama = ?";
    }
    $query .= " ORDER BY sc.id ASC";
    $mackayitlari = $db->prepare($query);
    if ($filterAsama) {
        $mackayitlari->execute([$current_user_id, $filterAsama]);
    } else {
        $mackayitlari->execute([$current_user_id]);
    }
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drone Soccer Türkiye - Maç Kayıtları</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        /* make table's 6-7-8-12-13-14 cols text bigger */
        /* only td */
        td:nth-child(6), td:nth-child(7), td:nth-child(8), td:nth-child(12), td:nth-child(13), td:nth-child(14) {
            font-size: 1.75rem;
        }
        
        /* 4-5-10-11 input text bigger */
        input[type="number"] {
            font-size: 1.25rem;
        }
    </style>
</head>
<body class="mx-5 mt-5">
    <h1 class="text-center">Maç Kayıtları</h1>

    <?php if ($createMatchError): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $createMatchError; ?>
        </div>
    <?php endif; ?>

    <h4>Maç Kayıtlarını Filtrele</h4>
    <form action="" method="post" class="mb-4 pl-0 pr-1">
        <div class="form-row">
            <div class="col-md-6">
                <select name="filterAsama" id="filterAsama" class="form-control">
                    <option value="">Tüm Aşamalar</option>
                    <?php
                        $asamalar = $db->prepare("SELECT DISTINCT asama FROM scoreboard WHERE kullanici_id = ?");
                        $asamalar->execute([$current_user_id]);
                        while ($asama = $asamalar->fetch(PDO::FETCH_ASSOC)) {
                            $selected = ($filterAsama == $asama['asama']) ? 'selected' : '';
                            echo "<option value='{$asama['asama']}' {$selected}>{$asama['asama']}</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="col-md-6">
                <button class="btn btn-primary">Filtrele</button>
            </div>
        </div>
    </form>

    <h4>Yeni Maç Kaydı Oluştur</h4>
    <form action="" method="post" class="mb-4">
        <div class="form-row">
            <div class="col">
                <label for="takim1_id" class="font-weight-bold mb-0">Kırmızı Takım</label>
                <select name="takim1_id" id="takim1_id" class="form-control" required>
                    <?php
                        $takimlar = $db->prepare("SELECT id, takim_adi FROM takimlar WHERE kullanici_id = ?");
                        $takimlar->execute([$current_user_id]);
                        while ($takim = $takimlar->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$takim['id']}'>{$takim['takim_adi']}</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="col">
                <label for="takim2_id" class="font-weight-bold mb-0">Mavi Takım</label>
                <select name="takim2_id" id="takim2_id" class="form-control" required>
                    <?php
                        $takimlar = $db->prepare("SELECT id, takim_adi FROM takimlar WHERE kullanici_id = ?");
                        $takimlar->execute([$current_user_id]);
                        while ($takim = $takimlar->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$takim['id']}'>{$takim['takim_adi']}</option>";
                        }
                    ?>
                </select>
            </div>
        </div>
        <div class="form-row mt-2">
            <div class="col-md-6">
                <select name="asama" id="asama" class="form-control" required>
                    <option value="Ön Eleme">Ön Eleme</option>
                    <option value="Çeyrek Final">Çeyrek Final</option>
                    <option value="Yarı Final">Yarı Final</option>
                    <option value="3-4 Maçı">3-4 Maçı</option>
                    <option value="Final">Final</option>
                </select>
            </div>

            <div class="col-md-6 text-right">
                <button name="createMatch" class="btn btn-primary">Yeni Maç Ekle</button>
            </div>
        </div>
    </form>

    <form action="" method="post">
        <table class="table table-bordered text-center mb-2">
            <tr>
                <th class="align-middle" colspan="2"></th>
                <th class="align-middle bg-danger text-light" style="background: #bd2130 !important;" colspan="6">Kırmızı Takım</th> 
                <th class="align-middle bg-primary text-light" style="background: #0056b3 !important;" colspan="6">Mavi Takım</th>
                <th class="align-middle" colspan="2"></th>
            </tr>
            <tr>
                <th class="align-middle bg-warning text-light font-weight-bold" style="background: #b8860b !important;">Maç</th>
                <th class="align-middle bg-warning text-light font-weight-bold" style="background: #b8860b !important;">Aşama</th>
                <th class="align-middle bg-danger text-light" style="background: #bd2130 !important;">Takım</th>
                <th class="align-middle bg-danger text-light" style="background: #bd2130 !important;">Gol</th>  
                <th class="align-middle bg-danger text-light" style="background: #bd2130 !important;">Penaltı</th>
                <th class="align-middle bg-danger text-light" style="background: #bd2130 !important;">Gol+Pen</th>
                <th class="align-middle bg-danger text-light" style="background: #bd2130 !important;">Average</th>
                <th class="align-middle bg-danger text-light" style="background: #bd2130 !important;">Puan</th>    
                <th class="align-middle bg-primary text-light" style="background: #0056b3 !important;">Takım</th>
                <th class="align-middle bg-primary text-light" style="background: #0056b3 !important;">Gol</th>
                <th class="align-middle bg-primary text-light" style="background: #0056b3 !important;">Penaltı</th>
                <th class="align-middle bg-primary text-light" style="background: #0056b3 !important;">Gol+Pen</th>  
                <th class="align-middle bg-primary text-light" style="background: #0056b3 !important;">Average</th>
                <th class="align-middle bg-primary text-light" style="background: #0056b3 !important;">Puan</th>    
                <th class="align-middle bg-warning text-light" style="background: #b8860b !important;">Kazanan</th>
                <th class="align-middle bg-warning text-light" style="background: #b8860b !important;">Sil</th>
            </tr>

            <?php
                $sayi = 1;
                $headersayac = 0;
                while ($mac = $mackayitlari->fetch(PDO::FETCH_ASSOC)) {
                    $kazanan_ad = '';
                    if ($mac['kazanan_takim_id'] == $mac['takim1_id']) {
                        $kazanan_ad = $mac['takim1_ad'];
                    } else if ($mac['kazanan_takim_id'] == $mac['takim2_id']) {
                        $kazanan_ad = $mac['takim2_ad'];
                    } else if ($mac['kazanan_takim_id'] == 0) {
                        $kazanan_ad = "Berabere";
                    } else {
                        $kazanan_ad = "Hata";
                    }
                    
                    // Show header row every 10 matches (but not before the first match)
                    if ($headersayac == 10 && $sayi > 1) {
                        echo "
                            <tr>
                                <th class='align-middle bg-warning text-light font-weight-bold' style='background: #b8860b !important;'>Maç</th>
                                <th class='align-middle bg-warning text-light font-weight-bold' style='background: #b8860b !important;'>Aşama</th>
                                <th class='align-middle bg-danger text-light' style='background: #bd2130 !important;'>Takım</th>
                                <th class='align-middle bg-danger text-light' style='background: #bd2130 !important;'>Gol</th>  
                                <th class='align-middle bg-danger text-light' style='background: #bd2130 !important;'>Penaltı</th>
                                <th class='align-middle bg-danger text-light' style='background: #bd2130 !important;'>Gol+Pen</th>
                                <th class='align-middle bg-danger text-light' style='background: #bd2130 !important;'>Average</th>
                                <th class='align-middle bg-danger text-light' style='background: #bd2130 !important;'>Puan</th>    
                                <th class='align-middle bg-primary text-light' style='background: #0056b3 !important;'>Takım</th>
                                <th class='align-middle bg-primary text-light' style='background: #0056b3 !important;'>Gol</th>
                                <th class='align-middle bg-primary text-light' style='background: #0056b3 !important;'>Penaltı</th>
                                <th class='align-middle bg-primary text-light' style='background: #0056b3 !important;'>Gol+Pen</th>  
                                <th class='align-middle bg-primary text-light' style='background: #0056b3 !important;'>Average</th>
                                <th class='align-middle bg-primary text-light' style='background: #0056b3 !important;'>Puan</th>    
                                <th class='align-middle bg-warning text-light' style='background: #b8860b !important;'>Kazanan</th>
                                <th class='align-middle bg-warning text-light' style='background: #b8860b !important;'>Sil</th>
                            </tr>";
                        $headersayac = 0; // Reset counter
                    }
                    
                    // Always show the data row
                    echo "
                        <tr>
                            <td class='align-middle bg-warning font-weight-bold'>{$sayi}</td>
                            <td class='align-middle bg-warning font-weight-bold'>{$mac['asama']}</td>
                            <td class='align-middle bg-danger text-light'>{$mac['takim1_ad']}</td>
                            <td class='align-middle bg-danger'><input type='number' name='matches[{$mac['id']}][takim1_gol]' value='{$mac['takim1_gol']}' class='form-control'></td>
                            <td class='align-middle bg-danger'><input type='number' name='matches[{$mac['id']}][takim1_pen]' value='{$mac['takim1_pen']}' class='form-control'></td>
                            <td class='align-middle bg-danger text-light'>". ($mac['takim1_gol'] + $mac['takim1_pen']) ."</td>
                            <td class='align-middle bg-danger text-light'>". ($mac['takim1_gol'] - $mac['takim2_gol']) ."</td>
                            <td class='align-middle bg-danger text-light'>{$mac['takim1_puan']}</td>
                            <td class='align-middle bg-primary text-light'>{$mac['takim2_ad']}</td>
                            <td class='align-middle bg-primary'><input type='number' name='matches[{$mac['id']}][takim2_gol]' value='{$mac['takim2_gol']}' class='form-control'></td>
                            <td class='align-middle bg-primary'><input type='number' name='matches[{$mac['id']}][takim2_pen]' value='{$mac['takim2_pen']}' class='form-control'></td>
                            <td class='align-middle bg-primary text-light'>". ($mac['takim2_gol'] + $mac['takim2_pen']) ."</td>
                            <td class='align-middle bg-primary text-light'>". ($mac['takim2_gol'] - $mac['takim1_gol']) ."</td>
                            <td class='align-middle bg-primary text-light'>{$mac['takim2_puan']}</td>";
                            if ($kazanan_ad == $mac['takim1_ad']) {
                                echo '<td class="align-middle bg-danger text-light">'.$kazanan_ad.'</td>';
                                echo '<input type="hidden" name="matches['.$mac['id'].'][kazanan_takim_id]" value="'.$mac['takim1_id'].'">';
                            } else if ($kazanan_ad == $mac['takim2_ad']) {
                                echo '<td class="align-middle bg-primary text-light">'. $kazanan_ad .'</td>';
                                echo '<input type="hidden" name="matches['.$mac['id'].'][kazanan_takim_id]" value="'.$mac['takim2_id'].'">';
                            } else {
                                echo '<td class="align-middle bg-secondary text-light">'. $kazanan_ad .'</td>';
                                echo '<input type="hidden" name="matches['.$mac['id'].'][kazanan_takim_id]" value="0">';
                            }
                            echo "
                                <input type='hidden' name='matches[{$mac['id']}][takim1_id]' value='{$mac['takim1_id']}' class='d-none'>
                                <input type='hidden' name='matches[{$mac['id']}][takim2_id]' value='{$mac['takim2_id']}' class='d-none'>
                            <td class='align-middle bg-warning'>
                                <button type='button' class='btn btn-danger' onclick='deleteMatch({$mac['id']})'>Sil</button>
                            </td>
                        </tr>
                    ";

                    $sayi++;
                    $headersayac++;
                }
            ?>
        </table>
        <button name="updateRecords" class="btn btn-success mb-2">Kayıtları Güncelle</button>
    </form>
    <form action="" method="post" onsubmit="return confirm('Kayıtları silmek istediğinize emin misiniz?')">
        <button name="macKayitTemizle" class="btn btn-danger mb-2">Kayıtları Temizle</button>
    </form>

    <script src="js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script>
        function deleteMatch(matchId) {
            if (confirm("Bu maçı silmek istediğinize emin misiniz?")) {
                const form = document.createElement('form');
                form.method = 'post';
                form.action = '';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'match_id';
                input.value = matchId;
                form.appendChild(input);
                const deleteButton = document.createElement('input');
                deleteButton.type = 'hidden';
                deleteButton.name = 'deleteMatch';
                deleteButton.value = '1';
                form.appendChild(deleteButton);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

    <script src="js/routing.js"></script>
</body>
</html>

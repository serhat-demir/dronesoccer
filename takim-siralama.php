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

    $mac_turu = $_GET['mac_turu'] ?? '';
    $tum_takimlar = [];

    if ($mac_turu) {
        $sorgu = $db->prepare("SELECT * FROM scoreboard WHERE asama = ? AND kullanici_id = ?");
        $sorgu->execute([$mac_turu, $current_user_id]);
        $maclar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sorgu = $db->prepare("SELECT * FROM scoreboard WHERE kullanici_id = ?");
        $sorgu->execute([$current_user_id]);
        $maclar = $sorgu->fetchAll(PDO::FETCH_ASSOC);
    }

    foreach ($maclar as $mac) {
        $takimlar = [
            [
                'id' => $mac['takim1_id'],
                'attigi' => $mac['takim1_gol'],
                'yedigi' => $mac['takim2_gol'],
                'kazandi' => ($mac['kazanan_takim_id'] == $mac['takim1_id']),
                'puan' => $mac['takim1_puan']
            ],
            [
                'id' => $mac['takim2_id'],
                'attigi' => $mac['takim2_gol'],
                'yedigi' => $mac['takim1_gol'],
                'kazandi' => ($mac['kazanan_takim_id'] == $mac['takim2_id']),
                'puan' => $mac['takim2_puan']
            ],
        ];

        foreach ($takimlar as $t) {
            if (!isset($tum_takimlar[$t['id']])) {
                $tum_takimlar[$t['id']] = [
                    'id' => $t['id'],
                    'kazandigi' => 0,
                    'oynadigi' => 0,
                    'attigi' => 0,
                    'yedigi' => 0,
                    'puan' => 0
                ];
            }

            $tum_takimlar[$t['id']]['oynadigi']++;
            if ($t['kazandi']) {
                $tum_takimlar[$t['id']]['kazandigi']++;
            }

            $tum_takimlar[$t['id']]['attigi'] += $t['attigi'];
            $tum_takimlar[$t['id']]['yedigi'] += $t['yedigi'];
            $tum_takimlar[$t['id']]['puan'] += $t['puan'];
        }
    }

    // Sıralama: puan → average → attığı gol
    usort($tum_takimlar, function ($a, $b) {
        if ($a['puan'] != $b['puan']) {
            return $b['puan'] - $a['puan'];
        }

        $a_avg = $a['attigi'] - $a['yedigi'];
        $b_avg = $b['attigi'] - $b['yedigi'];
        if ($a_avg != $b_avg) {
            return $b_avg - $a_avg;
        }

        return $b['attigi'] - $a['attigi'];
    });
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drone Soccer Türkiye - Takım Sıralaması</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body class="mx-0 px-0 mt-2">
    <h1 class="text-center display-3 font-weight-bold text-primary border-bottom border-primary">Takım Sıralaması</h1>

    <div class="row p-0 m-0">
        <div class="col-2">
            <div class="d-flex align-items-center justify-content-center" style="flex: 1;">
                <img src="logo.svg" alt="Vektörel Resim" style="max-width: 100%; height: auto;" class="ml-4">
            </div>
        </div>

        <div class="col-8">
            <table class="table table-bordered mb-0 text-center">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>#</th>
                        <th>Takım Adı</th>
                        <th>Oynadığı Maç</th>
                        <th>Kazandığı Maç</th>
                        <th>Attığı Gol</th>
                        <th>Yediği Gol</th>
                        <th>Average</th>
                        <th>Puan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sira = 1;
                    foreach ($tum_takimlar as $takim) {
                        $average = $takim['attigi'] - $takim['yedigi'];
                        $takimAdi = $db->prepare("SELECT takim_adi FROM takimlar WHERE id = ? AND kullanici_id = ?");
                        $takimAdi->execute([$takim['id'], $current_user_id]);
                        $takimVerisi = $takimAdi->fetch(PDO::FETCH_ASSOC);
                        echo "
                        <tr>
                            <td class=\"font-weight-bold\">{$sira}</td>
                            <td>{$takimVerisi['takim_adi']}</td>
                            <td>{$takim['oynadigi']}</td>
                            <td>{$takim['kazandigi']}</td>
                            <td>{$takim['attigi']}</td>
                            <td>{$takim['yedigi']}</td>
                            <td>{$average}</td>
                            <td>{$takim['puan']}</td>
                        </tr>
                        ";
                        $sira++;
                    }
                    ?>
                </tbody>
            </table>

            <!-- Maç türü seçimi -->
            <form action="" method="get" class="mt-2">
                <div class="form-row">
                    <div class="col-md-6">
                        <select name="mac_turu" id="mac_turu" class="form-control">
                            <option value="">Genel Sıralama</option>
                            <option value="Ön Eleme" <?php echo ($mac_turu == 'Ön Eleme') ? 'selected' : ''; ?>>Ön Eleme</option>
                            <option value="Çeyrek Final" <?php echo ($mac_turu == 'Çeyrek Final') ? 'selected' : ''; ?>>Çeyrek Final</option>
                            <option value="Yarı Final" <?php echo ($mac_turu == 'Yarı Final') ? 'selected' : ''; ?>>Yarı Final</option>
                            <option value="3-4 Maçı" <?php echo ($mac_turu == '3-4 Maçı') ? 'selected' : ''; ?>>3-4 Maçı</option>
                            <option value="Final" <?php echo ($mac_turu == 'Final') ? 'selected' : ''; ?>>Final</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary">Filtrele</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-2">
            <div class="d-flex align-items-center justify-content-center" style="flex: 1;">
                <img src="logo.svg" alt="Vektörel Resim" style="max-width: 100%; height: auto;" class="ml-4">
            </div>
        </div>
    </div>

    <script src="js/routing.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>

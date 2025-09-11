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
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drone Soccer Türkiye - Grup Sıralaması</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/fractional-grid.css">
    <style>
        .group-column h2 {
            text-align: center;
        }

        .logo-column {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
        }

        .logo-column img {
            max-width: 70%;
            height: auto;
        }

        table tr {
            height: 100px;
        }

        table th {
            font-size: 1.1rem;
        }

        table td, table th {
            vertical-align: middle !important;
        }

        .bg-blue {
            background-color: #439bf9 !important;
        }

        table td:nth-child(n+1) {
            font-size: 1.25rem;
        }

        .group-row {
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .group-column {
                margin-bottom: 2rem;
            }
        }
    </style>
</head>
<body class="mx-5 mt-5">
    <div class='row mb-2'>
        <div class='col-4 d-flex justify-content-end align-items-center'>
            <img src='logo.svg' alt='Drone Soccer' class='w-25'>
        </div>
        <div class='col-4 mx-0 px-0 d-flex justify-content-center align-items-center'>
            <h3 class='text-center text-primary display-4 font-weight-bold'>Grup Sıralaması</h3>
        </div>
        <div class='col-4 d-flex justify-content-start align-items-center'>
            <img src='logo.svg' alt='Drone Soccer' class='w-25'>
        </div>
    </div>
    
    <?php
    // Grupları al
    $gruplar = $db->prepare("SELECT * FROM gruplar WHERE kullanici_id = ? ORDER BY grup_adi");
    $gruplar->execute([$current_user_id]);
    $gruplar = $gruplar->fetchAll();

    $group_count = count($gruplar);

    // Check if group count is valid (only 2 or 4 groups allowed)
    if ($group_count != 2 && $group_count != 4) {
        echo "<div class='alert alert-danger text-center mt-5'>";
        echo "<h4>Hata!</h4>";
        echo "<p>Bu sayfa sadece 2 veya 4 grup için tasarlanmıştır.</p>";
        echo "<p>Mevcut grup sayısı: {$group_count}</p>";
        echo "<p>Lütfen grup sayınızı 2 veya 4 olarak ayarlayın.</p>";
        echo "</div>";
        echo "</body></html>";
        exit;
    }

    // Tabloları yazdıran fonksiyon
    function tabloyuYazdir($grup, $db, $current_user_id) {
        echo "<h2>{$grup['grup_adi']}</h2>";

        $grup_siralama = $db->prepare("
            SELECT 
                t.id AS takim_id,
                t.takim_adi,
                COUNT(CASE WHEN s.takim1_id = t.id OR s.takim2_id = t.id THEN 1 END) AS oynadigi_toplam_mac_sayisi,
                SUM(CASE 
                    WHEN s.takim1_id = t.id THEN s.takim1_puan
                    WHEN s.takim2_id = t.id THEN s.takim2_puan
                    ELSE 0 END
                ) AS topladigi_puan,
                SUM(CASE 
                    WHEN s.takim1_id = t.id THEN s.takim1_gol
                    WHEN s.takim2_id = t.id THEN s.takim2_gol
                    ELSE 0 END
                ) AS attigi_toplam_gol,
                SUM(CASE 
                    WHEN s.takim1_id = t.id THEN s.takim2_gol
                    WHEN s.takim2_id = t.id THEN s.takim1_gol
                    ELSE 0 END
                ) AS yedigi_toplam_gol,
                SUM(CASE 
                    WHEN s.takim1_id = t.id THEN s.takim1_pen
                    WHEN s.takim2_id = t.id THEN s.takim2_pen
                    ELSE 0 END
                ) AS kazandigi_penaltilar,
                (
                    SUM(CASE 
                        WHEN s.takim1_id = t.id THEN s.takim1_gol
                        WHEN s.takim2_id = t.id THEN s.takim2_gol
                        ELSE 0 END
                    ) - 
                    SUM(CASE 
                        WHEN s.takim1_id = t.id THEN s.takim2_gol
                        WHEN s.takim2_id = t.id THEN s.takim1_gol
                        ELSE 0 END
                    )
                ) AS average
            FROM takimlar t
            LEFT JOIN scoreboard s 
                ON (s.takim1_id = t.id OR s.takim2_id = t.id)
                -- AND s.asama = 'Ön Eleme'
                AND s.kullanici_id = ?
            WHERE t.takim_grup = ? AND t.kullanici_id = ?
            GROUP BY t.id, t.takim_adi
            ORDER BY topladigi_puan DESC, average DESC, attigi_toplam_gol DESC
        ");
        $grup_siralama->execute([$current_user_id, $grup['id'], $current_user_id]);
        $grup_siralama = $grup_siralama->fetchAll();

        echo "
            <table class='table table-bordered text-center table-striped mb-5'>
                <tr class='bg-primary text-light'>
                    <th>Sıralama</th>
                    <th>Takım Adı</th>
                    <th>Oynadığı Maç</th>
                    <th>Attığı Gol</th>
                    <th>Yediği Gol</th>
                    <th>Kazandığı Penaltı</th>
                    <th>Average</th>
                    <th class=\"bg-blue text-light font-weight-bold\">Puan</th>
                </tr>
        ";

        $sira = 1;
        foreach ($grup_siralama as $takim) {
            echo "
                <tr>
                    <td class='font-weight-bold'>{$sira}</td>
                    <td>{$takim['takim_adi']}</td>
                    <td>{$takim['oynadigi_toplam_mac_sayisi']}</td>
                    <td>{$takim['attigi_toplam_gol']}</td>
                    <td>{$takim['yedigi_toplam_gol']}</td>
                    <td>{$takim['kazandigi_penaltilar']}</td>
                    <td>{$takim['average']}</td>
                    <td class=\"bg-blue text-light font-weight-bold\">{$takim['topladigi_puan']}</td>
                </tr>
            ";
            $sira++;
        }

        echo "</table>";
    }

    // Modified layout logic for only 2 or 4 groups
    if ($group_count == 2) {
        // 2 groups: side by side
        echo "<div class='row'>";
        foreach ($gruplar as $grup) {
            echo "<div class='col-6 group-column'>";
            tabloyuYazdir($grup, $db, $current_user_id);
            echo "</div>";
        }
        echo "</div>";
    } else { // $group_count == 4
        // 4 groups: 2x2 layout
        for ($i = 0; $i < 4; $i += 2) {
            echo "<div class='row group-row'>";
            
            // Left column
            echo "<div class='col-6 group-column'>";
            tabloyuYazdir($gruplar[$i], $db, $current_user_id);
            echo "</div>";
            
            // Right column
            echo "<div class='col-6 group-column'>";
            tabloyuYazdir($gruplar[$i + 1], $db, $current_user_id);
            echo "</div>";
            
            echo "</div>";
        }
    }
    ?>

    <script src="js/bootstrap.min.js"></script>
    <script src="js/routing.js"></script>
</body>
</html>

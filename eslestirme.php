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

    function renderTitle($title) {
        echo "
            <div class='row mb-2'>
                <div class='col-4 d-flex justify-content-end align-items-center'>
                    <img src='logo.svg' alt='Drone Soccer' class='w-25'>
                </div>
                <div class='col-4 mx-0 px-0 d-flex justify-content-center align-items-center'>
                    <h3 class='text-center text-primary display-4 font-weight-bold'>{$title}</h3>
                </div>
                <div class='col-4 d-flex justify-content-start align-items-center'>
                    <img src='logo.svg' alt='Drone Soccer' class='w-25'>
                </div>
            </div>
        ";
    }
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drone Soccer Türkiye - Eleme Maçı Eşleştirme</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        .match-table th, .match-table td {
            text-align: center;
            vertical-align: middle;
        }

        .match-table td {
            padding: 15px;
            font-size: 1.25rem;
        }

        .match-table td:nth-child(4) {
            text-align: left;
        }

        .match-table th {
            background-color: #007bff;
            color: white;
            font-size: 1.5rem;
        }

        .winner-radio {
            display: block;
            margin: 5px 0;
        }

        .winner-radio input {
            scale: 1.25;
            position: relative;
            top: -1px;
            margin-right: 5px;
        }

        .btn-group {
            margin-top: 20px;
        }
    </style>
</head>
<body class="mx-5 mt-5">
    <?php
        // Çeyrek final eşleşmeleri için ilk 4 gruptan verileri çek
        $gruplar = $db->prepare("SELECT * FROM gruplar WHERE kullanici_id = ? ORDER BY grup_adi");
        $gruplar->execute([$current_user_id]);
        $gruplar = $gruplar->fetchAll();
        $takimlar = [];

        foreach ($gruplar as $grup) {
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
                    AND s.asama = 'Ön Eleme'
                    AND s.kullanici_id = ?
                WHERE t.takim_grup = ? AND t.kullanici_id = ?
                GROUP BY t.id, t.takim_adi
                ORDER BY topladigi_puan DESC, average DESC, attigi_toplam_gol DESC
            ");
            $grup_siralama->execute([$current_user_id, $grup['id'], $current_user_id]);
            $grup_siralama = $grup_siralama->fetchAll();

            $takimlar[] = $grup_siralama;
        }

        // Add safety check before creating quarter final matches
        if (count($takimlar) >= 2 && count($takimlar[0]) >= 4 && count($takimlar[1]) >= 4) {
            // Çeyrek final eşleşmelerini oluştur
            $cf_matches = [
                ['red' => $takimlar[0][0], 'blue' => $takimlar[1][3]],
                ['red' => $takimlar[0][1], 'blue' => $takimlar[1][2]],
                ['red' => $takimlar[0][2], 'blue' => $takimlar[1][1]],
                ['red' => $takimlar[0][3], 'blue' => $takimlar[1][0]],
            ];
        } else {
            echo '<div class="alert alert-warning">Çeyrek final için yeterli takım bulunamadı. Her grupta en az 4 takım olmalı.</div>';
            $cf_matches = [];
        }

        // Initialize $semi_final with a default value
        $semi_final = [];

        // Only proceed if we have valid quarter final matches
        if (!empty($cf_matches) && !isset($_POST['advance_to_semi_final']) && !isset($_POST['advance_to_final']) && !isset($_POST['show_final_winner'])) {
            renderTitle("Çeyrek Final Eşleşmeleri");
            echo "<form method='post' action=''>";

            echo "<table class='table table-bordered match-table'>
                    <thead>
                        <tr>
                            <th>Maç</th>
                            <th>Kırmızı Takım</th>
                            <th>Mavi Takım</th>
                            <th>Kazanan</th>
                        </tr>
                    </thead>
                    <tbody>";

            $i = 1;
            foreach ($cf_matches as $match) {
                $match_key = key($match);
                $red_team = $match['red']['takim_adi'];
                $blue_team = $match['blue']['takim_adi'];

                echo "<tr>
                        <td class='font-weight-bold'>$i</td>
                        <td>{$red_team}</td>
                        <td>{$blue_team}</td>
                        <td>
                            <div class='form-check winner-radio'>
                                <input class='form-check-input' type='radio' name='winner$i' value='{$red_team}' id='winner{$i}_red'>
                                <label class='form-check-label' for='winner{$i}_red'>
                                    {$red_team}
                                </label>
                            </div>
                            <div class='form-check winner-radio'>
                                <input class='form-check-input' type='radio' name='winner$i' value='{$blue_team}' id='winner{$i}_blue'>
                                <label class='form-check-label' for='winner{$i}_blue'>
                                    {$blue_team}
                                </label>
                            </div>
                        </td>
                    </tr>";
                $i++;
            }

            echo "</tbody></table>";

            // Yarı Finale Geç butonu
            echo "
                <div class='btn-group mb-2 mt-0'>
                    <button type='submit' class='btn btn-primary' name='advance_to_semi_final'>Yarı Finale Geç</button>
                </div>
            ";

            echo "</form>";
        }

        // Yarı finale geçme işlemi
        // Yarı final eşleşmelerini oluştururken
        if (isset($_POST['advance_to_semi_final'])) {
            $winners = [];
            
            // Çeyrek final kazananlarını al
            for ($i = 1; $i <= 4; $i++) {
                if (isset($_POST["winner$i"])) {
                    $winners[] = $_POST["winner$i"];
                }
            }

            // Yarı final eşleşmelerini oluştur
            if (count($winners) == 4) {
                // Yarı final eşleşmeleri
                $semi_final = [
                    'SF1' => [$winners[0], $winners[2]],  // Maç 1 kazananı vs Maç 3 kazananı
                    'SF2' => [$winners[1], $winners[3]],  // Maç 2 kazananı vs Maç 4 kazananı
                ];

                renderTitle("Yarı Final Eşleşmeleri");
                echo "<form method='post' action=''>";

                echo "<table class='table table-bordered match-table'>
                        <thead>
                            <tr>
                                <th>Maç</th>
                                <th>Kırmızı Takım</th>
                                <th>Mavi Takım</th>
                                <th>Kazanan</th>
                            </tr>
                        </thead>
                        <tbody>";

                $j = 1;
                foreach ($semi_final as $match_key => $teams) {
                    $red_team = $teams[0];
                    $blue_team = $teams[1];

                    echo "<tr>
                            <td class='font-weight-bold'>$j</td>
                            <td>{$red_team}</td>
                            <td>{$blue_team}</td>
                            <td>
                                <div class='form-check winner-radio'>
                                    <input class='form-check-input' type='radio' name='winner_sf$j' value='{$red_team}' id='winner_sf{$j}_red'>
                                    <label class='form-check-label' for='winner_sf{$j}_red'>
                                        {$red_team}
                                    </label>
                                </div>
                                <div class='form-check winner-radio'>
                                    <input class='form-check-input' type='radio' name='winner_sf$j' value='{$blue_team}' id='winner_sf{$j}_blue'>
                                    <label class='form-check-label' for='winner_sf{$j}_blue'>
                                        {$blue_team}
                                    </label>
                                </div>
                            </td>
                        </tr>";
                    $j++;
                }

                echo "</tbody></table>";

                // Winners listesini hidden input olarak ekle
                foreach ($winners as $index => $winner) {
                    echo "<input type='hidden' name='winners[]' value='{$winner}'>";
                }

                // Finale Geç butonu
                echo "
                    <div class='btn-group mb-2 mt-0'>
                        <button type='submit' class='btn btn-primary' name='advance_to_final'>Finale Geç</button>
                    </div>
                ";

                echo "</form>";
            }
        } 

        // Finale geçme işlemi
        if (isset($_POST['advance_to_final'])) {
            $final_winners = [];
            
            // Yarı final kazananlarını al
            for ($i = 1; $i <= 2; $i++) {
                if (isset($_POST["winner_sf$i"])) {
                    $final_winners[] = $_POST["winner_sf$i"];
                }
            }

            // Yarı finalde yarışan herkesi konsola yaz
            for ($i = 0; $i < count($_POST['winners']); $i++) {
                echo "<script>console.log('Yarı Finalde Yarışan: " . $_POST['winners'][$i] . "');</script>";
            }

            // Yarı final loserları bulmak için array diff al
            $losers = array_diff($_POST['winners'], $final_winners);

            // Array diff sonrası dizi farklılığını engellemek için losers listesindeki indexleri 0'dan başlat
            $losers = array_values($losers);

            // echo "<pre>";
            // print_r($losers);
            // print_r($final_winners);
            // print_r($_POST['winners']);
            // echo "</pre>";

            // Final eşleşmesini oluştur
            if (count($final_winners) == 2) {
                renderTitle("Final Eşleşmesi");
                echo "<form method='post' action=''>";

                echo "<table class='table table-bordered match-table'>
                        <thead>
                            <tr>
                                <th>Maç</th>
                                <th>Kırmızı Takım</th>
                                <th>Mavi Takım</th>
                                <th>Kazanan</th>
                            </tr>
                        </thead>
                        <tbody>";

                echo "<tr>
                        <td class='font-weight-bold'>Final</td>
                        <td>{$final_winners[0]}</td>
                        <td>{$final_winners[1]}</td>
                        <td>
                            <div class='form-check winner-radio'>
                                <input class='form-check-input' type='radio' name='winner_final' value='{$final_winners[0]}' id='winner_final_red'>
                                <label class='form-check-label' for='winner_final_red'>
                                    {$final_winners[0]}
                                </label>
                            </div>
                            <div class='form-check winner-radio'>
                                <input class='form-check-input' type='radio' name='winner_final' value='{$final_winners[1]}' id='winner_final_blue'>
                                <label class='form-check-label' for='winner_final_blue'>
                                    {$final_winners[1]}
                                </label>
                            </div>
                        </td>
                    </tr>";

                // 3-4 maçlarını yaz
                echo "<tr>
                        <td class='font-weight-bold'>3-4 Maçı</td>
                        <td>{$losers[0]}</td>
                        <td>{$losers[1]}</td>
                        <td>
                            <div class='form-check winner-radio'>
                                <input class='form-check-input' type='radio' name='winner_3_4' value='{$losers[0]}' id='winner_3_4_red'>
                                <label class='form-check-label' for='winner_3_4_red'>
                                    {$losers[0]}
                                </label>
                            </div>
                            <div class='form-check winner-radio'>
                                <input class='form-check-input' type='radio' name='winner_3_4' value='{$losers[1]}' id='winner_3_4_blue'>
                                <label class='form-check-label' for='winner_3_4_blue'>
                                    {$losers[1]}
                                </label>
                            </div>
                        </td>
                    </tr>";

                echo "</tbody></table>";

                // Finalde ve 3-4 maçında yarışan tüm takımları hidden input olarak ekle
                foreach ($final_winners as $index => $winner) {
                    echo "<input type='hidden' name='final_combined[]' value='{$winner}'>";
                }

                foreach ($losers as $index => $loser) {
                    echo "<input type='hidden' name='third_combined[]' value='{$loser}'>";
                }

                // Sonuçları kaydetmek için bir buton ekleyebilirsiniz
                echo "
                    <div class='btn-group mb-2 mt-0'>
                        <button type='submit' class='btn btn-success' name='show_final_winner'>Sonucu Göster</button>
                    </div>
                ";

                echo "</form>";
            }
        }

        if (isset($_POST['show_final_winner'])) {
            $final_winner = $_POST['winner_final']; // Final maçının kazananı
            $third_place_winner = $_POST['winner_3_4']; // 3-4 maçının kazananı
            $final_combined = $_POST['final_combined']; // Finalde yarışan takımlar
            $third_combined = $_POST['third_combined']; // 3-4 maçında yarışan takımlar

            // final maçının kaybedeni
            $final_loser = array_diff($final_combined, [$final_winner]);
            $final_loser = array_values($final_loser)[0]; // Indexleri sıfırdan başlat ve ilk elemanı al

            // 3-4 maçının kaybedeni
            $third_place_loser = array_diff($third_combined, [$third_place_winner]);
            $third_place_loser = array_values($third_place_loser)[0]; // Indexleri sıfırdan başlat ve ilk elemanı al

            // Sonuçları göster
            renderTitle("Yarışma Sonuçları");

            // line
            echo "<hr class='my-4 bg-primary'>";

            echo "
                <div class='text-center'>
                    <div class='mb-5' style='color: gold;'>
                        <span class='' style='font-size: 8rem; line-height: 125%;'>🏆 {$final_winner} 🏆</span>
                        <br/>
                        <span class='' style='font-size: 4rem; line-height: 90%;'>Şampiyon</span>
                    </div>

                    <div class='mb-5' style='color: silver;'>
                        <span class='' style='font-size: 6rem; line-height: 125%;'>🥈 {$final_loser} 🥈</span>
                        <br/>
                        <span class='' style='font-size: 3rem; line-height: 90%;'>İkinci</span>
                    </div>
                    
                    <div class='mb-5' style='color: #cd7f32;'>
                        <span class='' style='font-size: 4rem; line-height: 125%;'>🥉 {$third_place_winner} 🥉</span>
                        <br/>
                        <span class='' style='font-size: 2rem; line-height: 90%;'>Üçüncü</span>
                    </div>
                </div>"
            ;
        }
    ?>

    <script src="js/routing.js"></script>
</body>
</html>
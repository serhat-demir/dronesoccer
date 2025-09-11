<?php
    include 'db.php';
    ini_set('session.gc_maxlifetime', 86400);
    session_set_cookie_params(86400);

    session_start();

    if (!isset($_SESSION['user_id'])) {
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

    function createFinalFourMatches($gruplar_rankings) {
        $group_count = count($gruplar_rankings);
        $matches = [];

        // Final Four only works with exactly 2 groups and 2 teams each
        if ($group_count == 2) {
            if (count($gruplar_rankings[0]) >= 2 && count($gruplar_rankings[1]) >= 2) {
                $matches = [
                    ['red' => $gruplar_rankings[0][0], 'blue' => $gruplar_rankings[1][1]], // G1T1 vs G2T2
                    ['red' => $gruplar_rankings[0][1], 'blue' => $gruplar_rankings[1][0]], // G1T2 vs G2T1
                ];
            }
        }

        return $matches;
    }
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drone Soccer Türkiye - Final Four</title>
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
        // Fetch all groups and their rankings
        $gruplar = $db->prepare("SELECT * FROM gruplar WHERE kullanici_id = ? ORDER BY grup_adi");
        $gruplar->execute([$current_user_id]);
        $gruplar = $gruplar->fetchAll();
        $gruplar_rankings = [];

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
                    AND s.asama = 'Final Four'
                    AND s.kullanici_id = ?
                WHERE t.takim_grup = ? AND t.kullanici_id = ?
                GROUP BY t.id, t.takim_adi
                ORDER BY topladigi_puan DESC, average DESC, attigi_toplam_gol DESC
            ");
            $grup_siralama->execute([$current_user_id, $grup['id'], $current_user_id]);
            $grup_siralama = $grup_siralama->fetchAll();

            $gruplar_rankings[] = $grup_siralama;
        }

        // Create Final Four matches
        $ff_matches = createFinalFourMatches($gruplar_rankings);
        $group_count = count($gruplar_rankings);

        // Check for unsupported group counts
        if ($group_count != 2) {
            echo '<div class="alert alert-danger">';
            echo '<strong>Desteklenmeyen Grup Sayısı!</strong><br>';
            echo "Final Four sistemi sadece 2 grup formatını desteklemektedir.<br>";
            echo "Mevcut grup sayınız: <strong>{$group_count}</strong><br>";
            echo "Lütfen grup sayınızı 2 olacak şekilde düzenleyiniz.";
            echo '</div>';
            $ff_matches = [];
        } elseif (empty($ff_matches)) {
            echo '<div class="alert alert-warning">Final Four için yeterli takım bulunamadı. Her grupta en az 2 takım olmalı.</div>';
        }

        // Only proceed if we have valid Final Four matches
        if (!empty($ff_matches) && !isset($_POST['advance_to_final']) && !isset($_POST['show_final_winner'])) {
            renderTitle("Final Four Eşleşmeleri");
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
            foreach ($ff_matches as $match) {
                $red_team = $match['red']['takim_adi'];
                $blue_team = $match['blue']['takim_adi'];

                echo "<tr>
                        <td class='font-weight-bold'>$i</td>
                        <td>{$red_team}</td>
                        <td>{$blue_team}</td>
                        <td>
                            <div class='form-check winner-radio'>
                                <input class='form-check-input' type='radio' name='winner$i' value='{$red_team}' id='winner{$i}_red' required>
                                <label class='form-check-label' for='winner{$i}_red'>
                                    {$red_team}
                                </label>
                            </div>
                            <div class='form-check winner-radio'>
                                <input class='form-check-input' type='radio' name='winner$i' value='{$blue_team}' id='winner{$i}_blue' required>
                                <label class='form-check-label' for='winner{$i}_blue'>
                                    {$blue_team}
                                </label>
                            </div>
                        </td>
                    </tr>";
                $i++;
            }

            echo "</tbody></table>";

            echo "
                <div class='btn-group mb-2 mt-0'>
                    <button type='submit' class='btn btn-primary' name='advance_to_final'>Finale Geç</button>
                </div>
            ";

            echo "</form>";
        }

        // Finale geçme işlemi
        if (isset($_POST['advance_to_final'])) {
            $winners = [];
            $all_teams = [];
            
            // Final Four kazananlarını ve tüm takımları al
            for ($i = 1; $i <= count($ff_matches); $i++) {
                if (isset($_POST["winner$i"])) {
                    $winners[] = $_POST["winner$i"];
                }
            }

            // Tüm takımları topla
            foreach ($ff_matches as $match) {
                $all_teams[] = $match['red']['takim_adi'];
                $all_teams[] = $match['blue']['takim_adi'];
            }

            // Yarı final loserları bul
            $losers = array_diff($all_teams, $winners);
            $losers = array_values($losers);

            // Final eşleşmesini oluştur
            if (count($winners) == 2) {
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
                        <td>{$winners[0]}</td>
                        <td>{$winners[1]}</td>
                        <td>
                            <div class='form-check winner-radio'>
                                <input class='form-check-input' type='radio' name='winner_final' value='{$winners[0]}' id='winner_final_red' required>
                                <label class='form-check-label' for='winner_final_red'>
                                    {$winners[0]}
                                </label>
                            </div>
                            <div class='form-check winner-radio'>
                                <input class='form-check-input' type='radio' name='winner_final' value='{$winners[1]}' id='winner_final_blue' required>
                                <label class='form-check-label' for='winner_final_blue'>
                                    {$winners[1]}
                                </label>
                            </div>
                        </td>
                    </tr>";

                echo "<tr>
                        <td class='font-weight-bold'>3-4 Maçı</td>
                        <td>{$losers[0]}</td>
                        <td>{$losers[1]}</td>
                        <td>
                            <div class='form-check winner-radio'>
                                <input class='form-check-input' type='radio' name='winner_3_4' value='{$losers[0]}' id='winner_3_4_red' required>
                                <label class='form-check-label' for='winner_3_4_red'>
                                    {$losers[0]}
                                </label>
                            </div>
                            <div class='form-check winner-radio'>
                                <input class='form-check-input' type='radio' name='winner_3_4' value='{$losers[1]}' id='winner_3_4_blue' required>
                                <label class='form-check-label' for='winner_3_4_blue'>
                                    {$losers[1]}
                                </label>
                            </div>
                        </td>
                    </tr>";

                echo "</tbody></table>";

                foreach ($winners as $winner) {
                    echo "<input type='hidden' name='final_combined[]' value='{$winner}'>";
                }

                foreach ($losers as $loser) {
                    echo "<input type='hidden' name='third_combined[]' value='{$loser}'>";
                }

                echo "
                    <div class='btn-group mb-2 mt-0'>
                        <button type='submit' class='btn btn-success' name='show_final_winner'>Sonucu Göster</button>
                    </div>
                ";

                echo "</form>";
            }
        }

        if (isset($_POST['show_final_winner'])) {
            $final_winner = $_POST['winner_final'];
            $third_place_winner = $_POST['winner_3_4'];
            $final_combined = $_POST['final_combined'];
            $third_combined = $_POST['third_combined'];

            $final_loser = array_diff($final_combined, [$final_winner]);
            $final_loser = array_values($final_loser)[0];

            $third_place_loser = array_diff($third_combined, [$third_place_winner]);
            $third_place_loser = array_values($third_place_loser)[0];

            echo "
                <div class='text-center'>
                    <div class='mb-5' style='color: gold;'>
                        <span class='' style='font-size: 8rem; line-height: 125%;'>🏆 {$final_winner} 🏆</span>
                        <br/>
                        <span class='' style='font-size: 4rem; line-height: 90%;'>National Team</span>
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

                    <div class='mb-5' style='color: #8B4513;'>
                        <span class='' style='font-size: 3rem; line-height: 125%;'>{$third_place_loser}</span>
                        <br/>
                        <span class='' style='font-size: 1.5rem; line-height: 90%;'>Dördüncü</span>
                    </div>
                </div>"
            ;
        }
    ?>

    <script src="js/routing.js"></script>
</body>
</html>

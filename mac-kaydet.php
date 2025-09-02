<?php
    include_once "db.php";
    ini_set('session.gc_maxlifetime', 86400);
    session_set_cookie_params(86400);
    session_start();

    if (!isset($_SESSION['user_id'])) {
        // Redirect to login page if the user is not authenticated
        header('Location: login.php');
        exit;
    }
    
    $current_user_id = $_SESSION['user_id'];

    // get team1Id, team2Id, team1Score, team2Score from the request with isset validation and post method
    // return code and message on every situation also turkish messages

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (
            isset($_POST['team1Id']) && isset($_POST['team2Id']) &&
            isset($_POST['team1Score']) && isset($_POST['team2Score']) &&
            isset($_POST['team1Pen']) && isset($_POST['team2Pen']) &&
            isset($_POST['matchStage'])
        ) {
            $team1Id = $_POST['team1Id'];
            $team2Id = $_POST['team2Id'];
            $team1Score = $_POST['team1Score'];
            $team2Score = $_POST['team2Score'];
            $team1Pen = $_POST['team1Pen'];
            $team2Pen = $_POST['team2Pen'];
            $matchStage = $_POST['matchStage'];
            
            $team1Puan = 0;
            $team2Puan = 0;

            // include penalty scores to the total scores
            // $team1Score += $team1Pen;
            // $team2Score += $team2Pen;

            // find the winner, if they are equal, set winner to 0
            if (($team1Score + $team1Pen) > ($team2Score + $team2Pen)) {
                $winner = $team1Id;
                $team1Puan = 3;
            } elseif (($team1Score + $team1Pen) < ($team2Score + $team2Pen)) {
                $winner = $team2Id;
                $team2Puan = 3;
            } else {
                $winner = 0;
                $team1Puan = $team2Puan = 1;
            }

            // insert into the database
            $stmt = $db->prepare("
                INSERT INTO scoreboard (
                    takim1_id, takim1_gol, takim1_pen, takim1_puan,
                    takim2_id, takim2_gol, takim2_pen, takim2_puan,
                    kazanan_takim_id, asama, kullanici_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $team1Id,
                $team1Score,
                $team1Pen,
                $team1Puan,
                $team2Id,
                $team2Score,
                $team2Pen,
                $team2Puan,
                $winner,
                $matchStage,
                $current_user_id
            ]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(array("code" => 200, "message" => "Maç kaydedildi."));
            } else {
                echo json_encode(array("code" => 500, "message" => "Veritabanı hatası."));
            }
        } else {
            echo json_encode(array("code" => 400, "message" => "Eksik parametreler."));
        }
    } else {
        echo json_encode(array("code" => 405, "message" => "Geçersiz istek yöntemi."));
    }


?>
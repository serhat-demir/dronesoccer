<?php
    include_once 'db.php';
    ini_set('session.gc_maxlifetime', 86400);
    session_set_cookie_params(86400);
    
    session_start();

    if (!isset($_SESSION['user_id'])) {
        // Redirect to login page if the user is not authenticated
        header('Location: login.php');
        exit;
    }
    
    $current_user_id = $_SESSION['user_id'];

    // validation
    if (!isset($_GET['team1']) || !isset($_GET['team2']) || !isset($_GET['matchNumber']) || !isset($_GET['matchStage'])) {
        header("Location: index.php?error=1");
        exit;
    }

    $team1Id = $_GET['team1'];
    $team2Id = $_GET['team2'];
    $matchNumber = $_GET['matchNumber']; // Get match number from query string

    // Takım bilgilerini al ve kullanıcı sahipliğini kontrol et
    $team1Query = $db->prepare("SELECT * FROM takimlar WHERE id = ? AND kullanici_id = ?");
    $team1Query->execute([$team1Id, $current_user_id]);
    $team1Data = $team1Query->fetch(PDO::FETCH_ASSOC);
    
    $team2Query = $db->prepare("SELECT * FROM takimlar WHERE id = ? AND kullanici_id = ?");
    $team2Query->execute([$team2Id, $current_user_id]);
    $team2Data = $team2Query->fetch(PDO::FETCH_ASSOC);
    
    // Takımlar mevcut değilse veya kullanıcıya ait değilse hata ver
    if (!$team1Data || !$team2Data) {
        header("Location: index.php?error=1");
        exit;
    }
    
    $team1Name = $team1Data['takim_adi'];
    $team2Name = $team2Data['takim_adi'];
    
    // Eğer takımlar aynıysa hata ver
    if ($team1Id == $team2Id) {
        header("Location: index.php?error=2");
        exit;
    }

    // Maç aşamasını al
    $matchStage = $_GET['matchStage']; // Get match stage from query string

    // Eğer aşama boşsa varsayılan olarak "Ön Eleme" ayarla
    if (empty($matchStage)) {
        $matchStage = "Ön Eleme";
    }
?>

<!doctype html>
<html lang="tr">
<head>
    <title>Drone Soccer Türkiye - Scoreboard</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/fractional-grid.css">
    <style>
        html, body {
            overflow: hidden;
            background-color: #f8f9fa;
            font-size: 2vw;
        }

        /* Takım sütunları */
        .team-column {
            padding: 2vw;
            color: white;
            flex: 1;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .team1 { background-color: red; }
        .team2 { background-color: blue; }

        /* Sayaç */
        .counter {
            font-size: 15vw;
            text-align: center;
            /* -webkit-text-stroke-width: 3px; */
            /* -webkit-text-stroke-color: #dea600; */
            line-height: 100%;
        }

        /* Takım isimleri ve skor */
        h3, p {
            font-size: 3vw;
            margin-bottom: 0;
        }

        .score {
            font-size: 22vw;
            padding-bottom: 2.5vw;
            line-height: 90%;
        }

        /* Round başlığı */
        .round {
            font-size: 4vw;
        }

        /* Önceki Maç sonucu */
        .previous-match {
            font-size: 2vw;
            color: #6c757d;
        }

        .previous-match-title {
            font-size: 2.5vw;
            color: #6c757d;
        }

        /* Butonlar */
        .timer-button {
            width: 100%;
            font-size: 1.5vw;
        }

        .reset-button, .start-button, .end-button {
            width: 33%;
            font-size: 1.25vw;
        }

        /* Çizgi */
        .line {
            border: 0;
            border-top: 1px solid white;
            margin: 10px 0;
            width: 100%;
        }

        /* Kazanan yazısı */
        #winnerText {
            position: absolute !important;
            left: 50% !important;
            top: 20% !important;
            width: 150% !important;
            height: 50% !important;
            z-index: 99999;
            transform: translateX(-50%);
            font-size: 4vw !important;
        }

        /* Penalti metni */
        .penText {
            font-size: 3vw !important;
            /* color: #ffc107 !important; */
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body>

<div class="">
    <div class="row d-flex" style="flex-wrap: nowrap;">
        <!-- Sol Kolon - Takım 1 -->
        <div class="col-3-half team-column team1">
            <h3 id="team1Name" class="text-center d-flex justify-content-center align-items-end" style="height: 120px !important;">
                <?= $team1Name ?>
            </h3>
            <hr class="line">
            <h4 id="team1Score" class="score my-0">0</h4>
            <hr class="line">
            <p class="penText text-center">Penaltı <br> <span id="team1Pens" class="" style="font-size: 2em !important; line-height: 75% !important;">0</span></p>
        </div>

        <!-- Orta Kolon - Sayaç ve Butonlar -->
        <div class="col-5 mx-0 px-0">
            <h4 class="mb-2 mx-0 mb-0 round text-center font-weight-bold text-success bg-success text-light" style="line-height: 150%;">
                <?php if($matchStage != "Ön Eleme"): ?>
                    <?= $matchStage ?>
                <?php else: ?>
                    Maç <span id="roundCount"><?= $matchNumber ?></span>
                <?php endif; ?>
            </h4>

            <!-- <h4 class="mb-2 mx-0 mb-0 round text-center font-weight-bold text-success text-light"
                style="line-height: 150%; background: linear-gradient(-45deg, #28a745, #218838, #1e7e34, #28a745);
                    background-size: 400% 400%; animation: gradientBG 10s ease infinite;">
                <?php if($matchStage != "Ön Eleme"): ?>
                    <?= $matchStage ?>
                <?php else: ?>
                    Maç <span id="roundCount"><?= $matchNumber ?></span>
                <?php endif; ?>
            </h4> -->
            
            <div class="px-3">
                <div class="counter text-warning font-weight-bold" style="margin-top: -25px; color: #000 !important; border: 0px solid black !important;" id="timer">03:00</div>
                <hr class="line border-dark mt-0"> 

                <div class="text-center mb-1">
                    <img src="logo.svg" alt="Drone Soccer" style="max-width: 50%; height: auto;" class="ml-3">
                </div>

                <div class="button-container">
                    <button id="timerButton" class="btn btn-secondary py-1 timer-button font-weight-bold" disabled>Stop</button>
                </div>

                <!-- <p class="previous-match-title font-weight-bold text-center">Önceki Maç Sonucu</p>
                <div class="d-flex justify-content-center">
                    <div class="text-center mr-5">
                        <p class="previous-match font-weight-bold" id="previousMatchTeam1">Takım 1</p>
                        <p class="previous-match font-weight-bold" id="previousMatchTeam1Score">0</p>
                    </div>
                    <div class="text-center">
                        <p class="previous-match font-weight-bold" id="previousMatchTeam2">Takım 2</p>
                        <p class="previous-match font-weight-bold" id="previousMatchTeam2Score">0</p>
                    </div>
                </div> -->

                <h3 class="text-center font-weight-bold mt-2 d-none" id="kazanan">
                    <span id="kazananIsim"></span>
                </h3>

                <div class="bottom-center" style="position: absolute; bottom: 1%; left: 50%; transform: translateX(-50%); width: 100%; padding: 0 2vw;">
                    <div class="row no-gutters">
                        <div class="col-4 pr-1">
                            <button id="resetButton" class="btn btn-secondary reset-button font-weight-bold w-100" disabled>Reset</button>
                        </div>
                        <div class="col-4 px-1">
                            <button id="endButton" class="btn btn-danger text-light end-button font-weight-bold w-100" disabled>Maçı Bitir</button>
                        </div>
                        <div class="col-4 pl-1">
                            <button id="startButton" class="btn btn-success start-button font-weight-bold w-100">Başlat</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sağ Kolon - Takım 2 -->
        <div class="col-3-half team-column team2">
            <h3 id="team2Name" class="text-center d-flex justify-content-center align-items-end" style="height: 120px !important;">
                <?= $team2Name ?>
            </h3>
            <hr class="line">
            <h4 id="team2Score" class="score my-0">0</h4>
            <hr class="line">
            <p class="penText text-center">Penaltı <br> <span id="team2Pens" class="" style="font-size: 2em !important; line-height: 75% !important;">0</span></p>
        </div>
    </div>
    
    <audio id="bitisSes" src="bitis.mp3"></audio>
</div>

<script>
let timerInterval;
let seconds = 180;
let gameActive = false;
let matchStage = "<?= $matchStage ?>"; // Get match stage from PHP variable

let team1Score = 0;
let team2Score = 0;

let team1Name = "<?= $team1Name ?>";
let team2Name = "<?= $team2Name ?>";

let team1Id = <?= $team1Id ?>;
let team2Id = <?= $team2Id ?>;

let team1Pen = 0;
let team2Pen = 0;

let soundPlayed = false; // Flag to track if the sound has been played

let x = false;
let anim;

function toggleWinnerAnim() {
    anim = setInterval(() => {
        if (x) {
            document.getElementById('kazanan').classList.remove('d-none');
        } else {
            document.getElementById('kazanan').classList.add('d-none');
        }

        x = !x;
    }, 500);
}

function toggleButtons(state) {
    if (state == "start") {
        document.getElementById('timerButton').disabled = false;
        document.getElementById('startButton').disabled = true;
        document.getElementById('endButton').disabled = true;
        document.getElementById('resetButton').disabled = true;
    } else if (state == "reset") {
        document.getElementById('startButton').disabled = false;
        document.getElementById('endButton').disabled = true;
        document.getElementById('resetButton').disabled = true;

        document.getElementById('timerButton').innerText = 'Stop';
        document.getElementById('timerButton').disabled = true;
        document.getElementById('timerButton').classList.add('btn-secondary');
        document.getElementById('timerButton').classList.remove('btn-success');
    } else if (state == "end") {
        document.getElementById('startButton').disabled = true;
        document.getElementById('endButton').disabled = true;
        document.getElementById('resetButton').disabled = false;

        document.getElementById('timerButton').innerText = 'Stop';
        document.getElementById('timerButton').disabled = true;
        document.getElementById('timerButton').classList.add('btn-secondary');
        document.getElementById('timerButton').classList.remove('btn-success');
    } else if (state == "timerStart") {
        document.getElementById('startButton').disabled = true;
        document.getElementById('endButton').disabled = true;
        document.getElementById('resetButton').disabled = true;

        document.getElementById('timerButton').innerText = 'Stop';
        document.getElementById('timerButton').classList.add('btn-secondary');
        document.getElementById('timerButton').classList.remove('btn-success');
    } else if (state == "timerStop") {
        document.getElementById('startButton').disabled = true;
        document.getElementById('endButton').disabled = false;
        document.getElementById('resetButton').disabled = false;

        document.getElementById('timerButton').innerText = 'Start';
        document.getElementById('timerButton').classList.add('btn-success');
        document.getElementById('timerButton').classList.remove('btn-secondary');
    }
}

document.addEventListener('keyup', function(event) {
    if (gameActive) {
        switch (event.key) {
            // a ve s ile Takım 1 Skoru ve Penaltı artırma
            case 'a':
                team1Score++;
                document.getElementById('team1Score').innerText = team1Score;
                break;
            case 's':
                team1Pen++;
                document.getElementById('team1Pens').innerText = team1Pen;
                break;

            // - ve + ile Takım 2 Skoru ve Penaltı artırma
            case '-':
                team2Score++;
                document.getElementById('team2Score').innerText = team2Score;
                break;
            case '+':
                team2Pen++;
                document.getElementById('team2Pens').innerText = team2Pen;
                break;
            
            // v ve b ile Takım 1 Skoru ve Penaltı azaltma
            case 'v':
                if (team1Score > 0) {
                    team1Score--;
                    document.getElementById('team1Score').innerText = team1Score;
                }
                break;
            case 'b':
                if (team1Pen > 0) {
                    team1Pen--;
                    document.getElementById('team1Pens').innerText = team1Pen;
                }
                break;

            // n ve m ile Takım 2 Skoru ve Penaltı azaltma
            case 'n':
                if (team2Score > 0) {
                    team2Score--;
                    document.getElementById('team2Score').innerText = team2Score;
                }
                break;
            case 'm':
                if (team2Pen > 0) {
                    team2Pen--;
                    document.getElementById('team2Pens').innerText = team2Pen;
                }
                break;
        }
    } else {
        if (event.key === 'Escape') {
            window.open('sitemap.php', '_blank');
        }
    }
});

// Oyunu başlatma butonu
document.getElementById('startButton').addEventListener('click', function() {
    if (!gameActive) {
        gameActive = true;
        toggleButtons("start");
        startTimer();
    }
});

// Hızlı bitirme butonu
document.getElementById('endButton').addEventListener('click', function() {
    if (!gameActive) {
        clearInterval(timerInterval);
        timerInterval = null;
        gameActive = false;
        seconds = 180;

        let kazananIsim = ((team1Score + team1Pen) > (team2Score + team2Pen)) ? ("Kazanan \n" + team1Name) : ((team1Score + team1Pen) < (team2Score + team2Pen)) ? ("Kazanan \n" + team2Name) : "Berabere";
        document.getElementById('kazananIsim').innerText = kazananIsim;
        toggleWinnerAnim();

        toggleButtons("end");
        saveMatch();

        if (!soundPlayed) {
            const bitisSes = document.getElementById('bitisSes');
            bitisSes.play();
            soundPlayed = true; // Set the flag to true

            // Delay the alert to ensure the sound finishes playing
            bitisSes.onended = function() {
                // alert("Maç bitti");
                soundPlayed = false;
            };
        }
    }
});

// Oyunun sayacını başlatma fonksiyonu
function startTimer() {
    timerInterval = setInterval(function() {
        seconds--;
        let minutes = Math.floor(seconds / 60);
        let remainingSeconds = seconds % 60;
        document.getElementById('timer').innerText = (minutes < 10 ? '0' : '') + minutes + ':' + (remainingSeconds < 10 ? '0' : '') + remainingSeconds;

        if (seconds <= 0) {
            clearInterval(timerInterval);
            timerInterval = null; // Ensure the interval is cleared
            gameActive = false;
            seconds = 180;
            
            let kazananIsim = ((team1Score + team1Pen) > (team2Score + team2Pen)) ? ("Kazanan \n" + team1Name) : ((team1Score + team1Pen) < (team2Score + team2Pen)) ? ("Kazanan \n" + team2Name) : "Berabere";
            document.getElementById('kazananIsim').innerText = kazananIsim;
            toggleWinnerAnim();

            toggleButtons("end");
            saveMatch(); // Save the match when the timer ends
            
            // Play the sound only once
            if (!soundPlayed) {
                const bitisSes = document.getElementById('bitisSes');
                bitisSes.play();
                soundPlayed = true; // Set the flag to true

                // Delay the alert to ensure the sound finishes playing
                bitisSes.onended = function() {
                    // alert("Süre doldu! Maç sona erdi.");
                    soundPlayed = false;
                };
            }
        }
    }, 1000);
}

// Sayacı durdurup başlatma butonu
document.getElementById('timerButton').addEventListener('click', function() {
    if (gameActive) {
        clearInterval(timerInterval);
        gameActive = false;
        toggleButtons("timerStop");
    } else {
        gameActive = true;
        toggleButtons("timerStart");
        startTimer();
    }
});

// Reset butonu
document.getElementById('resetButton').addEventListener('click', function() {
    clearInterval(timerInterval);
    timerInterval = null; // Ensure the interval is cleared
    gameActive = false;
    seconds = 180; // Reset to 3 minutes
    team1Score = 0;
    team2Score = 0;
    team1Pen = 0;
    team2Pen = 0;
    document.getElementById('team1Score').innerText = team1Score;
    document.getElementById('team2Score').innerText = team2Score;
    document.getElementById('team1Pens').innerText = team1Pen;
    document.getElementById('team2Pens').innerText = team2Pen;
    document.getElementById('timer').innerText = '03:00';

    document.getElementById('kazanan').classList.add('d-none');
    document.getElementById('kazananIsim').innerText = '';
    clearInterval(anim);

    toggleButtons("reset");
});

// Maç kaydetme fonksiyonu
function saveMatch() {
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "mac-kaydet.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    const postData =
        "team1Id=" + team1Id +
        "&team2Id=" + team2Id +
        "&team1Score=" + team1Score +
        "&team2Score=" + team2Score +
        "&team1Pen=" + team1Pen +
        "&team2Pen=" + team2Pen +
        "&matchNumber=" + encodeURIComponent(<?= json_encode($matchNumber) ?>) +
        "&matchStage=" + encodeURIComponent("<?= $matchStage ?>");

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            // alert(response.message);
        }
    };
    xhr.send(postData);
}
</script>

<script src="js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>
</html>

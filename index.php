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
?>

<!doctype html>
<html lang="tr">
  <head>
    <title>Drone Soccer Türkiye - Scoreboard</title>
    
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">

    <style>
      html, body {
          overflow: hidden;
      }

      .custom-input {
          font-size: 2vw; /* Ekran genişliğine göre yazı boyutu */
      }

      .custom-text {
          font-size: 3vw; /* Ekran genişliğine göre yazı boyutu */
      }

      .sitemap-btn {
          position: fixed;
          bottom: 20px;
          right: 20px;
          z-index: 1000;
          padding: 10px 20px;
          font-size: 22px;
          font-weight: bold;
          box-shadow: 0 4px 8px rgba(0,0,0,0.3);
      }
    </style>
  </head>
  <body class="bg-dark my-4">
    <?php 
      if (isset($_GET['error'])) {
        if ($_GET['error'] == 1) {
          echo '<div class="alert alert-danger text-center w-50 mx-auto" role="alert">Lütfen bütün alanları doldurun!</div>';
        } elseif ($_GET['error'] == 2) {
          echo '<div class="alert alert-danger text-center w-50 mx-auto" role="alert">İki takım da aynı olamaz!</div>';
        }
      }
    ?>

    <h1 class="display-3 font-weight-bold text-primary text-center">Drone Soccer</h1>

    <div class="row mt-5 mb-3">
        <div class="col-md-1"></div>

        <div class="col-4">
            <h2 class="text-light custom-text" style="color: red !important;">Kırmızı Takım:</h2>
            <select id="team1" class="form-control custom-input">
              <option value="0">Takım Seçilmedi</option>
              <?php
                include 'db.php';

                $takimlar = $db->prepare("SELECT * FROM takimlar WHERE kullanici_id = ?");
                $takimlar->execute([$current_user_id]);
                $takimlar = $takimlar->fetchAll();
                foreach ($takimlar as $takim) {
                  echo '
                    <option value="'. $takim['id'] .'">'. $takim['takim_adi'] .'</option>
                  ';
                }
              ?>
            </select>
        </div>

        <div class="col-2 text-center">
            <h2 class="text-light custom-text">Maç</h2>
            <input type="number" id="matchNumber" class="form-control custom-input" placeholder="Maç No">

            <h2 class="text-light custom-text mt-2">Aşama</h2>
            <select id="matchStage" class="form-control custom-input">
                <option value="Ön Eleme" selected>Ön Eleme</option>
                <option value="Çeyrek Final">Çeyrek Final</option>
                <option value="Yarı Final">Yarı Final</option>
                <option value="3-4 Maçı">3-4 Maçı</option>
                <option value="Final">Final</option>
                <option value="Final Four">Final Four</option>
            </select>
        </div>

        <div class="col-4">
            <h2 class="text-light custom-text" style="color: blue !important;">Mavi Takım:</h2>
            <select id="team2" class="form-control custom-input">
              <option value="0">Takım Seçilmedi</option>
              <?php
                $takimlar = $db->prepare("SELECT * FROM takimlar WHERE kullanici_id = ?");
                $takimlar->execute([$current_user_id]);
                $takimlar = $takimlar->fetchAll();
                foreach ($takimlar as $takim) {
                  echo '
                    <option value="'. $takim['id'] .'">'. $takim['takim_adi'] .'</option>
                  ';
                }
              ?>
            </select>
        </div>

        <div class="col-1"></div>
    </div>

    <div class="text-center container">
        <a name="" id="submitBtn" class="btn btn-success btn-lg px-5 font-weight-bold custom-input" href="#" role="button">Maçı Başlat</a>
    </div>

    <!-- Sitemap Button -->
    <button id="sitemapBtn" class="btn btn-primary sitemap-btn" style="right: 165px;">
        <i class="fas fa-sitemap"></i> Site Haritası
    </button>
    
    <!-- Logout Button -->
    <button id="logoutBtn" class="btn btn-danger sitemap-btn">
        <i class="fas fa-sign-out-alt"></i> Çıkış Yap
    </button>
    
    <script src="js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  
    <script>
      document.getElementById('submitBtn').addEventListener('click', function(e) {
          e.preventDefault(); // Butonun varsayılan davranışını durdur
          var team1 = document.getElementById('team1').value; // Seçilen takım 1'in ID'si
          var team2 = document.getElementById('team2').value; // Seçilen takım 2'nin ID'si
          var matchNumber = document.getElementById('matchNumber').value; // Girilen maç numarası
          var matchStage = document.getElementById('matchStage').value; // Seçilen aşama
          
          // Seçilen takımların ve maç numarasının geçerli olup olmadığını kontrol et
          if (team1 == "0" || team2 == "0") {
              alert("Lütfen her iki takımı da seçin!");
              return; // Takımlar seçilmemişse işlemi durdur
          }
          if (!matchNumber) {
              alert("Lütfen maç numarasını girin!");
              return; // Maç numarası girilmemişse işlemi durdur
          }
          
          // URL'ye parametre ekle
          window.location.href = 'score.php?team1=' + team1 + '&team2=' + team2 + '&matchNumber=' + matchNumber + '&matchStage=' + matchStage;
        });

      // Logout button functionality
      document.getElementById('logoutBtn').addEventListener('click', function(e) {
          e.preventDefault();
          if (confirm('Oturumu kapatmak istediğinize emin misiniz?')) {
              window.location.href = 'logout.php';
          }
      });

      // Sitemap button functionality
      document.getElementById('sitemapBtn').addEventListener('click', function(e) {
          e.preventDefault();
          window.open('sitemap.php', '_blank');
      });
    </script>

    <script src="js/routing.js"></script>
  </body>
</html>
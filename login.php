<?php
require_once 'db.php';
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error_message = '';
$success_message = '';

// Handle Registration
if (isset($_POST['register'])) {
    $kullanici_adi = trim($_POST['kullanici_adi'] ?? '');
    $sifre = trim($_POST['sifre'] ?? '');
    $sifre_tekrar = trim($_POST['sifre_tekrar'] ?? '');
    $kurum = trim($_POST['kurum'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');

    if (empty($kullanici_adi) || empty($sifre) || empty($sifre_tekrar) || empty($kurum) || empty($telefon)) {
        $error_message = 'Tüm alanları doldurmanız gerekiyor.';
    } elseif (strlen($kullanici_adi) > 30 || strlen($sifre) > 30) {
        $error_message = 'Kullanıcı adı ve şifre en fazla 30 karakter olabilir.';
    } elseif (strlen($sifre) < 3) {
        $error_message = 'Şifre en az 3 karakter olmalıdır.';
    } elseif ($sifre !== $sifre_tekrar) {
        $error_message = 'Şifreler eşleşmiyor.';
    } elseif (strlen($kurum) > 255) {
        $error_message = 'Kurum adı en fazla 255 karakter olabilir.';
    } elseif (strlen($telefon) > 20) {
        $error_message = 'Telefon numarası en fazla 20 karakter olabilir.';
    } elseif (!preg_match('/^[0-9 ]+$/', $telefon)) {
        $error_message = 'Telefon numarası yalnızca rakam ve boşluk içerebilir.';
    } else {
        // Check if username already exists
        $check_stmt = $db->prepare("SELECT id FROM kullanicilar WHERE kullanici_adi = ?");
        $check_stmt->execute([$kullanici_adi]);
        
        if ($check_stmt->rowCount() > 0) {
            $error_message = 'Bu kullanıcı adı zaten kullanılıyor.';
        } else {
            // Create new user with transaction
            try {
                $db->beginTransaction();

                $hashed_password = password_hash($sifre, PASSWORD_DEFAULT);
                $insert_stmt = $db->prepare("INSERT INTO kullanicilar (kullanici_adi, sifre, is_admin, kurum, telefon, is_approved) VALUES (?, ?, 0, ?, ?, 0)");
                $insert_success = $insert_stmt->execute([$kullanici_adi, $hashed_password, $kurum, $telefon]);

                if (!$insert_success) {
                    $db->rollBack();
                    $error_message = 'Hesap oluşturulurken bir hata oluştu.';
                } else {
                    // Send email to website owners/admins via SMTP using PHPMailer
                    // deleted email part temporary
                
                    $db->commit();
                    $success_message = 'Hesabınız oluşturuldu. Onay için yöneticiye iletildi.';
                }
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                $error_message = 'Hesap oluşturulurken bir hata oluştu.';
            }
        }
    }
}

// Handle Login
if (isset($_POST['login'])) {
    $kullanici_adi = trim($_POST['kullanici_adi'] ?? '');
    $sifre = trim($_POST['sifre'] ?? '');
    
    if (empty($kullanici_adi) || empty($sifre)) {
        $error_message = 'Kullanıcı adı ve şifre alanları boş bırakılamaz.';
    } elseif (strlen($kullanici_adi) > 30 || strlen($sifre) > 30) {
        $error_message = 'Kullanıcı adı ve şifre en fazla 30 karakter olabilir.';
    } else {
        // Database query
        $stmt = $db->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = ?");
        $stmt->execute([$kullanici_adi]);
        $user = $stmt->fetch();
        
        if ($user) {
            if (!$user['is_approved']) {
                $error_message = 'Hesabınız henüz yönetici tarafından onaylanmadı.';
            } else {
                // First try password_verify (for hashed passwords)
                if (password_verify($sifre, $user['sifre'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['kullanici_adi'] = $user['kullanici_adi'];
                    header('Location: index.php');
                    exit;
                }
                // If password_verify fails, try direct comparison for legacy passwords
                elseif ($user['sifre'] === $sifre) {
                    // Login successful, update password to hashed version
                    $hashed_password = password_hash($sifre, PASSWORD_DEFAULT);
                    $update_stmt = $db->prepare("UPDATE kullanicilar SET sifre = ? WHERE id = ?");
                    $update_stmt->execute([$hashed_password, $user['id']]);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['kullanici_adi'] = $user['kullanici_adi'];
                    header('Location: index.php');
                    exit;
                } else {
                    $error_message = 'Kullanıcı adı veya şifre hatalı.';
                }
            }
        } else {
            $error_message = 'Kullanıcı adı veya şifre hatalı.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drone Soccer Türkiye - Oturum Aç / Kayıt Ol</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .auth-card {
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        .form-toggle {
            background: #007bff;
            color: white;
            padding: 20px;
        }
        .toggle-btn {
            background: transparent;
            border: 2px solid white;
            color: white;
            padding: 8px 20px;
            margin: 0 5px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .toggle-btn.active {
            background: white;
            color: #007bff;
        }
        .form-container {
            padding: 40px;
            background: white;
        }
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <img src="logo.svg" alt="Drone Soccer Logo" style="max-width: 120px; height: auto;">
                    </div>
                </div>
            </div>
            <h1 class="text-center display-4 pb-4 font-weight-bold text-primary">Drone Soccer Türkiye</h1>
            <div class="row justify-content-center">
                <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                    <div class="auth-card">
                        <!-- Form Toggle Header -->
                        <div class="form-toggle text-center">
                            <h3 class="mb-3">Hoş Geldiniz</h3>
                            <div>
                                <button type="button" class="toggle-btn active" id="loginToggle">Giriş Yap</button>
                                <button type="button" class="toggle-btn" id="registerToggle">Kayıt Ol</button>
                            </div>
                        </div>

                        <div class="form-container">
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo htmlspecialchars($error_message); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($success_message): ?>
                                <div class="alert alert-success" role="alert">
                                    <?php echo htmlspecialchars($success_message); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Login Form -->
                            <div id="loginForm" class="form-section active">
                                <form method="POST" action="" novalidate>
                                    <div class="form-group mb-3">
                                        <label for="login_kullanici_adi" class="form-label">Kullanıcı Adı</label>
                                        <input type="text" class="form-control" id="login_kullanici_adi" name="kullanici_adi" 
                                               maxlength="30" required value="admin">
                                        <div class="invalid-feedback">
                                            Kullanıcı adı gereklidir ve en fazla 30 karakter olabilir.
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="login_sifre" class="form-label">Şifre</label>
                                        <input type="password" class="form-control" id="login_sifre" name="sifre" 
                                               maxlength="30" value="1234" required>
                                        <div class="invalid-feedback">
                                            Şifre gereklidir ve en fazla 30 karakter olabilir.
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="login" class="btn btn-primary w-100 py-2">Giriş Yap</button>
                                </form>
                            </div>

                            <!-- Register Form -->
                            <div id="registerForm" class="form-section">
                                <form method="POST" action="" novalidate>
                                    <div class="form-group mb-3">
                                        <label for="register_kullanici_adi" class="form-label">Kullanıcı Adı</label>
                                        <input type="text" class="form-control" id="register_kullanici_adi" name="kullanici_adi" 
                                               maxlength="30" required>
                                        <div class="invalid-feedback">
                                            Kullanıcı adı gereklidir ve en fazla 30 karakter olabilir.
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label for="register_sifre" class="form-label">Şifre</label>
                                        <input type="password" class="form-control" id="register_sifre" name="sifre" 
                                               maxlength="30" minlength="3" required>
                                        <div class="invalid-feedback">
                                            Şifre en az 3, en fazla 30 karakter olabilir.
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="register_sifre_tekrar" class="form-label">Şifre Tekrar</label>
                                        <input type="password" class="form-control" id="register_sifre_tekrar" name="sifre_tekrar" 
                                               maxlength="30" required>
                                        <div class="invalid-feedback">
                                            Şifreler eşleşmelidir.
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="register_kurum" class="form-label">Kurum</label>
                                        <input type="text" class="form-control" id="register_kurum" name="kurum" maxlength="255" required>
                                        <div class="invalid-feedback">
                                            Lütfen kurum adını giriniz (en fazla 255 karakter).
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="register_telefon" class="form-label">Telefon Numarası</label>
                                        <input type="text" class="form-control" id="register_telefon" name="telefon" maxlength="20" required pattern="^[0-9 ]+$">
                                        <div class="invalid-feedback">
                                            Lütfen telefon numarası giriniz (en fazla 20 karakter, sadece rakam ve boşluk).
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="register" class="btn btn-primary w-100 py-2">Kayıt Ol</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // Form toggle functionality
        document.getElementById('loginToggle').addEventListener('click', function() {
            showLogin();
        });

        document.getElementById('registerToggle').addEventListener('click', function() {
            showRegister();
        });

        function showLogin() {
            document.getElementById('loginForm').classList.add('active');
            document.getElementById('registerForm').classList.remove('active');
            document.getElementById('loginToggle').classList.add('active');
            document.getElementById('registerToggle').classList.remove('active');
        }

        function showRegister() {
            document.getElementById('registerForm').classList.add('active');
            document.getElementById('loginForm').classList.remove('active');
            document.getElementById('registerToggle').classList.add('active');
            document.getElementById('loginToggle').classList.remove('active');
        }

        // Show register form if there was a registration error
        <?php if (isset($_POST['register'])): ?>
            showRegister();
        <?php endif; ?>

        // Client-side validation
        (function() {
            'use strict';
            
            const forms = document.querySelectorAll('form');
            
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    let isValid = true;

                    if (form.querySelector('[name="login"]')) {
                        // Login form validation
                        const kullaniciAdi = document.getElementById('login_kullanici_adi');
                        const sifre = document.getElementById('login_sifre');
                        
                        kullaniciAdi.setCustomValidity('');
                        sifre.setCustomValidity('');
                        
                        if (!kullaniciAdi.value.trim()) {
                            kullaniciAdi.setCustomValidity('Kullanıcı adı gereklidir.');
                            isValid = false;
                        } else if (kullaniciAdi.value.length > 30) {
                            kullaniciAdi.setCustomValidity('Kullanıcı adı en fazla 30 karakter olabilir.');
                            isValid = false;
                        }
                        
                        if (!sifre.value.trim()) {
                            sifre.setCustomValidity('Şifre gereklidir.');
                            isValid = false;
                        } else if (sifre.value.length > 30) {
                            sifre.setCustomValidity('Şifre en fazla 30 karakter olabilir.');
                            isValid = false;
                        }
                    } else if (form.querySelector('[name="register"]')) {
                        // Register form validation
                        const kullaniciAdi = document.getElementById('register_kullanici_adi');
                        const sifre = document.getElementById('register_sifre');
                        const sifreTekrar = document.getElementById('register_sifre_tekrar');
                        const kurumInput = document.getElementById('register_kurum');
                        const telefonInput = document.getElementById('register_telefon');
                        
                        kullaniciAdi.setCustomValidity('');
                        sifre.setCustomValidity('');
                        sifreTekrar.setCustomValidity('');
                        kurumInput.setCustomValidity('');
                        telefonInput.setCustomValidity('');
                        
                        if (!kullaniciAdi.value.trim()) {
                            kullaniciAdi.setCustomValidity('Kullanıcı adı gereklidir.');
                            isValid = false;
                        } else if (kullaniciAdi.value.length > 30) {
                            kullaniciAdi.setCustomValidity('Kullanıcı adı en fazla 30 karakter olabilir.');
                            isValid = false;
                        }
                        
                        if (!sifre.value.trim()) {
                            sifre.setCustomValidity('Şifre gereklidir.');
                            isValid = false;
                        } else if (sifre.value.length < 3) {
                            sifre.setCustomValidity('Şifre en az 3 karakter olmalıdır.');
                            isValid = false;
                        } else if (sifre.value.length > 30) {
                            sifre.setCustomValidity('Şifre en fazla 30 karakter olabilir.');
                            isValid = false;
                        }
                        
                        if (!sifreTekrar.value.trim()) {
                            sifreTekrar.setCustomValidity('Şifre tekrarı gereklidir.');
                            isValid = false;
                        } else if (sifre.value !== sifreTekrar.value) {
                            sifreTekrar.setCustomValidity('Şifreler eşleşmiyor.');
                            isValid = false;
                        }

                        if (!kurumInput.value.trim()) {
                            kurumInput.setCustomValidity('Kurum adı gereklidir.');
                            isValid = false;
                        } else if (kurumInput.value.length > 255) {
                            kurumInput.setCustomValidity('Kurum adı en fazla 255 karakter olabilir.');
                            isValid = false;
                        }

                        if (!telefonInput.value.trim()) {
                            telefonInput.setCustomValidity('Telefon numarası gereklidir.');
                            isValid = false;
                        } else if (telefonInput.value.length > 20) {
                            telefonInput.setCustomValidity('Telefon numarası en fazla 20 karakter olabilir.');
                            isValid = false;
                        } else if (!/^[0-9 ]+$/.test(telefonInput.value)) {
                            telefonInput.setCustomValidity('Telefon numarası yalnızca rakam ve boşluk içerebilir.');
                            isValid = false;
                        }
                    }
                    
                    if (!isValid || !form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
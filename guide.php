<?php
    ini_set('session.gc_maxlifetime', 86400);
    session_set_cookie_params(86400);
    session_start();

    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    include 'db.php';
    $current_user_id = $_SESSION['user_id'];
    $admin_check = $db->prepare("SELECT is_admin FROM kullanicilar WHERE id = ?");
    $admin_check->execute([$current_user_id]);
    $user_data = $admin_check->fetch(PDO::FETCH_ASSOC);

    if (!$user_data || $user_data['is_admin'] != 2) {
        // Only super admin (is_admin == 2) can access
        header('Location: index.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drone Soccer Türkiye - Yetki Sistemi</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        .guide-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 16px;
        }
        .perm-table th, .perm-table td {
            text-align: center;
            vertical-align: middle;
        }
        .perm-table th {
            background: #007bff;
            color: #fff;
        }
        .perm-table tr td:first-child {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10">
                <div class="bg-light rounded shadow-sm p-4">
                    <div class="guide-title text-center">Yetki Sistemi</div>
                    <p>
                        Bu sayfa yalnızca <strong>Üst Seviye Yönetici</strong> tarafından görüntülenebilir.<br>
                        Aşağıda sistemdeki kullanıcı rollerinin yetkileri özetlenmiştir:
                    </p>
                    <table class="table table-bordered perm-table mt-4 mb-4">
                        <thead>
                            <tr>
                                <th>Yetki</th>
                                <th>Normal Kullanıcı</th>
                                <th>Yönetici</th>
                                <th>Üst Seviye Yönetici</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Turnuva Düzenleme <br> <small>Scoreboard Kullanımı</small></td>
                                <td>✔️</td>
                                <td>✔️</td>
                                <td>✔️</td>
                            </tr>
                            <tr>
                                <td>Kullanıcıları Görüntüleme</td>
                                <td>❌</td>
                                <td>✔️ <br> <small>sadece normal kullanıcıları ve kendisini görebilir</small></td>
                                <td>✔️ <br><small>tüm kullanıcıları görebilir</small></td>
                            </tr>
                            <tr>
                                <td>Kullanıcı Ekleme</td>
                                <td>❌</td>
                                <td>✔️</td>
                                <td>✔️</td>
                            </tr>
                            <tr>
                                <td>Kullanıcı Yönetimi</td>
                                <td>❌</td>
                                <td>✔️ <br><small>sadece normal kullanıcıları ve kendi hesabını yönetebilir</small></td>
                                <td>✔️ <br><small>tüm kullanıcıları yönetebilir, <span style="color:#dc3545;">diğer üst yöneticiler hariç</span></small></td>
                            </tr>
                            <tr>
                                <td>Kullanıcı Onayları</td>
                                <td>❌</td>
                                <td>✔️ <br><small>sadece normal kullanıcıları onaylayıp reddedebilir</small></td>
                                <td>✔️ <br><small>tüm kullanıcıları onaylayıp reddedebilir</small></td>
                            </tr>
                            <tr>
                                <td>Yönetici Oluşturma</td>
                                <td>❌</td>
                                <td>❌</td>
                                <td>✔️</small></td>
                            </tr>
                            <tr>
                                <td>Üst Yönetici Oluşturma</td>
                                <td>❌</td>
                                <td>❌</td>
                                <td>❌</td>
                            </tr>
                        </tbody>
                    </table>
                    <div>
                        <strong>Açıklamalar:</strong>
                        <ul>
                            <li><b>Normal Kullanıcı:</b> Sadece turnuva düzenlemek için scoreboardı kullanabilir.</li>
                            <li><b>Yönetici:</b> Sisteme kullanıcı ekleyebilir/düzenleyebilir/silebilir, kullanıcıları onaylayabilir.</li>
                            <li><b>Üst Seviye Yönetici:</b> Tüm kullanıcıları yönetebilir, sisteme yeni yönetici ekleyip çıkartabilir.</li>
                            <li><b style="color:#dc3545;">Yeni üst yönetici oluşturulamaz. Sadece şu anda bulunduğunuz hesap üst yöneticidir.</b></li>
                        </ul>
                    </div>
                    <div class="text-center mt-4">
                        <a href="admin.php" class="btn btn-primary">Yönetici Paneline Dön</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

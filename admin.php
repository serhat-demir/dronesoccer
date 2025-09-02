<?php
    ini_set('session.gc_maxlifetime', 86400);
    session_set_cookie_params(86400);
    include 'db.php';
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    
    $current_user_id = $_SESSION['user_id'];
    
    // Fetch current user's username for greeting
    $username_stmt = $db->prepare("SELECT kullanici_adi FROM kullanicilar WHERE id = ?");
    $username_stmt->execute([$current_user_id]);
    $current_username = $username_stmt->fetchColumn();
    
    // Check user permission level
    $admin_check = $db->prepare("SELECT is_admin FROM kullanicilar WHERE id = ?");
    $admin_check->execute([$current_user_id]);
    $user_data = $admin_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_data || $user_data['is_admin'] == 0) {
        header('Location: index.php');
        exit;
    }
    
    $current_user_level = $user_data['is_admin']; // 1=limited admin, 2=super admin
    $is_super_admin = $current_user_level == 2;
    $is_limited_admin = $current_user_level == 1;

    $message = '';
    $error = '';

    // Add User
    if (isset($_POST['add_user'])) {
        $kullanici_adi = trim($_POST['kullanici_adi'] ?? '');
        $sifre = trim($_POST['sifre'] ?? '');
        $is_admin = 0;
        $kurum = trim($_POST['kurum'] ?? '');
        $telefon = trim($_POST['telefon'] ?? '');

        if ($kurum === '') $kurum = null;
        if ($telefon === '') $telefon = null;

        if ($is_super_admin && isset($_POST['is_admin'])) {
            $is_admin = intval($_POST['is_admin']);
            if (!in_array($is_admin, [0, 1])) {
                $is_admin = 0;
            }
        }

        if (empty($kullanici_adi) || empty($sifre)) {
            $error = 'Kullanıcı adı ve şifre boş olamaz.';
        } elseif (strlen($kullanici_adi) > 30 || strlen($sifre) > 30) {
            $error = 'Kullanıcı adı ve şifre en fazla 30 karakter olabilir.';
        } elseif (strlen($sifre) < 3) {
            $error = 'Şifre en az 3 karakter olmalıdır.';
        } elseif ($kurum !== null && strlen($kurum) > 255) {
            $error = 'Kurum adı en fazla 255 karakter olabilir.';
        } elseif ($telefon !== null && strlen($telefon) > 20) {
            $error = 'Telefon numarası en fazla 20 karakter olabilir.';
        } else {
            $check = $db->prepare("SELECT id FROM kullanicilar WHERE kullanici_adi = ?");
            $check->execute([$kullanici_adi]);
            if ($check->rowCount() > 0) {
                $error = 'Bu kullanıcı adı zaten mevcut.';
            } else {
                $hashed_password = password_hash($sifre, PASSWORD_DEFAULT);
                $add = $db->prepare("INSERT INTO kullanicilar (kullanici_adi, sifre, is_admin, kurum, telefon) VALUES (?, ?, ?, ?, ?)");
                if ($add->execute([$kullanici_adi, $hashed_password, $is_admin, $kurum, $telefon])) {
                    $message = 'Kullanıcı başarıyla eklendi.';
                } else {
                    $error = 'Kullanıcı eklenirken bir hata oluştu.';
                }
            }
        }
    }

    // Update User
    if (isset($_POST['update_user'])) {
        $user_id = $_POST['user_id'];
        $kullanici_adi = trim($_POST['edit_kullanici_adi'] ?? '');
        $sifre = trim($_POST['edit_sifre'] ?? '');
        $kurum = trim($_POST['edit_kurum'] ?? '');
        $telefon = trim($_POST['edit_telefon'] ?? '');
        $is_approved = isset($_POST['edit_is_approved']) ? intval($_POST['edit_is_approved']) : null;

        if ($kurum === '') $kurum = null;
        if ($telefon === '') $telefon = null;

        $current_edit_user = $db->prepare("SELECT is_admin FROM kullanicilar WHERE id = ?");
        $current_edit_user->execute([$user_id]);
        $edit_user_data = $current_edit_user->fetch(PDO::FETCH_ASSOC);

        if (!$edit_user_data) {
            $error = 'Kullanıcı bulunamadı.';
        } else {
            $target_user_level = $edit_user_data['is_admin'];
            if (
                $current_user_level == 1 &&
                $user_id != $current_user_id &&
                $target_user_level > 0
            ) {
                $error = 'Bu kullanıcıyı düzenleme yetkiniz yok.';
            } else {
                $is_admin = $target_user_level;
                if (
                    $is_super_admin &&
                    isset($_POST['edit_is_admin']) &&
                    $user_id != $current_user_id &&
                    $target_user_level != 2
                ) {
                    $is_admin = intval($_POST['edit_is_admin']);
                    if (!in_array($is_admin, [0, 1, 2])) {
                        $is_admin = $target_user_level;
                    }
                    if ($is_admin == 2 && $target_user_level != 2) {
                        $is_admin = 1;
                    }
                }

                if (!empty($error)) {
                    // Hata varsa güncelleme işlemi yapılmasın!
                } elseif (empty($kullanici_adi)) {
                    $error = 'Kullanıcı adı boş olamaz.';
                } elseif (strlen($kullanici_adi) > 30) {
                    $error = 'Kullanıcı adı en fazla 30 karakter olabilir.';
                } elseif ($kurum !== null && strlen($kurum) > 255) {
                    $error = 'Kurum adı en fazla 255 karakter olabilir.';
                } elseif ($telefon !== null && strlen($telefon) > 20) {
                    $error = 'Telefon numarası en fazla 20 karakter olabilir.';
                } else {
                    $check = $db->prepare("SELECT id FROM kullanicilar WHERE kullanici_adi = ? AND id != ?");
                    $check->execute([$kullanici_adi, $user_id]);
                    if ($check->rowCount() > 0) {
                        $error = 'Bu kullanıcı adı zaten mevcut.';
                    } else {
                        if (!empty($sifre)) {
                            if (strlen($sifre) > 30) {
                                $error = 'Şifre en fazla 30 karakter olabilir.';
                            } elseif (strlen($sifre) < 3) {
                                $error = 'Şifre en az 3 karakter olmalıdır.';
                            } else {
                                $hashed_password = password_hash($sifre, PASSWORD_DEFAULT);
                                $update = $db->prepare("UPDATE kullanicilar SET kullanici_adi = ?, sifre = ?, is_admin = ?, kurum = ?, telefon = ?, is_approved = ? WHERE id = ?");
                                $update->execute([$kullanici_adi, $hashed_password, $is_admin, $kurum, $telefon, $is_approved, $user_id]);
                            }
                        } else {
                            $update = $db->prepare("UPDATE kullanicilar SET kullanici_adi = ?, is_admin = ?, kurum = ?, telefon = ?, is_approved = ? WHERE id = ?");
                            $update->execute([$kullanici_adi, $is_admin, $kurum, $telefon, $is_approved, $user_id]);
                        }
                        if (isset($update) && $update->rowCount() >= 0) {
                            $message = 'Kullanıcı başarıyla güncellendi.';
                        } else {
                            $error = 'Kullanıcı güncellenirken bir hata oluştu.';
                        }
                    }
                }
            }
        }
    }

    // Delete User
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['delete_user_id'];
        
        // Prevent admin from deleting themselves
        if ($user_id == $current_user_id) {
            $error = 'Kendi hesabınızı silemezsiniz.';
        } else {
            // Get target user level
            $target_check = $db->prepare("SELECT is_admin FROM kullanicilar WHERE id = ?");
            $target_check->execute([$user_id]);
            $target_data = $target_check->fetch(PDO::FETCH_ASSOC);
            
            if ($target_data) {
                $target_level = $target_data['is_admin'];
                
                // Limited admins can only delete regular users
                if ($current_user_level == 1 && $target_level > 0) {
                    $error = 'Bu kullanıcıyı silme yetkiniz yok.';
                // Super admins cannot delete other super admins
                } elseif ($is_super_admin && $target_level == 2) {
                    $error = 'Başka bir üst seviye yöneticiyi silemezsiniz.';
                } else {
                    $delete = $db->prepare("DELETE FROM kullanicilar WHERE id = ?");
                    if ($delete->execute([$user_id])) {
                        $message = 'Kullanıcı başarıyla silindi.';
                    } else {
                        $error = 'Kullanıcı silinirken bir hata oluştu.';
                    }
                }
            } else {
                $error = 'Kullanıcı bulunamadı.';
            }
        }
    }

    // Kullanıcı onay işlemleri
    if (isset($_POST['approve_user'])) {
        $user_id = intval($_POST['user_id']);
        $approve = $db->prepare("UPDATE kullanicilar SET is_approved = 1 WHERE id = ?");
        $approve->execute([$user_id]);
        $message = 'Kullanıcı onaylandı.';
    }
    if (isset($_POST['reject_user'])) {
        $user_id = intval($_POST['user_id']);
        $reject = $db->prepare("UPDATE kullanicilar SET is_approved = 0 WHERE id = ?");
        $reject->execute([$user_id]);
        $message = 'Kullanıcı onayı kaldırıldı.';
    }

    // Get users based on permission level
    if ($is_super_admin) {
        // Super admin cannot see other super admins anymore (only self + others below)
        $users = $db->prepare("SELECT * FROM kullanicilar WHERE is_admin != 2 OR id = ? ORDER BY id DESC");
        $users->execute([$current_user_id]);
    } else {
        // Limited admin can only see regular users and themselves
        $users = $db->prepare("SELECT * FROM kullanicilar WHERE is_admin = 0 OR id = ? ORDER BY id DESC");
        $users->execute([$current_user_id]);
    }
    $all_users = $users->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drone Soccer Türkiye - Yönetici Paneli</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <style>
        .admin-header {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .user-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .table th {
            background-color: #007bff;
            color: white;
        }
        .admin-badge {
            background-color: #28a745;
        }
        .user-badge {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1 class="text-center display-4 font-weight-bold">Drone Soccer Türkiye</h1>
            <p class="text-center lead font-weight-bold">Yönetici Paneli </p>
            <p class="text-center">
                Hoş geldiniz, <strong><?= htmlspecialchars($current_username) ?></strong>
            </p>
        </div>
    </div>

    <div class="container">
        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- Add User Form -->
        <div class="user-form">
            <h3 class="mb-3">Yeni Kullanıcı Ekle</h3>
            <form method="post" action="">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="kullanici_adi">Kullanıcı Adı</label>
                        <input type="text" class="form-control" id="kullanici_adi" name="kullanici_adi" maxlength="30" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="sifre">Şifre (En az 3 karakter)</label>
                        <input type="password" class="form-control" id="sifre" name="sifre" maxlength="30" minlength="3" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label for="kurum">Kurum</label>
                        <input type="text" class="form-control" id="kurum" name="kurum" maxlength="255">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="telefon">Telefon</label>
                        <input type="text" class="form-control" id="telefon" name="telefon" maxlength="20">
                    </div>
                    <?php if ($is_super_admin): ?>
                    <div class="form-group col-md-3">
                        <label for="is_admin">Yetki</label>
                        <select class="form-control" id="is_admin" name="is_admin">
                            <option value="0">Kullanıcı</option>
                            <option value="1">Yönetici</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="form-row">
                    <div class="col text-right">
                        <button type="submit" name="add_user" class="btn btn-success px-4">Kaydet</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Tüm Kullanıcılar</h3>
            </div>
            <div class="card-body">
                <table id="usersTable" class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kullanıcı Adı</th>
                            <th>Kurum</th>
                            <th>Telefon</th>
                            <th>Rol</th>
                            <th>Onay</th>
                            <th>Kayıt Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <?= htmlspecialchars($user['kullanici_adi']) ?>
                                    <?php if ($user['id'] == $current_user_id): ?>
                                        <span class="badge badge-primary text-light ml-1">Sizin Hesabınız</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($user['kurum'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($user['telefon'] ?? '-') ?></td>
                                <td>
                                    <span class="badge <?php 
                                        if ($user['is_admin'] == 2) echo 'badge-danger';
                                        elseif ($user['is_admin'] == 1) echo 'badge-danger';
                                        else echo 'user-badge text-light';
                                    ?>">
                                        <?php 
                                            if ($user['is_admin'] == 2) echo 'Üst Yönetici';
                                            elseif ($user['is_admin'] == 1) echo 'Yönetici';
                                            else echo 'Kullanıcı';
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['is_approved']): ?>
                                        <span class="badge badge-success">Onaylı</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning text-light">Bekliyor</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= isset($user['created_at']) ? date('d.m.Y H:i', strtotime($user['created_at'])) : '-' ?></td>
                                <td>
                                    <?php 
                                        $can_edit = 
                                            ($is_super_admin && ($user['is_admin'] != 2 || $user['id'] == $current_user_id))
                                            || ($is_limited_admin && ($user['is_admin'] == 0 || $user['id'] == $current_user_id));
                                        $can_delete = $can_edit && $user['id'] != $current_user_id && !($is_super_admin && $user['is_admin'] == 2);
                                    ?>
                                    <?php if ($can_edit): ?>
                                        <button class="btn btn-sm btn-primary" onclick="editUser(
                                            <?= $user['id'] ?>,
                                            '<?= htmlspecialchars($user['kullanici_adi']) ?>',
                                            <?= $user['is_admin'] ?>,
                                            <?= $is_super_admin ? 'true' : 'false' ?>,
                                            '<?= htmlspecialchars($user['kurum'], ENT_QUOTES) ?>',
                                            '<?= htmlspecialchars($user['telefon'], ENT_QUOTES) ?>',
                                            <?= $user['is_approved'] ?>
                                        )">
                                            Düzenle
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($can_delete): ?>
                                        <button class="btn btn-sm btn-danger ml-1" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['kullanici_adi']) ?>')">
                                            Sil
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Navigation -->
        <div class="text-center mt-4 mb-5">
            <a href="index.php" class="btn btn-secondary btn-lg">Ana Sayfaya Dön</a>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kullanıcı Düzenle</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        <div class="form-group">
                            <label for="edit_kullanici_adi">Kullanıcı Adı</label>
                            <input type="text" class="form-control" id="edit_kullanici_adi" name="edit_kullanici_adi" maxlength="30" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_sifre">Yeni Şifre (En az 3 karakter, boş bırakın değiştirmek istemiyorsanız)</label>
                            <input type="password" class="form-control" id="edit_sifre" name="edit_sifre" maxlength="30" minlength="3">
                        </div>
                        <div class="form-group">
                            <label for="edit_kurum">Kurum</label>
                            <input type="text" class="form-control" id="edit_kurum" name="edit_kurum" maxlength="255">
                        </div>
                        <div class="form-group">
                            <label for="edit_telefon">Telefon</label>
                            <input type="text" class="form-control" id="edit_telefon" name="edit_telefon" maxlength="20">
                        </div>
                        <div class="form-group" id="admin_level_group" style="display: none;">
                            <label for="edit_is_admin">Yetki Seviyesi</label>
                            <select class="form-control" id="edit_is_admin" name="edit_is_admin">
                                <option value="0">Normal Kullanıcı</option>
                                <option value="1">Yönetici</option>
                                <option value="2" id="super_admin_option" style="display: none;">Yönetici (Üst Seviye)</option>
                            </select>
                        </div>
                        <div class="form-group" id="approve_group" style="display: none;">
                            <label for="edit_is_approved">Onay Durumu</label>
                            <select class="form-control" id="edit_is_approved" name="edit_is_approved">
                                <option value="1">Onaylı</option>
                                <option value="0">Bekliyor</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">İptal</button>
                        <button type="submit" name="update_user" class="btn btn-primary">Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Form (Hidden) -->
    <form id="deleteUserForm" method="post" action="" style="display: none;">
        <input type="hidden" id="delete_user_id" name="delete_user_id">
        <input type="hidden" name="delete_user" value="1">
    </form>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    
    <script>
        $(document).ready(function() {
            var turkishLang = {
                "sDecimal":        ",",
                "sEmptyTable":     "Tabloda herhangi bir veri mevcut değil",
                "sInfo":           "_TOTAL_ kayıttan _START_ - _END_ arasındaki kayıtlar gösteriliyor",
                "sInfoEmpty":      "Kayıt yok",
                "sInfoFiltered":   "(_MAX_ kayıt içerisinden bulunan)",
                "sInfoPostFix":    "",
                "sInfoThousands":  ".",
                "sLengthMenu":     "Sayfada _MENU_ kayıt göster",
                "sLoadingRecords": "Yükleniyor...",
                "sProcessing":     "İşleniyor...",
                "sSearch":         "Ara:",
                "sZeroRecords":    "Eşleşen kayıt bulunamadı",
                "oPaginate": {
                    "sFirst":    "İlk",
                    "sLast":     "Son",
                    "sNext":     "Sonraki",
                    "sPrevious": "Önceki"
                },
                "oAria": {
                    "sSortAscending":  ": artan sütun sıralamasını aktifleştir",
                    "sSortDescending": ": azalan sütun sıralamasını aktifleştir"
                }
            };

            $('#usersTable').DataTable({
                "language": turkishLang,
                "pageLength": 25,
                "order": [[ 0, "desc" ]]
            });
        });

        function editUser(id, username, isAdmin, canChangeLevel, kurum, telefon, isApproved) {
            $('#edit_user_id').val(id);
            $('#edit_kullanici_adi').val(username);
            $('#edit_sifre').val('');
            $('#edit_kurum').val(kurum);
            $('#edit_telefon').val(telefon);

            var currentUserId = <?= json_encode($current_user_id) ?>;
            var isLimitedAdmin = <?= json_encode($is_limited_admin) ?>;
            // Super admin can edit their own password and kurum, but not their own role
            // Limited admin can edit their own password and kurum, but not their own role
            if (
                canChangeLevel &&
                id != currentUserId &&
                isAdmin != 2 // Don't allow editing role if target is super admin
            ) {
                $('#edit_is_admin').val(isAdmin);
                if (isAdmin == 2) {
                    $('#super_admin_option').show();
                } else {
                    $('#super_admin_option').hide();
                }
                $('#admin_level_group').show();
            } else if (
                (canChangeLevel && id == currentUserId) ||
                (isLimitedAdmin && id == currentUserId)
            ) {
                // Editing self: hide role change, allow password/kurum
                $('#admin_level_group').hide();
            } else {
                $('#admin_level_group').hide();
            }

            // Hide approve group if editing own account
            if (id == currentUserId) {
                $('#approve_group').hide();
            } else {
                <?php if ($is_super_admin): ?>
                    $('#approve_group').show();
                <?php else: ?>
                    if (isAdmin == 0) {
                        $('#approve_group').show();
                    } else {
                        $('#approve_group').hide();
                    }
                <?php endif; ?>
            }

            $('#edit_is_approved').val(isApproved);

            $('#editUserModal').modal('show');
        }

        function deleteUser(id, username) {
            if (confirm('"{username}" kullanıcısını silmek istediğinize emin misiniz?\n\nBu işlem geri alınamaz ve kullanıcının tüm verileri silinecektir.'.replace('{username}', username))) {
                $('#delete_user_id').val(id);
                $('#deleteUserForm').submit();
            }
        }
    </script>
</body>
</html>

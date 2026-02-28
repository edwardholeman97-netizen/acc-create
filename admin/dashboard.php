<?php
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/database/connection.php';

$pdo = getDb();
$stmt = $pdo->query('SELECT id, account_id, form_data, created_at FROM cds_submissions ORDER BY created_at DESC');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - CDS Submissions</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 30px; background: #1a237e; color: white; }
        .admin-header h1 { margin: 0; font-size: 20px; }
        .admin-header a { color: white; text-decoration: none; }
        .admin-content { padding: 30px; }
        .msg { padding: 12px; margin-bottom: 20px; border-radius: 6px; background: #d4edda; color: #155724; }
        .msg.error { background: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; font-weight: 600; }
        .actions a { margin-right: 8px; }
        .btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 14px; cursor: pointer; border: none; }
        .btn-primary { background: #3498db; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover, .btn-primary:hover { opacity: 0.9; }
        .bulk-actions { margin-bottom: 20px; }
        .bulk-actions .btn { margin-right: 8px; }
        .delete-modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .delete-modal-overlay.active { display: flex; }
        .delete-modal { background: white; border-radius: 12px; padding: 24px; max-width: 400px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .delete-modal h3 { margin: 0 0 12px 0; font-size: 18px; color: #2c3e50; }
        .delete-modal p { margin: 0 0 20px 0; color: #5a6c7d; font-size: 14px; line-height: 1.5; }
        .delete-modal-actions { display: flex; gap: 12px; justify-content: flex-end; }
        .delete-modal-actions .btn { padding: 10px 20px; }
        .delete-modal-actions .btn-cancel { background: #95a5a6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; padding: 10px 20px; }
        .delete-modal-actions .btn-cancel:hover { background: #7f8c8d; }
        .delete-modal-actions .btn-confirm-delete { background: #e74c3c; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; padding: 10px 20px; }
        .delete-modal-actions .btn-confirm-delete:hover { background: #c0392b; }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>CDS Submissions</h1>
        <a href="logout.php">Logout</a>
    </div>
    <div class="admin-content">
        <?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

        <form id="bulkForm" method="post" action="delete.php" style="display:none"></form>
        <div class="bulk-actions">
            <button type="button" id="btnBulkDelete" class="btn btn-danger">Delete Selected</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>Account ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r):
                    $data = json_decode($r['form_data'], true) ?: [];
                    $name = trim(($data['Surname'] ?? '') . ' ' . ($data['NameDenoInitials'] ?? $data['NameDenoInitials'] ?? ''));
                    $email = $data['Email'] ?? '-';
                ?>
                <tr>
                    <td><input type="checkbox" class="bulk-checkbox" value="<?= (int)$r['id'] ?>"></td>
                    <td><?= htmlspecialchars($r['account_id']) ?></td>
                    <td><?= htmlspecialchars($name ?: '-') ?></td>
                    <td><?= htmlspecialchars($email) ?></td>
                    <td><?= htmlspecialchars($r['created_at']) ?></td>
                    <td class="actions">
                        <a href="edit.php?id=<?= (int)$r['id'] ?>" class="btn btn-primary">Edit</a>
                        <form method="post" action="delete.php" class="delete-form" style="display:inline" data-account-id="<?= htmlspecialchars($r['account_id']) ?>">
                            <input type="hidden" name="ids[]" value="<?= (int)$r['id'] ?>">
                            <button type="button" class="btn btn-danger btn-delete-one">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                <tr><td colspan="6">No submissions yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="deleteModal" class="delete-modal-overlay" role="dialog" aria-labelledby="deleteModalTitle" aria-modal="true">
        <div class="delete-modal">
            <h3 id="deleteModalTitle"><i class="fas fa-exclamation-triangle" style="color:#e74c3c;margin-right:8px;"></i> Confirm Delete</h3>
            <p id="deleteModalMessage">Are you sure you want to delete this record? This action cannot be undone.</p>
            <div class="delete-modal-actions">
                <button type="button" class="btn-cancel" id="deleteModalCancel">Cancel</button>
                <button type="button" class="btn-confirm-delete" id="deleteModalConfirm">Delete</button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('selectAll')?.addEventListener('change', function() {
            document.querySelectorAll('.bulk-checkbox').forEach(cb => cb.checked = this.checked);
        });

        const modal = document.getElementById('deleteModal');
        const modalMessage = document.getElementById('deleteModalMessage');
        const modalCancel = document.getElementById('deleteModalCancel');
        const modalConfirm = document.getElementById('deleteModalConfirm');
        let formToSubmit = null;

        function openDeleteModal(message, form) {
            modalMessage.textContent = message;
            formToSubmit = form;
            modal.classList.add('active');
        }

        function closeDeleteModal() {
            modal.classList.remove('active');
            formToSubmit = null;
        }

        modalCancel.addEventListener('click', closeDeleteModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeDeleteModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) closeDeleteModal();
        });

        modalConfirm.addEventListener('click', function() {
            if (formToSubmit) {
                if (formToSubmit.bulk && formToSubmit.form && formToSubmit.checked) {
                    formToSubmit.form.innerHTML = '';
                    formToSubmit.checked.forEach(cb => {
                        const inp = document.createElement('input');
                        inp.type = 'hidden';
                        inp.name = 'ids[]';
                        inp.value = cb.value;
                        formToSubmit.form.appendChild(inp);
                    });
                    formToSubmit.form.submit();
                } else if (formToSubmit.form) {
                    formToSubmit.form.submit();
                }
            }
            closeDeleteModal();
        });

        document.getElementById('btnBulkDelete').addEventListener('click', function() {
            const checked = document.querySelectorAll('.bulk-checkbox:checked');
            if (checked.length === 0) {
                alert('Please select at least one record to delete.');
                return;
            }
            const count = checked.length;
            const msg = count === 1
                ? 'Are you sure you want to delete this record? This action cannot be undone.'
                : 'Are you sure you want to delete ' + count + ' selected records? This action cannot be undone.';
            openDeleteModal(msg, { bulk: true, form: document.getElementById('bulkForm'), checked: checked });
        });

        document.querySelectorAll('.btn-delete-one').forEach(btn => {
            btn.addEventListener('click', function() {
                const form = this.closest('form');
                const accountId = form.dataset.accountId || 'this record';
                openDeleteModal('Are you sure you want to delete account ' + accountId + '? This action cannot be undone.', { bulk: false, form: form });
            });
        });
    </script>
</body>
</html>

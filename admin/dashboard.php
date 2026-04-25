<?php
require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/database/connection.php';

$pdo = getDb();

$validStatuses = ['pending_review', 'awaiting_edit', 'submitted_to_cse'];
$filter = $_GET['status'] ?? 'all';
if (!in_array($filter, $validStatuses, true) && $filter !== 'all') {
    $filter = 'all';
}

if ($filter === 'all') {
    $stmt = $pdo->query(
        'SELECT id, submission_uid, account_id, cse_account_id, form_data,'
            . ' status, admin_note, submitted_to_cse_at, created_at'
            . ' FROM cds_submissions ORDER BY created_at DESC'
    );
} else {
    $stmt = $pdo->prepare(
        'SELECT id, submission_uid, account_id, cse_account_id, form_data,'
            . ' status, admin_note, submitted_to_cse_at, created_at'
            . ' FROM cds_submissions WHERE status = ? ORDER BY created_at DESC'
    );
    $stmt->execute([$filter]);
}
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusCountsStmt = $pdo->query('SELECT status, COUNT(*) AS c FROM cds_submissions GROUP BY status');
$statusCountsRaw = $statusCountsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$statusCounts = [
    'pending_review' => (int)($statusCountsRaw['pending_review'] ?? 0),
    'awaiting_edit' => (int)($statusCountsRaw['awaiting_edit'] ?? 0),
    'submitted_to_cse' => (int)($statusCountsRaw['submitted_to_cse'] ?? 0),
];
$statusCounts['all'] = array_sum($statusCounts);

$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';

function status_label($s)
{
    return [
        'pending_review' => 'Pending Review',
        'awaiting_edit' => 'Awaiting Client Edit',
        'submitted_to_cse' => 'Submitted to CSE',
    ][$s] ?? $s;
}
function status_pill_class($s)
{
    return [
        'pending_review' => 'pill-pending',
        'awaiting_edit' => 'pill-awaiting',
        'submitted_to_cse' => 'pill-submitted',
    ][$s] ?? 'pill-pending';
}
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
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 16px;
            background: #000;
            color: #fff;
            border-radius: 16px;
        }

        .admin-header h1 {
            margin: 0;
            font-size: 20px;
        }

        .admin-header a {
            color: white;
            text-decoration: none;
        }

        .admin-content {
            padding: 30px;
        }

        .msg {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
            background: #d4edda;
            color: #155724;
        }

        .msg.error {
            background: #f8d7da;
            color: #721c24;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table {
            table-layout: fixed;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }

        th {
            background: #f5f5f5;
            font-weight: 600;
        }

        td.truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 0;
        }

        td.truncate small {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .actions a,
        .actions button {
            margin-right: 6px;
            margin-bottom: 4px;
        }

        .bulk-actions {
            margin-bottom: 20px;
        }

        .status-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .status-tab {
            padding: 8px 14px;
            border-radius: 20px;
            background: #f1f3f5;
            color: #4b5563;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid transparent;
        }

        .status-tab.active {
            background: #DD4200;
            color: #fff;
        }

        .status-tab .count {
            display: inline-block;
            margin-left: 6px;
            padding: 1px 7px;
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.08);
            font-size: 11px;
        }

        .status-tab.active .count {
            background: rgba(255, 255, 255, 0.25);
        }

        .pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .pill-pending {
            background: #FFF4DA;
            color: #B45309;
        }

        .pill-awaiting {
            background: #E0F2FE;
            color: #075985;
        }

        .pill-submitted {
            background: #DCFCE7;
            color: #166534;
        }

        .btn-sm {
            padding: 6px 10px;
            font-size: 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-success {
            background: #16a34a;
            color: #fff;
        }

        .btn-warning {
            background: #d97706;
            color: #fff;
        }

        .btn-secondary {
            background: #6b7280;
            color: #fff;
        }

        .delete-modal-overlay,
        .note-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .delete-modal-overlay.active,
        .note-modal-overlay.active {
            display: flex;
        }

        .delete-modal,
        .note-modal {
            background: white;
            border-radius: 12px;
            padding: 24px;
            max-width: 460px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .delete-modal h3,
        .note-modal h3 {
            margin: 0 0 12px 0;
            font-size: 18px;
            color: #2c3e50;
        }

        .delete-modal p,
        .note-modal p {
            margin: 0 0 16px 0;
            color: #5a6c7d;
            font-size: 14px;
            line-height: 1.5;
        }

        .note-modal textarea {
            width: 100%;
            min-height: 110px;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 14px;
        }

        .btn-cancel {
            background: #95a5a6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            padding: 10px 20px;
        }

        .btn-cancel:hover {
            background: #7f8c8d;
        }

        .btn-confirm-delete {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            padding: 10px 20px;
        }

        .btn-confirm-delete:hover {
            background: #c0392b;
        }

        .btn-confirm-primary {
            background: #DD4200;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            padding: 10px 20px;
        }

        .btn-confirm-primary .btn-spinner,
        .btn-confirm-delete .btn-spinner {
            display: none;
        }

        .btn-confirm-primary.is-loading,
        .btn-confirm-delete.is-loading {
            opacity: 0.85;
            cursor: not-allowed;
        }

        .btn-confirm-primary.is-loading .btn-label,
        .btn-confirm-delete.is-loading .btn-label {
            display: none;
        }

        .btn-confirm-primary.is-loading .btn-spinner,
        .btn-confirm-delete.is-loading .btn-spinner {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>

<body>
    <div class="admin-header">
        <h1>CDS Submissions</h1>
        <a href="logout.php">Logout</a>
    </div>
    <div class="admin-content">
        <?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($err): ?><div class="msg error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

        <div class="status-tabs">
            <?php
            $tabs = [
                'all' => 'All',
                'pending_review' => 'Pending Review',
                'awaiting_edit' => 'Awaiting Client Edit',
                'submitted_to_cse' => 'Submitted to CSE',
            ];
            foreach ($tabs as $key => $label):
                $active = ($filter === $key) ? ' active' : '';
                $href = $key === 'all' ? 'dashboard.php' : 'dashboard.php?status=' . urlencode($key);
            ?>
                <a class="status-tab<?= $active ?>" href="<?= htmlspecialchars($href) ?>">
                    <?= htmlspecialchars($label) ?>
                    <span class="count"><?= (int)$statusCounts[$key] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <form id="bulkForm" method="post" action="delete.php" style="display:none"></form>
        <div class="bulk-actions">
            <button type="button" id="btnBulkDelete" class="btn btn-danger">Delete Selected</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th width="40px"><input type="checkbox" id="selectAll"></th>
                    <th width="160px">Status</th>
                    <th width="180px">CSE Account</th>
                    <th width="30%">Name</th>
                    <th>Email</th>
                    <th>Created</th>
                    <th width="400px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r):
                    $data = json_decode($r['form_data'], true) ?: [];
                    $name = trim(($data['Surname'] ?? '') . ' ' . ($data['NameDenoInitials'] ?? ''));
                    $email = $data['Email'] ?? '-';
                    $status = $r['status'] ?: 'pending_review';
                    $cseId = $r['cse_account_id'] ?: ($r['account_id'] ?: '-');
                    $isSubmitted = ($status === 'submitted_to_cse');
                    $isPending = in_array($status, ['pending_review', 'awaiting_edit'], true);
                    $clientName = $name ?: '-';
                ?>
                    <tr>
                        <td><input type="checkbox" class="bulk-checkbox" value="<?= (int)$r['id'] ?>"></td>
                        <td><span class="pill <?= status_pill_class($status) ?>"><?= htmlspecialchars(status_label($status)) ?></span></td>
                        <td class="truncate" title="<?= htmlspecialchars($cseId) ?><?= $isSubmitted && $r['submitted_to_cse_at'] ? ' — ' . htmlspecialchars($r['submitted_to_cse_at']) : '' ?>">
                            <?php if ($isSubmitted): ?>
                                <strong><?= htmlspecialchars($cseId) ?></strong>
                                <?php if ($r['submitted_to_cse_at']): ?>
                                    <small style="color:#6b7280;"><?= htmlspecialchars($r['submitted_to_cse_at']) ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <small style="color:#9ca3af;">—</small>
                            <?php endif; ?>
                        </td>
                        <td class="truncate" title="<?= htmlspecialchars($clientName) ?>"><?= htmlspecialchars($clientName) ?></td>
                        <td class="truncate" title="<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></td>
                        <td class="truncate" title="<?= htmlspecialchars($r['created_at']) ?>"><?= htmlspecialchars($r['created_at']) ?></td>
                        <td class="actions">
                            <?php if ($isPending): ?>
                                <a href="edit.php?id=<?= (int)$r['id'] ?>" class="btn-sm btn-secondary"><i class="fas fa-pen"></i> Edit</a>
                                <form method="post" action="submit-to-cse.php" style="display:inline" class="approve-form" data-name="<?= htmlspecialchars($clientName) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <button type="button" class="btn-sm btn-success btn-approve"><i class="fas fa-check"></i> Approve </button>
                                </form>
                                <button type="button"
                                    class="btn-sm btn-warning btn-request-changes"
                                    data-id="<?= (int)$r['id'] ?>"
                                    data-name="<?= htmlspecialchars($clientName) ?>"
                                    data-email="<?= htmlspecialchars($email) ?>"
                                    data-note="<?= htmlspecialchars($r['admin_note'] ?? '') ?>">
                                    <i class="fas fa-pen-to-square"></i> Request Changes
                                </button>
                            <?php else: ?>
                                <a href="edit.php?id=<?= (int)$r['id'] ?>" class="btn-sm btn-secondary"><i class="fas fa-eye"></i> View</a>
                            <?php endif; ?>
                            <form method="post" action="delete.php" class="delete-form" style="display:inline" data-account-id="<?= htmlspecialchars($cseId) ?>">
                                <input type="hidden" name="ids[]" value="<?= (int)$r['id'] ?>">
                                <button type="button" class="btn-sm btn-danger btn-delete-one"><i class="fa-regular fa-trash-can"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; color:#6b7280; padding:30px;">No submissions in this view.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="deleteModal" class="delete-modal-overlay" role="dialog" aria-labelledby="deleteModalTitle" aria-modal="true">
        <div class="delete-modal">
            <h3 id="deleteModalTitle"><i class="fas fa-exclamation-triangle" style="color:#e74c3c;margin-right:8px;"></i> Confirm</h3>
            <p id="deleteModalMessage">Are you sure?</p>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" id="deleteModalCancel">Cancel</button>
                <button type="button" class="btn-confirm-delete" id="deleteModalConfirm">Confirm</button>
            </div>
        </div>
    </div>

    <div id="noteModal" class="note-modal-overlay" role="dialog" aria-labelledby="noteModalTitle" aria-modal="true">
        <div class="note-modal">
            <h3 id="noteModalTitle"><i class="fas fa-pen-to-square" style="color:#d97706;margin-right:8px;"></i> Send edit link</h3>
            <p id="noteModalSubtitle">An edit link will be emailed to the client. It will expire in 3 days, or as soon as they re-submit the application — whichever comes first.</p>
            <form id="noteForm" method="post" action="send-edit-link.php">
                <input type="hidden" name="id" id="noteFormId" value="">
                <label for="adminNote" style="display:block; font-weight:600; color:#3D3D3D; margin-bottom:6px; font-size:13px;">Note to client (optional)</label>
                <textarea name="note" id="adminNote" placeholder="e.g. Please update your address — the unit number is missing."></textarea>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" id="noteModalCancel">Cancel</button>
                    <button type="submit" class="btn-confirm-primary" id="noteModalSubmit">
                        <span class="btn-label"><i class="fas fa-paper-plane"></i> Send link</span>
                        <span class="btn-spinner" aria-hidden="true"><i class="fas fa-circle-notch fa-spin"></i> Sending…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('selectAll')?.addEventListener('change', function() {
            document.querySelectorAll('.bulk-checkbox').forEach(cb => cb.checked = this.checked);
        });

        // Generic confirm modal (delete + approve)
        const modal = document.getElementById('deleteModal');
        const modalTitle = document.getElementById('deleteModalTitle');
        const modalMessage = document.getElementById('deleteModalMessage');
        const modalCancel = document.getElementById('deleteModalCancel');
        const modalConfirm = document.getElementById('deleteModalConfirm');
        let pendingAction = null;

        function setConfirmModalLoading(isLoading, loadingText) {
            if (isLoading) {
                modalConfirm.classList.add('is-loading');
                modalConfirm.disabled = true;
                modalCancel.disabled = true;
                const spinner = modalConfirm.querySelector('.btn-spinner');
                if (spinner) spinner.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> ' + (loadingText || 'Working…');
            } else {
                modalConfirm.classList.remove('is-loading');
                modalConfirm.disabled = false;
                modalCancel.disabled = false;
            }
        }

        function openConfirmModal(opts) {
            modalTitle.innerHTML = (opts.icon || '<i class="fas fa-exclamation-triangle" style="color:#e74c3c;margin-right:8px;"></i>') + (opts.title || 'Confirm');
            modalMessage.textContent = opts.message || 'Are you sure?';
            const confirmText = opts.confirmText || 'Confirm';
            modalConfirm.className = opts.confirmClass || 'btn-confirm-delete';
            modalConfirm.innerHTML =
                '<span class="btn-label">' + confirmText.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])) + '</span>' +
                '<span class="btn-spinner" aria-hidden="true"></span>';
            setConfirmModalLoading(false);
            pendingAction = opts.onConfirm || null;
            modal._loadingText = opts.loadingText || (confirmText + '…');
            modal.classList.add('active');
        }

        function closeConfirmModal() {
            if (modalConfirm.classList.contains('is-loading')) return;
            modal.classList.remove('active');
            pendingAction = null;
        }

        modalCancel.addEventListener('click', closeConfirmModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeConfirmModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('active')) closeConfirmModal();
        });
        modalConfirm.addEventListener('click', function() {
            if (typeof pendingAction !== 'function') {
                closeConfirmModal();
                return;
            }
            setConfirmModalLoading(true, modal._loadingText);
            const action = pendingAction;
            pendingAction = null;
            try {
                action();
            } catch (err) {
                setConfirmModalLoading(false);
                throw err;
            }
        });

        // Bulk delete
        document.getElementById('btnBulkDelete').addEventListener('click', function() {
            const checked = document.querySelectorAll('.bulk-checkbox:checked');
            if (checked.length === 0) {
                alert('Please select at least one record to delete.');
                return;
            }
            const count = checked.length;
            const message = count === 1 ?
                'Are you sure you want to delete this record? This action cannot be undone.' :
                'Are you sure you want to delete ' + count + ' selected records? This action cannot be undone.';
            openConfirmModal({
                title: ' Confirm Delete',
                message: message,
                confirmText: 'Delete',
                loadingText: 'Deleting…',
                onConfirm: function() {
                    const form = document.getElementById('bulkForm');
                    form.innerHTML = '';
                    checked.forEach(cb => {
                        const inp = document.createElement('input');
                        inp.type = 'hidden';
                        inp.name = 'ids[]';
                        inp.value = cb.value;
                        form.appendChild(inp);
                    });
                    form.submit();
                }
            });
        });

        // Per-row delete
        document.querySelectorAll('.btn-delete-one').forEach(btn => {
            btn.addEventListener('click', function() {
                const form = this.closest('form');
                const accountId = form.dataset.accountId || 'this record';
                openConfirmModal({
                    title: ' Confirm Delete',
                    message: 'Delete record ' + accountId + '? This cannot be undone.',
                    confirmText: 'Delete',
                    loadingText: 'Deleting…',
                    onConfirm: () => form.submit()
                });
            });
        });

        // Approve & Send to CSE
        document.querySelectorAll('.btn-approve').forEach(btn => {
            btn.addEventListener('click', function() {
                const form = this.closest('form');
                const name = form.dataset.name || 'this submission';
                openConfirmModal({
                    icon: '<i class="fas fa-paper-plane" style="color:#16a34a;margin-right:8px;"></i>',
                    title: ' Approve & Send to CSE',
                    message: 'Push ' + name + ' to CSE now? The row will be locked once CSE accepts.',
                    confirmText: 'Send to CSE',
                    confirmClass: 'btn-confirm-primary',
                    loadingText: 'Sending to CSE…',
                    onConfirm: () => form.submit()
                });
            });
        });

        // Request changes (note modal)
        const noteModal = document.getElementById('noteModal');
        const noteForm = document.getElementById('noteForm');
        const noteFormId = document.getElementById('noteFormId');
        const adminNote = document.getElementById('adminNote');
        const noteModalCancel = document.getElementById('noteModalCancel');
        const noteModalSubmit = document.getElementById('noteModalSubmit');

        function setNoteFormLoading(isLoading) {
            if (isLoading) {
                noteModalSubmit.classList.add('is-loading');
                noteModalSubmit.disabled = true;
                noteModalCancel.disabled = true;
                adminNote.disabled = true;
            } else {
                noteModalSubmit.classList.remove('is-loading');
                noteModalSubmit.disabled = false;
                noteModalCancel.disabled = false;
                adminNote.disabled = false;
            }
        }

        noteModalCancel.addEventListener('click', () => noteModal.classList.remove('active'));
        noteModal.addEventListener('click', e => {
            if (e.target === noteModal && !noteModalSubmit.classList.contains('is-loading')) {
                noteModal.classList.remove('active');
            }
        });

        noteForm.addEventListener('submit', function() {
            setNoteFormLoading(true);
        });

        document.querySelectorAll('.btn-request-changes').forEach(btn => {
            btn.addEventListener('click', function() {
                noteFormId.value = this.dataset.id;
                adminNote.value = this.dataset.note || '';
                setNoteFormLoading(false);
                noteModal.classList.add('active');
                setTimeout(() => adminNote.focus(), 50);
            });
        });
    </script>
</body>

</html>
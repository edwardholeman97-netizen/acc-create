<?php require_once __DIR__ . '/includes/form_constants.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDS Account Opening</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baskervville:ital,wght@0,400..700;1,400..700&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">

</head>

<body>
<div class="container">
<?php include __DIR__ . '/includes/form_steps.php'; ?>
</div>

<script>
// Mode: "create" - first-time submission. Saves to DB only (pending review).
window.__formConfig = {
    mode: 'create',
    formData: {},
    lockedKeys: [],
    token: null
};
</script>
<?php include __DIR__ . '/includes/form_scripts.php'; ?>
</body>

</html>

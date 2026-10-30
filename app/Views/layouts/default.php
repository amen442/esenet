<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Template::escape($title ?? 'ESEN NET') ?></title>
    <meta name="description" content="<?= Template::escape($description ?? 'Plateforme de stages et d\'emplois') ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= Template::asset('css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= Template::asset('css/main.css') ?>">
    <?php if (isset($css)): ?>
        <?php foreach ($css as $cssFile): ?>
            <link rel="stylesheet" href="<?= Template::asset($cssFile) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" href="<?= Template::asset('assets/images/favicon.ico') ?>" type="image/x-icon">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Flash Messages -->
    <?php $flashMessages = Template::flash(); ?>
    <?php if (!empty($flashMessages)): ?>
        <div class="flash-messages">
            <?php foreach ($flashMessages as $type => $message): ?>
                <div class="alert alert-<?= $type ?> alert-dismissible fade show" role="alert">
                    <?= Template::escape($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <?= $content ?>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= Template::asset('js/main.js') ?>"></script>
    <?php if (isset($js)): ?>
        <?php foreach ($js as $jsFile): ?>
            <script src="<?= Template::asset($jsFile) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>

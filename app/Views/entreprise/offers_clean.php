<?php
// Utilisation du template system
Template::setLayout('default');
?>

<!-- Flash Messages -->
<?php if ($flash = Template::flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= Template::escape($flash) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($flash = Template::flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= Template::escape($flash) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Header -->
<div class="offers-page-header">
    <div class="offers-header-left">
        <h1><i class="fas fa-briefcase"></i> Mes Offres</h1>
        <p>Gérez et suivez vos offres de stage</p>
    </div>
    <div class="offers-header-right">
        <button class="offers-btn-create" data-bs-toggle="modal" data-bs-target="#createOfferModal">
            <i class="fas fa-plus"></i>
            <span>Nouvelle offre</span>
        </button>
    </div>
</div>

<?php
$total_offres = count($offres ?? []);
$active_offres = 0;
$expired_offres = 0;
$total_candidatures = 0;

foreach ($offres ?? [] as $o) {
    $d = new DateTime($o['dateExp']);
    if ($d < new DateTime()) { 
        $expired_offres++; 
    } else { 
        $active_offres++; 
    }
    $total_candidatures += $o['nb_candidatures'] ?? 0;
}
?>

<!-- Stats Row -->
<div class="offers-stats-row">
    <div class="offers-stat-chip">
        <div class="offers-stat-icon blue"><i class="fas fa-briefcase"></i></div>
        <div class="offers-stat-data">
            <span class="offers-stat-number"><?= $total_offres ?></span>
            <span class="offers-stat-label">Total</span>
        </div>
    </div>
    <div class="offers-stat-chip">
        <div class="offers-stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="offers-stat-data">
            <span class="offers-stat-number"><?= $active_offres ?></span>
            <span class="offers-stat-label">Actives</span>
        </div>
    </div>
    <div class="offers-stat-chip">
        <div class="offers-stat-icon red"><i class="fas fa-clock"></i></div>
        <div class="offers-stat-data">
            <span class="offers-stat-number"><?= $expired_offres ?></span>
            <span class="offers-stat-label">Expirées</span>
        </div>
    </div>
    <div class="offers-stat-chip">
        <div class="offers-stat-icon purple"><i class="fas fa-users"></i></div>
        <div class="offers-stat-data">
            <span class="offers-stat-number"><?= $total_candidatures ?></span>
            <span class="offers-stat-label">Candidatures</span>
        </div>
    </div>
</div>

<!-- Toolbar: Search + Filters -->
<div class="offers-toolbar">
    <div class="offers-search-box">
        <i class="fas fa-search"></i>
        <input type="text" class="offers-search-input" placeholder="Rechercher une offre...">
    </div>
    <div class="offers-filter-tabs">
        <button class="offers-filter-tab active" data-filter="all">
            <i class="fas fa-th-large"></i> Toutes
        </button>
        <button class="offers-filter-tab" data-filter="active">
            <i class="fas fa-check-circle"></i> Actives
        </button>
        <button class="offers-filter-tab" data-filter="expired">
            <i class="fas fa-clock"></i> Expirées
        </button>
    </div>
</div>

<!-- Offers Grid -->
<?php if (!empty($offres)): ?>
    <div class="offers-grid">
        <?php foreach ($offres as $offre): ?>
            <?php 
            $date_exp = new DateTime($offre['dateExp']);
            $date_pub = new DateTime($offre['datePub']);
            $today = new DateTime();
            $is_expired = $date_exp < $today;
            $days_left = $is_expired ? 0 : $today->diff($date_exp)->days;
            $total_days = $date_pub->diff($date_exp)->days;
            $elapsed_days = $date_pub->diff($today)->days;
            $progress = $total_days > 0 ? min(100, ($elapsed_days / $total_days) * 100) : 100;
            $status_key = $is_expired ? 'expired' : 'active';
            ?>
            <div class="offer-card-advanced" data-status="<?= $status_key ?>">
                <?php if (!empty($offre['image'])): ?>
                    <div class="offer-card-banner">
                        <img src="<?= Template::asset($offre['image']) ?>" alt="<?= Template::escape($offre['titre']) ?>">
                        <div class="offer-card-banner-overlay"></div>
                    </div>
                <?php else: ?>
                    <div class="offer-card-banner offer-card-banner-placeholder">
                        <div class="offer-card-banner-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="offer-card-body">
                    <div class="offer-card-top">
                        <span class="offer-card-type <?= strtolower($offre['type']) ?>">
                            <i class="fas fa-tag"></i> <?= Template::escape($offre['type']) ?>
                        </span>
                        <span class="offer-card-status <?= $status_key ?>">
                            <span class="status-dot"></span>
                            <?= $is_expired ? 'Expirée' : 'Active' ?>
                        </span>
                    </div>

                    <h3 class="offer-card-title"><?= Template::escape($offre['titre']) ?></h3>

                    <div class="offer-card-dates">
                        <div class="offer-card-date-item">
                            <i class="fas fa-calendar-plus"></i>
                            <span><?= Template::formatDate($offre['datePub']) ?></span>
                        </div>
                        <div class="offer-card-date-item">
                            <i class="fas fa-calendar-times"></i>
                            <span><?= Template::formatDate($offre['dateExp']) ?></span>
                        </div>
                    </div>

                    <!-- Time progress bar -->
                    <div class="offer-card-progress-wrapper">
                        <div class="offer-card-progress-label">
                            <span>Temps écoulé</span>
                            <span><?= $is_expired ? 'Expirée' : $days_left . 'j restants' ?></span>
                        </div>
                        <div class="offer-card-progress-bar">
                            <div class="offer-card-progress-fill <?= $progress > 80 ? 'danger' : ($progress > 50 ? 'warning' : 'safe') ?>" style="width: <?= min(100, $progress) ?>%"></div>
                        </div>
                    </div>

                    <div class="offer-card-candidatures">
                        <i class="fas fa-users"></i>
                        <span><strong><?= $offre['nb_candidatures'] ?? 0 ?></strong> candidature(s)</span>
                    </div>
                </div>

                <div class="offer-card-actions">
                    <?php if (!$is_expired): ?>
                        <button class="offer-card-btn edit" onclick="openEditOfferModal(<?= $offre['id'] ?>, '<?= Template::escape($offre['titre']) ?>', '<?= Template::escape($offre['type']) ?>', '<?= $offre['dateExp'] ?>', '<?= Template::escape($offre['statut']) ?>', '<?= Template::escape($offre['image'] ?? '') ?>')">
                            <i class="fas fa-pen"></i> Modifier
                        </button>
                    <?php endif; ?>
                    <form method="POST" action="<?= Template::url('/offers/delete') ?>" style="display: inline;">
                        <input type="hidden" name="offre_id" value="<?= $offre['id'] ?>">
                        <button type="submit" class="offer-card-btn delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette offre ?');">
                            <i class="fas fa-trash-alt"></i> Supprimer
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="offers-empty-state">
        <div class="offers-empty-icon">
            <i class="fas fa-briefcase"></i>
        </div>
        <h3>Aucune offre publiée</h3>
        <p>Commencez par créer votre première offre de stage</p>
        <button class="offers-btn-create" data-bs-toggle="modal" data-bs-target="#createOfferModal">
            <i class="fas fa-plus"></i>
            <span>Créer une offre</span>
        </button>
    </div>
<?php endif; ?>

<!-- Modal de création d'offre -->
<div class="modal fade offer-modal" id="createOfferModal" tabindex="-1" aria-labelledby="createOfferModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="offer-modal-content">
            <div class="offer-modal-header">
                <div class="offer-modal-header-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div>
                    <h5 class="offer-modal-title" id="createOfferModalLabel">Nouvelle offre</h5>
                    <p class="offer-modal-subtitle">Créez une nouvelle offre de stage</p>
                </div>
                <button type="button" class="offer-modal-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="offer-modal-body">
                <form method="POST" action="<?= Template::url('/offers/create') ?>" enctype="multipart/form-data">
                    <div class="offer-form-group">
                        <label class="offer-form-label">
                            <i class="fas fa-heading"></i> Titre du poste
                        </label>
                        <input type="text" name="titre" class="offer-form-input" placeholder="Ex: Développeur Full Stack" required>
                    </div>
                    <div class="offer-form-row">
                        <div class="offer-form-group">
                            <label class="offer-form-label">
                                <i class="fas fa-tag"></i> Type d'offre
                            </label>
                            <select name="type" class="offer-form-select" required>
                                <option value="">Sélectionner...</option>
                                <option value="Stage">Stage</option>
                                <option value="Alternance">Alternance</option>
                                <option value="Emploi">Emploi</option>
                            </select>
                        </div>
                        <div class="offer-form-group">
                            <label class="offer-form-label">
                                <i class="fas fa-calendar-alt"></i> Date d'expiration
                            </label>
                            <input type="date" name="date_exp" class="offer-form-input" required>
                        </div>
                    </div>
                    <div class="offer-form-group">
                        <label class="offer-form-label">
                            <i class="fas fa-image"></i> Photo de l'offre
                        </label>
                        <div class="offer-file-upload" id="createFileUpload">
                            <input type="file" name="image" accept="image/*" class="offer-file-input" id="createFileInput">
                            <div class="offer-file-placeholder" id="createFilePlaceholder">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Cliquez ou glissez une image ici</span>
                                <small>Formats acceptés : JPG, PNG, GIF</small>
                            </div>
                            <div class="offer-file-preview" id="createFilePreview" style="display:none;">
                                <img id="createPreviewImg" src="" alt="Aperçu">
                                <button type="button" class="offer-file-remove" id="createFileRemove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="offer-form-actions">
                        <button type="button" class="offer-form-btn cancel" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left"></i> Annuler
                        </button>
                        <button type="submit" class="offer-form-btn submit">
                            <i class="fas fa-rocket"></i> Publier l'offre
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de modification d'offre -->
<div class="modal fade offer-modal" id="editOfferModal" tabindex="-1" aria-labelledby="editOfferModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="offer-modal-content">
            <div class="offer-modal-header edit">
                <div class="offer-modal-header-icon">
                    <i class="fas fa-pen"></i>
                </div>
                <div>
                    <h5 class="offer-modal-title" id="editOfferModalLabel">Modifier l'offre</h5>
                    <p class="offer-modal-subtitle">Modifiez les informations de l'offre</p>
                </div>
                <button type="button" class="offer-modal-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="offer-modal-body">
                <form method="POST" action="<?= Template::url('/offers/update') ?>" enctype="multipart/form-data">
                    <input type="hidden" name="offre_id" id="editOffreId">
                    <div class="offer-form-group">
                        <label class="offer-form-label">
                            <i class="fas fa-heading"></i> Titre du poste
                        </label>
                        <input type="text" name="titre" id="editTitre" class="offer-form-input" placeholder="Titre du poste" required>
                    </div>
                    <div class="offer-form-row">
                        <div class="offer-form-group">
                            <label class="offer-form-label">
                                <i class="fas fa-tag"></i> Type d'offre
                            </label>
                            <select name="type" id="editType" class="offer-form-select" required>
                                <option value="">Sélectionner...</option>
                                <option value="Stage">Stage</option>
                                <option value="Alternance">Alternance</option>
                                <option value="Emploi">Emploi</option>
                            </select>
                        </div>
                        <div class="offer-form-group">
                            <label class="offer-form-label">
                                <i class="fas fa-calendar-alt"></i> Date d'expiration
                            </label>
                            <input type="date" name="date_exp" id="editDateExp" class="offer-form-input" required>
                        </div>
                    </div>
                    <div class="offer-form-row">
                        <div class="offer-form-group">
                            <label class="offer-form-label">
                                <i class="fas fa-toggle-on"></i> Statut
                            </label>
                            <select name="statut" id="editStatut" class="offer-form-select" required>
                                <option value="OUVERT">Ouvert</option>
                                <option value="FERME">Fermé</option>
                            </select>
                        </div>
                        <div class="offer-form-group">
                            <label class="offer-form-label">
                                <i class="fas fa-image"></i> Photo de l'offre
                            </label>
                            <div class="offer-file-upload" id="editFileUpload">
                                <input type="file" name="image" accept="image/*" class="offer-file-input" id="editFileInput">
                                <div class="offer-file-placeholder" id="editFilePlaceholder">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Cliquez ou glissez une image</span>
                                    <small>JPG, PNG, GIF</small>
                                </div>
                                <div class="offer-file-preview" id="editFilePreview" style="display:none;">
                                    <img id="editPreviewImg" src="" alt="Aperçu">
                                    <button type="button" class="offer-file-remove" id="editFileRemove">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="offer-form-actions">
                        <button type="button" class="offer-form-btn cancel" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left"></i> Annuler
                        </button>
                        <button type="submit" class="offer-form-btn submit edit">
                            <i class="fas fa-save"></i> Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openEditOfferModal(offreId, titre, type, dateExp, statut, image) {
    document.getElementById('editOffreId').value = offreId;
    document.getElementById('editTitre').value = titre;
    document.getElementById('editType').value = type;
    document.getElementById('editDateExp').value = dateExp;
    document.getElementById('editStatut').value = statut;

    // Afficher l'image existante si elle existe
    const editFilePlaceholder = document.getElementById('editFilePlaceholder');
    const editFilePreview = document.getElementById('editFilePreview');
    const editPreviewImg = document.getElementById('editPreviewImg');
    if (image && editPreviewImg) {
        editPreviewImg.src = '<?= Template::asset('') ?>' + image;
        editFilePlaceholder.style.display = 'none';
        editFilePreview.style.display = 'flex';
    } else {
        editFilePlaceholder.style.display = 'flex';
        editFilePreview.style.display = 'none';
    }

    const modal = new bootstrap.Modal(document.getElementById('editOfferModal'));
    modal.show();
}
</script>

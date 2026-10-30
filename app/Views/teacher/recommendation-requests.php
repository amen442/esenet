<?php
require_once __DIR__ . '/../layouts/components.php';
require_once __DIR__ . '/../../config/db_connect.php';

// Obtenir l'ID de l'enseignant connecté
$teacher_id = get_teacher_id();

if (!$teacher_id) {
    header("Location: ../../login.php");
    exit();
}

// Traitement de la réponse à une demande de recommandation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_request'])) {
    $id_demande = $_POST['id_demande'];
    $reponse = $_POST['reponse'];
    $statut = $_POST['statut']; // 'ACCEPTEE' ou 'REFUSEE'
    
    if (repondre_demande_recommandation($id_demande, $reponse, $statut)) {
        $_SESSION['success_message'] = "Votre réponse a été envoyée avec succès !";
    } else {
        $_SESSION['error_message'] = "Erreur lors de l'envoi de votre réponse.";
    }
    
    header("Location: recommendation-requests.php");
    exit();
}

// Récupérer les messages de succès/erreur depuis la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Ne pas effacer les messages immédiatement pour qu'ils persistent après rafraîchissement

// Récupérer les demandes en attente
$demandes_en_attente = get_demandes_recommandation_enseignant($teacher_id, 'EN_ATTENTE');
$demandes_traitees = get_demandes_recommandation_enseignant($teacher_id, 'ACCEPTEE');
$demandes_refusees = get_demandes_recommandation_enseignant($teacher_id, 'REFUSEE');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demandes de recommandation - ESEN NET</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/teacher-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/teacher-dashboard.css">
    <link rel="icon" href="../../public/assets/images/1.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include '../layouts/teacher-navbar.php'; ?>
    
    <main class="dashboard-main">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1 class="dashboard-title">
                    <i class="fas fa-star"></i>
                    Demandes de recommandation
                </h1>
                <p class="dashboard-subtitle">Répondez aux demandes de recommandation des entreprises</p>
            </div>

            <?php if ($success_message): ?>
                <div class="alert-card alert-success-custom">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert-card alert-error-custom">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Demandes en attente -->
            <div class="dashboard-card">
                <h3>
                    <i class="fas fa-clock"></i>
                    Demandes en attente
                    <?php if (count($demandes_en_attente) > 0): ?>
                        <span class="badge bg-warning"><?php echo count($demandes_en_attente); ?></span>
                    <?php endif; ?>
                </h3>
                
                <?php if (empty($demandes_en_attente)): ?>
                    <p class="text-muted">Aucune demande en attente</p>
                <?php else: ?>
                    <?php foreach ($demandes_en_attente as $demande): ?>
                        <div class="recommendation-request-card">
                            <div class="request-header">
                                <div class="request-info">
                                    <h4>
                                        <i class="fas fa-building"></i>
                                        <?php echo htmlspecialchars($demande['entreprise_nom']); ?>
                                    </h4>
                                    <p class="request-date">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($demande['date_demande'])); ?>
                                    </p>
                                </div>
                                <div class="request-actions">
                                    <button class="btn btn-sm btn-success" 
                                            onclick="showResponseModal(<?php echo $demande['id']; ?>, 'ACCEPTEE')">
                                        <i class="fas fa-check"></i> Accepter
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="showResponseModal(<?php echo $demande['id']; ?>, 'REFUSEE')">
                                        <i class="fas fa-times"></i> Refuser
                                    </button>
                                </div>
                            </div>
                            
                            <div class="request-details">
                                <div class="detail-item">
                                    <strong>Étudiant:</strong>
                                    <?php echo htmlspecialchars($demande['etudiant_nom'] . ' ' . $demande['etudiant_prenom']); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>Offre:</strong>
                                    <?php echo htmlspecialchars($demande['offre_titre']); ?>
                                </div>
                                <?php if (!empty($demande['message'])): ?>
                                    <div class="detail-item">
                                        <strong>Message:</strong>
                                        <p><?php echo nl2br(htmlspecialchars($demande['message'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Demandes traitées -->
            <div class="dashboard-card">
                <h3>
                    <i class="fas fa-check-circle"></i>
                    Demandes traitées
                </h3>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Entreprise</th>
                                <th>Étudiant</th>
                                <th>Offre</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $traitees = array_merge($demandes_traitees, $demandes_refusees);
                            foreach ($traitees as $demande): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($demande['entreprise_nom']); ?></td>
                                    <td><?php echo htmlspecialchars($demande['etudiant_nom'] . ' ' . $demande['etudiant_prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($demande['offre_titre']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($demande['date_demande'])); ?></td>
                                    <td>
                                        <?php if ($demande['statut'] === 'ACCEPTEE'): ?>
                                            <span class="badge bg-success">Acceptée</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Refusée</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" 
                                                onclick="viewResponse(<?php echo $demande['id']; ?>)">
                                            <i class="fas fa-eye"></i> Voir
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de réponse -->
    <div class="modal fade" id="responseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Répondre à la demande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="respond_request" value="1">
                        <input type="hidden" name="id_demande" id="id_demande">
                        <input type="hidden" name="statut" id="statut">
                        
                        <div class="form-group">
                            <label>Votre réponse</label>
                            <textarea name="reponse" class="form-control" rows="4" required
                                      placeholder="Expliquez votre décision..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Envoyer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de visualisation -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails de la réponse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewModalBody">
                    <!-- Contenu dynamique -->
                </div>
            </div>
        </div>
    </div>

<?php include 'notification-box.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../public/assets/js/main.js"></script>
<script>
function showResponseModal(id, statut) {
    document.getElementById('id_demande').value = id;
    document.getElementById('statut').value = statut;
    
    const submitBtn = document.getElementById('submitBtn');
    if (statut === 'ACCEPTEE') {
        submitBtn.className = 'btn btn-success';
        submitBtn.innerHTML = '<i class="fas fa-check"></i> Accepter';
    } else {
        submitBtn.className = 'btn btn-danger';
        submitBtn.innerHTML = '<i class="fas fa-times"></i> Refuser';
    }
    
    new bootstrap.Modal(document.getElementById('responseModal')).show();
}

function viewResponse(id) {
    // Récupérer les détails de la demande via AJAX ou les afficher directement
    const demandes = <?php echo json_encode(array_merge($traitees, [])); ?>;
    const demande = demandes.find(d => d.id == id);
    
    if (demande) {
        const content = `
            <div class="request-details">
                <div class="detail-item">
                    <strong>Entreprise:</strong> ${demande.entreprise_nom}
                </div>
                <div class="detail-item">
                    <strong>Étudiant:</strong> ${demande.etudiant_nom} ${demande.etudiant_prenom}
                </div>
                <div class="detail-item">
                    <strong>Offre:</strong> ${demande.offre_titre}
                </div>
                <div class="detail-item">
                    <strong>Date de demande:</strong> ${new Date(demande.date_demande).toLocaleString('fr-FR')}
                </div>
                <div class="detail-item">
                    <strong>Date de réponse:</strong> ${new Date(demande.date_reponse).toLocaleString('fr-FR')}
                </div>
                <div class="detail-item">
                    <strong>Statut:</strong> 
                    <span class="badge ${demande.statut === 'ACCEPTEE' ? 'bg-success' : 'bg-danger'}">
                        ${demande.statut === 'ACCEPTEE' ? 'Acceptée' : 'Refusée'}
                    </span>
                </div>
                <div class="detail-item">
                    <strong>Votre réponse:</strong>
                    <p>${demande.reponse || 'Aucune réponse fournie'}</p>
                </div>
            </div>
        `;
        
        document.getElementById('viewModalBody').innerHTML = content;
        new bootstrap.Modal(document.getElementById('viewModal')).show();
    }
}
</script>
<script src="../../public/assets/js/main.js"></script>
</body>
</html>

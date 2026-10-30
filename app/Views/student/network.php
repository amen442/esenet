<?php
require_once __DIR__ . '/../layouts/components.php';
require_once __DIR__ . '/../../config/db_connect.php';

// Obtenir l'ID de l'étudiant connecté
$etudiant_id = get_etudiant_id();

// Si aucun étudiant n'est connecté, rediriger vers la page de connexion
if (!$etudiant_id) {
    header("Location: ../../login.php");
    exit();
}

// Traitement de l'ajout d'une personne
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_person'])) {
    $nom = $_POST['nom'] ?? '';
    $poste = $_POST['poste'] ?? '';
    $nomsociete = $_POST['nomsociete'] ?? '';
    $cin = $_POST['cin'] ?? null;
    $experience = $_POST['experience'] ?? '';
    $statut = $_POST['statut'] ?? 'contact';
    
    // Validation du CIN (8 chiffres si renseigné)
    if (!empty($cin)) {
        // Supprimer les espaces et autres caractères non numériques
        $cin = preg_replace('/[^0-9]/', '', $cin);
        
        if (strlen($cin) !== 8) {
            $error_message = "Le CIN doit contenir exactement 8 chiffres.";
        } else {
            // Le CIN est valide
            $cin = (int)$cin;
        }
    } else {
        $cin = null; // CIN optionnel
    }
    
    $personne_id = null;
    
    // Vérifier si la personne existe déjà par CIN
    if ($cin) {
        $check_cin = $conn->prepare("SELECT id FROM personne WHERE cin = ?");
        $check_cin->execute([$cin]);
        $existing_person = $check_cin->fetch();
        if ($existing_person) {
            $personne_id = $existing_person['id'];
        }
    }
    
    // Si la personne n'existe pas, la créer
    if (!$personne_id) {
        $stmt = $conn->prepare("INSERT INTO personne (nom, poste, nomsociete, cin, dateInscription) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt->execute([$nom, $poste, $nomsociete, $cin])) {
            $personne_id = $conn->lastInsertId();
        } else {
            $error_message = "Erreur lors de l'ajout de la personne.";
        }
    }
    
    // Créer une nouvelle relation (permettre plusieurs relations par personne)
    if ($personne_id) {
        $stmt_rel = $conn->prepare("INSERT INTO relation (idEtudiant, idPersonne, dateAjout, statut, experience) VALUES (?, ?, NOW(), ?, ?)");
        if ($stmt_rel->execute([$etudiant_id, $personne_id, $statut, $experience])) {
            $_SESSION['success_message'] = "Personne ajoutée avec succès !";
            header("Location: network.php");
            exit();
        } else {
            $error_message = "Erreur lors de la création de la relation.";
        }
    }
}

// Vérifier le message de succès dans la session et le supprimer après affichage
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Récupérer les personnes rencontrées par l'étudiant avec regroupement par CIN
$mes_contacts = [];
try {
    $stmt = $conn->prepare("SELECT p.id, p.nom, p.poste, p.nomsociete, p.cin, MAX(r.dateAjout) as dateAjout, GROUP_CONCAT(DISTINCT r.statut SEPARATOR ', ') as statuts, GROUP_CONCAT(DISTINCT r.experience SEPARATOR ' | ') as experiences FROM personne p JOIN relation r ON p.id = r.idPersonne WHERE r.idEtudiant = ? GROUP BY COALESCE(p.cin, p.id) ORDER BY MAX(r.dateAjout) DESC");
    $stmt->execute([$etudiant_id]);
    foreach ($stmt->fetchAll() as $row) {
        $mes_contacts[] = $row;
    }
} catch (Exception $e) {
    $mes_contacts = [];
}

// Récupérer toutes les relations détaillées pour le modal
$all_relations = [];
try {
    $stmt = $conn->prepare("SELECT r.id, r.idPersonne, r.statut, r.experience, r.dateAjout, p.nom FROM relation r JOIN personne p ON r.idPersonne = p.id WHERE r.idEtudiant = ? ORDER BY r.dateAjout DESC");
    $stmt->execute([$etudiant_id]);
    foreach ($stmt->fetchAll() as $row) {
        $all_relations[] = $row;
    }
} catch (Exception $e) {
    $all_relations = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réseau - ESEN NET</title>
    <meta name="description" content="Étendez votre réseau professionnel">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../public/assets/css/student-navbar.css">
    <link rel="stylesheet" href="../../public/assets/css/student-dashboard.css">
    <link rel="icon" href="../../public/assets/images/1.jpeg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== CUSTOM CURSOR ===== */
        * {
            cursor: none;
        }

        .custom-cursor {
            position: fixed;
            top: 0;
            left: 0;
            width: 20px;
            height: 20px;
            border: 2px solid #3b82f6;
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            transition: transform 0.15s ease, background 0.15s ease, border-color 0.15s ease;
            transform: translate(-50%, -50%);
        }

        .custom-cursor-dot {
            position: fixed;
            top: 0;
            left: 0;
            width: 6px;
            height: 6px;
            background: #3b82f6;
            border-radius: 50%;
            pointer-events: none;
            z-index: 10000;
            transform: translate(-50%, -50%);
        }

        .custom-cursor.hover {
            transform: translate(-50%, -50%) scale(1.5);
            background: rgba(59, 130, 246, 0.1);
            border-color: #00d4c4;
        }

        .custom-cursor.clicking {
            transform: translate(-50%, -50%) scale(0.8);
            background: rgba(0, 212, 196, 0.3);
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.querySelector('.toggle-dark');
            const savedTheme = localStorage.getItem('theme') || 'light';
            if (savedTheme === 'dark') {
                document.body.classList.add('dark');
            }
            themeToggle.addEventListener('click', function() {
                document.body.classList.toggle('dark');
                const isDark = document.body.classList.contains('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            });
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const verticalNavbar = document.getElementById('verticalNavbar');
            if (mobileMenuToggle && verticalNavbar) {
                mobileMenuToggle.addEventListener('click', function() {
                    verticalNavbar.classList.toggle('active');
                });
                document.addEventListener('click', function(e) {
                    if (!verticalNavbar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                        verticalNavbar.classList.remove('active');
                    }
                });
            }
            
            // Toggle navbar avec la flèche
            const navbarToggle = document.getElementById('navbarToggle');
            const navbarShowBtn = document.getElementById('navbarShowBtn');
            
            if (navbarToggle && verticalNavbar) {
                navbarToggle.addEventListener('click', function() {
                    verticalNavbar.classList.toggle('collapsed');
                    updateNavbarShowBtn();
                });
            }
            
            if (navbarShowBtn && verticalNavbar) {
                navbarShowBtn.addEventListener('click', function() {
                    verticalNavbar.classList.remove('collapsed');
                    updateNavbarShowBtn();
                });
            }
            
            function updateNavbarShowBtn() {
                if (verticalNavbar.classList.contains('collapsed')) {
                    navbarShowBtn.style.display = 'flex';
                } else {
                    navbarShowBtn.style.display = 'none';
                }
            }
            
            // Initialiser l'état du bouton show
            updateNavbarShowBtn();

            // Custom Cursor
            const cursor = document.querySelector('.custom-cursor');
            const cursorDot = document.querySelector('.custom-cursor-dot');
            let mouseX = 0, mouseY = 0;
            let cursorX = 0, cursorY = 0;

            if (cursor && cursorDot) {
                document.addEventListener('mousemove', (e) => {
                    mouseX = e.clientX;
                    mouseY = e.clientY;
                });

                function animateCursor() {
                    const dx = mouseX - cursorX;
                    const dy = mouseY - cursorY;
                    cursorX += dx * 0.15;
                    cursorY += dy * 0.15;

                    cursor.style.left = cursorX + 'px';
                    cursor.style.top = cursorY + 'px';
                    cursorDot.style.left = mouseX + 'px';
                    cursorDot.style.top = mouseY + 'px';

                    requestAnimationFrame(animateCursor);
                }
                animateCursor();

                // Hover effect on interactive elements
                const interactiveElements = document.querySelectorAll('a, button, input, .btn, .form-group input, .form-group textarea, .dashboard-card, .network-card, .add-contact-btn');
                interactiveElements.forEach(el => {
                    el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
                    el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
                });

                // Click effect
                document.addEventListener('mousedown', () => cursor.classList.add('clicking'));
                document.addEventListener('mouseup', () => cursor.classList.remove('clicking'));
            }
        });
    </script>
</head>
<body>
    <!-- Animated Background Shapes -->
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>

    <!-- Custom Cursor -->
    <div class="custom-cursor"></div>
    <div class="custom-cursor-dot"></div>

    <?php include 'student-navbar.php'; ?>

    <div class="grid-pattern"></div>

    <main class="main-content-wrapper">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>Réseau</h1>
                <p>Étendez votre réseau professionnel</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success" style="background: rgba(0, 212, 196, 0.2); border: 1px solid rgba(0, 212, 196, 0.4); color: #00d4c4; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem;">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #ef4444; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Ajouter une personne rencontrée</h3>
                    <form method="POST" action="" autocomplete="off">
                        <input type="hidden" name="add_person" value="1">
                        <div class="form-group">
                            <label>Nom complet</label>
                            <input type="text" name="nom" required style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff;">
                        </div>
                        <div class="form-group">
                            <label>Poste</label>
                            <input type="text" name="poste" required style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff;">
                        </div>
                        <div class="form-group">
                            <label>Entreprise / Société</label>
                            <input type="text" name="nomsociete" style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff;">
                        </div>
                        <div class="form-group">
                            <label>CIN (optionnel - 8 chiffres)</label>
                            <input type="text" name="cin" id="cinInput" maxlength="8" pattern="[0-9]{8}" placeholder="12345678" style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff;">
                            <small style="color: #94a3b8; font-size: 0.85rem;">8 chiffres obligatoires si renseigné</small>
                        </div>
                        <div class="form-group">
                            <label>Expérience partagée</label>
                            <textarea name="experience" rows="3" placeholder="Décrivez votre rencontre et ce que vous avez appris..." style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff;"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Type de relation</label>
                            <select name="statut" required style="width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff;">
                                <option value="contact">Contact</option>
                                <option value="sponsor">Sponsor</option>
                                <option value="partenaire">Partenaire</option>
                                <option value="speaker">Speaker</option>
                                <option value="mentor">Mentor</option>
                                <option value="collaborateur">Collaborateur</option>
                            </select>
                        </div>
                        <button type="submit" class="btn primary">
                            <i class="fas fa-user-plus"></i> Ajouter au réseau
                        </button>
                    </form>
                </div>

                <div class="dashboard-card">
                    <h3>Mes contacts</h3>
                    <div class="applications-list">
                        <?php if (!empty($mes_contacts)): ?>
                            <?php foreach ($mes_contacts as $contact): ?>
                                <div class="application-item" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 1rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: start;">
                                    <div class="application-info" style="flex: 1;">
                                        <h4 style="color: #ffffff; margin: 0 0 0.5rem 0; cursor: pointer;" onclick="openPersonModal(<?php echo $contact['id']; ?>, '<?php echo htmlspecialchars($contact['nom']); ?>', '<?php echo htmlspecialchars($contact['poste']); ?>', '<?php echo htmlspecialchars($contact['nomsociete'] ?? 'Indépendant'); ?>', '<?php echo htmlspecialchars($contact['cin'] ?? ''); ?>')"><?php echo htmlspecialchars($contact['nom']); ?> <i class="fas fa-eye" style="font-size: 0.8rem; margin-left: 0.5rem;"></i></h4>
                                        <p style="color: #e2e8f0; margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($contact['poste']); ?> - <?php echo htmlspecialchars($contact['nomsociete'] ?? 'Indépendant'); ?></p>
                                        <?php if (!empty($contact['experiences'])): ?>
                                            <p style="font-size: 0.85rem; color: #e2e8f0; margin-top: 0.5rem; font-style: italic;">
                                                <i class="fas fa-comment-alt"></i> "<?php echo htmlspecialchars($contact['experiences']); ?>"
                                            </p>
                                        <?php endif; ?>
                                        <p style="font-size: 0.85rem; color: #e2e8f0; margin-top: 0.25rem;">
                                            <i class="fas fa-calendar-alt"></i> Ajouté le <?php echo date('d/m/Y', strtotime($contact['dateAjout'])); ?>
                                        </p>
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-end;">
                                        <?php 
                                        $statuts = explode(', ', $contact['statuts']);
                                        foreach ($statuts as $statut): 
                                        ?>
                                            <span class="application-status accepted" style="text-transform: capitalize; padding: 0.5rem 1rem; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($statut); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: #e2e8f0; padding: 2rem;">Aucun contact dans votre réseau pour le moment</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de détails de la personne -->
    <div class="modal-overlay" id="personModal" style="display: none;">
        <div class="modal-container" style="max-width: 700px;">
            <div class="modal-header">
                <h3 id="modalPersonName">Détails de la personne</h3>
                <button class="modal-close" onclick="closePersonModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div style="background: rgba(255, 255, 255, 0.05); padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem;">
                    <h4 style="color: #ffffff; margin: 0 0 1rem 0;">Informations personnelles</h4>
                    <p style="color: #e2e8f0; margin: 0.5rem 0;"><strong>Poste:</strong> <span id="modalPersonPoste"></span></p>
                    <p style="color: #e2e8f0; margin: 0.5rem 0;"><strong>Entreprise:</strong> <span id="modalPersonSociete"></span></p>
                    <p style="color: #e2e8f0; margin: 0.5rem 0;"><strong>CIN:</strong> <span id="modalPersonCin"></span></p>
                </div>
                
                <h4 style="color: #ffffff; margin: 0 0 1rem 0;">Historique des relations</h4>
                <div id="modalRelationsList">
                    <!-- Relations will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        const allRelations = <?php echo json_encode($all_relations); ?>;

        // Validation du CIN (8 chiffres)
        document.addEventListener('DOMContentLoaded', function() {
            const cinInput = document.getElementById('cinInput');
            if (cinInput) {
                cinInput.addEventListener('input', function(e) {
                    // N'autoriser que les chiffres
                    this.value = this.value.replace(/[^0-9]/g, '');
                    
                    // Limiter à 8 chiffres
                    if (this.value.length > 8) {
                        this.value = this.value.slice(0, 8);
                    }
                });
                
                cinInput.addEventListener('blur', function() {
                    // Validation finale
                    if (this.value && this.value.length !== 8) {
                        this.setCustomValidity('Le CIN doit contenir exactement 8 chiffres');
                        this.reportValidity();
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
            
            // Formatage des numéros de téléphone (XX XXX XXX)
            const phoneInputs = document.querySelectorAll('input[type="tel"], input[name*="phone"], input[name*="telephone"], input[name*="tel"]');
            phoneInputs.forEach(function(input) {
                input.addEventListener('input', function(e) {
                    // N'autoriser que les chiffres et espaces
                    let value = this.value.replace(/[^0-9 ]/g, '');
                    
                    // Compter les chiffres (ignorer les espaces)
                    let digitsOnly = value.replace(/[^0-9]/g, '');
                    
                    // Limiter à 8 chiffres maximum mais permettre la correction
                    if (digitsOnly.length > 8) {
                        digitsOnly = digitsOnly.slice(0, 8);
                    }
                    
                    // Appliquer le formatage XX XXX XXX uniquement si on a assez de chiffres
                    if (digitsOnly.length >= 2) {
                        value = digitsOnly.slice(0, 2);
                        if (digitsOnly.length > 2) {
                            value += ' ' + digitsOnly.slice(2, 5);
                        }
                        if (digitsOnly.length > 5) {
                            value += ' ' + digitsOnly.slice(5, 8);
                        }
                    } else {
                        value = digitsOnly;
                    }
                    
                    this.value = value;
                });
            });
        });

        function openPersonModal(personId, nom, poste, societe, cin) {
            document.getElementById('modalPersonName').textContent = nom;
            document.getElementById('modalPersonPoste').textContent = poste;
            document.getElementById('modalPersonSociete').textContent = societe;
            document.getElementById('modalPersonCin').textContent = cin || 'Non renseigné';
            
            const relationsList = document.getElementById('modalRelationsList');
            relationsList.innerHTML = '';
            
            const personRelations = allRelations.filter(r => r.idPersonne === personId);
            
            if (personRelations.length > 0) {
                personRelations.forEach(rel => {
                    const relDiv = document.createElement('div');
                    relDiv.style.cssText = 'background: rgba(255, 255, 255, 0.05); padding: 1rem; border-radius: 8px; margin-bottom: 0.75rem; border: 1px solid rgba(255, 255, 255, 0.1);';
                    relDiv.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="padding: 0.25rem 0.75rem; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-transform: capitalize;">
                                <i class="fas fa-tag"></i> ${rel.statut}
                            </span>
                            <span style="font-size: 0.8rem; color: #e2e8f0;">
                                <i class="fas fa-calendar-alt"></i> ${new Date(rel.dateAjout).toLocaleDateString('fr-FR')}
                            </span>
                        </div>
                        ${rel.experience ? `<p style="font-size: 0.85rem; color: #e2e8f0; margin: 0; font-style: italic;"><i class="fas fa-comment-alt"></i> "${rel.experience}"</p>` : ''}
                    `;
                    relationsList.appendChild(relDiv);
                });
            } else {
                relationsList.innerHTML = '<p style="color: #e2e8f0; text-align: center; padding: 1rem;">Aucune relation enregistrée</p>';
            }
            
            document.getElementById('personModal').style.display = 'flex';
        }

        function closePersonModal() {
            document.getElementById('personModal').style.display = 'none';
        }

        // Fermer le modal en cliquant à l'extérieur
        document.getElementById('personModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePersonModal();
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../public/assets/js/main.js"></script>

    <!-- Chatbot Widget -->
    <div class="chatbot-widget" id="chatbotWidget">
        <div class="chatbot-toggle" id="chatbotToggle">
            <i class="fas fa-robot"></i>
        </div>
        <div class="chatbot-container" id="chatbotContainer">
            <div class="chatbot-header">
                <div class="chatbot-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chatbot-title">
                    <h4>Assistant ESEN NET</h4>
                    <span class="status">En ligne</span>
                </div>
                <button class="chatbot-close" id="chatbotClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="chatbot-messages" id="chatbotMessages">
                <div class="message bot">
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">
                        <p>Bonjour ! Je suis votre assistant ESEN NET. Je peux analyser votre profil et vous recommander les meilleures offres et événements selon vos compétences et badges. Voulez-vous que je fasse une analyse ?</p>
                    </div>
                </div>
            </div>
            <div class="chatbot-input">
                <input type="text" id="chatbotInput" placeholder="Tapez votre message...">
                <button id="chatbotSend">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Chatbot Data -->
    <script>
        window.studentData = {
            badges: <?php echo json_encode($etudiant_info['badges'] ?? ['Débutant']); ?>,
            skills: <?php echo json_encode($etudiant_info['specialite'] ?? 'Informatique'); ?>,
            offers: <?php echo json_encode($offres ?? []); ?>,
            events: <?php echo json_encode($events ?? []); ?>
        };
    </script>

    
<!-- Chatbot JavaScript -->
    <script src="../../public/assets/js/chatbot.js"></script>
</body>
</html>

<?php
require_once __DIR__ . '/../config/db_connect.php';

// ===== FONCTIONS POUR LES OFFRES =====

// Obtenir toutes les offres d'une entreprise
function get_entreprise_offres($entreprise_id) {
    global $conn;
    $sql = "SELECT o.*, 
            (SELECT COUNT(*) FROM candidature c WHERE c.idOffre = o.id) as nb_candidatures
            FROM offre o 
            WHERE o.idEntreprise = ? 
            ORDER BY o.datePub DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$entreprise_id]);
    return $stmt->fetchAll();
}

// Obtenir les offres récentes d'une entreprise
function get_recent_offres($entreprise_id, $limit = 3) {
    global $conn;
    $sql = "SELECT o.*, 
            (SELECT COUNT(*) FROM candidature c WHERE c.idOffre = o.id) as nb_candidatures
            FROM offre o 
            WHERE o.idEntreprise = ? 
            ORDER BY o.datePub DESC 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$entreprise_id, $limit]);
    return $stmt->fetchAll();
}

// Créer une nouvelle offre
function create_offre($entreprise_id, $titre, $type, $date_exp, $statut = 'OUVERT', $image = null) {
    global $conn;
    $date_pub = date('Y-m-d');
    $sql = "INSERT INTO offre (titre, type, datePub, dateExp, statut, image, idEntreprise)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$titre, $type, $date_pub, $date_exp, $statut, $image, $entreprise_id]);
}

// Mettre à jour une offre
function update_offre($offre_id, $titre, $type, $date_exp, $statut, $image = null) {
    global $conn;
    if ($image !== null) {
        $sql = "UPDATE offre SET titre = ?, type = ?, dateExp = ?, statut = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$titre, $type, $date_exp, $statut, $image, $offre_id]);
    } else {
        $sql = "UPDATE offre SET titre = ?, type = ?, dateExp = ?, statut = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$titre, $type, $date_exp, $statut, $offre_id]);
    }
}

// Supprimer une offre
function delete_offre($offre_id) {
    global $conn;
    $sql = "DELETE FROM offre WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$offre_id]);
}

// Obtenir une offre par ID
function get_offre_by_id($offre_id) {
    global $conn;
    $sql = "SELECT * FROM offre WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$offre_id]);
    return $stmt->fetch();
}

// Clôturer une offre
function close_offre($offre_id) {
    global $conn;
    $sql = "UPDATE offre SET statut = 'FERME' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$offre_id]);
}

// ===== FONCTIONS POUR LES CANDIDATURES =====

// Obtenir toutes les candidatures pour les offres d'une entreprise
function get_entreprise_candidatures($entreprise_id) {
    global $conn;
    $sql = "SELECT c.*, e.nom, e.prenom, e.specialite, o.titre as offre_titre
            FROM candidature c
            JOIN etudiant e ON c.idEtudiant = e.id
            JOIN offre o ON c.idOffre = o.id
            WHERE o.idEntreprise = ?
            ORDER BY c.datePost DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$entreprise_id]);
    return $stmt->fetchAll();
}

// Mettre à jour le statut d'une candidature
function update_candidature_statut($candidature_id, $statut) {
    global $conn;
    $sql = "UPDATE candidature SET statut = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$statut, $candidature_id]);
}

// ===== FONCTIONS POUR LES ÉVÉNEMENTS =====

// La fonction get_all_evenements() est déclarée dans config/db_connect.php
// pour éviter les conflits de redéclaration

// Obtenir les événements à venir
function get_upcoming_evenements($limit = 3, $entreprise_id = null) {
    global $conn;
    if ($entreprise_id) {
        $sql = "SELECT * FROM evenement
                WHERE entreprise_id = ?
                ORDER BY dateEvent DESC
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$entreprise_id, $limit]);
    } else {
        $sql = "SELECT * FROM evenement
                ORDER BY dateEvent DESC
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$limit]);
    }
    return $stmt->fetchAll();
}

// Créer un nouvel événement
function create_evenement($titre, $description, $date_event, $lieu, $capacite, $statut = 'OUVERT', $image = null, $entreprise_id = null) {
    global $conn;
    $sql = "INSERT INTO evenement (titre, description, dateEvent, lieu, capacite, statut, image, entreprise_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$titre, $description, $date_event, $lieu, $capacite, $statut, $image, $entreprise_id]);
}

// Mettre à jour un événement
function update_evenement($event_id, $titre, $description, $date_event, $lieu, $capacite, $statut, $image = null) {
    global $conn;
    if ($image !== null) {
        $sql = "UPDATE evenement SET titre = ?, description = ?, dateEvent = ?, lieu = ?, capacite = ?, statut = ?, image = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$titre, $description, $date_event, $lieu, $capacite, $statut, $image, $event_id]);
    } else {
        $sql = "UPDATE evenement SET titre = ?, description = ?, dateEvent = ?, lieu = ?, capacite = ?, statut = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([$titre, $description, $date_event, $lieu, $capacite, $statut, $event_id]);
    }
}

// Supprimer un événement
function delete_evenement($event_id) {
    global $conn;
    $sql = "DELETE FROM evenement WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$event_id]);
}

// Obtenir les participants aux événements de l'entreprise
function get_event_participants($entreprise_id) {
    global $conn;
    $sql = "SELECT p.id, p.dateInscri, p.role, p.statut, p.badge,
                   e.id as event_id, e.titre as event_titre, e.dateEvent,
                   et.id as etudiant_id, et.nom, et.prenom, et.specialite
            FROM participation p
            JOIN evenement e ON p.idEvenement = e.id
            JOIN etudiant et ON p.idEtudiant = et.id
            WHERE e.entreprise_id = ?
            ORDER BY e.dateEvent DESC, p.dateInscri DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$entreprise_id]);
    return $stmt->fetchAll();
}

// Obtenir un événement par ID
function get_evenement_by_id($event_id) {
    global $conn;
    $sql = "SELECT * FROM evenement WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$event_id]);
    return $stmt->fetch();
}

// ===== FONCTIONS POUR LE PROFIL ENTREPRISE =====

// Mettre à jour les informations de l'entreprise
function update_entreprise_info($entreprise_id, $nomsociete, $secteur, $description, $siteweb, $logo = null) {
    global $conn;
    if ($logo !== null) {
        $sql = "UPDATE entreprise SET nomsociete = ?, secteur = ?, description = ?, siteweb = ?, logo = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
            } else {
        $sql = "UPDATE entreprise SET nomsociete = ?, secteur = ?, description = ?, siteweb = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        }
    return $stmt->execute([$nomsociete, $secteur, $description, $siteweb, $entreprise_id]);
}

// Mettre à jour les informations utilisateur (email, téléphone)
function update_entreprise_user($entreprise_id, $email, $telephone) {
    global $conn;
    $sql = "UPDATE utilisateur SET email = ?, telephone = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$email, $telephone, $entreprise_id]);
}

// Mettre à jour le mot de passe
function update_password($user_id, $new_password) {
    global $conn;
    $sql = "UPDATE utilisateur SET motDePasse = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    return $stmt->execute([$new_password, $user_id]);
}

// Vérifier le mot de passe actuel
function verify_password($user_id, $current_password) {
    global $conn;
    $sql = "SELECT motDePasse FROM utilisateur WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result && $result['motDePasse'] === $current_password;
}

// ===== FONCTIONS POUR LES STATISTIQUES =====

// Obtenir les statistiques du dashboard
function get_dashboard_stats($entreprise_id) {
    global $conn;
    
    // Nombre d'offres publiées
    $sql_offres = "SELECT COUNT(*) as total FROM offre WHERE idEntreprise = ?";
    $stmt = $conn->prepare($sql_offres);
    $stmt->execute([$entreprise_id]);
    $result = $stmt->fetch();
    $nb_offres = $result['total'] ?? 0;
    
    // Nombre de candidatures reçues
    $sql_candidatures = "SELECT COUNT(*) as total 
                        FROM candidature c 
                        JOIN offre o ON c.idOffre = o.id 
                        WHERE o.idEntreprise = ?";
    $stmt = $conn->prepare($sql_candidatures);
    $stmt->execute([$entreprise_id]);
    $result = $stmt->fetch();
    $nb_candidatures = $result['total'] ?? 0;
    
    // Nombre de candidatures acceptées
    $sql_acceptees = "SELECT COUNT(*) as total 
                     FROM candidature c 
                     JOIN offre o ON c.idOffre = o.id 
                     WHERE o.idEntreprise = ? AND c.statut = 'ACCEPTE'";
    $stmt = $conn->prepare($sql_acceptees);
    $stmt->execute([$entreprise_id]);
    $result = $stmt->fetch();
    $nb_acceptees = $result['total'] ?? 0;
    
    // Nombre d'offres actives
    $sql_actives = "SELECT COUNT(*) as total FROM offre 
                   WHERE idEntreprise = ? AND statut = 'OUVERT'";
    $stmt = $conn->prepare($sql_actives);
    $stmt->execute([$entreprise_id]);
    $result = $stmt->fetch();
    $nb_actives = $result['total'] ?? 0;
    
    return [
        'offres' => $nb_offres,
        'candidatures' => $nb_candidatures,
        'acceptees' => $nb_acceptees,
        'actives' => $nb_actives
    ];
}
?>

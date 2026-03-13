<?php
session_start();
require_once "data.php";

if (!isset($_GET['id_rdv'])) {
    exit();
}

$id_rdv = intval($_GET['id_rdv']);
$user_id = $_SESSION['user_id'] ?? 0;

// Marquer les messages comme vus (appel AJAX séparé)
// On ne le fait pas ici pour éviter les ralentissements

$sql = "SELECT m.*, 
               c.nom_complet as nom_client, 
               p.nom_complet as nom_vendeur,
               c.id_cli as client_id,
               p.id_pro as vendeur_id
        FROM chat_messages m
        JOIN rendez_vous r ON m.id_rdv = r.id_rdv
        LEFT JOIN client c ON r.id_client = c.id_cli
        LEFT JOIN maison ma ON r.id_maison = ma.id_maison
        LEFT JOIN proprietaire p ON ma.id_pro = p.id_pro
        WHERE m.id_rdv = ? 
        ORDER BY m.date_envoi ASC";

$stmt = $db->prepare($sql);
if (!$stmt) {
    echo "<div style='text-align: center; color: #ef4444; padding: 40px;'>";
    echo "Erreur de chargement des messages.";
    echo "</div>";
    exit();
}

$stmt->bind_param("i", $id_rdv);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "<div style='text-align: center; color: #94a3b8; padding: 40px; font-style: italic;'>";
    echo "Aucun message pour l'instant. Soyez le premier à écrire !";
    echo "</div>";
    exit();
}

while($msg = $res->fetch_assoc()){
    $is_vendeur = ($msg['role_exp'] === 'vendeur');
    $is_me = ($msg['id_expediteur'] == $user_id);
    $align = $is_vendeur ? 'flex-start' : 'flex-end';
    $bg = $is_vendeur ? '#ffffff' : 'linear-gradient(135deg, #2563eb, #1d4ed8)';
    $color = $is_vendeur ? '#1e293b' : '#ffffff';
    $name_color = $is_vendeur ? '#64748b' : '#2563eb';
    $radius = $is_vendeur ? '18px 18px 18px 4px' : '18px 18px 4px 18px';
    
    if ($is_vendeur) {
        $nom = "<i class='fa-solid fa-store'></i> " . htmlspecialchars($msg['nom_vendeur'] ?? 'Vendeur');
    } else {
        $nom = htmlspecialchars($msg['nom_client'] ?? 'Client') . " <i class='fa-solid fa-user'></i>";
    }

    $heure = date('H:i', strtotime($msg['date_envoi']));
    $message = nl2br(htmlspecialchars($msg['message']));
    
    // Déterminer l'icône de statut
    $status_icon = '';
    if ($is_me) {
        switch($msg['statut']) {
            case 'vu':
                $status_icon = '<i class="fa-solid fa-check-double" style="color: #2563eb;" title="Vu"></i>';
                break;
            case 'delivre':
                $status_icon = '<i class="fa-solid fa-check-double" style="color: #f59e0b;" title="Délivré"></i>';
                break;
            default:
                $status_icon = '<i class="fa-solid fa-check" style="color: #94a3b8;" title="Envoyé"></i>';
        }
    }
    
    // Gestion des fichiers
    $fichier_html = '';
    if (!empty($msg['type_fichier']) && !empty($msg['chemin_fichier'])) {
        $nom_fichier = basename($msg['chemin_fichier']);
        $chemin_fichier = htmlspecialchars($msg['chemin_fichier']);
        
        switch($msg['type_fichier']) {
            case 'image':
                $fichier_html = '<div style="margin-top: 8px;">
                    <a href="' . $chemin_fichier . '" target="_blank">
                        <img src="' . $chemin_fichier . '" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 2px solid rgba(255,255,255,0.2);">
                    </a>
                </div>';
                break;
            case 'pdf':
                $fichier_html = '<div style="margin-top: 8px;">
                    <a href="' . $chemin_fichier . '" target="_blank" style="display: flex; align-items: center; gap: 8px; color: ' . ($is_vendeur ? '#2563eb' : 'white') . '; text-decoration: none; background: rgba(0,0,0,0.1); padding: 8px 12px; border-radius: 8px;">
                        <i class="fa-solid fa-file-pdf" style="color: #ef4444;"></i>
                        <span style="flex: 1;">' . htmlspecialchars($nom_fichier) . '</span>
                        <i class="fa-solid fa-download"></i>
                    </a>
                </div>';
                break;
            case 'word':
                $fichier_html = '<div style="margin-top: 8px;">
                    <a href="' . $chemin_fichier . '" target="_blank" style="display: flex; align-items: center; gap: 8px; color: ' . ($is_vendeur ? '#2563eb' : 'white') . '; text-decoration: none; background: rgba(0,0,0,0.1); padding: 8px 12px; border-radius: 8px;">
                        <i class="fa-solid fa-file-word" style="color: #2563eb;"></i>
                        <span style="flex: 1;">' . htmlspecialchars($nom_fichier) . '</span>
                        <i class="fa-solid fa-download"></i>
                    </a>
                </div>';
                break;
            default:
                $fichier_html = '<div style="margin-top: 8px;">
                    <a href="' . $chemin_fichier . '" target="_blank" style="display: flex; align-items: center; gap: 8px; color: ' . ($is_vendeur ? '#2563eb' : 'white') . '; text-decoration: none; background: rgba(0,0,0,0.1); padding: 8px 12px; border-radius: 8px;">
                        <i class="fa-solid fa-file"></i>
                        <span style="flex: 1;">' . htmlspecialchars($nom_fichier) . '</span>
                        <i class="fa-solid fa-download"></i>
                    </a>
                </div>';
        }
    }

    echo "
    <div class='message-wrapper' data-message-id='{$msg['id_msg']}' style='display: flex; flex-direction: column; align-items: $align; margin-bottom: 16px; width: 100%;'>
        <span style='font-size: 0.7rem; color: $name_color; font-weight: 700; margin-bottom: 4px; text-transform: uppercase;'>$nom</span>
        <div style='background: $bg; color: $color; padding: 12px 16px; border-radius: $radius; max-width: 80%; box-shadow: 0 2px 5px rgba(0,0,0,0.05); word-wrap: break-word; position: relative;'>
            $message
            $fichier_html
            <div style='display: flex; align-items: center; justify-content: flex-end; gap: 4px; margin-top: 4px; font-size: 0.65rem; opacity: 0.7;'>
                <span>$heure</span>
                $status_icon
            </div>
        </div>
    </div>";
}
?>
<?php
session_start();
require_once "data.php";

if (!isset($_GET['id_rdv'])) {
    exit();
}

$id_rdv = intval($_GET['id_rdv']);

$sql = "SELECT m.*, 
               c.nom_complet as nom_client, 
               p.nom_complet as nom_vendeur,
               c.id_cli as client_id,
               p.id_pro as vendeur_id
        FROM discussion_messages m
        JOIN rendez_vous r ON m.id_rdv = r.id_rdv
        LEFT JOIN client c ON r.id_client = c.id_cli
        LEFT JOIN maison ma ON r.id_maison = ma.id_maison
        LEFT JOIN proprietaire p ON ma.id_pro = p.id_pro
        WHERE m.id_rdv = ? 
        ORDER BY m.date_envoi ASC";

$stmt = $db->prepare($sql);
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

    echo "
    <div style='display: flex; flex-direction: column; align-items: $align; margin-bottom: 20px; width: 100%;'>
        <span style='font-size: 0.7rem; color: $name_color; font-weight: 700; margin-bottom: 5px; text-transform: uppercase;'>$nom</span>
        <div style='background: $bg; color: $color; padding: 12px 18px; border-radius: $radius; max-width: 80%; box-shadow: 0 2px 10px rgba(0,0,0,0.05); word-wrap: break-word;'>
            $message
            <div style='font-size: 0.6rem; margin-top: 8px; opacity: 0.7; text-align: right;'>$heure</div>
        </div>
    </div>";
}
?>
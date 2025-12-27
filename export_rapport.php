<?php
require_once "includes/auth.php";
require_once "config/database.php";

// Vérifier le format demandé
$format = isset($_GET['format']) && in_array($_GET['format'], ['csv', 'pdf']) ? $_GET['format'] : 'csv';

// --- Filtres identiques à index.php ---
$conditions = [];
$params = [];
if(!empty($_GET['date_from'])){
    $conditions[] = "v.date_vente >= ?";
    $params[] = $_GET['date_from']." 00:00:00";
}
if(!empty($_GET['date_to'])){
    $conditions[] = "v.date_vente <= ?";
    $params[] = $_GET['date_to']." 23:59:59";
}
if(!empty($_GET['medicament_id'])){
    $conditions[] = "vi.medicament_id = ?";
    $params[] = $_GET['medicament_id'];
}
$where = $conditions ? "WHERE ".implode(" AND ", $conditions) : "";

try {
    $sql = "
        SELECT v.date_vente, u.username AS agent, m.nom AS medicament,
               vi.quantite, vi.prix_unitaire, vi.prix_achat,
               (vi.quantite * (vi.prix_unitaire - vi.prix_achat)) AS benefice,
               (vi.quantite * vi.prix_unitaire) AS total_produit
        FROM ventes v
        JOIN vente_items vi ON vi.vente_id = v.id
        JOIN medicaments m ON m.id = vi.medicament_id
        JOIN users u ON u.id = v.agent_id
        $where
        ORDER BY v.date_vente DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ventes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur SQL : ".$e->getMessage());
}

// --- Export CSV ---
if($format === 'csv'){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=rapport_ventes.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Agent', 'Produit', 'Quantité', 'Prix Unitaire', 'Total', 'Bénéfice']);

    foreach($ventes as $v){
        fputcsv($output, [
            $v['date_vente'],
            $v['agent'],
            $v['medicament'],
            $v['quantite'],
            $v['prix_unitaire'],
            $v['total_produit'],
            $v['benefice']
        ]);
    }
    fclose($output);
    exit;
}

// --- Export PDF ---
if($format === 'pdf'){
    require_once('../vendor/fpdf186/fpdf.php'); // Chemin corrigé vers FPDF

    $pdf = new FPDF('L','mm','A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,"Rapport des ventes",0,1,'C');

    // En-tête
    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(200,200,200);
    $pdf->Cell(30,8,'Date',1,0,'C',true);
    $pdf->Cell(40,8,'Agent',1,0,'C',true);
    $pdf->Cell(60,8,'Produit',1,0,'C',true);
    $pdf->Cell(20,8,'Qté',1,0,'C',true);
    $pdf->Cell(30,8,'Prix U.',1,0,'C',true);
    $pdf->Cell(30,8,'Total',1,0,'C',true);
    $pdf->Cell(30,8,'Bénéfice',1,1,'C',true);

    // Contenu
    $pdf->SetFont('Arial','',10);
    foreach($ventes as $v){
        $pdf->Cell(30,8,$v['date_vente'],1);
        $pdf->Cell(40,8,$v['agent'],1);
        $pdf->Cell(60,8,$v['medicament'],1);
        $pdf->Cell(20,8,$v['quantite'],1,0,'C');
        $pdf->Cell(30,8,number_format($v['prix_unitaire'],0,',',' '),1,0,'R');
        $pdf->Cell(30,8,number_format($v['total_produit'],0,',',' '),1,0,'R');
        $pdf->Cell(30,8,number_format($v['benefice'],0,',',' '),1,1,'R');
    }

    $pdf->Output('D','rapport_ventes.pdf');
    exit;
}

<?php
require_once "includes/auth.php";
require_once "config/database.php";
require_once "vendor/fpdf186/fpdf.php";

if(!isset($_GET['id'])) {
    die("ID de vente manquant.");
}

$vente_id = (int)$_GET['id'];

// Récupérer les informations de la vente
$stmt = $pdo->prepare("
    SELECT v.id, v.date_vente, v.total, u.username AS agent
    FROM ventes v
    LEFT JOIN users u ON u.id = v.agent_id
    WHERE v.id = ?
");
$stmt->execute([$vente_id]);
$vente = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$vente) {
    die("Vente introuvable.");
}

// Récupérer les items de la vente
$stmt = $pdo->prepare("
    SELECT m.nom, vi.quantite, vi.prix_unitaire, (vi.quantite * vi.prix_unitaire) AS total_produit
    FROM vente_items vi
    JOIN medicaments m ON m.id = vi.medicament_id
    WHERE vi.vente_id = ?
");
$stmt->execute([$vente_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Créer le PDF (format ticket de caisse - 80mm)
class TicketPDF extends FPDF {
    function Header() {
        // En-tête du ticket
        $this->SetFont('Arial','B',16);
        $this->Cell(0,8,'PHARMACY STOCK',0,1,'C');
        $this->SetFont('Arial','',10);
        $this->Cell(0,5,'Gestion de Stock Pharmacie',0,1,'C');
        $this->Ln(3);
        $this->Line(10, $this->GetY(), 80, $this->GetY());
        $this->Ln(3);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,5,'Merci de votre visite !',0,1,'C');
    }
}

$pdf = new TicketPDF('P', 'mm', array(80, 200)); // Format ticket 80mm
$pdf->SetMargins(5, 5, 5);
$pdf->AddPage();

// Informations de la facture
$pdf->SetFont('Arial','',9);
$pdf->Cell(0,5,'Facture N°: ' . $vente['id'],0,1);
$pdf->Cell(0,5,'Date: ' . date('d/m/Y H:i', strtotime($vente['date_vente'])),0,1);
$pdf->Cell(0,5,'Agent: ' . $vente['agent'],0,1);
$pdf->Ln(2);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(3);

// En-tête du tableau
$pdf->SetFont('Arial','B',8);
$pdf->Cell(30,5,'Produit',0,0);
$pdf->Cell(10,5,'Qte',0,0,'C');
$pdf->Cell(15,5,'P.U.',0,0,'R');
$pdf->Cell(20,5,'Total',0,1,'R');
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(1);

// Produits
$pdf->SetFont('Arial','',8);
foreach($items as $item) {
    $nom = substr($item['nom'], 0, 20); // Limiter la longueur
    $pdf->Cell(30,4,$nom,0,0);
    $pdf->Cell(10,4,$item['quantite'],0,0,'C');
    $pdf->Cell(15,4,number_format($item['prix_unitaire'],0,',',' '),0,0,'R');
    $pdf->Cell(20,4,number_format($item['total_produit'],0,',',' '),0,1,'R');
}

$pdf->Ln(2);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(2);

// Total
$pdf->SetFont('Arial','B',10);
$pdf->Cell(55,6,'TOTAL:',0,0,'R');
$pdf->Cell(20,6,number_format($vente['total'],0,',',' ') . ' F CFA',0,1,'R');

$pdf->Ln(5);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(3);

// Message de fin
$pdf->SetFont('Arial','I',8);
$pdf->Cell(0,4,'Ticket de caisse - Pharmacy Stock',0,1,'C');
$pdf->Cell(0,4,'Merci de votre confiance !',0,1,'C');

// Générer le PDF
$pdf->Output('D', 'facture_' . $vente_id . '.pdf');



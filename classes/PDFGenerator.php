<?php
require_once __DIR__ . '/../vendor/autoload.php';

class PDFGenerator {
    private $pdf;
    
    public function __construct() {
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $this->pdf->SetCreator('CBG Ticket System');
        $this->pdf->SetAuthor('CBG Management');
        $this->pdf->SetTitle('Transfert Rapport - Ticket Non Résolu');
        
        // Set default header data with logo
        $this->pdf->SetHeaderData('static/logo.png', 30, 'CBG - Gestion des Incidents', 'Transfert Rapport - Ticket Non Résolu');
        
        // Set header and footer fonts
        $this->pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $this->pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        
        // Set default monospaced font
        $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        
        // Set margins
        $this->pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // Set auto page breaks
        $this->pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Set image scale factor
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Set default font subsetting mode
        $this->pdf->setFontSubsetting(true);
        
        // Set font
        $this->pdf->SetFont('helvetica', '', 10);
    }
    
    public function generateTicketReport($ticket, $attachments = []) {
        // Add a page
        $this->pdf->AddPage();
        
        // Add logo at the top
        $this->pdf->Image('static/logo.png', 10, 10, 40, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        
        // Set font for title
        $this->pdf->SetFont('helvetica', 'B', 18);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(0, 15, 'RAPPORT DE TICKET NON RÉSOLU', 0, 1, 'C');
        
        // Add a line under the title
        $this->pdf->Line(20, $this->pdf->GetY(), 190, $this->pdf->GetY());
        $this->pdf->Ln(10);
        
        // Ticket details in a professional format
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'INFORMATIONS DU TICKET', 1, 1, 'C', true);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetFillColor(255, 255, 255);
        
        // Create a table-like structure for ticket details
        $this->pdf->Cell(50, 8, 'ID Ticket:', 1, 0, 'L', true);
        $this->pdf->Cell(0, 8, '#' . $ticket['id'], 1, 1, 'L', true);
        
        $this->pdf->Cell(50, 8, 'Titre:', 1, 0, 'L', true);
        $this->pdf->Cell(0, 8, $ticket['title'], 1, 1, 'L', true);
        
        $this->pdf->Cell(50, 8, 'Priorité:', 1, 0, 'L', true);
        $priorityLabels = ['low' => 'Faible', 'medium' => 'Moyenne', 'high' => 'Élevée'];
        $priorityColor = ['low' => [34, 197, 94], 'medium' => [234, 179, 8], 'high' => [239, 68, 68]];
        $this->pdf->SetTextColor($priorityColor[$ticket['priority']][0], $priorityColor[$ticket['priority']][1], $priorityColor[$ticket['priority']][2]);
        $this->pdf->Cell(0, 8, $priorityLabels[$ticket['priority']] ?? $ticket['priority'], 1, 1, 'L', true);
        $this->pdf->SetTextColor(0, 0, 0);
        
        $this->pdf->Cell(50, 8, 'Statut:', 1, 0, 'L', true);
        $this->pdf->SetTextColor(239, 68, 68);
        $this->pdf->Cell(0, 8, 'Non Résolu', 1, 1, 'L', true);
        $this->pdf->SetTextColor(0, 0, 0);
        
        $this->pdf->Cell(50, 8, 'Créé par:', 1, 0, 'L', true);
        $this->pdf->Cell(0, 8, $ticket['user_email'], 1, 1, 'L', true);
        
        $this->pdf->Cell(50, 8, 'Date création:', 1, 0, 'L', true);
        $this->pdf->Cell(0, 8, date('d/m/Y H:i', strtotime($ticket['created_at'])), 1, 1, 'L', true);
        
        $this->pdf->Cell(50, 8, 'Date mise à jour:', 1, 0, 'L', true);
        $this->pdf->Cell(0, 8, date('d/m/Y H:i', strtotime($ticket['updated_at'])), 1, 1, 'L', true);
        
        $this->pdf->Ln(10);
        
        // Description section
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'DESCRIPTION DU PROBLÈME', 1, 1, 'C', true);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetFillColor(255, 255, 255);
        
        // Add description in a bordered box
        $this->pdf->MultiCell(0, 8, $ticket['description'], 1, 'L', true);
        
        $this->pdf->Ln(10);
        
        // Attachments section
        if (!empty($attachments)) {
            $this->pdf->SetFillColor(240, 240, 240);
            $this->pdf->SetFont('helvetica', 'B', 14);
            $this->pdf->Cell(0, 10, 'CAPTURES D\'ÉCRAN JOINTES', 1, 1, 'C', true);
            $this->pdf->SetFont('helvetica', '', 10);
            $this->pdf->SetFillColor(255, 255, 255);
            
            foreach ($attachments as $attachment) {
                $this->pdf->Cell(0, 8, '• ' . basename($attachment['file_path']), 1, 1, 'L', true);
            }
        }
        
        $this->pdf->Ln(15);
        
        // Professional footer
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Rect(10, $this->pdf->GetY(), 190, 20, 'F');
        
        $this->pdf->SetFont('helvetica', 'I', 9);
        $this->pdf->SetTextColor(100, 100, 100);
        $this->pdf->Cell(0, 6, 'Rapport généré automatiquement le ' . date('d/m/Y à H:i'), 0, 1, 'C');
        $this->pdf->Cell(0, 6, 'CBG - Système de Gestion des Incidents', 0, 1, 'C');
        $this->pdf->Cell(0, 6, 'Document confidentiel - Usage interne uniquement', 0, 1, 'C');
    }
    
    public function generateMultipleTicketsReport($tickets) {
        // Add a page
        $this->pdf->AddPage();
        
        // Add logo at the top
        $this->pdf->Image('static/logo.png', 10, 10, 40, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        
        // Set font for title
        $this->pdf->SetFont('helvetica', 'B', 18);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(0, 15, 'RAPPORT DES TICKETS NON RÉSOLUS', 0, 1, 'C');
        
        // Add a line under the title
        $this->pdf->Line(20, $this->pdf->GetY(), 190, $this->pdf->GetY());
        $this->pdf->Ln(10);
        
        // Summary section
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'RÉSUMÉ EXÉCUTIF', 1, 1, 'C', true);
        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetFillColor(255, 255, 255);
        
        $this->pdf->Cell(0, 8, 'Nombre total de tickets non résolus: ' . count($tickets), 1, 1, 'L', true);
        
        // Calculate statistics
        $priorityStats = ['low' => 0, 'medium' => 0, 'high' => 0];
        foreach ($tickets as $ticket) {
            $priorityStats[$ticket['priority']]++;
        }
        
        $this->pdf->Cell(0, 8, 'Tickets de priorité élevée: ' . $priorityStats['high'], 1, 1, 'L', true);
        $this->pdf->Cell(0, 8, 'Tickets de priorité moyenne: ' . $priorityStats['medium'], 1, 1, 'L', true);
        $this->pdf->Cell(0, 8, 'Tickets de priorité faible: ' . $priorityStats['low'], 1, 1, 'L', true);
        
        $this->pdf->Ln(10);
        
        // Table header with professional styling
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(20, 10, 'ID', 1, 0, 'C', true);
        $this->pdf->Cell(60, 10, 'Titre', 1, 0, 'C', true);
        $this->pdf->Cell(30, 10, 'Priorité', 1, 0, 'C', true);
        $this->pdf->Cell(50, 10, 'Utilisateur', 1, 0, 'C', true);
        $this->pdf->Cell(30, 10, 'Date', 1, 1, 'C', true);
        
        // Table content with alternating row colors
        $this->pdf->SetFont('helvetica', '', 9);
        $fill = false;
        foreach ($tickets as $ticket) {
            $priorityLabels = ['low' => 'Faible', 'medium' => 'Moyenne', 'high' => 'Élevée'];
            $priorityColor = ['low' => [34, 197, 94], 'medium' => [234, 179, 8], 'high' => [239, 68, 68]];
            
            $this->pdf->Cell(20, 8, '#' . $ticket['id'], 1, 0, 'C', $fill);
            $this->pdf->Cell(60, 8, substr($ticket['title'], 0, 25) . (strlen($ticket['title']) > 25 ? '...' : ''), 1, 0, 'L', $fill);
            
            // Priority with color
            $this->pdf->SetTextColor($priorityColor[$ticket['priority']][0], $priorityColor[$ticket['priority']][1], $priorityColor[$ticket['priority']][2]);
            $this->pdf->Cell(30, 8, $priorityLabels[$ticket['priority']] ?? $ticket['priority'], 1, 0, 'C', $fill);
            $this->pdf->SetTextColor(0, 0, 0);
            
            $this->pdf->Cell(50, 8, $ticket['user_email'], 1, 0, 'L', $fill);
            $this->pdf->Cell(30, 8, date('d/m/Y', strtotime($ticket['created_at'])), 1, 1, 'C', $fill);
            
            $fill = !$fill;
        }
        
        $this->pdf->Ln(15);
        
        // Professional footer
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Rect(10, $this->pdf->GetY(), 190, 20, 'F');
        
        $this->pdf->SetFont('helvetica', 'I', 9);
        $this->pdf->SetTextColor(100, 100, 100);
        $this->pdf->Cell(0, 6, 'Rapport généré automatiquement le ' . date('d/m/Y à H:i'), 0, 1, 'C');
        $this->pdf->Cell(0, 6, 'CBG - Système de Gestion des Incidents', 0, 1, 'C');
        $this->pdf->Cell(0, 6, 'Document confidentiel - Usage interne uniquement', 0, 1, 'C');
    }
    
    public function output($filename = '') {
        if (empty($filename)) {
            $filename = 'transfert_rapport_' . date('Y-m-d_H-i-s') . '.pdf';
        }
        
        $this->pdf->Output($filename, 'D');
    }
}
?>

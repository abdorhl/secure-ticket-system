<?php
require_once 'config/database.php';
requireAuth();

if ($_SESSION['role'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Pagination settings
$ticketsPerPage = 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $ticketsPerPage;

// Get total count of tickets for pagination
$countStmt = $conn->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND deleted_at IS NULL");
$countStmt->execute([$_SESSION['user_id']]);
$totalTickets = $countStmt->fetchColumn();
$totalPages = ceil($totalTickets / $ticketsPerPage);

// Récupérer les tickets de l'utilisateur avec pagination
$stmt = $conn->prepare("SELECT * FROM tickets WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT {$ticketsPerPage} OFFSET {$offset}");
$stmt->execute([$_SESSION['user_id']]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Dashboard Utilisateur - Gestion des Incidents';
include 'includes/header.php';
?>

<div class="min-h-screen bg-background">
    <!-- Header -->
    <header class="border-b bg-card shadow-sm">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <img src="static/logo.png" 
                     alt="CBG Logo" 
                     class="w-16 h-12">
                <div>
                    <h1 class="text-2xl font-bold text-primary">Tableau de bord utilisateur</h1>
                    <p class="text-muted-foreground">Gestion de vos tickets et transfert rapport</p>
                </div>
            </div>
            <a href="auth/logout.php" 
               class="flex items-center gap-2 px-4 py-2 border border-input rounded-md hover:bg-accent">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                Déconnexion
            </a>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-card p-6 rounded-lg border">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-muted-foreground text-sm">Total</p>
                        <p class="text-2xl font-bold"><?php echo count($tickets); ?></p>
                    </div>
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
            
            <?php 
            $statusCounts = ['open' => 0, 'in_progress' => 0, 'resolved' => 0];
            foreach ($tickets as $ticket) {
                if (isset($statusCounts[$ticket['status']])) {
                    $statusCounts[$ticket['status']]++;
                }
            }
            ?>
            
            <div class="bg-card p-6 rounded-lg border">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-muted-foreground text-sm">Ouverts</p>
                        <p class="text-2xl font-bold"><?php echo $statusCounts['open']; ?></p>
                    </div>
                    <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-card p-6 rounded-lg border">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-muted-foreground text-sm">En cours</p>
                        <p class="text-2xl font-bold"><?php echo $statusCounts['in_progress']; ?></p>
                    </div>
                    <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-card p-6 rounded-lg border">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-muted-foreground text-sm">Résolus</p>
                        <p class="text-2xl font-bold"><?php echo $statusCounts['resolved']; ?></p>
                    </div>
                    <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold">Mes tickets</h2>
            <div class="flex gap-3">
                <button onclick="captureScreen()" 
                        class="bg-secondary text-secondary-foreground px-4 py-2 rounded-md hover:bg-secondary/80 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Transfert Rapport
                </button>
                <button onclick="openNewTicketModal()" 
                        class="bg-primary text-primary-foreground px-4 py-2 rounded-md hover:bg-primary/90 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nouveau ticket
                </button>
            </div>
        </div>

        <!-- Tickets Table -->
        <div class="bg-card rounded-lg border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="text-left p-4 font-medium">Titre</th>
                            <th class="text-left p-4 font-medium">Type</th>
                            <th class="text-left p-4 font-medium">Priorité</th>
                            <th class="text-left p-4 font-medium">Statut</th>
                            <th class="text-left p-4 font-medium">Date de création</th>
                            <th class="text-left p-4 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-muted-foreground">
                                    Aucun ticket trouvé. Créez votre premier ticket !
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr class="border-t">
                                    <td class="p-4">
                                        <div class="font-medium"><?php echo htmlspecialchars($ticket['title']); ?></div>
                                        <div class="text-sm text-muted-foreground truncate max-w-xs">
                                            <?php echo htmlspecialchars($ticket['description']); ?>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <?php
                                        $problemTypeColors = [
                                            'hardware' => 'bg-blue-100 text-blue-800',
                                            'software' => 'bg-purple-100 text-purple-800'
                                        ];
                                        $problemTypeLabels = [
                                            'hardware' => 'Matériel',
                                            'software' => 'Logiciel'
                                        ];
                                        ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $problemTypeColors[$ticket['problem_type']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo $problemTypeLabels[$ticket['problem_type']] ?? 'Inconnu'; ?>
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <?php
                                        $priorityColors = [
                                            'low' => 'bg-green-100 text-green-800',
                                            'medium' => 'bg-yellow-100 text-yellow-800',
                                            'high' => 'bg-red-100 text-red-800'
                                        ];
                                        $priorityLabels = [
                                            'low' => 'Faible',
                                            'medium' => 'Moyenne',
                                            'high' => 'Élevée'
                                        ];
                                        ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $priorityColors[$ticket['priority']]; ?>">
                                            <?php echo $priorityLabels[$ticket['priority']]; ?>
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <?php
                                        $statusColors = [
                                            'open' => 'bg-orange-100 text-orange-800',
                                            'in_progress' => 'bg-blue-100 text-blue-800',
                                            'resolved' => 'bg-green-100 text-green-800',
                                            'closed' => 'bg-gray-100 text-gray-800'
                                        ];
                                        $statusLabels = [
                                            'open' => 'Ouvert',
                                            'in_progress' => 'En cours',
                                            'resolved' => 'Résolu',
                                            'closed' => 'Fermé'
                                        ];
                                        ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusColors[$ticket['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo $statusLabels[$ticket['status']] ?? 'Inconnu'; ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-sm text-muted-foreground">
                                        <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?>
                                    </td>
                                    <td class="p-4">
                                        <button onclick="viewTicket(<?php echo $ticket['id']; ?>)" 
                                                class="text-primary hover:text-primary/80">
                                            Voir
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center items-center mt-6 space-x-2">
            <!-- Previous button -->
            <?php if ($currentPage > 1): ?>
                <a href="?page=<?php echo $currentPage - 1; ?>" 
                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Précédent
                </a>
            <?php else: ?>
                <span class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed">
                    Précédent
                </span>
            <?php endif; ?>

            <!-- Page numbers -->
            <?php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            
            if ($startPage > 1): ?>
                <a href="?page=1" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">1</a>
                <?php if ($startPage > 2): ?>
                    <span class="px-3 py-2 text-sm font-medium text-gray-500">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i == $currentPage): ?>
                    <span class="px-3 py-2 text-sm font-medium text-white bg-primary border border-primary rounded-md">
                        <?php echo $i; ?>
                    </span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>" 
                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                    <span class="px-3 py-2 text-sm font-medium text-gray-500">...</span>
                <?php endif; ?>
                <a href="?page=<?php echo $totalPages; ?>" 
                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    <?php echo $totalPages; ?>
                </a>
            <?php endif; ?>

            <!-- Next button -->
            <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?php echo $currentPage + 1; ?>" 
                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Suivant
                </a>
            <?php else: ?>
                <span class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed">
                    Suivant
                </span>
            <?php endif; ?>
        </div>

        <!-- Pagination info -->
        <div class="text-center mt-4 text-sm text-muted-foreground">
            Affichage de <?php echo $offset + 1; ?> à <?php echo min($offset + $ticketsPerPage, $totalTickets); ?> 
            sur <?php echo $totalTickets; ?> tickets
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nouveau Ticket -->
<div id="newTicketModal" class="fixed inset-0 bg-black transition-opacity duration-300 ease-in-out hidden bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-card rounded-lg p-3 w-full max-w-md mx-4 transform transition-all duration-300 ease-in-out translate-y-4 opacity-0 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-2">
            <h3 class="text-base font-semibold">Nouveau ticket</h3>
            <button onclick="closeNewTicketModal()" class="text-muted-foreground hover:text-foreground">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form id="newTicketForm" class="space-y-2">
            <div>
                <label class="block text-sm font-medium mb-1">Titre</label>
                <input type="text" name="title" required 
                       class="w-full p-1.5 border border-input bg-background rounded-md focus:outline-none focus:ring-2 focus:ring-ring text-sm">
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-1">Priorité</label>
                <select name="priority" 
                        class="w-full p-1.5 border border-input bg-background rounded-md focus:outline-none focus:ring-2 focus:ring-ring text-sm">
                    <option value="low">Faible</option>
                    <option value="medium" selected>Moyenne</option>
                    <option value="high">Élevée</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Type de problème</label>
                <select name="problem_type" 
                        class="w-full p-1.5 border border-input bg-background rounded-md focus:outline-none focus:ring-2 focus:ring-ring text-sm">
                    <option value="software" selected>Logiciel</option>
                    <option value="hardware">Matériel</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Description</label>
                <textarea name="description" required rows="2"
                          class="w-full p-1.5 border border-input bg-background rounded-md focus:outline-none focus:ring-2 focus:ring-ring text-sm"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Captures d'écran</label>
                <div class="bg-muted/20 p-2 rounded-md text-xs mb-1">
                    <kbd class="px-1 bg-muted rounded">Win+Shift+S</kbd> pour capturer, puis 
                    <kbd class="px-1 bg-muted rounded">Ctrl+V</kbd> pour coller
                </div>
                <div id="screenshotPreview" class="grid grid-cols-2 gap-2 mb-1">
                    <!-- Screenshots will be added here -->
                </div>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="submit" 
                        class="flex-1 bg-primary text-primary-foreground px-3 py-1.5 rounded-md hover:bg-primary/90 text-sm">
                    Créer le ticket
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Capture d'écran -->
<div id="screenshotModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-card rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4">Capture d'écran - Transfert Rapport</h3>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Description</label>
            <textarea id="screenshotDescription" rows="3" 
                      placeholder="Décrivez le contenu de la capture..."
                      class="w-full p-3 border border-input bg-background rounded-md focus:outline-none focus:ring-2 focus:ring-ring"></textarea>
        </div>
        <div class="flex gap-3">
            <button type="button" onclick="closeScreenshotModal()" 
                    class="flex-1 px-4 py-2 border border-input rounded-md hover:bg-accent">
                Annuler
            </button>
            <button onclick="takeScreenshot()" 
                    class="flex-1 bg-primary text-primary-foreground px-4 py-2 rounded-md hover:bg-primary/90">
                Capturer
            </button>
        </div>
    </div>
</div>

<script>
function openNewTicketModal() {
    const modal = document.getElementById('newTicketModal');
    const modalContent = modal.querySelector('.bg-card');
    modal.classList.remove('hidden');
    // Force reflow
    void modal.offsetWidth;
    modalContent.classList.remove('translate-y-4', 'opacity-0');
}

function closeNewTicketModal() {
    const modal = document.getElementById('newTicketModal');
    const modalContent = modal.querySelector('.bg-card');
    modalContent.classList.add('translate-y-4', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('newTicketForm').reset();
    }, 300);
}

function viewTicket(ticketId) {
    window.location.href = `ticket_details.php?id=${ticketId}`;
}

document.getElementById('newTicketForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const screenshots = Array.from(document.querySelectorAll('input[name="screenshots[]"]')).filter(input => input.value);
    
    // Add each valid screenshot as a separate file
    for (let i = 0; i < screenshots.length; i++) {
        const response = await fetch(screenshots[i].value);
        const blob = await response.blob();
        formData.append('screenshots[]', blob, `screenshot_${i}.png`);
    }
    
    try {
        const response = await fetch('api/tickets.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Erreur: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erreur lors de la création du ticket');
    }
});

// Gestion des captures d'écran
function captureScreen() {
    document.getElementById('screenshotModal').classList.remove('hidden');
}

function closeScreenshotModal() {
    document.getElementById('screenshotModal').classList.add('hidden');
    document.getElementById('screenshotDescription').value = '';
}

async function takeScreenshot() {
    try {
        const stream = await navigator.mediaDevices.getDisplayMedia({
            video: { mediaSource: 'screen' }
        });
        
        const video = document.createElement('video');
        video.srcObject = stream;
        video.play();
        
        video.addEventListener('loadedmetadata', () => {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            
            stream.getTracks().forEach(track => track.stop());
            
            const imageData = canvas.toDataURL('image/png');
            const description = document.getElementById('screenshotDescription').value;
            
            saveScreenshot(imageData, description);
        });
        
    } catch (error) {
        alert('Erreur lors de la capture d\'écran: ' + error.message);
        closeScreenshotModal();
    }
}

async function saveScreenshot(imageData, description) {
    try {
        // Convert base64 to blob
        const fetchResponse = await fetch(imageData);
        const blob = await fetchResponse.blob();
        
        const formData = new FormData();
        formData.append('screenshot', blob, 'screenshot.png');
        formData.append('description', description);
        
        const response = await fetch('api/screenshot.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeScreenshotModal();
            // Show success notification
            const preview = document.createElement('div');
            preview.className = 'fixed bottom-4 right-4 bg-green-100 text-green-800 p-4 rounded-lg shadow-lg z-50';
            preview.innerHTML = `
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Capture d'écran sauvegardée</span>
                </div>
            `;
            document.body.appendChild(preview);
            setTimeout(() => preview.remove(), 3000);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Upload error:', error);
        alert('Erreur lors de la sauvegarde de la capture: ' + error.message);
    }
}

document.addEventListener('paste', function(event) {
    const items = (event.clipboardData || event.originalEvent.clipboardData).items;
    
    for (let item of items) {
        if (item.type.indexOf('image') === 0) {
            const blob = item.getAsFile();
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const preview = document.getElementById('screenshotPreview');
                const container = document.createElement('div');
                container.className = 'relative';
                
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'w-full h-32 object-cover rounded-md';
                
                const removeBtn = document.createElement('button');
                removeBtn.className = 'absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600';
                removeBtn.innerHTML = `
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                `;
                removeBtn.onclick = function() {
                    container.remove();
                };
                
                container.appendChild(img);
                container.appendChild(removeBtn);
                preview.appendChild(container);
                
                // Store base64 data in hidden input
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'screenshots[]';
                input.value = e.target.result;
                container.appendChild(input);
            };
            
            reader.readAsDataURL(blob);
        }
    }
});
</script>

</body>
</html>
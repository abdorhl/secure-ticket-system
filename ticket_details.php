<?php
require_once 'config/database.php';
requireAuth();

if (!isset($_GET['id'])) {
    header('Location: user_dashboard.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get ticket details
$stmt = $conn->prepare("
    SELECT t.*, u.email as user_name 
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.id = ? AND (t.user_id = ? OR ? = 'admin')
");
$stmt->execute([$_GET['id'], $_SESSION['user_id'], $_SESSION['role']]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header('Location: user_dashboard.php');
    exit;
}

// Get attachments
$stmt = $conn->prepare("SELECT * FROM ticket_attachments WHERE ticket_id = ?");
$stmt->execute([$_GET['id']]);
$attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Détails du ticket - Gestion des Incidents';
include 'includes/header.php';
?>

<div class="min-h-screen bg-background">
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <a href="user_dashboard.php" class="text-primary hover:text-primary/80 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Retour au tableau de bord
            </a>
        </div>

        <div class="bg-card rounded-lg border p-6">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($ticket['title']); ?></h1>
                    <div class="flex gap-4 text-sm text-muted-foreground">
                        <span>Par <?php echo htmlspecialchars($ticket['user_name']); ?></span>
                        <span>•</span>
                        <span><?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <select id="ticketStatus" 
                            onchange="updateTicketStatus(<?php echo $ticket['id']; ?>, this.value)"
                            class="border border-input rounded-md px-3 py-1 text-sm">
                        <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Ouvert</option>
                        <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>En cours</option>
                        <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Résolu</option>
                        <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Fermé</option>
                        <option value="no_resolu" <?php echo $ticket['status'] === 'no_resolu' ? 'selected' : ''; ?>>Non Résolu</option>
                    </select>
                    <?php endif; ?>
                    <div class="flex gap-2">
                        <?php
                        $priorityColors = [
                            'low' => 'bg-green-100 text-green-800',
                            'medium' => 'bg-yellow-100 text-yellow-800',
                            'high' => 'bg-red-100 text-red-800'
                        ];
                        $statusColors = [
                            'open' => 'bg-orange-100 text-orange-800',
                            'in_progress' => 'bg-blue-100 text-blue-800',
                            'resolved' => 'bg-green-100 text-green-800',
                            'closed' => 'bg-gray-100 text-gray-800',
                            'no_resolu' => 'bg-red-100 text-red-800'
                        ];
                        $problemTypeColors = [
                            'hardware' => 'bg-blue-100 text-blue-800',
                            'software' => 'bg-purple-100 text-purple-800'
                        ];
                        $priorityLabels = ['low' => 'Faible', 'medium' => 'Moyenne', 'high' => 'Élevée'];
                        $statusLabels = [
                            'open' => 'Ouvert',
                            'in_progress' => 'En cours',
                            'resolved' => 'Résolu',
                            'closed' => 'Fermé',
                            'no_resolu' => 'Non Résolu'
                        ];
                        $problemTypeLabels = [
                            'hardware' => 'Matériel',
                            'software' => 'Logiciel'
                        ];
                        ?>
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $problemTypeColors[$ticket['problem_type']] ?? 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo $problemTypeLabels[$ticket['problem_type']] ?? 'Inconnu'; ?>
                        </span>
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $priorityColors[$ticket['priority']]; ?>">
                            <?php echo $priorityLabels[$ticket['priority']]; ?>
                        </span>
                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusColors[$ticket['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo $statusLabels[$ticket['status']] ?? 'Inconnu'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="prose max-w-none mb-8">
                <p><?php echo nl2br(htmlspecialchars($ticket['description'])); ?></p>
            </div>

            <?php if (!empty($attachments)): ?>
            <div class="border-t pt-6">
                <h2 class="text-lg font-semibold mb-4">Captures d'écran</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($attachments as $attachment): ?>
                    <div class="relative group">
                        <img src="<?php echo htmlspecialchars($attachment['file_path']); ?>" 
                             alt="Screenshot" 
                             class="w-full h-48 object-cover rounded-lg cursor-pointer"
                             onclick="openImageModal(this.src)">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="max-w-4xl w-full">
        <img id="modalImage" src="" alt="Full size screenshot" class="w-full h-auto">
        <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white hover:text-gray-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
</div>

<script>
function openImageModal(src) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    modalImage.src = src;
    modal.classList.remove('hidden');
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
}

// Close modal on background click
document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImageModal();
    }
});

async function updateTicketStatus(ticketId, status) {
    try {
        const response = await fetch('api/update_ticket.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ticket_id: ticketId,
                status: status
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success notification
            const notification = document.createElement('div');
            notification.className = 'fixed bottom-4 right-4 bg-green-100 text-green-800 p-4 rounded-lg shadow-lg z-50';
            notification.innerHTML = `
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Statut du ticket mis à jour</span>
                </div>
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erreur lors de la mise à jour du statut');
    }
}
</script>

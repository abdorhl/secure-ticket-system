<?php
require_once 'config/database.php';
requireAdmin();

$db = new Database();
$conn = $db->connect();

// Pagination and filtering settings
$ticketsPerPage = 15;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $ticketsPerPage;

// Filter parameters
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$searchFilter = $_GET['search'] ?? '';

// Build WHERE clause for filtering
$whereConditions = ["t.deleted_at IS NULL"];
$params = [];

if ($statusFilter) {
    $whereConditions[] = "t.status = ?";
    $params[] = $statusFilter;
}

if ($priorityFilter) {
    $whereConditions[] = "t.priority = ?";
    $params[] = $priorityFilter;
}

if ($searchFilter) {
    $whereConditions[] = "(t.title LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%{$searchFilter}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = implode(" AND ", $whereConditions);

// Get total count of tickets for pagination
$countStmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE {$whereClause}
");
$countStmt->execute($params);
$totalTickets = $countStmt->fetchColumn();
$totalPages = ceil($totalTickets / $ticketsPerPage);

// Récupérer tous les tickets avec les informations utilisateur et pagination
$stmt = $conn->prepare("
    SELECT t.*, u.email as user_email 
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE {$whereClause}
    ORDER BY t.created_at DESC
    LIMIT {$ticketsPerPage} OFFSET {$offset}
");
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les statistiques (from all tickets, not just current page)
$statsStmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
        SUM(CASE WHEN status = 'no_resolu' THEN 1 ELSE 0 END) as no_resolu
    FROM tickets 
    WHERE deleted_at IS NULL
");
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Dashboard Admin - Gestion des Incidents';
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
                    <h1 class="text-2xl font-bold text-primary">Administration</h1>
                    <p class="text-muted-foreground">Gestion des tickets et transfert rapport</p>
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
         <div class="grid grid-cols-1 md:grid-cols-6 gap-6 mb-8">
             <div class="bg-card p-6 rounded-lg border">
                 <div class="flex items-center justify-between">
                     <div>
                         <p class="text-muted-foreground text-sm">Total des tickets</p>
                         <p class="text-2xl font-bold"><?php echo $stats['total']; ?></p>
                     </div>
                     <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                     </svg>
                 </div>
             </div>

             <div class="bg-card p-6 rounded-lg border">
                 <div class="flex items-center justify-between">
                     <div>
                         <p class="text-muted-foreground text-sm">Tickets ouverts</p>
                         <p class="text-2xl font-bold"><?php echo $stats['open']; ?></p>
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
                         <p class="text-2xl font-bold"><?php echo $stats['in_progress']; ?></p>
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
                         <p class="text-2xl font-bold"><?php echo $stats['resolved']; ?></p>
                     </div>
                     <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                     </svg>
                 </div>
             </div>

             <div class="bg-card p-6 rounded-lg border">
                 <div class="flex items-center justify-between">
                     <div>
                         <p class="text-muted-foreground text-sm">Fermés</p>
                         <p class="text-2xl font-bold"><?php echo $stats['closed']; ?></p>
                     </div>
                     <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                     </svg>
                 </div>
             </div>

             <div class="bg-card p-6 rounded-lg border">
                 <div class="flex items-center justify-between">
                     <div>
                         <p class="text-muted-foreground text-sm">Non Résolus</p>
                         <p class="text-2xl font-bold"><?php echo $stats['no_resolu']; ?></p>
                     </div>
                     <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                     </svg>
                 </div>
             </div>
         </div>

        <!-- Navigation Tabs -->
        <div class="bg-card rounded-lg border mb-6">
            <div class="flex border-b">
                <button onclick="showTab('tickets')" id="ticketsTab" 
                        class="px-6 py-3 border-b-2 border-primary text-primary font-medium">
                    Tickets
                </button>
                <button onclick="showTab('users')" id="usersTab" 
                        class="px-6 py-3 border-b-2 border-transparent text-muted-foreground hover:text-foreground">
                    Utilisateurs
                </button>
                <button onclick="showTab('reports')" id="reportsTab" 
                        class="px-6 py-3 border-b-2 border-transparent text-muted-foreground hover:text-foreground">
                    Transfert Rapport
                </button>
                <button onclick="showTab('historique')" id="historiqueTab" 
                        class="px-6 py-3 border-b-2 border-transparent text-muted-foreground hover:text-foreground">
                    Historique
                </button>
            </div>
        </div>
        <div id="usersSection" style="display: none;">
            <div class="bg-card rounded-lg border p-4 mb-6">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold">Gestion des utilisateurs</h2>
                    <button onclick="openNewUserModal()" 
                            class="bg-primary text-primary-foreground px-4 py-2 rounded-md hover:bg-primary/90 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Créer un compte
                    </button>
                </div>
            </div>
            
            <div class="bg-card rounded-lg border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="text-left p-4 font-medium">Email</th>
                                <th class="text-left p-4 font-medium">Rôle</th>
                                <th class="text-left p-4 font-medium">Date de création</th>
                                <th class="text-left p-4 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <!-- Users will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
                <div id="reportsSection" style="display: none;">
            <div class="bg-card rounded-lg border p-4 mb-6">
                <h2 class="text-lg font-semibold">Transfert Rapport - Tickets Non Résolus</h2>
                <p class="text-muted-foreground">Gérez les tickets non résolus et générez des rapports PDF</p>
                <div class="flex gap-3 mt-4">
                    <button onclick="generateAllReports()" 
                            class="bg-primary text-primary-foreground px-4 py-2 rounded-md hover:bg-primary/90 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Générer Rapport Complet
                    </button>
                    <button onclick="loadNoResoluTickets()" 
                            class="bg-secondary text-secondary-foreground px-4 py-2 rounded-md hover:bg-secondary/80 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Actualiser
                    </button>
                </div>
            </div>
            
            <div class="bg-card rounded-lg border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="text-left p-4 font-medium">ID</th>
                                <th class="text-left p-4 font-medium">Titre</th>
                                <th class="text-left p-4 font-medium">Utilisateur</th>
                                <th class="text-left p-4 font-medium">Priorité</th>
                                <th class="text-left p-4 font-medium">Date</th>
                                <th class="text-left p-4 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="noResoluTableBody">
                            <!-- No Resolu tickets will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Historique Section -->
        <div id="historiqueSection" style="display: none;">
            <div class="bg-card rounded-lg border p-4 mb-6">
                <h2 class="text-lg font-semibold">Historique Complet - Tous les Tickets</h2>
                <p class="text-muted-foreground">Consultez l'historique de tous les tickets, y compris les supprimés</p>
                <div class="flex gap-3 mt-4">
                    <button onclick="loadHistorique()" 
                            class="bg-primary text-primary-foreground px-4 py-2 rounded-md hover:bg-primary/90 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Charger Historique
                    </button>
                    <button onclick="loadDeletedTickets()" 
                            class="bg-secondary text-secondary-foreground px-4 py-2 rounded-md hover:bg-secondary/80 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Tickets Supprimés
                    </button>
                    <button onclick="generateHistoriqueReport()" 
                            class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Rapport Historique
                    </button>
                </div>
            </div>
            
            <!-- Historique Table -->
            <div class="bg-card rounded-lg border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="text-left p-4 font-medium">Date</th>
                                <th class="text-left p-4 font-medium">Action</th>
                                <th class="text-left p-4 font-medium">Ticket</th>
                                <th class="text-left p-4 font-medium">Utilisateur</th>
                                <th class="text-left p-4 font-medium">Détails</th>
                                <th class="text-left p-4 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="historiqueTableBody">
                            <!-- Historique will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tickets Section -->
        <div id="ticketsSection">
            <!-- Filters -->
            <div class="bg-card rounded-lg border p-4 mb-6">
                <div class="flex flex-wrap gap-4 items-center justify-between">
                    <div class="flex flex-wrap gap-4 items-center">
                        <div>
                            <label class="block text-sm font-medium mb-1">Filtrer par statut</label>
                            <select id="statusFilter" onchange="filterTickets()" 
                                    class="p-2 border border-input bg-background rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">Tous</option>
                                <option value="open" <?php echo $statusFilter === 'open' ? 'selected' : ''; ?>>Ouvert</option>
                                <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>En cours</option>
                                <option value="resolved" <?php echo $statusFilter === 'resolved' ? 'selected' : ''; ?>>Résolu</option>
                                <option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>Fermé</option>
                                <option value="no_resolu" <?php echo $statusFilter === 'no_resolu' ? 'selected' : ''; ?>>Non Résolu</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-1">Filtrer par priorité</label>
                            <select id="priorityFilter" onchange="filterTickets()" 
                                    class="p-2 border border-input bg-background rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                                <option value="">Toutes</option>
                                <option value="low" <?php echo $priorityFilter === 'low' ? 'selected' : ''; ?>>Faible</option>
                                <option value="medium" <?php echo $priorityFilter === 'medium' ? 'selected' : ''; ?>>Moyenne</option>
                                <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>Élevée</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Rechercher</label>
                            <input type="text" id="searchInput" onkeyup="filterTickets()" 
                                   placeholder="Titre ou email..."
                                   value="<?php echo htmlspecialchars($searchFilter); ?>"
                                   class="p-2 border border-input bg-background rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                        </div>
                     </div>
                </div>
            </div>

            <!-- Tickets Table -->
            <div class="bg-card rounded-lg border overflow-hidden">
                <div class="p-4 border-b">
                    <h2 class="text-lg font-semibold">Gestion des tickets</h2>
                </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="text-left p-4 font-medium">ID</th>
                            <th class="text-left p-4 font-medium">Titre</th>
                            <th class="text-left p-4 font-medium">Utilisateur</th>
                            <th class="text-left p-4 font-medium">Type</th>
                            <th class="text-left p-4 font-medium">Priorité</th>
                            <th class="text-left p-4 font-medium">Statut</th>
                            <th class="text-left p-4 font-medium">Date</th>
                            <th class="text-left p-4 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ticketsTableBody">
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-8 text-muted-foreground">
                                    Aucun ticket trouvé.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr class="border-t ticket-row" 
                                    data-status="<?php echo $ticket['status']; ?>" 
                                    data-priority="<?php echo $ticket['priority']; ?>"
                                    data-search="<?php echo strtolower($ticket['title'] . ' ' . $ticket['user_email']); ?>">
                                    <td class="p-4 font-mono text-sm">#<?php echo $ticket['id']; ?></td>
                                    <td class="p-4">
                                        <div class="font-medium"><?php echo htmlspecialchars($ticket['title']); ?></div>
                                        <div class="text-sm text-muted-foreground truncate max-w-xs">
                                            <?php echo htmlspecialchars($ticket['description']); ?>
                                        </div>
                                    </td>
                                    <td class="p-4 text-sm"><?php echo htmlspecialchars($ticket['user_email']); ?></td>
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
                                        <select onchange="updateTicketStatus(<?php echo $ticket['id']; ?>, this.value)" 
                                                class="p-1 border border-input bg-background rounded text-xs">
                                            <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Ouvert</option>
                                            <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>En cours</option>
                                            <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Résolu</option>
                                            <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Fermé</option>
                                            <option value="no_resolu" <?php echo $ticket['status'] === 'no_resolu' ? 'selected' : ''; ?>>Non Résolu</option>
                                        </select>
                                        <span class="hidden px-2 py-1 rounded-full text-xs font-medium <?php 
                                            echo match($ticket['status']) {
                                                'open' => 'bg-orange-100 text-orange-800',
                                                'in_progress' => 'bg-blue-100 text-blue-800',
                                                'resolved' => 'bg-green-100 text-green-800',
                                                'closed' => 'bg-gray-100 text-gray-800',
                                                'no_resolu' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            };
                                        ?>">
                                            <?php 
                                            echo match($ticket['status']) {
                                                'open' => 'Ouvert',
                                                'in_progress' => 'En cours',
                                                'resolved' => 'Résolu',
                                                'closed' => 'Fermé',
                                                'no_resolu' => 'Non Résolu',
                                                default => 'Inconnu'
                                            };
                                            ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-sm text-muted-foreground">
                                        <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex gap-2">
                                            <button onclick="viewTicket(<?php echo $ticket['id']; ?>)" 
                                                    class="text-primary hover:text-primary/80 text-sm">
                                                Voir
                                            </button>
                                            <button onclick="deleteTicket(<?php echo $ticket['id']; ?>)" 
                                                    class="text-destructive hover:text-destructive/80 text-sm">
                                                Supprimer
                                            </button>
                                        </div>
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
                <?php
                $prevParams = $_GET;
                $prevParams['page'] = $currentPage - 1;
                $prevQuery = http_build_query($prevParams);
                ?>
                <a href="?<?php echo $prevQuery; ?>" 
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
                <?php
                $firstParams = $_GET;
                $firstParams['page'] = 1;
                $firstQuery = http_build_query($firstParams);
                ?>
                <a href="?<?php echo $firstQuery; ?>" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">1</a>
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
                    <?php
                    $pageParams = $_GET;
                    $pageParams['page'] = $i;
                    $pageQuery = http_build_query($pageParams);
                    ?>
                    <a href="?<?php echo $pageQuery; ?>" 
                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                    <span class="px-3 py-2 text-sm font-medium text-gray-500">...</span>
                <?php endif; ?>
                <?php
                $lastParams = $_GET;
                $lastParams['page'] = $totalPages;
                $lastQuery = http_build_query($lastParams);
                ?>
                <a href="?<?php echo $lastQuery; ?>" 
                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    <?php echo $totalPages; ?>
                </a>
            <?php endif; ?>

            <!-- Next button -->
            <?php if ($currentPage < $totalPages): ?>
                <?php
                $nextParams = $_GET;
                $nextParams['page'] = $currentPage + 1;
                $nextQuery = http_build_query($nextParams);
                ?>
                <a href="?<?php echo $nextQuery; ?>" 
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

<!-- Modal Nouveau Utilisateur -->
<div id="newUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-card rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4">Créer un nouveau compte utilisateur</h3>
        <form id="newUserForm">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Email</label>
                <input type="email" name="email" required 
                       placeholder="utilisateur@outlook.com"
                       class="w-full p-3 border border-input bg-background rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                <p class="text-xs text-muted-foreground mt-1">Les utilisateurs doivent avoir un email Outlook</p>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Mot de passe</label>
                <input type="password" name="password" required 
                       class="w-full p-3 border border-input bg-background rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
            </div>
            
            <input type="hidden" name="role" value="user">

            <div class="flex gap-3">
                <button type="button" onclick="closeNewUserModal()" 
                        class="flex-1 px-4 py-2 border border-input rounded-md hover:bg-accent">
                    Annuler
                </button>
                <button type="submit" 
                        class="flex-1 bg-primary text-primary-foreground px-4 py-2 rounded-md hover:bg-primary/90">
                    Créer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Navigation entre les onglets
function showTab(tabName) {
    // Cacher toutes les sections
    document.getElementById('ticketsSection').style.display = 'none';
    document.getElementById('usersSection').style.display = 'none';
    document.getElementById('reportsSection').style.display = 'none';
    document.getElementById('historiqueSection').style.display = 'none';
    
    // Réinitialiser les onglets
    document.getElementById('ticketsTab').className = 'px-6 py-3 border-b-2 border-transparent text-muted-foreground hover:text-foreground';
    document.getElementById('usersTab').className = 'px-6 py-3 border-b-2 border-transparent text-muted-foreground hover:text-foreground';
    document.getElementById('reportsTab').className = 'px-6 py-3 border-b-2 border-transparent text-muted-foreground hover:text-foreground';
    document.getElementById('historiqueTab').className = 'px-6 py-3 border-b-2 border-transparent text-muted-foreground hover:text-foreground';
    
    // Afficher la section active et charger les données
    const section = document.getElementById(tabName + 'Section');
    section.style.display = 'block';
    document.getElementById(tabName + 'Tab').className = 'px-6 py-3 border-b-2 border-primary text-primary font-medium';
    
    // Load data immediately when switching to the tab
    if (tabName === 'users') {
        loadUsers();
    } else if (tabName === 'reports') {
        loadNoResoluTickets();
    } else if (tabName === 'historique') {
        loadHistorique();
    }
}

// Add this line after all scripts to trigger initial load
document.addEventListener('DOMContentLoaded', function() {
    showTab('tickets'); // Show tickets tab by default
});

// Gestion des tickets
function filterTickets() {
    const statusFilter = document.getElementById('statusFilter').value;
    const priorityFilter = document.getElementById('priorityFilter').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    // Build query parameters
    const params = new URLSearchParams();
    if (statusFilter) params.append('status', statusFilter);
    if (priorityFilter) params.append('priority', priorityFilter);
    if (searchTerm) params.append('search', searchTerm);
    
    // Keep current page if no filters are applied
    const currentPage = new URLSearchParams(window.location.search).get('page');
    if (currentPage && !statusFilter && !priorityFilter && !searchTerm) {
        params.append('page', currentPage);
    }
    
    // Redirect with filters
    const queryString = params.toString();
    window.location.href = queryString ? `?${queryString}` : '?';
}

function viewTicket(ticketId) {
    window.location.href = `ticket_details.php?id=${ticketId}`;
}

async function updateTicketStatus(ticketId, newStatus) {
    try {
        const response = await fetch('api/update_ticket.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ticket_id: ticketId,
                status: newStatus
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update status colors in the UI
            const statusColors = {
                'open': 'bg-blue-100 text-blue-800',
                'in_progress': 'bg-yellow-100 text-yellow-800',
                'resolved': 'bg-green-100 text-green-800',
                'closed': 'bg-gray-100 text-gray-800'
            };
            
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

async function deleteTicket(ticketId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce ticket ?')) {
        return;
    }
    
    try {
        const response = await fetch('api/delete_ticket.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ ticket_id: ticketId })
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
                    <span>Ticket supprimé avec succès</span>
                </div>
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
            
            // Reload the page to refresh the ticket list
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('Erreur: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erreur lors de la suppression du ticket');
    }
}

// Gestion des utilisateurs
function openNewUserModal() {
    document.getElementById('newUserModal').classList.remove('hidden');
}

function closeNewUserModal() {
    document.getElementById('newUserModal').classList.add('hidden');
    document.getElementById('newUserForm').reset();
}

async function loadUsers() {
    try {
        const response = await fetch('api/users.php');
        const result = await response.json();
        
        if (result.success) {
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = '';
            
            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-8 text-muted-foreground">Aucun utilisateur trouvé.</td></tr>';
                return;
            }
            
            // Filter out admin users
            const users = result.data.filter(user => user.role === 'user');
            
            users.forEach(user => {
                const createdAt = new Date(user.created_at).toLocaleString('fr-FR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                const row = document.createElement('tr');
                row.className = 'border-t';
                row.innerHTML = `
                    <td class="p-4">${user.email}</td>
                    <td class="p-4">
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Utilisateur
                        </span>
                    </td>
                    <td class="p-4 text-sm text-muted-foreground">${createdAt}</td>
                    <td class="p-4">
                        <button onclick="deleteUser(${user.id})" 
                                class="text-destructive hover:text-destructive/80 text-sm">
                            Supprimer
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        const tbody = document.getElementById('usersTableBody');
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-8 text-destructive">Erreur lors du chargement des utilisateurs</td></tr>';
    }
}

async function deleteUser(userId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        return;
    }
    
    try {
        const response = await fetch('api/users.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: userId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            loadUsers();
        } else {
            alert('Erreur: ' + result.message);
        }
    } catch (error) {
        alert('Erreur lors de la suppression de l\'utilisateur');
    }
}

// Gestionnaire de création d'utilisateur
document.getElementById('newUserForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('api/users.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeNewUserModal();
            loadUsers();
            alert('Compte créé avec succès');
        } else {
            alert('Erreur: ' + result.message);
        }
    } catch (error) {
        alert('Erreur lors de la création du compte');
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
        const formData = new FormData();
        formData.append('imageData', imageData);
        formData.append('description', description);
        
        const response = await fetch('api/screenshot.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeScreenshotModal();
            alert('Capture d\'écran sauvegardée avec succès');
            if (document.getElementById('reportsSection').style.display !== 'none') {
                loadScreenshots();
            }
        } else {
            alert('Erreur: ' + result.message);
        }
    } catch (error) {
        alert('Erreur lors de la sauvegarde de la capture');
    }
}

async function loadScreenshots() {
    try {
        const response = await fetch('api/screenshot.php');
        const result = await response.json();
        
        if (result.success) {
            const tbody = document.getElementById('screenshotsTableBody');
            tbody.innerHTML = '';
            
            if (result.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-8 text-muted-foreground">Aucune capture d\'écran trouvée.</td></tr>';
                return;
            }
            
            result.data.forEach(screenshot => {
                const row = document.createElement('tr');
                row.className = 'border-t';
                row.innerHTML = `
                    <td class="p-4">${screenshot.user_email || 'Utilisateur inconnu'}</td>
                    <td class="p-4">${screenshot.description || 'Aucune description'}</td>
                    <td class="p-4 text-sm text-muted-foreground">${new Date(screenshot.created_at).toLocaleDateString('fr-FR')}</td>
                    <td class="p-4">
                        <a href="public/screenshots/${screenshot.filename}" target="_blank" 
                           class="text-primary hover:text-primary/80 text-sm mr-2">
                            Voir
                        </a>
                        <a href="public/screenshots/${screenshot.filename}" download 
                           class="text-secondary hover:text-secondary/80 text-sm">
                            Télécharger
                        </a>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }
            } catch (error) {
            console.error('Erreur lors du chargement des captures:', error);
        }
    }
    
    // Gestion des tickets non résolus
    async function loadNoResoluTickets() {
        try {
            const response = await fetch('api/generate_report.php');
            const result = await response.json();
            
            if (result.success) {
                const tbody = document.getElementById('noResoluTableBody');
                tbody.innerHTML = '';
                
                if (result.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-muted-foreground">Aucun ticket non résolu trouvé.</td></tr>';
                    return;
                }
                
                result.data.forEach(ticket => {
                    const priorityColors = {
                        'low': 'bg-green-100 text-green-800',
                        'medium': 'bg-yellow-100 text-yellow-800',
                        'high': 'bg-red-100 text-red-800'
                    };
                    
                    const priorityLabels = {
                        'low': 'Faible',
                        'medium': 'Moyenne',
                        'high': 'Élevée'
                    };
                    
                    const row = document.createElement('tr');
                    row.className = 'border-t';
                    row.innerHTML = `
                        <td class="p-4 font-mono text-sm">#${ticket.id}</td>
                        <td class="p-4">
                            <div class="font-medium">${ticket.title}</div>
                            <div class="text-sm text-muted-foreground truncate max-w-xs">
                                ${ticket.description}
                            </div>
                        </td>
                        <td class="p-4 text-sm">${ticket.user_email}</td>
                        <td class="p-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium ${priorityColors[ticket.priority]}">
                                ${priorityLabels[ticket.priority]}
                            </span>
                        </td>
                        <td class="p-4 text-sm text-muted-foreground">${new Date(ticket.created_at).toLocaleDateString('fr-FR')}</td>
                        <td class="p-4">
                            <div class="flex gap-2">
                                <button onclick="generateSingleReport(${ticket.id})" 
                                        class="text-primary hover:text-primary/80 text-sm">
                                    PDF
                                </button>
                                <button onclick="viewTicket(${ticket.id})" 
                                        class="text-secondary hover:text-secondary/80 text-sm">
                                    Voir
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des tickets non résolus:', error);
            const tbody = document.getElementById('noResoluTableBody');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-destructive">Erreur lors du chargement des tickets</td></tr>';
        }
    }
    
    async function generateSingleReport(ticketId) {
        try {
            const response = await fetch('api/generate_report.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'generate_single_report',
                    ticket_id: ticketId
                })
            });
            
            if (response.ok) {
                // Create a blob from the PDF data
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                
                // Create a temporary link and trigger download
                const a = document.createElement('a');
                a.href = url;
                a.download = `ticket_${ticketId}_non_resolu.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                // Show success notification
                const notification = document.createElement('div');
                notification.className = 'fixed bottom-4 right-4 bg-green-100 text-green-800 p-4 rounded-lg shadow-lg z-50';
                notification.innerHTML = `
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Rapport PDF généré avec succès</span>
                    </div>
                `;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);
            } else {
                const result = await response.json();
                throw new Error(result.message || 'Erreur lors de la génération du rapport');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Erreur lors de la génération du rapport: ' + error.message);
        }
    }
    
    async function generateAllReports() {
        try {
            const response = await fetch('api/generate_report.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'generate_all_report'
                })
            });
            
            if (response.ok) {
                // Create a blob from the PDF data
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                
                // Create a temporary link and trigger download
                const a = document.createElement('a');
                a.href = url;
                a.download = 'rapport_tickets_non_resolus.pdf';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                // Show success notification
                const notification = document.createElement('div');
                notification.className = 'fixed bottom-4 right-4 bg-green-100 text-green-800 p-4 rounded-lg shadow-lg z-50';
                notification.innerHTML = `
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Rapport complet généré avec succès</span>
                    </div>
                `;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);
            } else {
                const result = await response.json();
                throw new Error(result.message || 'Erreur lors de la génération du rapport');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Erreur lors de la génération du rapport: ' + error.message);
        }
    }
    
    // Gestion de l'historique
    async function loadHistorique() {
        try {
            const response = await fetch('api/historique.php');
            const result = await response.json();
            
            if (result.success) {
                const tbody = document.getElementById('historiqueTableBody');
                tbody.innerHTML = '';
                
                if (result.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-muted-foreground">Aucun historique trouvé.</td></tr>';
                    return;
                }
                
                result.data.forEach(entry => {
                    const actionColors = {
                        'created': 'bg-green-100 text-green-800',
                        'updated': 'bg-blue-100 text-blue-800',
                        'deleted': 'bg-red-100 text-red-800',
                        'status_changed': 'bg-yellow-100 text-yellow-800',
                        'priority_changed': 'bg-purple-100 text-purple-800'
                    };
                    
                    const actionLabels = {
                        'created': 'Créé',
                        'updated': 'Modifié',
                        'deleted': 'Supprimé',
                        'status_changed': 'Statut changé',
                        'priority_changed': 'Priorité changée'
                    };
                    
                    const row = document.createElement('tr');
                    row.className = 'border-t';
                    row.innerHTML = `
                        <td class="p-4 text-sm text-muted-foreground">${new Date(entry.created_at).toLocaleString('fr-FR')}</td>
                        <td class="p-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium ${actionColors[entry.action]}">
                                ${actionLabels[entry.action]}
                            </span>
                        </td>
                        <td class="p-4">
                            <div class="font-medium">${entry.ticket_title || 'Ticket supprimé'}</div>
                            <div class="text-sm text-muted-foreground">ID: #${entry.ticket_id}</div>
                        </td>
                        <td class="p-4 text-sm">${entry.user_email}</td>
                        <td class="p-4 text-sm text-muted-foreground">${entry.details || 'Aucun détail'}</td>
                        <td class="p-4">
                            <div class="flex gap-2">
                                ${entry.action === 'deleted' ? `
                                    <button onclick="restoreTicket(${entry.ticket_id})" 
                                            class="text-green-600 hover:text-green-800 text-sm">
                                        Restaurer
                                    </button>
                                ` : ''}
                                <button onclick="viewTicketDetails(${entry.ticket_id})" 
                                        class="text-primary hover:text-primary/80 text-sm">
                                    Détails
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Erreur lors du chargement de l\'historique:', error);
            const tbody = document.getElementById('historiqueTableBody');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-destructive">Erreur lors du chargement de l\'historique</td></tr>';
        }
    }
    
    async function loadDeletedTickets() {
        try {
            const response = await fetch('api/historique.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'get_deleted_tickets'
                })
            });
            const result = await response.json();
            
            if (result.success) {
                const tbody = document.getElementById('historiqueTableBody');
                tbody.innerHTML = '';
                
                if (result.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-muted-foreground">Aucun ticket supprimé trouvé.</td></tr>';
                    return;
                }
                
                result.data.forEach(ticket => {
                    const priorityColors = {
                        'low': 'bg-green-100 text-green-800',
                        'medium': 'bg-yellow-100 text-yellow-800',
                        'high': 'bg-red-100 text-red-800'
                    };
                    
                    const priorityLabels = {
                        'low': 'Faible',
                        'medium': 'Moyenne',
                        'high': 'Élevée'
                    };
                    
                    const row = document.createElement('tr');
                    row.className = 'border-t';
                    row.innerHTML = `
                        <td class="p-4 text-sm text-muted-foreground">${new Date(ticket.deleted_at).toLocaleString('fr-FR')}</td>
                        <td class="p-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Supprimé
                            </span>
                        </td>
                        <td class="p-4">
                            <div class="font-medium">${ticket.title}</div>
                            <div class="text-sm text-muted-foreground">ID: #${ticket.id}</div>
                            <div class="text-sm text-muted-foreground">Priorité: 
                                <span class="px-1 py-0.5 rounded text-xs font-medium ${priorityColors[ticket.priority]}">
                                    ${priorityLabels[ticket.priority]}
                                </span>
                            </div>
                        </td>
                        <td class="p-4 text-sm">${ticket.user_email}</td>
                        <td class="p-4 text-sm text-muted-foreground">${ticket.deletion_reason || 'Supprimé par l\'administrateur'}</td>
                        <td class="p-4">
                            <div class="flex gap-2">
                                <button onclick="restoreTicket(${ticket.id})" 
                                        class="text-green-600 hover:text-green-800 text-sm">
                                    Restaurer
                                </button>
                                <button onclick="viewTicketDetails(${ticket.id})" 
                                        class="text-primary hover:text-primary/80 text-sm">
                                    Détails
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des tickets supprimés:', error);
            const tbody = document.getElementById('historiqueTableBody');
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-destructive">Erreur lors du chargement des tickets supprimés</td></tr>';
        }
    }
    
    async function restoreTicket(ticketId) {
        if (!confirm('Êtes-vous sûr de vouloir restaurer ce ticket ?')) {
            return;
        }
        
        try {
            const response = await fetch('api/historique.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'restore_ticket',
                    ticket_id: ticketId
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
                        <span>Ticket restauré avec succès</span>
                    </div>
                `;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);
                
                // Reload the historique
                loadHistorique();
            } else {
                alert('Erreur: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Erreur lors de la restauration du ticket');
        }
    }
    
    function viewTicketDetails(ticketId) {
        // For deleted tickets, show details from historique
        alert('Fonctionnalité de détails pour tickets supprimés à implémenter');
    }
    
    async function generateHistoriqueReport() {
        try {
            const response = await fetch('api/historique.php');
            const result = await response.json();
            
            if (result.success) {
                // Create a simple text report
                let reportContent = 'RAPPORT HISTORIQUE - CBG Gestion des Incidents\n';
                reportContent += '================================================\n\n';
                reportContent += `Généré le: ${new Date().toLocaleString('fr-FR')}\n`;
                reportContent += `Total d'actions: ${result.count}\n\n`;
                
                result.data.forEach((entry, index) => {
                    reportContent += `${index + 1}. ${entry.action.toUpperCase()} - ${entry.ticket_title || 'Ticket supprimé'}\n`;
                    reportContent += `   Date: ${new Date(entry.created_at).toLocaleString('fr-FR')}\n`;
                    reportContent += `   Utilisateur: ${entry.user_email}\n`;
                    reportContent += `   Détails: ${entry.details || 'Aucun détail'}\n\n`;
                });
                
                // Create and download the report
                const blob = new Blob([reportContent], { type: 'text/plain;charset=utf-8' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `historique_${new Date().toISOString().split('T')[0]}.txt`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                // Show success notification
                const notification = document.createElement('div');
                notification.className = 'fixed bottom-4 right-4 bg-green-100 text-green-800 p-4 rounded-lg shadow-lg z-50';
                notification.innerHTML = `
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Rapport historique généré avec succès</span>
                    </div>
                `;
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 3000);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Erreur lors de la génération du rapport historique: ' + error.message);
        }
    }
</script>

</body>
</html>

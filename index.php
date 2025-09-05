<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    $redirectUrl = $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php';
    header("Location: $redirectUrl");
    exit;
}

$error = '';

if ($_POST) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if ($email && $password) {
        $db = new Database();
        $conn = $db->connect();
        
        $stmt = $conn->prepare("SELECT id, email, password, role FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            $redirectUrl = $user['role'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php';
            header("Location: $redirectUrl");
            exit;
        } else {
            $error = 'Email ou mot de passe incorrect';
        }
    }
}

$pageTitle = 'Connexion - Gestion des Incidents';
include 'includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary/5 to-primary/10">
    <div class="w-full max-w-md mx-4">
        <div class="bg-card shadow-xl rounded-lg border">
            <div class="p-6 text-center border-b">
                <div class="mx-auto mb-4">
                    <img src="static/logo.png" 
                         alt="CBG Logo" 
                         class="w-20 h-16 mx-auto mb-2">
                </div>
                <h1 class="text-2xl font-bold">Gestion des Incidents</h1>
                <p class="text-muted-foreground">Connectez-vous pour accéder à la plateforme</p>
            </div>
            
            <div class="p-6">
                <?php if ($error): ?>
                    <div class="mb-4 p-3 bg-destructive/10 border border-destructive/20 rounded-md">
                        <p class="text-destructive text-sm"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Type de compte</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" onclick="selectRole('user')" 
                                    class="role-btn flex items-center justify-center gap-2 p-3 border rounded-md bg-primary text-primary-foreground"
                                    id="user-btn">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Utilisateur
                            </button>
                            <button type="button" onclick="selectRole('admin')" 
                                    class="role-btn flex items-center justify-center gap-2 p-3 border rounded-md bg-secondary text-secondary-foreground"
                                    id="admin-btn">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                Admin
                            </button>
                        </div>
                        <input type="hidden" name="role" value="user" id="role-input">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium mb-2">Email</label>
                        <input type="email" id="email" name="email" required
                               placeholder="votre.email@outlook.com"
                               class="w-full p-3 border border-input bg-background rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium mb-2">Mot de passe</label>
                        <input type="password" id="password" name="password" required
                               class="w-full p-3 border border-input bg-background rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                    </div>

                    <button type="submit" 
                            class="w-full bg-primary text-primary-foreground p-3 rounded-md hover:bg-primary/90 transition-colors">
                        Se connecter
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function selectRole(role) {
    document.getElementById('role-input').value = role;
    
    const userBtn = document.getElementById('user-btn');
    const adminBtn = document.getElementById('admin-btn');
    
    if (role === 'user') {
        userBtn.className = 'role-btn flex items-center justify-center gap-2 p-3 border rounded-md bg-primary text-primary-foreground';
        adminBtn.className = 'role-btn flex items-center justify-center gap-2 p-3 border rounded-md bg-secondary text-secondary-foreground';
    } else {
        userBtn.className = 'role-btn flex items-center justify-center gap-2 p-3 border rounded-md bg-secondary text-secondary-foreground';
        adminBtn.className = 'role-btn flex items-center justify-center gap-2 p-3 border rounded-md bg-primary text-primary-foreground';
    }
}
</script>

</body>
</html>
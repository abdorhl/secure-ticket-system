<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Gestion des Incidents'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            DEFAULT: 'hsl(354, 98%, 44%)',
                            foreground: 'hsl(0, 0%, 100%)',
                            glow: 'hsl(354, 98%, 55%)'
                        },
                        background: 'hsl(0, 0%, 100%)',
                        foreground: 'hsl(222.2, 84%, 4.9%)',
                        card: 'hsl(0, 0%, 100%)',
                        'card-foreground': 'hsl(222.2, 84%, 4.9%)',
                        popover: 'hsl(0, 0%, 100%)',
                        'popover-foreground': 'hsl(222.2, 84%, 4.9%)',
                        secondary: 'hsl(210, 40%, 96.1%)',
                        'secondary-foreground': 'hsl(222.2, 47.4%, 11.2%)',
                        muted: 'hsl(210, 40%, 96.1%)',
                        'muted-foreground': 'hsl(215.4, 16.3%, 46.9%)',
                        accent: 'hsl(210, 40%, 96.1%)',
                        'accent-foreground': 'hsl(222.2, 47.4%, 11.2%)',
                        destructive: 'hsl(0, 84.2%, 60.2%)',
                        'destructive-foreground': 'hsl(210, 40%, 98%)',
                        border: 'hsl(214.3, 31.8%, 91.4%)',
                        input: 'hsl(214.3, 31.8%, 91.4%)',
                        ring: 'hsl(354, 98%, 44%)'
                    }
                }
            }
        }
    </script>
    <link rel="icon" href="static/logo.png" type="image/png">
</head>
<body class="bg-background text-foreground">
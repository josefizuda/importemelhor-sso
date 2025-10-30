<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Importe Melhor</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/public/css/login.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- Background Pattern -->
<div class="login-background">
    <div class="pattern-overlay"></div>
</div>

<!-- Login Container -->
<div class="login-container">
    
    <!-- Left Side - Branding -->
    <div class="login-branding">
        <div class="brand-content">
            <div class="brand-logo">
                <img src="/public/images/LogoHazul.png" alt="Importe Melhor" class="logo-image">
            </div>
            <h1 class="brand-title" style="display: none;">Importe Melhor</h1>
            <p class="brand-subtitle">Plataforma completa de gestão para importação e comércio exterior</p>
            
            <div class="brand-features">
                <div class="feature-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Gestão de participantes</span>
                </div>
                <div class="feature-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Controle de documentos</span>
                </div>
                <div class="feature-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Notificações automáticas</span>
                </div>
                <div class="feature-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Itinerários personalizados</span>
                </div>
            </div>
            
            <div class="brand-footer">
                <p>Desenvolvido por <strong>Importe Melhor</strong></p>
            </div>
        </div>
    </div>
    
    <!-- Right Side - Login Form -->
    <div class="login-form-container">
        <div class="login-form-wrapper">
            
            <!-- Mobile Logo -->
            <div class="mobile-logo">
                <img src="/public/images/LogoHazul.png" alt="Importe Melhor" class="mobile-logo-image">
            </div>
            
            <div class="login-header">
                <h2>Bem-vindo de volta! 👋</h2>
                <p>Acesse todas as ferramentas com login único</p>
            </div>
            
            <!-- Microsoft SSO Button -->
            <a href="/sso-login.php" class="btn-microsoft">
                <svg width="21" height="21" viewBox="0 0 21 21" fill="none">
                    <rect width="10" height="10" fill="#F25022"/>
                    <rect x="11" width="10" height="10" fill="#7FBA00"/>
                    <rect y="11" width="10" height="10" fill="#00A4EF"/>
                    <rect x="11" y="11" width="10" height="10" fill="#FFB900"/>
                </svg>
                <span>Entrar com Microsoft</span>
            </a>
            
            <div class="login-help">
                <p style="text-align: center; color: #6B7280; font-size: 0.875rem;">
                    ✅ Login único para todas plataformas<br>
                    ✅ Segurança corporativa Microsoft<br>
                    ✅ Acesso rápido e simplificado
                </p>
            </div>
            
        </div>
    </div>
    
</div>

</body>
</html>

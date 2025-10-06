<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@2.0.46/css/materialdesignicons.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

<style>
    /* Subtle Dark Theme for Admin */
    .admin-header {
        background: rgba(20, 20, 20, 0.95) !important;
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }
    
    .admin-sidebar {
        background: rgba(20, 20, 20, 0.95) !important;
        backdrop-filter: blur(20px);
        border-right: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
    }
    
    .nav-item-enhanced {
        padding: 12px 20px;
        margin: 4px 0;
        border-radius: 12px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        cursor: pointer;
        font-weight: 500;
        color: #e5e7eb !important;
    }
    
    .nav-item-enhanced::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
        transition: left 0.5s ease;
    }
    
    .nav-item-enhanced:hover::before {
        left: 100%;
    }
    
    .nav-item-enhanced:hover {
        background: rgba(255, 255, 255, 0.05);
        transform: translateX(2px);
        color: #f3f4f6 !important;
    }
    
    .nav-item-enhanced.active {
        background: rgba(34, 197, 94, 0.15) !important;
        color: #22c55e !important;
        border: 1px solid rgba(34, 197, 94, 0.3);
        box-shadow: 0 2px 8px rgba(34, 197, 94, 0.2);
    }
    
    .nav-item-enhanced.active::before {
        display: none;
    }
    
    .nav-item-enhanced i {
        font-size: 1.1rem;
        width: 24px;
        text-align: center;
        margin-right: 12px;
        transition: transform 0.3s ease;
    }
    
    .nav-item-enhanced:hover i {
        transform: scale(1.05);
    }
    
    .menu-toggle {
        transition: all 0.3s ease;
        padding: 8px;
        border-radius: 8px;
    }
    
    .menu-toggle:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: scale(1.05);
    }
    
    /* Special colors for specific items */
    .nav-item-enhanced.text-blue-300 {
        color: #93c5fd !important;
    }
    
    .nav-item-enhanced.text-red-600 {
        color: #f87171 !important;
    }
    
    .nav-item-enhanced.text-blue-300:hover {
        color: #60a5fa !important;
        background: rgba(59, 130, 246, 0.1) !important;
    }
    
    .nav-item-enhanced.text-red-600:hover {
        color: #ef4444 !important;
        background: rgba(239, 68, 68, 0.1) !important;
    }
    
    /* Logo styling */
    .admin-logo {
        filter: brightness(1.1);
        transition: transform 0.3s ease;
    }
    
    .admin-logo:hover {
        transform: scale(1.02);
    }
</style>

<header class="admin-header fixed lg:hidden w-full h-20 top-0 z-[9998] flex justify-between items-center px-5">
    <i class="fa-solid fa-bars text-white text-2xl cursor-pointer menu-toggle" id="menu-toggle"></i>
    <div></div>
</header>

<aside id="aside" class="admin-sidebar fixed w-full lg:w-[280px] h-[calc(100vh-80px)] lg:h-full z-[9999] top-20 lg:top-0 left-0 flex flex-col justify-start gap-6 text-xl text-gray-300 px-8 hidden lg:flex" style="padding: 20px 16px !important;">
    
    <nav class="flex flex-col gap-2">
        <p onclick="window.location.href='/admin'" class="nav-item-enhanced flex items-center gap-4 cursor-pointer" style="padding-left: 16px;"> 
            <i class="fa-solid fa-chart-line"></i> Dashboard
        </p>
        <p onclick="window.location.href='config.php'" class="nav-item-enhanced flex items-center gap-4 cursor-pointer" style="padding-left: 16px;"> 
            <i class="fa-solid fa-gear"></i> Configurações
        </p>
        <p onclick="window.location.href='gateway.php'" class="nav-item-enhanced flex items-center gap-4 cursor-pointer" style="padding-left: 16px;"> 
            <i class="fa-solid fa-landmark"></i> Gateway
        </p>
        <p onclick="window.location.href='usuarios.php'" class="nav-item-enhanced flex items-center gap-4 cursor-pointer" style="padding-left: 16px;"> 
            <i class="fa-solid fa-users"></i> Usuários
        </p>
        <p onclick="window.location.href='afiliados.php'" class="nav-item-enhanced flex items-center gap-4 cursor-pointer" style="padding-left: 16px;"> 
            <i class="fa-solid fa-people-arrows"></i> Afiliados
        </p>
        <p onclick="window.location.href='cartelas.php'" class="nav-item-enhanced flex items-center gap-4 cursor-pointer" style="padding-left: 16px;"> 
            <i class="fa-solid fa-gem"></i> Raspadinhas
        </p>
        <p onclick="window.location.href='depositos.php'" class="nav-item-enhanced flex items-center gap-4 cursor-pointer" style="padding-left: 16px;"> 
            <i class="fa-solid fa-plus-circle"></i> Depósitos
        </p>
        <p onclick="window.location.href='saques.php'" class="nav-item-enhanced flex items-center gap-4 cursor-pointer" style="padding-left: 16px;"> 
            <i class="fa-solid fa-minus-circle"></i> Saques
        </p>
        
        <div class="my-4 border-t border-gray-600"></div>
        
        <p onclick="window.location.href='/'" class="nav-item-enhanced flex items-center gap-4 cursor-pointer" style="padding-left: 16px;"> 
            <i class="fa-solid fa-globe"></i> Plataforma
        </p>
        <p onclick="window.open('https://wa.me/+5584999591257', '_blank')" class="nav-item-enhanced text-blue-300 flex items-center gap-4 cursor-pointer" style="padding-left: 16px;"> 
            <i class="fa-solid fa-life-ring"></i> Suporte
        </p>
        <p onclick="window.location.href='/logout'" class="nav-item-enhanced text-red-600 flex items-center gap-4 cursor-pointer" style="padding-left: 16px;"> 
            <i class="fa-solid fa-sign-out-alt"></i> Sair
        </p>
    </nav>
</aside>

<script>
    const menuToggle = document.getElementById("menu-toggle");
    const aside = document.getElementById("aside");

    menuToggle.addEventListener("click", () => {
        if (aside.classList.contains("hidden")) {
            aside.classList.remove("hidden");
        } else {
            aside.classList.add("hidden");
        }
    });

    // Auto-hide sidebar on mobile when clicking outside
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 1024 && 
            !aside.contains(event.target) && 
            !menuToggle.contains(event.target) && 
            !aside.classList.contains('hidden')) {
            aside.classList.add('hidden');
        }
    });
</script>
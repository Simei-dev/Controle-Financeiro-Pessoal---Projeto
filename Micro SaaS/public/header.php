<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT email
    FROM users 
    WHERE id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$email_usuario = $user ? $user['email'] : 'Usuário';
?>

<style>
    .header {
        background: var(--header-bg);
    border-bottom: 1px solid var(--header-border);

        position: sticky;
        top: 0;
        z-index: 9999;

        padding-top: 14px;
        padding-bottom: 14px;
    }

    .nav {
        max-width: 1210px;
        margin: auto;
        padding: 0 20px;
        /* remove padding vertical daqui */

        display: flex;
        align-items: center;
        justify-content: space-between;

        min-height: 60px;
        /* altura um pouco maior */
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
    }

    .logo-icon {
        background: var(--logo-gradient);
        padding: 8px;
        border-radius: 8px;
        color: white;
        font-weight: bold;
    }

    .logo-text {
        font-size: 20px;
        font-weight: bold;
        background: var(--logo-text-gradient);

        background-clip: text;
        -webkit-background-clip: text;

        color: transparent;
        -webkit-text-fill-color: transparent;
    }

    .menu {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .menu a {
        padding: 8px 14px;
        border-radius: 8px;
        text-decoration: none;
        color: var(--menu-text);
        font-weight: 500;
    }

    .menu a:hover {
        transform: translateY(-1px);
    }

    .menu .active {
        background: var(--primary-gradient);
        color: white;
    }

    .user {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .email {
        font-size: 14px;
        color: var(--email);
    }

    .logout {
        padding: 8px 14px;
        border-radius: 8px;
        border: 1px solid var(--logout-border);
        text-decoration: none;
        color: var(--menu-text);
    }

    .logout:hover {
       background: var(--logout-hover-bg);
    color: var(--logout-hover-text);
    }

    .mobile-btn {
        display: none;
        font-size: 22px;
        cursor: pointer;
    }

    .mobile-menu {
        display: none;
        flex-direction: column;
        padding: 10px;
        border-top: 1px solid #e5e7eb;
    }

    .mobile-menu a {
        padding: 10px;
        text-decoration: none;
        color: #374151;
    }

    @media(max-width:768px) {

        .menu {
            display: none;
        }

        .user {
            display: none;
        }

        .mobile-btn {
            display: block;
        }

        .mobile-menu.show {
            display: flex;
        }

    }

    body {
        margin: 0;
        padding: 0;
    }

    /* ===== THEME DROPDOWN ===== */

    .theme-dropdown {
        position: relative;
    }

    .theme-btn {
        background: none;
        border: none;
        cursor: pointer;
    }

    .theme-iconn {
        width: 22px;
        height: 22px;
        transition: 0.2s;
        filter: invert(1);
    }
    

    .theme-iconn:hover {
        opacity: 0.9;
        transform: rotate(20deg);
    }

    .theme-menu {
        position: absolute;
        right: 0;
        top: 40px;
        width: 220px;

        background: var(--card-bg);
        border-radius: 12px;
        box-shadow: var(--dropdown-shadow);
        padding: 8px 0;

        display: none;
        z-index: 999999;
        overflow: hidden;
        color: var(--text-color);
    }

    .theme-menu.active {
        display: block;
    }

    /* ===== PÁGINAS INTERNAS ===== */

    .theme-page {
        display: none;
    }

    .theme-page.active {
        display: block;
    }

    .theme-title {
        font-size: 13px;
        font-weight: 600;
        padding: 8px 15px;
        opacity: 0.6;
    }

    /* ===== ITENS ===== */

    .theme-option {
        padding: 10px 15px;
        cursor: pointer;
        font-size: 14px;
        transition: 0.2s;

        display: flex;
        justify-content: space-between;
        align-items: center;
        /* ensure consistent color regardless of surrounding context */
        color: var(--text-color);
    }

    .theme-option:hover {
        background: var(--dropdown-hover);
    }

    body.dark-mode .theme-option:hover {
        background: rgba(255, 255, 255, 0.08);
    }

    .theme-back {
        padding: 10px 15px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        border-bottom: 1px solid var(--dropdown-border);
    }

    body.dark-mode .theme-back {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .theme-value {
        font-size: 12px;
        opacity: 0.6;
    }

    .theme-option.selected {
        background: rgba(0, 0, 0, 0.08);
        font-weight: 600;
    }

    body.dark-mode .theme-option.selected {
        background: rgba(255, 255, 255, 0.12);
    }
    
</style>


<header class="header">

    <div class="nav">

        <a href="dashboard.php" class="logo">

            <div class="logo-icon">
                💰
            </div>

            <div class="logo-text">
                FinanceTracker
            </div>

        </a>


        <div class="menu">

            <a href="dashboard.php"
                class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                Painel
            </a>

            <a href="transacoes.php"
                class="<?= basename($_SERVER['PHP_SELF']) == 'transacoes.php' ? 'active' : '' ?>">
                Transações
            </a>

        </div>


        <div class="user">

            <div class="email">
                <strong><?= htmlspecialchars($email_usuario) ?></strong>
            </div>

            <div class="theme-dropdown">

                <button class="theme-btn" onclick="toggleThemeMenu()">
                    <img src="../imagem/config.svg" class="theme-iconn">
                </button>

                <div class="theme-menu" id="themeMenu">

                    <!-- MENU PRINCIPAL -->
                    <div class="theme-page active" id="theme-main">

                        <div class="theme-option" onclick="openThemeSubmenu()">
                            <span>Tema</span>
                            <span class="theme-value" id="themeLabel">
                                Automático
                            </span>
                        </div>

                    </div>

                    <!-- SUBMENU TEMA -->
                    <div class="theme-page" id="theme-sub">

                        <div class="theme-back" onclick="goBackTheme()">
                            Tema
                        </div>

                        <div class="theme-option" onclick="setTheme('system')">
                            Automático (Sistema)
                        </div>

                        <div class="theme-option" onclick="setTheme('light')">
                            Claro
                        </div>

                        <div class="theme-option" onclick="setTheme('dark')">
                            Escuro
                        </div>

                    </div>

                </div>

            </div>
            <a href="logout.php" class="logout">
                Sair
            </a>

        </div>


        <div class="mobile-btn" onclick="toggleMenu()">
            ☰
        </div>

    </div>


    <div id="mobileMenu" class="mobile-menu">

        <a href="dashboard.php">Painel</a>

        <a href="entradas.php">Transações</a>

        <a href="logout.php">Sair</a>

    </div>

</header>


<script>
    function toggleMenu() {

        var menu = document.getElementById("mobileMenu");

        menu.classList.toggle("show");

    }
</script>
<script>
    function toggleThemeMenu() {
        document.getElementById("themeMenu")
            .classList.toggle("active");
    }

    function openThemeSubmenu() {
        document.getElementById("theme-main").classList.remove("active");
        document.getElementById("theme-sub").classList.add("active");
    }

    function goBackTheme() {
        document.getElementById("theme-sub").classList.remove("active");
        document.getElementById("theme-main").classList.add("active");
    }

    function setTheme(mode) {

        localStorage.setItem("theme", mode);

        document.body.classList.remove("dark-mode");

        if (mode === "dark") {
            document.body.classList.add("dark-mode");
        }

        if (mode === "system") {
            if (window.matchMedia("(prefers-color-scheme: dark)").matches) {
                document.body.classList.add("dark-mode");
            }
        }

        updateThemeLabel();
    }

    function updateThemeLabel() {
        const theme = localStorage.getItem("theme") || "system";
        const label = document.getElementById("themeLabel");

        if (theme === "light") label.innerText = "Claro";
        else if (theme === "dark") label.innerText = "Escuro";
        else label.innerText = "Automático";
    }

    document.addEventListener("DOMContentLoaded", function() {
        updateThemeLabel();
    });

    function updateThemeLabel() {

        const theme = localStorage.getItem("theme") || "system";
        const label = document.getElementById("themeLabel");

        if (theme === "light") label.innerText = "Claro";
        else if (theme === "dark") label.innerText = "Escuro";
        else label.innerText = "Automático";

        // Remove selected de todos
        document.querySelectorAll("#theme-sub .theme-option")
            .forEach(el => el.classList.remove("selected"));

        // Marca o ativo
        document.querySelectorAll("#theme-sub .theme-option")
            .forEach(el => {
                if (
                    (theme === "system" && el.innerText.includes("Automático")) ||
                    (theme === "light" && el.innerText.includes("Claro")) ||
                    (theme === "dark" && el.innerText.includes("Escuro"))
                ) {
                    el.classList.add("selected");
                }
            });
    }
    document.addEventListener("DOMContentLoaded", function(){

    const savedTheme = localStorage.getItem("theme") || "system";

    if(savedTheme === "dark"){
        document.body.classList.add("dark-mode");
    }

    if(savedTheme === "system"){
        if(window.matchMedia("(prefers-color-scheme: dark)").matches){
            document.body.classList.add("dark-mode");
        }
    }

    updateThemeLabel();
});
    document.addEventListener("click", function(e) {

        const menu = document.getElementById("themeMenu");
        const dropdown = document.querySelector(".theme-dropdown");

        if (!dropdown.contains(e.target)) {
            menu.classList.remove("active");
        }

    });

    function toggleThemeMenu() {

        const menu = document.getElementById("themeMenu");

        // Sempre voltar para página principal
        document.getElementById("theme-sub").classList.remove("active");
        document.getElementById("theme-main").classList.add("active");

        menu.classList.toggle("active");
    }
    function setTheme(mode){

    localStorage.setItem("theme", mode);

    document.body.classList.remove("dark-mode");

    if(mode === "dark"){
        document.body.classList.add("dark-mode");
    }

    if(mode === "system"){
        if(window.matchMedia("(prefers-color-scheme: dark)").matches){
            document.body.classList.add("dark-mode");
        }
    }

    updateThemeLabel();
}
</script>
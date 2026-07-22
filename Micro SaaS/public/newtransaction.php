<!-- BOTÕES de criar transação -->
<div class="new-transaction-buttons">
    <button onclick="openNewTransactionModal('entrada')" class="btn-new-transaction btn-entrada" title="Nova Entrada">
        <img src="../imagem/entrada.svg" alt="Entrada">
    </button>
    <button onclick="openNewTransactionModal('saida')" class="btn-new-transaction btn-saida" title="Nova Saída">
        <img src="../imagem/saida.svg" alt="Saída">
    </button>
</div>
<?php

require_once '../config/database.php';

$company_id = $_SESSION['company_id'];

$sql = "
SELECT *
FROM categories
WHERE company_id IS NULL
   OR company_id = ?
ORDER BY
   company_id IS NULL DESC,
   name ASC
";

$stmt = $pdo->prepare($sql); // ✅ usar $pdo
$stmt->execute([$company_id]);

$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!-- MODAL -->
<link rel="stylesheet" href="./css/theme.css">
<div id="newTransactionModal" class="nt-overlay">

    <div class="nt-modal">

        <div class="nt-header">
            <h2>Nova Transação</h2>
            <button type="button" onclick="closeNewTransactionModal()" class="nt-close">✕</button>
        </div>

        <form id="nt-form">
            <input type="hidden" name="id" id="nt-id">
            <input type="hidden" name="modo" id="nt-modo" value="novo">
            <!-- TIPO (oculto) -->
            <input type="hidden" name="tipo" id="nt-tipo" value="entrada">


               <!-- VALOR -->
            <div class="nt-group">
                <label>Valor</label>
                <input
                    type="text"
                    name="valor"
                    id="nt-valor"
                    inputmode="numeric"
                    placeholder="0,00"
                    required>
            </div>
            
            <!-- DESCRIÇÃO -->
            <div class="nt-group">
                <label>Descrição</label>
                <input type="text" name="descricao" required>
            </div>


            <!-- CATEGORIA -->
            <div class="nt-group">
                <label>Categoria</label>
                <select name="category_id" id="nt-category" required>

                    <option value="">Selecione uma categoria</option>

                    <?php foreach ($categorias as $cat): ?>

                        <option value="<?= $cat['id'] ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>

                    <?php endforeach; ?>
                    <option value="nova_categoria">
                        + Criar nova categoria
                    </option>

                </select>
            </div>

            <!-- DATA -->
            <div class="nt-group">
                <label id="nt-data-label">Data</label>
                <input
                    type="date"
                    name="data"
                    id="nt-data"
                    value="<?= date('Y-m-d') ?>"
                    required>
            </div>

            <!-- AÇÕES EXTRAS -->
            <div class="nt-extra-actions">

                <div class="nt-action">
                    <button type="button" id="nt-repeat-btn" class="nt-extra-btn" onclick="toggleRepeat()">
                        <img src="../imagem/repetir.svg" class="theme-icon">
                    </button>
                    <span class="nt-action-label">Repetir</span>
                </div>

                <div class="nt-action">
                    <button type="button" class="nt-extra-btn">
                        <img src="../imagem/clipe.svg" class="theme-icon">
                    </button>
                    <span class="nt-action-label">Anexo</span>
                </div>

            </div><!-- BLOCO REPETIÇÃO -->
            <div id="nt-repeat-box" class="nt-repeat-box" style="display:none;">
                <div class="nt-group">
                    <label class="nt-radio-line">
                        <input type="radio" name="repeat_type" value="fixo" onchange="handleRepeatType()">
                        <span>É uma receita fixa</span>
                    </label>
                    <label class="nt-radio-line">
                        <input type="radio" name="repeat_type" value="parcelado" onchange="handleRepeatType()">
                        <span>É um lançamento parcelado</span>
                    </label>

                </div>

                <!-- FIXO -->
                <div id="nt-fixo-options" style="display:none;">
                    <div class="nt-group">
                        <label>Frequência</label>
                        <select name="repeat_frequency">
                            <option value="daily">Diário</option>
                            <option value="weekly">Semanal</option>
                            <option value="monthly" selected>Mensal</option>
                            <option value="semiannual">Semestral</option>
                            <option value="yearly">Anual</option>
                        </select>
                    </div>
                </div>
                <!-- PARCELADO -->
                <div id="nt-parcelado-options" style="display:none;">

                    <div class="nt-row">

                        <div class="nt-group half">
                            <label>Parcelas</label>
                            <select name="installments" id="nt-installments" onchange="updateParceladoResumo()">
                                <?php for ($i = 1; $i <= 480; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?>x</option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="nt-group half">
                            <label>Frequência</label>
                            <select name="installment_frequency" id="nt-installment-frequency" onchange="updateParceladoResumo()">
                                <option value="monthly" selected>Mensal</option>
                                <option value="weekly">Semanal</option>
                                <option value="daily">Diário</option>
                            </select>
                        </div>

                    </div>

                    <div class="nt-parcelado-resumo" id="nt-parcelado-resumo">
                        Serão lançadas 1 parcela de R$ 0,00
                    </div>

                </div>

            </div><br>
            <button type="submit" class="nt-save" id="nt-btn-salvar">
                Salvar Transação
            </button>

        </form>

    </div>

</div>

<div id="createCategoryModal" class="nt-overlay">

    <div class="nt-modal">

        <div class="nt-header">
            <h2>Nova Categoria</h2>
            <button onclick="closeCreateCategoryModal()" class="nt-close">✕</button>
        </div>

        <div class="nt-group">
            <label>Nome</label>
            <input type="text" id="new-category-name">
        </div>

        <div class="nt-group">
            <label>Cor</label>

            <div class="color-picker-wrapper">

                <div class="color-preview" id="color-preview"></div>

                <div class="color-input-container">

                    <input
                        type="color"
                        id="new-category-color"
                        value="#3b82f6">

                    <span>Escolher cor</span>

                </div>

            </div>
        </div>

        <button onclick="createCategory()" class="nt-save">
            Criar Categoria
        </button>

    </div>

</div>

<div id="deleteModal" class="nt-overlay">

    <div class="nt-modal">

        <div class="nt-header">
            <h2>Excluir Transação</h2>
            <button onclick="closeDeleteModal()" class="nt-close">✕</button>
        </div>

        <p style="margin-bottom:20px; color: var(--text-color);">
            Tem certeza que deseja excluir esta transação?
        </p>

        <input type="hidden" id="delete-id">
        <input type="hidden" id="delete-tipo">

        <button onclick="confirmDelete()" class="nt-save"
            style="background:linear-gradient(135deg,#ef4444,#dc2626)">
            Excluir
        </button>

    </div>

</div>

<style>
    .nt-row {
        display: flex;
        gap: 15px;
    }

    .nt-group.half {
        flex: 1;
    }

    .nt-parcelado-resumo {
        margin-top: 15px;
        padding: 12px;
        background: var(--nt-soft-bg);
        color: var(--text-color);
        border-radius: 10px;
        font-size: 14px;

    }

    .color-picker-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .color-preview {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: var(--primary-color);
        border: 2px solid var(--border-card);
    }

    .color-input-container {
        position: relative;
        background: var(--nt-soft-bg);
        border: 1px solid var(--border-card);
        padding: 6px 12px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
    }

    .color-input-container:hover {
        background: var(--nt-soft-hover);
    }

    .color-input-container input {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    /* BOTÃO */
    .color-picker-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .color-preview {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #3b82f6;
        border: 2px solid #e5e7eb;
    }

    .color-change-btn {
        background: var(--nt-soft-bg);
        border: 1px solid var(--border-card);
        padding: 6px 12px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
    }

    .color-change-btn:hover {
        background: var(--nt-soft-hover);
    }

    /* botões que iniciam criação de transação diretamente por tipo */
    .new-transaction-buttons {
        display: flex;
        gap: 10px;
        align-items: center; /* center vertical alignment for buttons with different heights */
    }

    .btn-new-transaction {
        background: none;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.2s;
        padding: 0;
    }

    /* default icon styling (no tinting so natural colors show) */
    .btn-new-transaction img {
        width: 100%;
        height: 100%;
        filter: none;
        object-fit: contain;
    }

    .btn-new-transaction:hover {
        opacity: 0.9;
        transform: scale(1.05);
    }

    /* entrada maior que saída */
    .btn-new-transaction.btn-entrada {
        width: 60px;
        height: 60px;
    }

    .btn-new-transaction.btn-saida {
        width: 50px;
        height: 50px;
    }

    .btn-new-transaction:hover {
        opacity: 0.9;
        transform: scale(1.05);
    }

    .btn-new-transaction.btn-entrada {
        background: none;
    }

    .btn-new-transaction.btn-saida {
        background: none
    }

    /* OVERLAY — FIX DEFINITIVO */

    .nt-overlay {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: var(--modal-overlay);
        z-index: 2147483647;
    }

    /* MODAL */

    .nt-modal {
        width: 420px;
        max-width: 95%;
        padding: 25px;
        border-radius: 12px;
        background: var(--modal-bg);
        box-shadow: var(--modal-shadow);
        /* textual content should follow theme colors (dark mode support) */
        color: var(--text-color);
    }

    /* RESTANTE IGUAL */



    .nt-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .nt-close {
        border: none;
        background: none;
        font-size: 20px;
        cursor: pointer;
        color: var(--text-color);
    }

    .nt-group {
        margin-bottom: 15px;
    }

    /* labels inside transaction modal/form should adapt to theme */
    .nt-group label {
        color: var(--text-color);
        font-weight: 500;
    }

    /* GARANTE QUE TODOS RESPEITEM O MESMO CÁLCULO DE LARGURA */
    .nt-group input,
    .nt-group select {
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid var(--input-border);
        background: var(--modal-bg);
        color: var(--text-color);
        box-sizing: border-box;
        /* 🔥 ESSA LINHA resolve 90% dos casos */
    }

    /* Remove estilo estranho do select */
    .nt-group select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }

    .nt-save {
        width: 100%;
        padding: 12px;
        border-radius: 10px;
        border: none;
        font-weight: bold;
        color: white;
        background: var(--primary-gradient);
        cursor: pointer;
    }


    .nt-radio-line input[type="radio"] {
        accent-color: #3b82f6;
        /* verde elegante */
    }

    .nt-extra-actions {
        display: flex;
        gap: 25px;
        margin-top: 15px;
        margin-bottom: 10px;
        justify-content: center;
    }

    .nt-action {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }

    /* círculo perfeito */
    .nt-extra-btn {
        flex: none;
        width: 55px;
        height: 55px;
        aspect-ratio: 1 / 1;

        background: var(--nt-soft-bg);
        border: 1px solid var(--border-card);
        border-radius: 50%;

        display: grid;
        place-items: center;

        padding: 0;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .nt-extra-btn:hover {
        background: var(--nt-soft-hover);
        transition: 0.2s;
    }

    /* ícone */
    .theme-icon {
        width: 22px;
        height: 22px;
        filter: var(--filtericon);
        transition: 0.2s;
    }
     .theme-icon:hover {
        opacity: 0.9;
        transform: rotate(20deg);
    }

    /* texto */
    .nt-action-label {
        font-size: 12px;
        font-weight: 500;
        color: var(--text-color);
    }

    .nt-extra-btn:hover {
        background: var(--nt-soft-hover);
    }



    .nt-extra-btn.active {
        background: var(--nt-soft-hover);
        /* mais escuro que o normal */
        border-color: var(--primary-color);
        transform: scale(0.95);
        /* leve pressionado */
    }

    /* RADIO MODERNO */
    .nt-radio-group {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 10px;
    }

    .nt-radio-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        border: 1px solid var(--border-card);
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .nt-radio-option:hover {
        background: var(--nt-soft-hover);
    }

    .nt-radio-option.active {
        border-color: var(--success-color);
        background: var(--success-soft-bg);
    }


    .nt-radio-circle::after {
        content: '';
        width: 8px;
        height: 8px;
        background: #10b981;
        border-radius: 50%;
        display: none;
    }

    .nt-radio-option.active .nt-radio-circle {
        border-color: #10b981;
    }

    .nt-radio-line {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-size: 14px;
        color: var(--text-color);
        margin-bottom: 8px;
    }

    .nt-radio-line input[type="radio"] {
        margin: 0;
        width: 16px;
        height: 16px;
        cursor: pointer;
    }
</style>

<script>
    function updateParceladoResumo() {

        const parcelas = document.getElementById("nt-installments").value;
        const valorInput = document.getElementById("nt-valor").value;

        if (!valorInput) return;

        let valorNumerico = valorInput
            .replace(/\./g, "")
            .replace(",", ".");

        valorNumerico = parseFloat(valorNumerico) || 0;

        const valorParcela = valorNumerico / parcelas;

        const resumo = document.getElementById("nt-parcelado-resumo");

        resumo.innerText =
            `Serão lançadas ${parcelas} parcela${parcelas > 1 ? 's' : ''} de R$ ` +
            valorParcela.toLocaleString("pt-BR", {
                minimumFractionDigits: 2
            });
    }
    /* ==============================
       ATUALIZAR PARCELADO AUTOMÁTICO
    ============================== */

    document.addEventListener("DOMContentLoaded", function() {

        const valorInput = document.getElementById("nt-valor");
        const parcelasInput = document.querySelector('[name="installments"]');
        const frequenciaInput = document.querySelector('[name="installment_frequency"]');

        if (valorInput) {
            valorInput.addEventListener("input", updateParceladoResumo);
        }

        if (parcelasInput) {
            parcelasInput.addEventListener("change", updateParceladoResumo);
        }

        if (frequenciaInput) {
            frequenciaInput.addEventListener("change", updateParceladoResumo);
        }

    });

   function toggleRepeat() {
    const box = document.getElementById("nt-repeat-box");
    const btn = document.getElementById("nt-repeat-btn");

    box.style.display =
        box.style.display === "none" ? "block" : "none";

    btn.classList.toggle("active");
}

    function handleRepeatType() {

        const type = document.querySelector('input[name="repeat_type"]:checked')?.value;

        const fixo = document.getElementById("nt-fixo-options");
        const parcelado = document.getElementById("nt-parcelado-options");

        if (!fixo || !parcelado) return;

        // esconde tudo primeiro
        fixo.style.display = "none";
        parcelado.style.display = "none";

        if (type === "fixo") {
            fixo.style.display = "block";
        }

        if (type === "parcelado") {
            parcelado.style.display = "block";
        }
    }

    function resetRepeatFields() {

        // desmarca os radios
        document.querySelectorAll('input[name="repeat_type"]').forEach(radio => {
            radio.checked = false;
        });

        // esconde opções
        document.getElementById("nt-fixo-options").style.display = "none";
        document.getElementById("nt-parcelado-options").style.display = "none";
    }

    document.addEventListener("DOMContentLoaded", function() {

        /* ==============================
           MOVER MODAIS PRO BODY
        ============================== */

        const modal1 = document.getElementById("newTransactionModal");
        const modal2 = document.getElementById("deleteModal");
        const modal3 = document.getElementById("createCategoryModal");

        if (modal1) document.body.appendChild(modal1);
        if (modal2) document.body.appendChild(modal2);
        if (modal3) document.body.appendChild(modal3);


        /* ==============================
           DATA HOJE
        ============================== */

        (function setTodayDate() {
            const campoData = document.getElementById("nt-data");
            if (!campoData) return;
            const d = new Date();
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            campoData.value = `${yyyy}-${mm}-${dd}`;
        })();


        /* ==============================
   MÁSCARA VALOR + ATUALIZAÇÃO
============================== */

        const inputValor = document.getElementById("nt-valor");

        if (inputValor) {

            inputValor.addEventListener("input", function(e) {

                let value = e.target.value.replace(/\D/g, "");

                if (value === "") {
                    e.target.value = "0,00";
                    updateParceladoResumo();
                    return;
                }

                value = (parseInt(value) / 100).toFixed(2);

                value = value.replace(".", ",");
                value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

                e.target.value = value;

                // 🔥 CHAMA AQUI, DEPOIS DA FORMATAÇÃO
                updateParceladoResumo();

            });

        }


        /* ==============================
           DETECTAR NOVA CATEGORIA
        ============================== */

        const selectCategoria = document.getElementById("nt-category");

        if (selectCategoria) {

            selectCategoria.addEventListener("change", function() {

                if (this.value === "nova_categoria") {

                    this.value = "";

                    openCreateCategoryModal();

                }

            });

        }

        /* ==============================
           PREVIEW DE COR - inicializar aqui
        ============================== */
        const colorInputInit = document.getElementById("new-category-color");
        const colorPreviewInit = document.getElementById("color-preview");
        if (colorInputInit && colorPreviewInit) {
            colorPreviewInit.style.background = colorInputInit.value;
            colorInputInit.addEventListener('input', function() {
                colorPreviewInit.style.background = this.value;
            });
            colorInputInit.addEventListener('change', function() {
                colorPreviewInit.style.background = this.value;
            });
        }

    });


    /* ==============================
       SUBMIT FORM
    ============================== */

    // originally updated form action based on type, now it's a stub to avoid errors
    function updateFormAction() {
        // no-op – kept for legacy calls
    }

    const form = document.getElementById("nt-form");

    if (form) {
        form.addEventListener("submit", function(e) {

            e.preventDefault();

            const btn = document.getElementById("nt-btn-salvar");
            btn.disabled = true;

            const modo = document.getElementById("nt-modo").value;

            let url = "salvar_transacao.php";

            if (modo === "editar") {
                url = "editar_transacao.php";
            }

            fetch(url, {
                    method: "POST",
                    body: new FormData(this)
                })
                .then(() => {
                    closeNewTransactionModal();
                    location.reload();
                })
                .catch(() => {
                    btn.disabled = false;
                    alert("Erro");
                });
        });
    }
    updateFormAction();
    /* ==============================
       ABRIR / FECHAR MODAL
    ============================== */
    function openNewTransactionModal(defaultType) {

        resetModal(); // garante modo novo

        // aplicar tipo passado pelo botão externo, armazenar no hidden
        if (defaultType) {
            document.getElementById("nt-tipo").value = defaultType;

            // ajustar título para ser mais específico
            const headerH2 = document.querySelector('.nt-header h2');
            if (headerH2) {
                headerH2.innerText = defaultType === 'entrada' ? 'Nova Entrada' : 'Nova Saída';
            }
        }

        updateFormAction(); // form action may depend on type
        document.getElementById("newTransactionModal").style.display = "flex";

    }

    function closeNewTransactionModal() {

        document.getElementById("newTransactionModal").style.display = "none";

    };

    /* ==============================
       EDITAR
    ============================== */

    function openEditTransaction(data) {
        console.log(data);
        openNewTransactionModal();

        function formatMoneyBR(value) {

            if (!value) return "0,00";

            value = parseFloat(value);

            return value.toLocaleString("pt-BR", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

        }
        document.getElementById("nt-modo").value = "editar";
        document.getElementById("nt-id").value = data.id;

        // Corrige título (é H2, não H3)
        document.querySelector(".nt-header h2").innerText = "Editar Transação";
        document.getElementById("nt-btn-salvar").innerText = "Salvar Alterações";

        // Define tipo manualmente
        document.getElementById("nt-tipo").value = data.type;

        // Atualiza visual sem depender do click
        document.querySelectorAll(".nt-type").forEach(btn => {
            btn.classList.remove("active");

            if (btn.dataset.value === data.type) {
                btn.classList.add("active");
            }
        });

        // Preenche campos
        document.querySelector('[name="descricao"]').value = data.descricao || "";
        document.querySelector('[name="valor"]').value = formatMoneyBR(data.valor);
        document.querySelector('[name="data"]').value = data.data || "";
        document.getElementById("nt-category").value = data.category_id || "";

        updateFormAction();
    }

    /* ==============================
       RESET MODAL (IMPORTANTE)
    ============================== */

    function resetModal() {

        document.getElementById("nt-form").reset();
        document.getElementById("nt-modo").value = "novo";
        document.getElementById("nt-id").value = "";

        // garante título correto (h2 no modal)
        const headerH2 = document.querySelector('.nt-header h2');
        if (headerH2) headerH2.innerText = 'Nova Transação';
        else {
            const headerH3 = document.querySelector('.nt-header h3');
            if (headerH3) headerH3.innerText = 'Nova Transação';
        }
        document.getElementById("nt-btn-salvar").innerText = "Salvar";

        // reset hidden type value
        document.getElementById("nt-tipo").value = "entrada";

        // após reset, garantir que o campo data receba data de hoje (usar local)
        const campoData = document.getElementById('nt-data');
        if (campoData) {
            const d = new Date();
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            campoData.value = `${yyyy}-${mm}-${dd}`;
        }

        // também resetar preview de cor (caso modal de criar categoria usado anteriormente)
        const colorInput = document.getElementById('new-category-color');
        const colorPreview = document.getElementById('color-preview');
        if (colorInput && colorPreview) {
            colorPreview.style.background = colorInput.value;
        }

    }

    /* ==============================
       EXCLUIR (AGORA SÓ ID)
    ============================== */
    function openDeleteModal(id) {

        document.getElementById("delete-id").value = id;
        document.getElementById("deleteModal").style.display = "flex";

    }

    function closeDeleteModal() {

        document.getElementById("deleteModal").style.display = "none";

    }

    function confirmDelete() {

        const id = document.getElementById("delete-id").value;

        fetch("excluir_transacao.php", {

                method: "POST",

                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },

                body: "id=" + id

            })
            .then(() => {
                closeDeleteModal();
                location.reload();
            })
            .catch(() => alert("Erro ao excluir"));

    }

    /* ==============================
       CRIAR CATEGORIA
    ============================== */
    function openCreateCategoryModal() {
        document.getElementById("createCategoryModal").style.display = "flex";
    }

    function closeCreateCategoryModal() {
        document.getElementById("createCategoryModal").style.display = "none";
    }
    document.getElementById("nt-category").addEventListener("change", function() {

        if (this.value === "nova_categoria") {

            this.value = "";

            openCreateCategoryModal();

        }

    });

    function createCategory() {
        const name = document.getElementById("new-category-name").value;
        const color = document.getElementById("new-category-color").value;

        if (!name) {
            alert("Digite um nome");
            return;
        }

        fetch("criar_categoria.php", {

                method: "POST",

                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },

                body: "name=" + encodeURIComponent(name) +
                    "&color=" + encodeURIComponent(color)

            })
            .then(res => res.json())
            .then(cat => {

                const select = document.getElementById("nt-category");

                const option = document.createElement("option");

                option.value = cat.id;
                option.textContent = cat.name;

                select.appendChild(option);

                select.value = cat.id;

                closeCreateCategoryModal();

            })
            .catch(() => alert("Erro ao criar categoria"));

    }
    const colorInput = document.getElementById("new-category-color");
    const colorPreview = document.getElementById("color-preview");

    if (colorInput && colorPreview) {

        colorPreview.style.background = colorInput.value;

        colorInput.addEventListener("input", function() {

            colorPreview.style.background = this.value;

        });

    }
</script>
<!-- Modal Backdrop -->
<div id="backdrop2" class="modal-backdrop"></div>

<!-- Deposit Modal -->
<section id="depositModal" class="modal-container">
    <div class="modal-wrapper">
        <div class="modal-card">
            <button id="closeDepositModal" class="modal-close">
                <i class="bi bi-x"></i>
            </button>

            <div class="modal-icon">
                <i class="bi bi-credit-card"></i>
            </div>

            <h2 class="modal-title">Depósito</h2>

            <form id="depositForm" class="modal-form">
                <div class="form-group">
                    <div class="input-icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <input type="text" name="amount" id="amountInput" required class="form-input"
                        placeholder="Digite o valor do depósito" inputmode="numeric">
                </div>

                <div class="quick-amounts">
                    <button type="button" data-value="20" class="quick-amount">R$ 20</button>
                    <button type="button" data-value="50" class="quick-amount">R$ 50</button>
                    <button type="button" data-value="100" class="quick-amount">R$ 100</button>
                    <button type="button" data-value="200" class="quick-amount">R$ 200</button>
                </div>

                <div class="form-group">
                    <div class="input-icon">
                        <i class="bi bi-person-vcard"></i>
                    </div>
                    <input type="text" name="cpf" id="cpfInput" required class="form-input"
                        placeholder="CPF (000.000.000-00)" maxlength="14">
                </div>

                <button type="submit" class="submit-btn">
                    <i class="bi bi-check-circle"></i>
                    Depositar
                </button>
            </form>

            <div id="qrArea" class="qr-area">
                <h3 class="qr-title">PIX gerado com sucesso!</h3>
                <p class="qr-description">Escaneie o QR Code ou use o código Pix Copia e Cola</p>
                <img id="qrImg" src="" alt="QR Code" class="qr-image">
                <div class="qr-code-container">
                    <input id="qrCodeValue" type="text" readonly class="qr-input" value="">
                    <button id="copyQr" class="copy-btn">
                        <i class="bi bi-clipboard"></i>
                        Copiar
                    </button>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Withdraw Modal -->
<section id="withdrawModal" class="modal-container">
    <div class="modal-wrapper">
        <div class="modal-card">
            <button id="closeWithdrawModal" class="modal-close">
                <i class="bi bi-x"></i>
            </button>

            <div class="modal-icon withdraw">
                <i class="bi bi-cash-coin"></i>
            </div>

            <h2 class="modal-title">Solicitar Saque</h2>

            <div class="balance-card">
                <h3 class="balance-label">Saldo Disponível</h3>
                <p class="balance-amount" id="currentBalance">R$ 0,00</p>
            </div>

            <form id="withdrawForm" class="modal-form">
                <div class="form-group">
                    <div class="input-icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <input type="text" name="amount" id="withdrawAmount" required class="form-input"
                        placeholder="Digite o valor do saque" inputmode="numeric">
                </div>

                <div class="quick-amounts">
                    <button type="button" data-value="50" class="quick-withdraw">R$ 50</button>
                    <button type="button" data-value="100" class="quick-withdraw">R$ 100</button>
                    <button type="button" data-value="200" class="quick-withdraw">R$ 200</button>
                    <button type="button" data-value="500" class="quick-withdraw">R$ 500</button>
                </div>

                <div class="form-group">
                    <div class="input-icon">
                        <i class="bi bi-person-vcard"></i>
                    </div>
                    <input type="text" name="cpf" id="withdrawCpf" required class="form-input"
                        placeholder="CPF (000.000.000-00)" maxlength="14">
                </div>

                <button type="submit" class="submit-btn withdraw-btn">
                    <i class="bi bi-check-circle"></i>
                    Solicitar Saque
                </button>
            </form>
        </div>
    </div>
</section>

<style>
    /* Modal Styles */
    .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(8px);
        z-index: 1200;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .modal-backdrop.active {
        opacity: 1;
        visibility: visible;
    }

    .modal-container {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1250;
        overflow-y: auto;
        padding: 2rem;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .modal-container.active {
        opacity: 1;
        visibility: visible;
    }

    .modal-wrapper {
        width: 100%;
        max-width: 650px;
    }

    .modal-card {
        background: rgba(20, 20, 20, 0.95);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        padding: 3rem;
        position: relative;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        margin-bottom: 2rem;
    }

    .modal-close {
        position: absolute;
        top: -12px;
        right: -12px;
        width: 40px;
        height: 40px;
        background: #ef4444;
        border: none;
        border-radius: 50%;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 1.2rem;
    }

    .modal-close:hover {
        background: #dc2626;
        transform: scale(1.1);
    }

    .modal-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        border-radius: 20px;
        margin: 0 auto 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        box-shadow: 0 8px 24px rgba(34, 197, 94, 0.3);
    }

    .modal-icon.withdraw {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        box-shadow: 0 8px 24px rgba(245, 158, 11, 0.3);
    }

    .modal-title {
        color: white;
        font-size: 2rem;
        font-weight: 800;
        text-align: center;
        margin-bottom: 2rem;
    }

    .balance-card {
        background: rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.2);
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
        margin-bottom: 2rem;
    }

    .balance-label {
        color: #9ca3af;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .balance-amount {
        color: #22c55e;
        font-size: 1.5rem;
        font-weight: 800;
    }

    .modal-form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .form-group {
        position: relative;
    }

    .form-input {
        width: 100%;
        padding: 1rem 1rem 1rem 3rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        color: white;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-input:focus {
        outline: none;
        border-color: #22c55e;
        background: rgba(255, 255, 255, 0.08);
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
    }

    .form-input::placeholder {
        color: #6b7280;
    }

    .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        font-size: 1rem;
        transition: color 0.3s ease;
    }

    .form-group:focus-within .input-icon {
        color: #22c55e;
    }

    .quick-amounts {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.5rem;
    }

    .quick-amount,
    .quick-withdraw {
        background: rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.3);
        color: #22c55e;
        padding: 0.75rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .quick-amount:hover,
    .quick-withdraw:hover {
        background: rgba(34, 197, 94, 0.2);
        border-color: #22c55e;
        transform: translateY(-2px);
    }

    .submit-btn {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: white;
        border: none;
        padding: 1rem;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 20px rgba(34, 197, 94, 0.3);
    }

    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(34, 197, 94, 0.4);
    }

    .withdraw-btn {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        box-shadow: 0 4px 20px rgba(245, 158, 11, 0.3);
    }

    .withdraw-btn:hover {
        box-shadow: 0 8px 30px rgba(245, 158, 11, 0.4);
    }

    .qr-area {
        text-align: center;
        display: none;
    }

    .qr-area.active {
        display: block;
    }

    .qr-title {
        color: white;
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .qr-description {
        color: #9ca3af;
        margin-bottom: 2rem;
    }

    .qr-image {
        width: 250px;
        height: 250px;
        background: white;
        padding: 1rem;
        border-radius: 16px;
        margin: 0 auto 2rem;
    }

    .qr-code-container {
        position: relative;
        display: flex;
        gap: 0.5rem;
    }

    .qr-input {
        flex: 1;
        padding: 0.75rem;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        color: white;
        font-family: monospace;
        font-size: 0.9rem;
    }

    .copy-btn {
        background: #22c55e;
        color: white;
        border: none;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .copy-btn:hover {
        background: #16a34a;
    }

    .features-grid {
        display: none;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }

    .feature-card {
        background: rgba(20, 20, 20, 0.8);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
        color: white;
    }

    .feature-card i {
        font-size: 2rem;
        color: #22c55e;
        margin-bottom: 1rem;
    }

    .feature-card h4 {
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .feature-card p {
        color: #9ca3af;
        font-size: 0.9rem;
        line-height: 1.4;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .modal-card {
            padding: 2rem;
            margin: 1rem;
        }

        .quick-amounts {
            grid-template-columns: repeat(2, 1fr);
        }

        .qr-image {
            width: 200px;
            height: 200px;
        }
    }

    @media (min-width: 768px) {
        .features-grid {
            display: grid;
        }
    }
</style>

<script>
    // Modal Management
    function openDepositModal() {
        document.getElementById('depositModal').classList.add('active');
        document.getElementById('backdrop2').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeDepositModal() {
        document.getElementById('depositModal').classList.remove('active');
        document.getElementById('backdrop2').classList.remove('active');
        document.body.style.overflow = '';
        document.getElementById('depositForm').style.display = 'flex';
        document.getElementById('qrArea').classList.remove('active');
        document.getElementById('depositForm').reset();
    }

    function openWithdrawModal(balance) {
        document.getElementById('withdrawModal').classList.add('active');
        document.getElementById('backdrop2').classList.add('active');
        document.body.style.overflow = 'hidden';
        document.getElementById('currentBalance').textContent = `R$ ${balance.toFixed(2).replace('.', ',')}`;
    }

    function closeWithdrawModal() {
        document.getElementById('withdrawModal').classList.remove('active');
        document.getElementById('backdrop2').classList.remove('active');
        document.body.style.overflow = '';
        document.getElementById('withdrawForm').reset();
    }

    // Event Listeners
    document.getElementById('closeDepositModal')?.addEventListener('click', closeDepositModal);
    document.getElementById('closeWithdrawModal')?.addEventListener('click', closeWithdrawModal);
    document.getElementById('backdrop2')?.addEventListener('click', function () {
        closeDepositModal();
        closeWithdrawModal();
    });

    // CPF Formatting
    function formatCPF(input) {
        input.addEventListener('input', e => {
            let v = e.target.value.replace(/\D/g, '').slice(0, 11);
            v = v.replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = v;
        });
    }

    document.getElementById('cpfInput') && formatCPF(document.getElementById('cpfInput'));
    document.getElementById('withdrawCpf') && formatCPF(document.getElementById('withdrawCpf'));

    // Quick Amount Buttons
    document.querySelectorAll('.quick-amount').forEach(btn => {
        btn.addEventListener('click', () => {
            const val = parseFloat(btn.dataset.value);
            const input = document.getElementById('amountInput');
            const current = parseFloat(input.value.replace(',', '.')) || 0;
            input.value = (current + val).toFixed(2).replace('.', ',');
        });
    });

    document.querySelectorAll('.quick-withdraw').forEach(btn => {
        btn.addEventListener('click', () => {
            const val = parseFloat(btn.dataset.value);
            document.getElementById('withdrawAmount').value = val.toFixed(2).replace('.', ',');
        });
    });

    // Deposit Form Submission
    document.getElementById('depositForm')?.addEventListener('submit', async e => {
        e.preventDefault();

        const amountInput = document.getElementById('amountInput');
        const value = parseFloat(amountInput.value.replace(/[^\d,]/g, '').replace(',', '.'));
        const depositoMin = <?= isset($depositoMin) ? $depositoMin : 20 ?>;

        if (isNaN(value)) {
            Notiflix.Notify.failure('Por favor, insira um valor válido');
            return;
        }

        if (value < depositoMin) {
            Notiflix.Notify.failure(`O valor mínimo para depósito é R$ ${depositoMin.toFixed(2).replace('.', ',')}`);
            return;
        }

        Notiflix.Loading.standard('Gerando pagamento...');
        const form = e.target;
        const formData = new FormData(form);

        try {
            const res = await fetch('/api/payment.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.qrcode) {
                form.style.display = 'none';
                const qrArea = document.getElementById('qrArea');
                document.getElementById('qrImg').src =
                    `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(data.qrcode)}`;
                document.getElementById('qrCodeValue').value = data.qrcode;
                qrArea.classList.add('active');
                Notiflix.Loading.remove();
                Notiflix.Notify.success('Pagamento gerado!');

                // Payment polling
                const qrcodeValue = data.qrcode;
                const intervalId = setInterval(async () => {
                    try {
                        const resConsult = await fetch('/api/consult_pix.php', {
                            method: 'POST',
                            body: new URLSearchParams({ qrcode: qrcodeValue })
                        });
                        const consultData = await resConsult.json();

                        if (consultData.paid === true) {
                            clearInterval(intervalId);
                            Notiflix.Notify.success('Pagamento aprovado!');
                            setTimeout(() => {
                                window.location.href = '/';
                            }, 2000);
                        }
                    } catch (err) {
                        console.error('Erro no polling', err);
                        clearInterval(intervalId);
                    }
                }, 2000);
            } else {
                Notiflix.Loading.remove();
                Notiflix.Notify.failure(data.message || 'Erro ao gerar QR Code. Tente novamente.');
            }
        } catch (err) {
            Notiflix.Loading.remove();
            console.error(err);
            Notiflix.Notify.failure('Erro na requisição. Verifique sua conexão.');
        }
    });

    // Copy QR Code
    document.getElementById('copyQr')?.addEventListener('click', () => {
        const input = document.getElementById('qrCodeValue');
        input.select();
        document.execCommand('copy');
        Notiflix.Notify.success('Copiado!');
    });

    // Withdraw Form Submission
    document.getElementById('withdrawForm')?.addEventListener('submit', async e => {
        e.preventDefault();

        const formData = new FormData(e.target);
        const amount = parseFloat(formData.get('amount').replace(',', '.'));
        const saqueMin = <?= isset($saqueMin) ? $saqueMin : 50 ?>;

        if (isNaN(amount)) {
            Notiflix.Notify.failure('Por favor, insira um valor válido');
            return;
        }

        if (amount < saqueMin) {
            Notiflix.Notify.failure(`O valor mínimo para saque é R$ ${saqueMin.toFixed(2).replace('.', ',')}`);
            return;
        }

        Notiflix.Loading.standard('Processando saque...');
        const cpf = formData.get('cpf').replace(/\D/g, '');

        try {
            const res = await fetch('/api/withdraw.php', {
                method: 'POST',
                body: JSON.stringify({ amount, cpf }),
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include' // 🔥 mantém a sessão ativa
            });

            const data = await res.json();

            if (data.success) {
                Notiflix.Notify.success(data.message);
                setTimeout(() => {
                    closeWithdrawModal();
                    window.location.reload();
                }, 2000);
            } else {
                Notiflix.Notify.failure(data.message || 'Erro ao processar saque');
            }
        } catch (err) {
            Notiflix.Notify.failure('Erro na conexão com o servidor');
            console.error(err);
        } finally {
            Notiflix.Loading.remove();
        }
    });
</script>
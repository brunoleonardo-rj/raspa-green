<footer class="footer">
    <div class="footer-container">
        <div class="footer-content">
            <div>
                <div class="footer-brand">
                    <?php if ($logoSite && file_exists($_SERVER['DOCUMENT_ROOT'] . $logoSite)): ?>
                        <img src="<?= htmlspecialchars($logoSite) ?>" alt="<?= htmlspecialchars($nomeSite) ?>" class="footer-logo-image">
                    <?php else: ?>
                        <div class="footer-logo-icon">
                            <?= strtoupper(substr($nomeSite, 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="footer-description">
                    © 2025 <?php echo $nomeSite; ?>. Todos os direitos reservados.
                </p>
                <p class="footer-description">
                    Raspadinhas e outros jogos de azar são regulamentados e cobertos pela nossa licença de jogos. Jogue com responsabilidade.
                </p>
            </div>
                         
            <div class="footer-section">
                <h3>Regulamentos</h3>
                <ul class="footer-links">
                    <li><a href="#">Jogo responsável</a></li>
                    <li><a href="#">Política de Privacidade</a></li>
                    <li><a href="#">Termos de Uso</a></li>
                </ul>
            </div>
                         
            <div class="footer-section">
                <h3>Ajuda</h3>
                <ul class="footer-links">
                    <li><a href="#">Perguntas Frequentes</a></li>
                    <li><a href="#">Como Jogar</a></li>
                    <li><a href="#">Suporte Técnico</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<style>
/* Footer Logo Styles */
.footer-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.footer-logo-image {
    height: 40px;
    width: auto;
    max-width: 150px;
    object-fit: contain;
    transition: all 0.3s ease;
}

.footer-logo-image:hover {
    transform: scale(1.02);
}

.footer-logo-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #ffffff;
    font-weight: 800;
    box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
    transition: all 0.3s ease;
}

.footer-logo-icon:hover {
    box-shadow: 0 6px 16px rgba(34, 197, 94, 0.4);
    transform: translateY(-1px);
}

.footer-brand span {
    font-size: 1.25rem;
    font-weight: 700;
    color: white;
}

/* Footer Base Styles */
.footer {
    background: linear-gradient(145deg, #0a0a0a 0%, #1a1a1a 100%);
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    margin-top: 4rem;
}

.footer-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 3rem 2rem 2rem;
}

.footer-content {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 3rem;
    margin-bottom: 2rem;
}

.footer-section h3 {
    color: white;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    border-bottom: 2px solid #22c55e;
    padding-bottom: 0.5rem;
    display: inline-block;
}

.footer-links {
    list-style: none;
    padding: 0;
}

.footer-links li {
    margin-bottom: 0.75rem;
}

.footer-links a {
    color: #9ca3af;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.footer-links a:hover {
    color: #22c55e;
    padding-left: 0.5rem;
}

.footer-description {
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .footer-container {
        padding: 2rem 1rem 1.5rem;
    }
    
    .footer-brand {
        gap: 0.5rem;
    }
    
    .footer-logo-image {
        height: 35px;
    }
    
    .footer-logo-icon {
        width: 35px;
        height: 35px;
        font-size: 1.1rem;
        border-radius: 8px;
    }
    
    .footer-brand span {
        font-size: 1.1rem;
    }
}

@media (max-width: 480px) {
    .footer-brand {
        gap: 0.4rem;
    }
    
    .footer-logo-image {
        height: 32px;
    }
    
    .footer-logo-icon {
        width: 32px;
        height: 32px;
        font-size: 1rem;
        border-radius: 6px;
    }
    
    .footer-brand span {
        font-size: 1rem;
    }
    
    .footer-section h3 {
        font-size: 1rem;
    }
    
    .footer-links a {
        font-size: 0.9rem;
    }
    
    .footer-description {
        font-size: 0.85rem;
    }
}
</style>
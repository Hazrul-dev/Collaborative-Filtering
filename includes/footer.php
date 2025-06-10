</main>
    
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Tentang Kami</h3>
                    <p>Toko Izra Fashion menyediakan hijab berkualitas dengan model terkini dan harga terjangkau.</p>
                    <div class="footer-features">
                        <span class="feature-item">✓ Kualitas Premium</span>
                        <span class="feature-item">✓ Pengiriman Cepat</span>
                        <span class="feature-item">✓ Garansi Kepuasan</span>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Layanan</h3>
                    <ul class="footer-links">
                        <li><a href="#katalog">Katalog Produk</a></li>
                        <li><a href="#size-guide">Panduan Ukuran</a></li>
                        <li><a href="#care">Cara Perawatan</a></li>
                        <li><a href="#return">Kebijakan Return</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Kontak</h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>info@izrafashion.com</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>081234567890</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Medan, Indonesia</span>
                        </div>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Follow Kami</h3>
                    <div class="social-links">
                        <a href="#" class="social-link facebook" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link instagram" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link whatsapp" aria-label="WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="#" class="social-link tiktok" aria-label="TikTok">
                            <i class="fab fa-tiktok"></i>
                        </a>
                    </div>
                    <div class="newsletter">
                        <h4>Newsletter</h4>
                        <form class="newsletter-form">
                            <input type="email" placeholder="Email Anda" required>
                            <button type="submit">Subscribe</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> Toko Izra Fashion. All rights reserved.</p>
                </div>
                <div class="footer-bottom-links">
                    <a href="#privacy">Privacy Policy</a>
                    <a href="#terms">Terms of Service</a>
                    <a href="#sitemap">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="<?php echo isset($isAdminPage) ? '../assets/js/script.js' : 'assets/js/script.js'; ?>"></script>
    <?php if (isset($isAdminPage) && $isAdminPage): ?>
        <script src="../assets/js/admin.js"></script>
    <?php endif; ?>
<style>
    /* Footer Styles */
.footer {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: #ecf0f1;
    padding: 40px 0 20px;
    margin-top: 60px;
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
}

.footer-section h3 {
    color: #e74c3c;
    margin-bottom: 15px;
    font-size: 18px;
    font-weight: 600;
}

.footer-section p {
    color: #bdc3c7;
    line-height: 1.6;
    margin-bottom: 15px;
}

/* Footer Features */
.footer-features {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.feature-item {
    color: #27ae60;
    font-size: 14px;
    font-weight: 500;
}

/* Footer Links */
.footer-links {
    list-style: none;
    padding: 0;
}

.footer-links li {
    margin-bottom: 8px;
}

.footer-links a {
    color: #bdc3c7;
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-links a:hover {
    color: #e74c3c;
}

/* Contact Info */
.contact-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #bdc3c7;
}

.contact-item i {
    color: #e74c3c;
    width: 16px;
}

/* Social Links */
.social-links {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}

.social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    color: #ecf0f1;
    text-decoration: none;
    transition: all 0.3s ease;
}

.social-link:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

.social-link.facebook:hover { background: #3b5998; }
.social-link.instagram:hover { background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); }
.social-link.whatsapp:hover { background: #25d366; }
.social-link.tiktok:hover { background: #000; }

/* Newsletter */
.newsletter h4 {
    color: #ecf0f1;
    margin-bottom: 10px;
    font-size: 16px;
}

.newsletter-form {
    display: flex;
    gap: 8px;
}

.newsletter-form input {
    flex: 1;
    padding: 8px 12px;
    border: none;
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.1);
    color: #ecf0f1;
    font-size: 14px;
}

.newsletter-form input::placeholder {
    color: #95a5a6;
}

.newsletter-form button {
    padding: 8px 16px;
    background: #e74c3c;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s ease;
}

.newsletter-form button:hover {
    background: #c0392b;
}

/* Footer Bottom */
.footer-bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.copyright p {
    color: #95a5a6;
    font-size: 14px;
    margin: 0;
}

.footer-bottom-links {
    display: flex;
    gap: 20px;
}

.footer-bottom-links a {
    color: #95a5a6;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s ease;
}

.footer-bottom-links a:hover {
    color: #e74c3c;
}

/* Responsive */
@media (max-width: 768px) {
    .footer {
        padding: 30px 0 15px;
    }
    
    .footer-content {
        grid-template-columns: 1fr;
        gap: 25px;
    }
    
    .footer-bottom {
        flex-direction: column;
        text-align: center;
    }
    
    .newsletter-form {
        flex-direction: column;
    }
    
    .newsletter-form input,
    .newsletter-form button {
        width: 100%;
    }
}
    </style>
<script src="/assets/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>
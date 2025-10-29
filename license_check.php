<?php
// license_check.php
// Lisans kontrol sistemi - Dev Efkwn

class LicenseValidator {
    private $licenseFileUrl = "https://raw.githubusercontent.com/kullanici_adi/depo_adi/main/license_list.txt";
    private $allowedDomains = [];
    private $licenseKey = "sofex.tr";
    private $cacheTime = 3600; // 1 saat cache
    private $cacheFile = "license_cache.json";

    public function __construct() {
        $this->loadLicenseData();
    }

    private function loadLicenseData() {
        // Cache kontrolü
        if ($this->isCacheValid()) {
            $this->loadFromCache();
            return;
        }

        // GitHub'dan lisans listesini çek
        $this->fetchLicenseData();
    }

    private function isCacheValid() {
        if (!file_exists($this->cacheFile)) {
            return false;
        }

        $cacheData = json_decode(file_get_contents($this->cacheFile), true);
        if (!$cacheData || !isset($cacheData['timestamp'])) {
            return false;
        }

        return (time() - $cacheData['timestamp']) < $this->cacheTime;
    }

    private function loadFromCache() {
        $cacheData = json_decode(file_get_contents($this->cacheFile), true);
        if ($cacheData && isset($cacheData['domains'])) {
            $this->allowedDomains = $cacheData['domains'];
        }
    }

    private function fetchLicenseData() {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
            ]
        ]);

        $licenseData = @file_get_contents($this->licenseFileUrl, false, $context);
        
        if ($licenseData === false) {
            // GitHub'a ulaşılamazsa cache'den yükle
            if (file_exists($this->cacheFile)) {
                $this->loadFromCache();
                return;
            }
            // Cache de yoksa geçici olarak izin ver
            $this->allowedDomains = [$this->getCurrentDomain()];
            return;
        }

        $this->parseLicenseData($licenseData);
        $this->saveToCache();
    }

    private function parseLicenseData($licenseData) {
        $lines = explode("\n", trim($licenseData));
        $this->allowedDomains = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue; // Yorum satırlarını atla
            }

            $parts = explode('|', $line);
            if (count($parts) >= 2) {
                $domain = trim($parts[0]);
                $status = trim($parts[1]);
                
                if ($status === 'yes') {
                    $this->allowedDomains[] = $domain;
                }
            }
        }
    }

    private function saveToCache() {
        $cacheData = [
            'domains' => $this->allowedDomains,
            'timestamp' => time()
        ];
        file_put_contents($this->cacheFile, json_encode($cacheData));
    }

    private function getCurrentDomain() {
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // www'yu kaldır
        $domain = str_replace('www.', '', $domain);
        return $domain;
    }

    public function validateLicense() {
        $currentDomain = $this->getCurrentDomain();
        
        // Localhost için her zaman izin ver
        if ($currentDomain === 'localhost' || $currentDomain === '127.0.0.1') {
            return true;
        }

        // Lisanslı domainleri kontrol et
        foreach ($this->allowedDomains as $allowedDomain) {
            if ($this->domainMatches($currentDomain, $allowedDomain)) {
                return true;
            }
        }

        return false;
    }

    private function domainMatches($currentDomain, $allowedDomain) {
        // Tam eşleşme
        if ($currentDomain === $allowedDomain) {
            return true;
        }

        // Alt domain kontrolü (örnek.com için www.örnek.com, blog.örnek.com vb.)
        if (strpos($allowedDomain, '.') !== false) {
            $pattern = '/^(.+\.)?' . preg_quote($allowedDomain, '/') . '$/';
            return preg_match($pattern, $currentDomain) === 1;
        }

        return false;
    }

    public function showLicenseError() {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="tr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Lisans Hatası - Dev Efkwn</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Arial', sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                }
                
                .error-container {
                    background: rgba(255, 255, 255, 0.1);
                    backdrop-filter: blur(10px);
                    padding: 3rem;
                    border-radius: 20px;
                    text-align: center;
                    max-width: 500px;
                    width: 90%;
                    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
                    border: 1px solid rgba(255, 255, 255, 0.2);
                }
                
                .error-icon {
                    font-size: 4rem;
                    margin-bottom: 1rem;
                    color: #ff6b6b;
                }
                
                h1 {
                    font-size: 2rem;
                    margin-bottom: 1rem;
                    color: white;
                }
                
                p {
                    font-size: 1.1rem;
                    margin-bottom: 2rem;
                    line-height: 1.6;
                    opacity: 0.9;
                }
                
                .contact-info {
                    background: rgba(255, 255, 255, 0.2);
                    padding: 1.5rem;
                    border-radius: 10px;
                    margin-top: 2rem;
                }
                
                .contact-info h3 {
                    margin-bottom: 1rem;
                    font-size: 1.2rem;
                }
                
                .btn {
                    display: inline-block;
                    background: #4361ee;
                    color: white;
                    padding: 12px 30px;
                    border-radius: 50px;
                    text-decoration: none;
                    font-weight: bold;
                    transition: all 0.3s ease;
                    border: none;
                    cursor: pointer;
                    margin: 0.5rem;
                }
                
                .btn:hover {
                    background: #3a0ca3;
                    transform: translateY(-2px);
                }
                
                .domain {
                    font-weight: bold;
                    color: #4cc9f0;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <h1>🚫 Lisans Hatası</h1>
                <p>
                    Bu domain (<span class="domain"><?php echo htmlspecialchars($this->getCurrentDomain()); ?></span>) için lisans bulunamadı.
                </p>
                <p>
                    Web sitesi lisanslı domainler dışında kullanılamaz. 
                    Lisans satın almak veya mevcut lisansınızı güncellemek için bizimle iletişime geçin.
                </p>
                
                <div class="contact-info">
                    <h3>📞 İletişim Bilgileri</h3>
                    <p>Email: tefkan3@yahoo.com</p>
                    <p>Telefon: +90 (555) 123 45 67</p>
                </div>
                
                <div style="margin-top: 2rem;">
                    <a href="mailto:tefkan3@yahoo.com" class="btn">
                        <i class="fas fa-envelope"></i> E-posta Gönder
                    </a>
                    <a href="https://wa.me/905551234567" class="btn" target="_blank">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>
            
            <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
        </body>
        </html>
        <?php
        exit;
    }
}

// Lisans kontrolünü başlat
$licenseValidator = new LicenseValidator();

if (!$licenseValidator->validateLicense()) {
    $licenseValidator->showLicenseError();
}
?>

# DODO AI SEO - WordPress AI Blog Oluşturucu

WordPress için AI destekli, Rank Math uyumlu SEO blog yazısı oluşturma eklentisi.

## Version 2.0.0 - Production Ready 🚀

DODO AI SEO artık production ortamlarında güvenle kullanılabilir, enterprise-grade bir WordPress eklentisidir.

## 🚀 Özellikler

### 🤖 AI İçerik Üretimi
- OpenAI GPT-4 ve GPT-3.5 desteği
- Akıllı model seçimi (maliyet optimizasyonu)
- Token ve maliyet tahmini
- Çoklu içerik türü desteği
- Rank Math SEO entegrasyonu
- Minimum 3000 kelime içerik üretimi

### 📊 İçerik Analizi & Intelligence
- 5 boyutlu sağlık skoru (SEO, Kalite, Okunabilirlik, Semantik, AI Risk)
- Güven analizi ve risk değerlendirmesi
- Akıllı öneri motoru
- Semantik kalite analizi
- Editorial stil tespiti
- İçerik derinlik analizi
- AI tespit risk analizi

### ✨ İçerik İyileştirme
- Bölüm bazlı inline düzenleme
- Gerçek zamanlı diff önizleme
- AI düşünce süreci görselleştirmesi
- Revizyon geçmişi ve karşılaştırma
- Tek tıkla geri alma

### 🔗 Semantik Link Motoru
- Akıllı iç link önerileri
- Yetim sayfa tespiti
- Otorite akışı analizi
- Bağlam bazlı link yerleştirme
- WooCommerce ürün entegrasyonu

### 📈 Analytics & Raporlama
- İçerik sağlığı dashboard'u
- Skor geçmişi takibi
- Trend analizi
- Kullanım raporları
- Maliyet takibi

### 🎯 Keyword Opportunities
- AI destekli keyword fırsatları
- Intent analizi
- Benzer içerik kontrolü
- Otomatik yayınlama

### 📅 Yayınlama Zamanlayıcı
- Toplu içerik yayınlama
- Zamanlanmış yayın
- Kaçırılan zamanlama düzeltme
- Kuyruk yönetimi

### 🔍 İçerik Audit
- Toplu içerik analizi
- Sağlık skoru hesaplama
- İyileştirme önerileri
- Önceliklendirme

### 🌐 GEO Scoring
- Coğrafi hedefleme analizi
- Lokasyon bazlı optimizasyon
- Yerel SEO desteği

### 🎨 Premium UI/UX
- Modern SaaS tasarımı (Linear, Notion, Grammarly tarzı)
- Inline düzenleme deneyimi
- Klavye kısayolları
- Skeleton loading
- Toast bildirimleri
- Micro interactions

## 🆕 Yeni (v2.0.0) - Production Hardening

### 🛡️ Ecosystem Compatibility
- PHP 8.0+ (8.3 önerilir)
- WordPress 5.8+ (6.4 önerilir)
- Rank Math SEO uyumluluğu
- WooCommerce uyumluluğu
- Elementor uyumluluğu
- Gutenberg uyumluluğu
- Classic Editor uyumluluğu
- LiteSpeed Cache uyumluluğu
- Multisite desteği

### 🗄️ Database & Migration System
- Versiyonlu şema migrasyonları (v1 → v4)
- Otomatik tablo onarımı
- Safe upgrade hooks
- Rollback-safe migration logic

### ⚙️ Background Job Queue
- Arka plan işleme sistemi
- Retry mekanizması (max 3 attempts)
- Progress tracking (0-100%)
- 5 job tipi: blog_generation, analytics_snapshot, cluster_analysis, content_audit, bulk_improvement
- Hourly cron processor
- Auto cleanup (7 days retention)

### 💰 AI Cost & Token Optimization
- Token estimation ve cost calculation
- Prompt compression
- Cache-first strategy
- Model selection rules (GPT-3.5 vs GPT-4)
- Content chunking (max 3000 tokens)
- Usage statistics (today/week/month)
- Budget checking with daily limits

### 📦 Bulk Processing System
- Memory-safe chunk processing (5000 words per chunk)
- Batch processing (10 posts per batch)
- Memory monitoring (stops at 80% limit)
- Timeout protection
- Paginated queries

### 🚨 Error Handling & Recovery
- Global error handler
- User-friendly error messages
- 10 recovery actions
- Auto-heal capabilities
- Error logging with context
- Recent errors tracking

### 📝 Structured Logging
- 5 log levels: debug, info, warning, error, critical
- Module-based logging
- Filter by level/module/date
- Export logs (JSON/CSV)
- Production-safe (no debug logs when WP_DEBUG is false)

### 🔒 Security Hardening
- Nonce verification
- Capability checks
- Input sanitization (9 types)
- Output escaping (5 types)
- SQL injection protection
- File upload validation
- Rate limiting
- Security audit system

### 🏥 System Health Check
- OpenAI API connection test
- Database tables check
- WP Cron status check
- Memory limit check
- PHP version check
- Required extensions check
- Cache status check
- Plugin conflicts detection
- Overall health status: healthy/warning/critical

## 📋 Gereksinimler

- **WordPress:** 5.8 veya üzeri (6.4 önerilir)
- **PHP:** 8.0 veya üzeri (8.3 önerilir)
- **PHP Extensions:** curl, json, mbstring
- **Memory Limit:** 128MB önerilir
- **OpenAI API Key:** Gerekli
- **Rank Math SEO:** Önerilir (opsiyonel)

## 🔧 Kurulum

1. Eklenti dosyalarını `/wp-content/plugins/dodo-ai-seo/` dizinine yükleyin
2. WordPress admin panelinden eklentiyi aktifleştirin
3. **DODO AI SEO > Ayarlar** sayfasından OpenAI API anahtarınızı girin
4. **DODO AI SEO > Health Check** sayfasından sistem sağlığını kontrol edin
5. **DODO AI SEO > Yeni Blog Oluştur** sayfasından ilk blog yazınızı oluşturun

## 🔄 Güncelleme (v1.x → v2.0.0)

1. **Yedek alın** (veritabanı + dosyalar)
2. Eklentiyi güncelleyin
3. Veritabanı migrasyonları otomatik çalışacaktır
4. Sistem sağlığı kontrolünü çalıştırın
5. Tüm verileriniz korunur

## 🎯 Kullanım

### 1. Ayarları Yapılandırın
- OpenAI API anahtarınızı girin
- Varsayılan ton ve uzunluk ayarlarını belirleyin
- İç link sayısı limitlerini ayarlayın
- Maliyet limitlerini belirleyin

### 2. Sistem Sağlığını Kontrol Edin
1. **DODO AI SEO > Health Check** sayfasına gidin
2. Tüm kontrollerin ✅ olduğundan emin olun
3. ⚠️ uyarıları giderin
4. ❌ kritik hataları düzeltin

### 3. Blog Oluşturun
1. **Odak Anahtar Kelime:** Ana anahtar kelimenizi girin
2. **Ton:** İçerik tonunu seçin
3. **Uzunluk:** İçerik uzunluğunu belirleyin
4. **İçerik Türü:** Blog, Nasıl Yapılır, Karşılaştırma, vb.
5. **Blog Oluştur** butonuna tıklayın

### 4. İçerik İyileştirin
1. **DODO AI SEO > Content Improver** sayfasına gidin
2. Bir yazı seçin veya içerik yapıştırın
3. Odak keyword girin
4. **Analiz Et** butonuna tıklayın
5. Önerileri inceleyin ve uygulayın

### 5. Keyword Opportunities
1. **DODO AI SEO > Keyword Opportunities** sayfasına gidin
2. **Fırsat Üret** butonuna tıklayın
3. AI keyword fırsatları analiz edecektir
4. Fırsatları onaylayın
5. Toplu içerik üretimi başlatın

### 6. Analytics İzleyin
1. **DODO AI SEO > Analytics** sayfasına gidin
2. İçerik sağlığı metriklerini görüntüleyin
3. Trend grafiklerini inceleyin
4. İyileştirme gereken içerikleri belirleyin

## ✅ Uyumluluk

### Test Edildi
- ✅ WordPress 6.4
- ✅ PHP 8.3
- ✅ Rank Math SEO 1.0.x
- ✅ WooCommerce 8.x
- ✅ Elementor 3.x
- ✅ Gutenberg (WordPress 6.4)
- ✅ Classic Editor
- ✅ LiteSpeed Cache
- ✅ Multisite

### Bilinen Çakışmalar
- ⚠️ WP Rocket (cache temizleme gerekebilir)
- ⚠️ Autoptimize (JS optimizasyonu devre dışı bırakılmalı)

## 🔒 Güvenlik

- Tüm AJAX endpoint'leri nonce ile korunur
- Capability checks her işlemde yapılır
- Input sanitization ve output escaping
- SQL injection koruması (prepared statements)
- Rate limiting desteği
- File upload validation
- XSS ve CSRF koruması

## ⚡ Performans

- Cache-first strateji (1 saat transient cache)
- Memory-safe bulk processing
- Timeout koruması
- Paginated queries
- Asset versioning (cache busting)
- Lazy loading
- Background job processing

## 📚 Dokümantasyon

- **Kurulum:** [INSTALLATION.md](INSTALLATION.md)
- **Capability Mapping:** [CAPABILITIES.md](CAPABILITIES.md)
- **Changelog:** [CHANGELOG.md](CHANGELOG.md)
- **Sistem Gereksinimleri:** PHP 8.0+, WordPress 5.8+

## 🗺️ Roadmap

### v2.1.0 (Planned)
- Settings & Onboarding V2
- Regression test suite
- Multi-language support
- Custom capability system

### v2.2.0 (Planned)
- Team collaboration features
- Approval workflows
- Role-based cost limits
- Advanced analytics

## 📄 Lisans

GPL v2 or later

## 👨‍💻 Yazar

DODO AI - https://dodoai.com

## 🙏 Credits

- OpenAI GPT-4 & GPT-3.5
- Rank Math SEO
- Chart.js
- WordPress Core Team
2. **Yazı Konusu:** Detaylı konu açıklaması yazın
3. **Uzunluk:** Kısa, Orta veya Uzun seçin
4. **Ton:** Yazı tonunu belirleyin
5. **Kategori:** İlgili kategoriyi seçin
6. **Yayın Durumu:** Taslak veya Yayınla

### 3. İçerik Üretimi
- AI otomatik olarak alakalı iç linkleri bulur
- SEO uyumlu içerik üretir
- Rank Math meta verilerini doldurur
- Post'u WordPress'e kaydeder

## 🏗️ Mimari

### Dosya Yapısı
```
dodo-ai-seo/
├── dodo-ai-seo.php              # Ana eklenti dosyası
├── uninstall.php                # Kaldırma scripti
├── README.md                    # Dokümantasyon
│
├── includes/                    # Core sınıflar
│   ├── class-dodo-core.php
│   ├── class-dodo-admin.php
│   ├── class-dodo-settings.php
│   ├── class-dodo-generator.php
│   ├── class-dodo-openai.php
│   ├── class-dodo-internal-links.php
│   ├── class-dodo-rankmath.php
│   ├── class-dodo-post-creator.php
│   ├── class-dodo-content-analyzer.php
│   └── class-dodo-prompt-builder.php
│
├── admin/                       # Admin panel
│   └── views/
│       ├── page-new-blog.php
│       └── page-settings.php
│
└── assets/                      # CSS/JS
    ├── css/admin.css
    └── js/admin.js
```

### Sınıf Sorumlulukları

**DODO_Core:** Eklenti başlatma ve bağımlılık yönetimi
**DODO_Admin:** Admin panel ve menü yönetimi
**DODO_Settings:** Ayarlar ve konfigürasyon
**DODO_Generator:** Ana orkestratör - tüm süreci yönetir
**DODO_OpenAI:** OpenAI API entegrasyonu
**DODO_Internal_Links:** Akıllı iç link seçimi
**DODO_Content_Analyzer:** İçerik benzerlik analizi
**DODO_Prompt_Builder:** AI prompt oluşturma
**DODO_RankMath:** Rank Math SEO entegrasyonu
**DODO_Post_Creator:** WordPress post oluşturma

## 🔄 İş Akışı

1. Kullanıcı formu doldurur
2. Generator parametreleri validate eder
3. Internal Links alakalı içerikleri bulur
4. Content Analyzer benzerlik skorları hesaplar
5. Prompt Builder AI promptunu oluşturur
6. OpenAI içeriği üretir
7. Post Creator WordPress post'u oluşturur
8. RankMath SEO meta verilerini doldurur
9. Kullanıcıya sonuç gösterilir

## 🎨 İç Link Algoritması

```
Skor = (Başlık Benzerliği × 0.4) + 
       (Kategori Eşleşmesi × 0.3) + 
       (Anahtar Kelime Eşleşmesi × 0.3)
```

- En yüksek skorlu 3-5 ürün
- En yüksek skorlu 3-5 blog yazısı
- İlgili kategoriler
- Minimum skor eşiği: 25-30

## 🔐 Güvenlik

- Nonce kontrolü her formda
- API key şifreleme
- Capability kontrolü (manage_options)
- Input sanitization
- Output escaping
- SQL injection koruması

## 📊 SEO Kuralları

### Otomatik Doldurulur:
- SEO Title (50-60 karakter)
- Meta Description (150-160 karakter)
- Focus Keyword
- URL Slug
- Excerpt

### İçerik Yapısı:
- Minimum 3000 kelime
- H2/H3 başlık hiyerarşisi
- SSS bölümü (5+ soru)
- 3 CTA bölümü
- Doğal anahtar kelime kullanımı
- Semantik SEO kelimeleri

## 🚧 Gelecek Özellikler (V2+)

- [ ] Görsel üretimi (DALL-E, Midjourney)
- [ ] WebP optimizasyonu
- [ ] Alt text otomasyonu
- [ ] Featured image seçimi
- [ ] Google görsel çekme
- [ ] Toplu içerik üretimi
- [ ] Eski yazı güncelleme
- [ ] Search Console entegrasyonu
- [ ] Semrush entegrasyonu
- [ ] A/B test desteği
- [ ] İçerik takvimi

## 🐛 Hata Ayıklama

### API Hatası
- OpenAI API anahtarınızı kontrol edin
- API limitinizi kontrol edin
- İnternet bağlantınızı kontrol edin

### İçerik Üretilmiyor
- PHP 8.0+ kullandığınızdan emin olun
- WordPress debug modunu açın
- Error log'ları kontrol edin

### Rank Math Meta Verileri Doldurulmuyor
- Rank Math eklentisinin aktif olduğundan emin olun
- Rank Math versiyonunu güncelleyin

## 📝 Changelog

### Version 1.0.0 (2024)
- İlk sürüm
- OpenAI GPT-4o entegrasyonu
- Rank Math uyumluluğu
- Akıllı iç link sistemi
- WooCommerce entegrasyonu
- SEO uyumlu içerik üretimi

## 👨‍💻 Geliştirici

**DODO AI**
- Website: https://dodoai.com
- Support: support@dodoai.com

## 📄 Lisans

GPL v2 or later

## 🙏 Teşekkürler

- OpenAI API
- Rank Math SEO
- WordPress Community

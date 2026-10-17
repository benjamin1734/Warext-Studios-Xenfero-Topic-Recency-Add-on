## 1.2.4 - 2026-10-17

- Corrected XenForo minimum version wording; version checks no longer depend on hard-coded release numbers.
- Removed obsolete tracked build/release staging files (current packages are generated from upload/).

## 1.2.3 - 2026-09-27

### English
- Removed the remaining hard-coded Turkish labels from the ACP dashboard, forum selector, voting details and public validation errors.
- Added matching English and Turkish phrases for every newly localized string.
- Added regression checks that fail when localized UI text is hard-coded again.
- Added language-pack coverage validation so every base add-on phrase must exist in both English and Turkish XML packages.
- Fixed stale release metadata checks that still expected version 1.1.0.

### Türkçe
- ACP dashboard, forum seçimi, oy ayrıntıları ve public doğrulama hatalarında kalan sabit Türkçe metinler phrase sistemine taşındı.
- Yeni phrase anahtarlarının Türkçe ve İngilizce karşılıkları eklendi.
- Arayüze yeniden hard-coded yerelleştirilmiş metin eklenmesini engelleyen regresyon kontrolleri eklendi.
- Ana eklenti phrase listesindeki her anahtarın hem Turkish.xml hem English.xml içinde bulunmasını doğrulayan dil paketi testi eklendi.
- Hâlâ 1.1.0 sürümünü bekleyen eski release metadata testleri düzeltildi.

## 1.2.2 - 2026-09-27

- Completed phrase coverage for forum settings, filters, thread badges, vote panel, owner/moderator actions and detail UI.
- Updated release regression tests to validate phrase-backed UI rather than Turkish literals.
- Turkish and English language packs remain separate release assets.

## 1.2.1 - 2026-09-27

- Fixed CI/template validation so mixed historical and new phrase metadata are accepted.
- Keeps the Turkish/English language packs and phrase-backed interface from 1.2.0.
- Triggers a fresh direct-install release with both language XML assets.

## 1.2.0 - 2026-09-26

- Added native XenForo Turkish/English phrase-based language support.
- Moved remaining user-facing Thread Freshness template labels, buttons, status names and descriptions to phrases.
- Added importable `languages/Turkish.xml` and `languages/English.xml` packages.
- Language switching now follows the visitor's normal XenForo language selection.
- Added a no-hard-coded-UI-text development rule for future changes.

# Değişiklik Günlüğü

## 1.1.0 Stable - Yandan Açılan Oy Ayrıntıları

- Oy ayrıntıları artık masaüstünde ana güncellik kartını aşağı doğru büyütmek yerine kartın solunda ayrı bir panel olarak açılır.
- Yan panel sürüm, neden, açıklama ve güncel konu ID alanlarını aynı form içinde tutar; oy kaydetme/güncelleme davranışı değişmez.
- 900px ve altındaki ekranlarda ayrıntılar ortalanmış, arka planı karartan responsive bir sheet/modal görünümünde açılır.
- Mobil ayrıntı paneline kapatma düğmesi, arka plana tıklayarak kapatma ve Escape ile kapatma desteği eklendi.
- Masaüstü widget genişliği JavaScript tarafında da 340px ile eşitlendi.

## 1.0.10 Stable - Kompakt Oylama Alanı

- Oylama alanındaki açıklama başlığı ve yardımcı metinler kaldırıldı.
- Çalıştı / Çalışmadı seçimleri geniş kartlar yerine küçük XenForo uyumlu seçim butonlarına dönüştürüldü.
- Seçili butonun dolu renk durumu korunur; oy yalnızca Kaydet/Güncelle butonuyla işlenir.
- Kaydet/Güncelle butonu seçimlerin yanına alınarak panel yüksekliği belirgin şekilde azaltıldı.
- Oy ayrıntısı bağlantısı kısaltıldı ve isteğe bağlı alanlar kapalı kalmaya devam eder.
- Masaüstü kart genişliği 340px'e düşürüldü; mobil görünüm kompakt şekilde korunur.

## 1.0.9 Stable - Oylama Kutularının Görünürlük Düzeltmesi

- Konu sahibi doğrulamayı kaldırdıktan sonra Çalıştı / Çalışmadı seçim kutuları artık kesin olarak görünür.
- Konu sahibinin normal topluluk oylamasına geçişi artık oy izni, minimum mesaj ve hesap yaşı koşullarına takılmaz; konu sahibi doğrulaması ile topluluk oyu aynı anda kullanılamaz.
- Diğer kullanıcılar için oy izni, minimum mesaj ve minimum hesap yaşı korumaları aynen devam eder.
- Oy değiştirme ve Oyunu güncelle akışı korunur.

## 1.0.8 Stable - Oy Güncelleme ve Sahip Akışı

- Konu sahibi doğrulamasını kaldırdıktan sonra konu sahibi normal topluluk oylamasına katılabilir.
- Mevcut oylar sonradan değiştirilebilir; Çalıştı seçimi ileride Çalışmadı olarak veya tersi yönde güncellenebilir.
- Mevcut oy olduğunda ana buton “Oyunu güncelle” olarak görünür.
- Konu sahibi doğrulamasını kaldırma işlemi tam sayfa yenilemeyle sonuçlanır; rozet ve oy kutuları anında doğru duruma geçer.
- Masaüstünde güncellik kartının konu başlığıyla aynı satırdaki yerleşimi korunur; mobilde başlığın altına iner.
- ACP navigasyon ikonları XenForo ile daha uyumlu Font Awesome adlarıyla güncellendi.

## 1.0.7 Stable - Başlık Hizası ve Oy Değiştirme

- Masaüstünde konu güncellik paneli konu başlığıyla aynı satırda sağ tarafa hizalandı.
- Mobilde panel başlığın hemen altında tam genişlikte kalacak şekilde responsive yerleşim güncellendi.
- Kullanıcıların aynı doğrulama döngüsünde kendi oylarını daha sonra değiştirebilmesi sağlandı.
- ACP ana başlık ve alt menü ikonları XenForo çekirdeğinde bulunan uyumlu Font Awesome ikonlarıyla değiştirildi.

## 1.0.6 Stable - Oylama Arayüzü Yenilemesi

- Çalıştı / Çalışmadı aksiyonları seçilebilir, seçildiğinde içi dolan iki seçenekli kontrol yapısına geçirildi.
- Oyun gönderilmesi için ayrı bir “Oyu kaydet” butonu eklendi.
- Bilgi, İşlemler ve Oy ayrıntısı kontrolleri XenForo buton görünümüne geçirildi.
- Widget masaüstünde kompakt, mobilde tam genişlik olacak şekilde sağlamlaştırıldı.
- ACP alt menülerine Genel Bakış, Forum/Kategori Ayarları ve Ayarlar ikonları eklendi.

## 1.0.5 Stable - Belirgin Kontroller

- Yardım ve işlem alanları “Bilgi” ve “İşlemler” etiketli gerçek buton görünümüne taşındı.
- Tarayıcının varsayılan details ok işaretleri tamamen kaldırıldı.
- Durum, oy sayıları ve konu sahibi doğrulaması okunabilir rozet/chip yapısına geçirildi.
- Oy butonlarının yükseklik, boşluk, köşe, hover ve gölge durumları belirginleştirildi.
- Kart genişliği sabit genişlik + max-width modeliyle güvenilir biçimde kompakt tutuldu.
- Mobil dokunma hedefleri büyütüldü.

## 1.0.4 Stable - Mikro Oylama Kartı

- Masaüstü güncellik kartı 310px genişliğinde mikro karta indirildi.
- Varsayılan görünüm yalnızca durum, çok kısa oy özeti ve gerekiyorsa iki oy butonunu gösteriyor.
- Oy kullanamayan üyelerde gereksiz “şartları karşılamıyorsunuz” satırı kaldırıldı.
- Konu sahibi doğrulaması küçük “Sahibi doğruladı” durumuna dönüştürüldü; kaldırma işlemi üç nokta menüsüne taşındı.
- Bilgi metni soru işareti açılır penceresinde tutuldu.
- Moderatör, güncel konu ve konu sahibi işlemleri ayrı bir büyük Yönetim satırı yerine üç nokta açılır menüsüne taşındı.
- Sonuç ayrıntıları yalnızca gerçekten neden/sürüm sonucu bulunduğunda görünür.
- Mobil görünüm konu başlığının altında tam genişlikte, düşük yükseklikte devam eder.

## 1.0.3 Stable - Yan Oylama Paneli

- Konu altındaki ortalanmış geniş güncellik kartı kaldırıldı; panel konu başlığı alanının sağında küçük bir oylama kartı olarak konumlandırıldı.
- Mobil görünümde aynı kart başlığın hemen altına tam genişlikte taşınıyor.
- “Çalıştı / Çalışmadı” oylaması iki doğrudan aksiyona indirildi; sürüm, neden, açıklama ve alternatif konu alanları isteğe bağlı ayrıntı bölümüne alındı.
- Soru işareti üzerinden açılan kısa bilgilendirme alanı eklendi.
- Güncel çözüm yönlendirmesi, moderatör doğrulaması ve yönetici yönlendirme araçları ayrı büyük paneller yerine aynı küçük kartın yönetim bölümüne taşındı.
- Sonuç ve sürüm ayrıntıları varsayılan olarak kapalı tutularak konu görünümündeki görsel yük azaltıldı.

## 1.0.2 Stable - Arayüz İyileştirmesi

- Konu güncellik alanı geniş mavi başlıklı panel görünümünden çıkarılarak 760px maksimum genişlikte kompakt karta dönüştürüldü.
- Başlık, durum rozeti ve konu sahibi doğrulama aksiyonu tek satırda/toplu alanda gösteriliyor.
- Oy istatistikleri yatay ve daha az yer kaplayan özet biçimine geçirildi.
- Ayrıntılı sürüm ve hata nedenleri gerektiğinde açılan “Doğrulama ayrıntıları” alanına taşındı.
- Güncel çözüm yönlendirme alanı büyük form görünümünden çıkarıldı; konu ID + Kaydet işlemi tek satırlık kompakt kontrole dönüştürüldü.
- Ön doğrulama görünümü de aynı kompakt tasarıma uyarlandı.

## 1.0.1 Stable - Hata Düzeltme

- Konu sahibi “hâlâ geçerli” işlemindeki `ThreadFreshness::$app` tanımsız property hatası giderildi.
- ACP forum düzenleme kaydı tek seferlik input filtreleme + `FormAction::basicEntitySave()` akışına taşındı.
- ACP kategori/forum toplu kaydında gereksiz tüm tablo güncellemesi kaldırıldı; yalnızca değişen forumlar yazılıyor ve hata halinde transaction rollback ediliyor.
- Konu sahibi doğrulama aksiyonundaki geniş submit alanı normal boyutlu ve daha anlaşılır bir butona dönüştürüldü.
- Eklenti sürümü `1.0.1` / `1010170` olarak yükseltildi.
- Paket üretimi sürüm bilgisini `addon.json` üzerinden dinamik okuyacak hale getirildi.
- Bu hatalar için kalıcı regresyon testi eklendi.

## 1.0.0 Stable - Nihai

- Forum bazlı `Anlamlı güncelleme` ve `Her son mesaj` yaş hesaplama modları tamamlandı.
- Anlamlı güncelleme hesabı konu sahibinin mesajları, ilk mesaj düzenlemeleri ve seçilmiş çözüm gönderisiyle uyumlu hale getirildi.
- Yeni doğrulama döngüsünde eski oyların, eski moderatör override durumlarının ve eski form verilerinin taşınması engellendi.
- Durum motoru ham oy sayısı + zaman ağırlıklı yüzde kullanacak şekilde düzeltildi ve eşikler ACP üzerinden yönetilebilir hale getirildi.
- Kendi konusuna oy, oy değiştirme ve moderatör doğrulama izinleri tam permission kontrolüne bağlandı.
- Olumsuz oy nedenleri whitelist doğrulamasına alındı; alternatif güncel konu önerisi ve neden dağılımı eklendi.
- Konu sahibi için topluluk sonucunu ezmeyen “hâlâ geçerli” bildirimi eklendi.
- Moderatör override, forum filtreleri, doğrulanmamış konu araması ve stale state senaryolarındaki tutarsızlıklar giderildi.
- Güncel Çözümler araması, durum rozetleri ve forum filtreleri referans tarihleriyle tutarlı hale getirildi.
- ACP içerik sağlığı ekranı etkin forumlar, mevcut doğrulama döngüsü ve son olumsuz geri bildirimlerle sınırlandırıldı.
- Yeniden hesaplama cron'u benzersiz job anahtarıyla kuyruğa alınarak üst üste job oluşması engellendi.
- Orphan kullanıcı/oy temizliği, stale aggregate state işaretleme ve gerekli DB indeksleri eklendi.
- Genel konu listelerinde N+1 sorgu riski bulk preload kontrolüyle kaldırıldı.
- Güncel Çözümler arama girdisi 100 karakterle sınırlandırıldı.
- XenForo 2.3.7+ template method kısıtlamaları için `get/is/has/can` sözleşmesine geçildi.
- Stable kaynakta eski `_output` geliştirme verileri kaldırıldı; `_data` XML ve `hashes.json` tek release kaynağı haline getirildi.
- PHP 8.4 lint, güvenlik, regresyon, release-data, release-static, XML, hash ve ZIP bütünlük kontrolleri CI'a bağlandı.
- Manuel SQL gerektirmeyen kurulum/yükseltme/kaldırma şema akışı `Setup.php` üzerinden doğrulandı.

> Not: Nihai ürün kullanıcıya **1.0.0** olarak sunulur. Denetim sürecinde kullanılan dahili XenForo `version_id` değeri geriye çekilmemiştir; bu, daha önce test paketi kurmuş sistemlerde downgrade davranışını önlemek içindir.

## 1.0.0 Alpha 6

- Kritik durum değişikliği bildirimleri, web push, yeniden hesaplama job'u ve orphan cleanup tamamlandı.

## 1.0.0 Alpha 5

- Konu listesi rozetleri, bulk preload, durum filtresi ve moderatör doğrulama sistemi tamamlandı.

## 1.0.0 Alpha 4

- Forum bazlı etkinleştirme, bekleme süresi, sürüm listesi ve yeniden doğrulama döngüsü tamamlandı.

## 1.0.0 Alpha 3

- Konu doğrulama paneli, oy endpoint'i, permission kontrolleri, neden ve sürüm alanları tamamlandı.

## 1.0.0 Alpha 2

- XenForo 2.3 uyumluluk, oy ağırlığı ve PHP 8.4 otomatik testleri tamamlandı.

## 1.0.0 Alpha 1

- İlk eklenti iskeleti, veritabanı tabloları, entity/repository/service/job katmanları ve durum motoru oluşturuldu.

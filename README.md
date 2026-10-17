# Warext Studios | XenForo Thread Freshness System

## English

Warext Studios Thread Freshness System is a community-driven XenForo 2.3 add-on for validating whether forum threads, solutions, and technical information are still current and working.

## Direct XenForo ACP installation

**Ready-to-install ZIP:** `XenForo-ACP-Direct-Install-Warext-Konu-Guncellik-1.2.4.zip`

**Download:** Use the latest package from the [Releases](https://github.com/benjamin1734/Warext-Studios---Konu-Guncellik-Sistemi/releases) page.

Upload this ZIP directly from XenForo ACP → Add-ons → Install/upgrade from archive without extracting it.

The archive installer must be enabled:

`$config['enableAddOnArchiveInstaller'] = true;`

## Main features

- Per-forum enablement and waiting period
- Per-forum age calculation using either `Meaningful update` or `Every latest post`
- Meaningful-update mode can consider thread-owner posts, first-post edits, and the selected solution post for question threads
- Community voting for worked / did not work
- One user / one vote, vote changing, and voting again in a new verification cycle
- Account-age and post-count requirements
- Voting on your own thread requires both the ACP switch and the user-group permission
- Version-based verification results
- Negative-vote reasons and reason distribution
- Selectable up-to-date alternative thread recommendation
- Non-binding “still valid” confirmation from the thread owner
- Current, probably current, uncertain, suspicious, not working, revalidating, and unverified states
- State engine combining raw vote count with time-weighted percentages
- ACP-configurable status thresholds
- Older votes gradually receive less weight
- Automatic revalidation
- Thread-list badges and status filters
- Moderator verification with the ability to return to the community result
- Notifications and web push for critical status changes
- Redirect/reference to the current solution thread
- Current Solutions search page
- ACP summaries for content health, critical threads, and recent negative feedback
- Concurrent vote locking
- Orphan-record cleanup
- Automated PHP 8.4 tests
- Release package containing installable `_data` XML and `hashes.json`

## Requirements

- XenForo 2.3.7+
- PHP 8.1+

## Installation

Upload `XenForo-ACP-Direct-Install-Warext-Konu-Guncellik-1.2.4.zip` through XenForo ACP's archive-based add-on install/upgrade screen.

After installation:

1. Enable the system from the add-on options.
2. Configure user-group permissions for voting, changing votes, voting on own threads, and moderator verification.
3. Enable freshness verification from the relevant forum's edit screen.
4. Configure the waiting period, age-calculation method, and version list when needed.
5. Adjust status-calculation thresholds in ACP options if required.

## Database

No manual SQL import is required. Installation, upgrade, and uninstall schema operations are handled through `Setup.php`.

## Support

For questions, bug reports, installation support, and help with Warext Studios XenForo add-ons, you can join our support Discord server:

**Discord:** https://discord.gg/tgsV5XMcFS

---

## Türkçe

XenForo 2.3 için topluluk tabanlı konu ve çözüm güncellik doğrulama eklentisi.

## Doğrudan XenForo ACP kurulumu

**Hazır ZIP:** `XenForo-ACP-Direct-Install-Warext-Konu-Guncellik-1.2.4.zip`

**İndirme:** En güncel paketi [Releases](https://github.com/benjamin1734/Warext-Studios---Konu-Guncellik-Sistemi/releases) sayfasından kullanın.

Bu ZIP'i çıkartmadan doğrudan XenForo ACP → Add-ons → Install/upgrade from archive ekranından seçip yükleyin.

Arşiv kurucusunun açık olması gerekir:

`$config['enableAddOnArchiveInstaller'] = true;`

## Ana özellikler

- Forum bazlı etkinleştirme ve bekleme süresi
- Forum başına `Anlamlı güncelleme` veya `Her son mesaj` yaş hesabı
- Anlamlı güncellemede konu sahibinin mesajları, ilk mesaj düzenlemeleri ve seçilmiş soru çözüm gönderisini esas alma
- Çalıştı / çalışmadı topluluk oylaması
- Tek kullanıcı / tek oy, oy değiştirme ve yeni doğrulama döngüsünde yeniden oy kullanma
- Hesap yaşı ve mesaj sayısı koşulları
- Kendi konusuna oy için hem ACP anahtarı hem kullanıcı grubu izni
- Sürüm bazlı doğrulama sonuçları
- Olumsuz oy nedenleri ve neden dağılımı
- Seçilebilir güncel alternatif konu önerisi
- Konu sahibinin bağlayıcı olmayan “hâlâ geçerli” bildirimi
- Güncel, muhtemelen güncel, kararsız, şüpheli, çalışmıyor, yeniden doğrulanıyor ve doğrulanmamış durumları
- Ham oy sayısı + zaman ağırlıklı yüzde kullanan durum motoru
- ACP üzerinden değiştirilebilir durum eşikleri
- Eski oyları zamanla düşük ağırlıkla değerlendirme
- Otomatik yeniden doğrulama
- Konu listesi rozetleri ve durum filtreleri
- Moderatör doğrulaması ve topluluk sonucuna geri dönüş
- Kritik durum değişikliği bildirimleri ve web push
- Güncel çözüm konusu yönlendirmesi
- Güncel Çözümler arama sayfası
- ACP içerik sağlığı, kritik konu ve son olumsuz geri bildirim özeti
- Eşzamanlı oy kilitleme
- Orphan kayıt temizliği
- PHP 8.4 otomatik testleri
- Kurulabilir `_data` XML ve `hashes.json` içeren release paketi

## Gereksinimler

- XenForo 2.3.7+
- PHP 8.1+

## Kurulum

`XenForo-ACP-Direct-Install-Warext-Konu-Guncellik-1.2.4.zip` paketini XenForo yönetim panelindeki arşivden eklenti kurma/yükseltme ekranından yükleyin.

Kurulumdan sonra:

1. Eklenti seçeneklerinden sistemi etkinleştirin.
2. Kullanıcı grubu izinlerinden oy, oy değiştirme, kendi konusuna oy ve moderatör doğrulama izinlerini yapılandırın.
3. İlgili forumun düzenleme ekranından güncellik doğrulamasını açın.
4. Forum için bekleme süresini, yaş hesabı yöntemini ve gerekiyorsa sürüm listesini ayarlayın.
5. Gerekiyorsa durum hesaplama eşiklerini ACP seçeneklerinden özelleştirin.

## Veri tabanı

Manuel SQL içe aktarma gerekmez. Kurulum, yükseltme ve kaldırma şema işlemleri `Setup.php` üzerinden yürütülür.

## Destek

Sorularınız, hata bildirimleriniz, kurulum desteği ve Warext Studios XenForo eklentileriyle ilgili yardım için destek Discord sunucumuza katılabilirsiniz:

**Discord:** https://discord.gg/tgsV5XMcFS

## Language support / Dil desteği



**Compatibility note:** The declared minimum is XenForo 2.3.7; XenForo 2.3.13 has been checked statically only, not on a licensed live forum.

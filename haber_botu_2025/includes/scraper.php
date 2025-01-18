<?php
if (!defined('ABSPATH')) {
    exit;
}

function cnnturk_haber_botu_fetch_news() {
    // Çalışma durumunu kontrol et
    $is_running = get_transient('cnnturk_bot_running');
    if ($is_running) {
        CNNTurk_Logger::log('Bot zaten çalışıyor, işlem iptal edildi');
        return;
    }
    
    // Çalışma durumunu işaretle (5 dakika için)
    set_transient('cnnturk_bot_running', true, 5 * MINUTE_IN_SECONDS);
    
    CNNTurk_Logger::log('Haber çekme işlemi başladı');
    
    $url = "https://www.cnnturk.com/";
    
    try {
        // CURL ile içeriği çekelim
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        
        $html = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('Curl hatası: ' . curl_error($ch));
        }
        
        curl_close($ch);

        if (!$html) {
            throw new Exception('İçerik alınamadı');
        }

        CNNTurk_Logger::log('Ana sayfa içeriği alındı');
        
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $news_items = [];
        $limit = 3; // Sadece 3 haber için
        $count = 0;

        // Tüm haber linklerini topla
        $links = $xpath->query("//a[contains(@href, '/turkiye/') or contains(@href, '/dunya/') or contains(@href, '/ekonomi/')]");
        
        CNNTurk_Logger::log('Bulunan link sayısı: ' . $links->length);

        foreach ($links as $link) {
            if ($count >= $limit) break; // 3 habere ulaşınca döngüyü sonlandır
            
            $href = $link->getAttribute('href');
            $title = trim($link->textContent);

            // Boş başlıkları ve çok kısa başlıkları atla
            if (empty($title) || strlen($title) < 10) {
                continue;
            }

            // Tam URL oluştur
            if (!strpos($href, 'https://')) {
                $href = "https://www.cnnturk.com" . $href;
            }

            // Galeri ve video sayfalarını atla
            if (strpos($href, '/galeri/') !== false || strpos($href, '/video/') !== false) {
                continue;
            }

            $news_items[$href] = [
                'title' => $title,
                'link' => $href
            ];

            CNNTurk_Logger::log("Haber bulundu: " . $title);
            $count++;
        }

        // Her haberin detayını al ve işlemler arasında bekle
        foreach ($news_items as $news) {
            if (!cnnturk_haber_botu_is_duplicate($news['title'])) {
                cnnturk_haber_botu_fetch_news_details($news['title'], $news['link']);
                sleep(2); // Her haber arasında 2 saniye bekle
            } else {
                CNNTurk_Logger::log("Mükerrer haber atlandı: " . $news['title']);
            }
        }

        // Son çalışma zamanını güncelle
        update_option('cnnturk_last_run', current_time('Y-m-d H:i:s'));

        // İşlem bittiğinde çalışma durumunu temizle
        delete_transient('cnnturk_bot_running');
        CNNTurk_Logger::log("Haber çekme işlemi tamamlandı");

    } catch (Exception $e) {
        CNNTurk_Logger::log('HATA: ' . $e->getMessage(), 'error');
        // Hata durumunda da çalışma durumunu temizle
        delete_transient('cnnturk_bot_running');
        return;
    }
}

function cnnturk_haber_botu_fetch_news_details($title, $link) {
    try {
        CNNTurk_Logger::log("Haber detayı alınıyor: " . $link);

        // CURL ile haber detayını çek
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        
        $html = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('Curl hatası: ' . curl_error($ch));
        }
        
        curl_close($ch);

        if (!$html) {
            throw new Exception("Haber detayı alınamadı");
        }

        // Debug için HTML çıktısını dosyaya kaydet
        $debug_file = WP_CONTENT_DIR . '/cnnturk-debug.html';
        file_put_contents($debug_file, $html);
        CNNTurk_Logger::log("Debug HTML kaydedildi: " . $debug_file);

        // Debug için HTML çıktısını loglayalım
        CNNTurk_Logger::log("HTML içeriği: " . substr($html, 0, 1000));

        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        // Haber görseli - og:image meta tag'inden al
        $image = '';
        $img_nodes = $xpath->query("//meta[@property='og:image']");
        if ($img_nodes->length > 0) {
            $image = $img_nodes->item(0)->getAttribute('content');
            CNNTurk_Logger::log("Görsel bulundu: " . $image);
        }

        // Haber içeriği - farklı class'ları deneyelim
        $content = '';
        $possible_content_selectors = [
            "//section[contains(@class, 'detail-content')]//p",
            "//div[contains(@class, 'detail-content-inner')]//p",
            "//div[contains(@class, 'detail-content')]//p",
            "//div[contains(@class, 'news-content')]//p",
            "//div[contains(@class, 'article-body')]//p",
            "//div[contains(@class, 'news-detail')]//p",
            "//article//div[contains(@class, 'content')]//p",
            "//div[contains(@class, 'detail-container')]//p"
        ];

        foreach ($possible_content_selectors as $selector) {
            CNNTurk_Logger::log("Seçici deneniyor: " . $selector);
            $content_nodes = $xpath->query($selector);
            
            if ($content_nodes && $content_nodes->length > 0) {
                foreach ($content_nodes as $node) {
                    $paragraph = trim($node->textContent);
                    if (!empty($paragraph)) {
                        $content .= '<p>' . $paragraph . '</p>';
                    }
                }
                
                if (!empty($content)) {
                    CNNTurk_Logger::log("İçerik bulundu, seçici: " . $selector);
                    break;
                }
            }
        }

        if (empty($content)) {
            // Son çare: Tüm metin içeriğini al
            $body_content = $xpath->query("//body")[0]->textContent;
            CNNTurk_Logger::log("Body içeriği (ilk 500 karakter): " . substr($body_content, 0, 500));
            throw new Exception("Haber içeriği bulunamadı - Hiçbir seçici çalışmadı");
        }

        CNNTurk_Logger::log("İçerik uzunluğu: " . strlen($content));

        // WordPress'e haber ekleme
        cnnturk_haber_botu_insert_news($title, $content, $image, $link);
        CNNTurk_Logger::log("Haber başarıyla eklendi: " . $title);

    } catch (Exception $e) {
        CNNTurk_Logger::log("HATA - Haber detayı alınırken: " . $e->getMessage(), 'error');
        // HTML yapısını debug için logla
        CNNTurk_Logger::log("DOM yapısı: " . $dom->saveHTML());
    }
}

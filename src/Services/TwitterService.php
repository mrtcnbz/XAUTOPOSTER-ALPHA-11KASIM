<?php
namespace XAutoPoster\Services;

use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterService {
    private $connection;
    private $debug = true;
    
    public function __construct($apiKey, $apiSecret, $accessToken, $accessTokenSecret) {
        try {
            if (empty($apiKey) || empty($apiSecret) || empty($accessToken) || empty($accessTokenSecret)) {
                throw new \Exception('Twitter API bilgileri eksik');
            }

            $this->connection = new TwitterOAuth(
                $apiKey,
                $apiSecret,
                $accessToken,
                $accessTokenSecret
            );
            
            // API bağlantı ayarları
            $this->connection->setTimeouts(30, 30);
            
            // API versiyonunu ayarla
            $this->connection->setApiVersion('2');
            
            // Bağlantıyı test et
            $this->verifyCredentials();
        } catch (\Exception $e) {
            error_log('Twitter Bağlantı Hatası: ' . $e->getMessage());
            throw new \Exception('Twitter API bağlantısı kurulamadı: ' . $e->getMessage());
        }
    }
    
    public function verifyCredentials() {
        try {
            // API v1.1'i kullanarak kimlik doğrulama
            $this->connection->setApiVersion('1.1');
            $result = $this->connection->get('account/verify_credentials');
            
            if ($this->debug) {
                error_log('Twitter Doğrulama Yanıtı: ' . print_r($result, true));
            }
            
            if ($this->connection->getLastHttpCode() !== 200) {
                throw new \Exception('API doğrulama başarısız: HTTP ' . $this->connection->getLastHttpCode());
            }
            
            if (isset($result->errors)) {
                $error = $result->errors[0]->message ?? 'Bilinmeyen API hatası';
                throw new \Exception($error);
            }
            
            // API v2'ye geri dön
            $this->connection->setApiVersion('2');
            
            return true;
        } catch (\Exception $e) {
            error_log('Twitter API Doğrulama Hatası: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function sharePost($post) {
        try {
            $title = html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8');
            $permalink = get_permalink($post);
            $hashtags = $this->getPostHashtags($post);
            $content = $this->formatPostContent($title, $permalink, $hashtags);
            
            $mediaIds = [];
            if (has_post_thumbnail($post)) {
                $imageId = get_post_thumbnail_id($post);
                $imagePath = get_attached_file($imageId);
                if ($imagePath && file_exists($imagePath)) {
                    $this->connection->setApiVersion('1.1');
                    $media = $this->connection->upload('media/upload', ['media' => $imagePath]);
                    $this->connection->setApiVersion('2');
                    
                    if (isset($media->media_id_string)) {
                        $mediaIds[] = $media->media_id_string;
                    }
                }
            }

            $params = ['text' => $content];
            if (!empty($mediaIds)) {
                $params['media'] = ['media_ids' => $mediaIds];
            }
            
            $result = $this->connection->post('tweets', $params, true);
            
            if ($this->debug) {
                error_log('Twitter Paylaşım Yanıtı: ' . print_r($result, true));
            }
            
            if ($this->connection->getLastHttpCode() !== 201) {
                throw new \Exception('Paylaşım başarısız: HTTP ' . $this->connection->getLastHttpCode());
            }
            
            if (!isset($result->data->id)) {
                throw new \Exception(isset($result->errors[0]->message) ? 
                    $result->errors[0]->message : 'Paylaşım başarısız oldu');
            }
            
            return $result;
        } catch (\Exception $e) {
            error_log('Twitter Paylaşım Hatası: ' . $e->getMessage());
            throw new \Exception('Paylaşım hatası: ' . $e->getMessage());
        }
    }
    
    private function getPostHashtags($post) {
        $hashtags = [];
        
        $categories = get_the_category($post->ID);
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $hashtags[] = '#' . preg_replace('/\s+/', '', $category->name);
            }
        }
        
        $tags = get_the_tags($post->ID);
        if (!empty($tags)) {
            foreach ($tags as $tag) {
                $hashtags[] = '#' . preg_replace('/\s+/', '', $tag->name);
            }
        }
        
        return array_unique($hashtags);
    }
    
    private function formatPostContent($title, $permalink, $hashtags) {
        $options = get_option('xautoposter_auto_share_options', []);
        $template = isset($options['post_template']) ? 
            $options['post_template'] : '%title% %link% %hashtags%';
            
        $hashtagsStr = implode(' ', array_slice($hashtags, 0, 3));
        
        $content = str_replace(
            ['%title%', '%link%', '%hashtags%'],
            [$title, $permalink, $hashtagsStr],
            $template
        );
        
        return mb_substr($content, 0, 280);
    }
    
    public function getTweetMetrics($tweetId) {
        try {
            $result = $this->connection->get('tweets/' . $tweetId, [
                'tweet.fields' => 'public_metrics,created_at'
            ]);
            
            if ($this->connection->getLastHttpCode() !== 200) {
                throw new \Exception('Metrik alınamadı: HTTP ' . $this->connection->getLastHttpCode());
            }
            
            return isset($result->data) ? $result->data : null;
        } catch (\Exception $e) {
            error_log('Twitter Metrik Hatası: ' . $e->getMessage());
            return null;
        }
    }
}
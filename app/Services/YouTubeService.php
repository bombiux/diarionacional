<?php

namespace App\Services;

class YouTubeService
{
    private const CHANNEL_ID = 'UCfhttf2jcAIZsHreCcnoOeQ';
    private const TRANSIENT_KEY = 'dn_youtube_recent_videos';
    private const TRANSIENT_EXPIRATION = 14400; // 4 horas en segundos

    /**
     * Obtiene la lista de videos más recientes del canal.
     * Intenta leer de Transients, si no existe o expiró consulta el feed RSS
     * de YouTube, y si la consulta falla, recurre a un listado fallback real.
     *
     * @param int $limit Número de videos a retornar.
     * @return array
     */
    public static function getRecentVideos(int $limit = 4): array
    {
        $cached_videos = get_transient(self::TRANSIENT_KEY);

        if ($cached_videos !== false && is_array($cached_videos) && !empty($cached_videos)) {
            return array_slice($cached_videos, 0, $limit);
        }

        $videos = self::fetchFromFeed();

        if (empty($videos)) {
            $videos = self::getFallbackVideos();
        } else {
            // Guardar en caché
            set_transient(self::TRANSIENT_KEY, $videos, self::TRANSIENT_EXPIRATION);
        }

        return array_slice($videos, 0, $limit);
    }

    /**
     * Consulta el feed de YouTube y procesa el XML.
     *
     * @return array
     */
    private static function fetchFromFeed(): array
    {
        $url = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . self::CHANNEL_ID;
        $response = wp_remote_get($url, [
            'timeout' => 5,
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return [];
        }

        // Desactivar temporalmente errores externos de libxml al cargar XML inseguro o mal formateado
        $use_errors = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_use_internal_errors($use_errors);

        if ($xml === false || !isset($xml->entry)) {
            return [];
        }

        $videos = [];
        foreach ($xml->entry as $entry) {
            $yt_ns = $entry->children('yt', true);
            $media_ns = $entry->children('media', true);

            $id = (string) ($yt_ns->videoId ?? '');
            $title = (string) ($entry->title ?? '');
            
            // Si falta el ID en el namespace directo, intentar extraerlo del elemento <id>
            if (empty($id) && isset($entry->id)) {
                $raw_id = (string) $entry->id;
                if (preg_match('/yt:video:(.+)$/', $raw_id, $matches)) {
                    $id = $matches[1];
                }
            }

            if (empty($id)) {
                continue;
            }

            // Procesar grupo de multimedia si existe
            $category = 'Especial';
            if (isset($media_ns->group)) {
                $group = $media_ns->group;
                // Si hay descripción, intentar derivar una categoría o usar por defecto
                $desc = (string) ($group->description ?? '');
                if (stripos($desc, 'suceso') !== false || stripos($title, 'suceso') !== false || stripos($title, 'accidente') !== false) {
                    $category = 'Sucesos';
                } elseif (stripos($desc, 'gastronom') !== false || stripos($title, 'gastronom') !== false) {
                    $category = 'Gastronomía';
                } elseif (stripos($desc, 'nacional') !== false || stripos($title, 'nacional') !== false) {
                    $category = 'Nacional';
                } elseif (stripos($title, 'short') !== false || stripos($desc, 'short') !== false) {
                    $category = 'Short';
                }
            }

            $videos[] = [
                'id' => $id,
                'title' => $title,
                'duration' => '', // El feed de YouTube no provee la duración directamente. Se deja vacío y se maneja en el frontend.
                'category' => $category,
            ];
        }

        return $videos;
    }

    /**
     * Videos fallback reales y probados de Diario Nacional TV.
     *
     * @return array
     */
    private static function getFallbackVideos(): array
    {
        return [
            [
                'id' => 'QHZz_wLHSy0',
                'title' => 'En Nicaragua',
                'duration' => '00:30',
                'category' => 'Short',
            ],
            [
                'id' => 'JmItOTb7P2w',
                'title' => 'En #nicaragua',
                'duration' => '00:45',
                'category' => 'Short',
            ],
            [
                'id' => 'AtpQZ6U3cwI',
                'title' => '🇳🇮 Nicaragua es reconocida una vez más como la mejor gastronomía de Centroamérica',
                'duration' => '02:15',
                'category' => 'Gastronomía',
            ],
            [
                'id' => 'DzjnpiKOsDc',
                'title' => 'Motociclista invadió el carril contrario e impactó a otros dos motorizados en Cofradía',
                'duration' => '03:40',
                'category' => 'Sucesos',
            ],
        ];
    }
}

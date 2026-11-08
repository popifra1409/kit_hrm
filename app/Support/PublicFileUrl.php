<?php

namespace App\Support;

/**
 * URL des pièces jointes (photos, actes, diplômes, pièces de contrat) telles que
 * l'API les renvoie à l'application web.
 *
 * L'unique serveur de fichiers est celui qui héberge l'API et la base de données, mais
 * on y entre par deux adresses différentes : l'adresse du réseau local pour les agents
 * présents dans la structure, le domaine public pour les autres. Une URL absolue construite
 * depuis APP_URL n'est donc correcte que pour l'une des deux entrées — et pour aucune si
 * APP_URL conserve une valeur de développement. L'API renvoie un chemin relatif, que le
 * front résout contre l'adresse par laquelle il joigne l'API ; le même jeu de réponses
 * vaut alors pour les deux entrées.
 */
class PublicFileUrl
{
    public static function path(mixed $diskPath): ?string
    {
        $diskPath = is_array($diskPath) ? ($diskPath[0] ?? null) : $diskPath;

        if (! is_string($diskPath) || trim($diskPath) === '') {
            return null;
        }

        $diskPath = trim($diskPath);

        // Valeur héritée d'un ancien téléversement déjà absolue : transmise telle quelle,
        // le front sait distinguer un chemin d'une URL.
        if (preg_match('#^https?://#i', $diskPath) === 1) {
            return $diskPath;
        }

        $prefix = trim(self::publicDiskPrefix(), '/');
        $path = ltrim($diskPath, '/');

        return '/' . ($path === $prefix || str_starts_with($path, $prefix . '/')
            ? $path
            : $prefix . '/' . $path);
    }

    /**
     * Préfixe sous lequel le disque public est exposé, sans le domaine : « storage »,
     * ou « app/public/storage » si l'application est servie depuis un sous-répertoire.
     */
    private static function publicDiskPrefix(): string
    {
        $url = (string) config('filesystems.disks.public.url');

        // APP_URL est parfois saisi sans schéma (« kithrm.test ») : sans correction,
        // parse_url() lirait alors le domaine comme un chemin.
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $url) !== 1) {
            $url = 'http://' . $url;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return ($path === null || $path === false || $path === '') ? 'storage' : trim((string) $path, '/');
    }
}

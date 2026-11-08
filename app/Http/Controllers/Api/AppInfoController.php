<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Support\PublicFileUrl;
use Illuminate\Support\Facades\Storage;

/**
 * @tags Application
 */
class AppInfoController extends Controller
{
    /**
     * Informations publiques de l'application (logo, nom de la structure).
     * Accessible sans authentification — utilisé sur les écrans de connexion/activation.
     *
     * @unauthenticated
     *
     * @response 200 {
     *   "hospital_name": "Hôpital Général de Yaoundé",
     *   "hospital_short_name": "HGY",
     *   "logo_url": "/storage/hospital/logo.png",
     *   "favicon_url": "/storage/hospital/favicon.png"
     * }
     */
    public function show()
    {
        $logoUrl = $this->publicUrl(SystemSetting::get('hospital_logo'));

        return response()->json([
            'hospital_name' => SystemSetting::get('hospital_name', 'Hôpital Général de Yaoundé'),
            'hospital_short_name' => SystemSetting::get('hospital_short_name', SystemSetting::get('hospital_acronym', 'HGY')),
            'logo_url' => $logoUrl,
            'favicon_url' => $this->publicUrl(SystemSetting::get('hospital_favicon')) ?? $logoUrl,
        ]);
    }

    /**
     * Chemin d'une pièce « Infos de la structure » → URL relative à l'application, ou null
     * si inexploitable. La page de réglages stocke une chaîne ou un tableau (FileUpload),
     * et une valeur héritée d'un ancien téléversement peut être un chemin local
     * absent du disque : dans ce cas l'écran retombe sur son logo par défaut.
     */
    private function publicUrl(mixed $path): ?string
    {
        $path = is_array($path) ? ($path[0] ?? null) : $path;

        if (!is_string($path) || trim($path) === '' || !Storage::disk('public')->exists($path)) {
            return null;
        }

        return PublicFileUrl::path($path);
    }
}

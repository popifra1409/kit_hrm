<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
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
     *   "logo_url": "https://.../storage/branding/logo.png"
     * }
     */
    public function show()
    {
        $logoPath = SystemSetting::get('logo_path');

        return response()->json([
            'hospital_name' => SystemSetting::get('hospital_name', 'Hôpital Général de Yaoundé'),
            'hospital_short_name' => SystemSetting::get('hospital_short_name', SystemSetting::get('hospital_acronym', 'HGY')),
            'logo_url' => ($logoPath && Storage::disk('public')->exists($logoPath))
                ? Storage::disk('public')->url($logoPath)
                : null,
        ]);
    }
}

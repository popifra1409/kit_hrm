<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * @tags Preferences
 */
class PreferenceController extends Controller
{
    /**
     * Ce que le compte emporte d'un appareil à l'autre, avec les valeurs admissibles.
     *
     * Une clé absente de cette liste est ignorée : le portail peut envoyer un réglage que
     * l'API ne connaît pas encore sans que la requête échoue, mais ce réglage n'est
     * enregistré que quand il est déclaré ici. Cette liste est donc le seul endroit où
     * ajouter une préférence synchronisée, et elle doit avancer en même temps que
     * `SyncedPreferences` côté portail.
     */
    private const SYNCED = [
        'lang' => ['fr', 'en'],
        'font_scale' => ['sm', 'md', 'lg'],
        'font_family' => ['system', 'arial', 'verdana', 'georgia'],
        'theme' => ['light', 'dark', 'system'],
    ];

    /**
     * Réglages enregistrés pour le compte, ou null si l'agent n'a encore rien modifié.
     *
     * @response 200 {"preferences": {"lang": "fr", "font_scale": "lg", "font_family": "arial", "theme": "dark"}}
     * @response 200 {"preferences": null}
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json(['preferences' => $request->user()->preferences]);
    }

    /**
     * Enregistre la photographie complète des réglages de l'agent.
     *
     * Le portail envoie toujours l'ensemble des clés connues : ce qui est reçu remplace ce
     * qui était stocké, au lieu de le fusionner. Une préférence retirée du portail disparaît
     * ainsi du compte, plutôt que de ressurgir à la connexion suivante.
     *
     * @response 200 {"preferences": {"lang": "en", "font_scale": "md", "font_family": "system"}}
     * @response 422 {"message": "...", "errors": {"preferences.font_scale": ["..."]}}
     */
    public function update(Request $request): JsonResponse
    {
        $received = $request->validate(['preferences' => ['required', 'array']])['preferences'];

        $user = $request->user();
        $preferences = $this->onlySynced($received);

        $user->preferences = $preferences;
        $user->save();

        return response()->json(['preferences' => $preferences]);
    }

    /**
     * Une préférence envoyée par erreur ne doit pas se retrouver stockée puis relue sur un
     * autre appareil : une valeur hors liste refuse la requête (message en français, comme
     * tout le corps de l'API — le portail, lui, n'envoie que des valeurs de sa propre liste).
     * Une clé que l'API ne connaît pas passe à la trappe sans bloquer le reste.
     */
    private function onlySynced(array $received): array
    {
        $clean = [];

        foreach (self::SYNCED as $key => $allowed) {
            if (!array_key_exists($key, $received)) {
                continue;
            }

            $value = $received[$key];

            if (!is_string($value) || !in_array($value, $allowed, true)) {
                throw ValidationException::withMessages([
                    'preferences.' . $key => 'Valeur non reconnue pour ce réglage.',
                ]);
            }

            $clean[$key] = $value;
        }

        return $clean;
    }
}

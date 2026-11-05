<?php

namespace App\Http\Controllers\Api;

use App\Enums\RegistrationSource;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StorePublicRegistrationRequest;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Services\MemberService;
use Illuminate\Http\JsonResponse;

class PublicRegistrationController extends Controller
{
    public function __construct(private readonly MemberService $members) {}

    /**
     * POST /api/public/registrations (sans authentification, 10 requêtes / heure par IP)
     * Payload : { last_name, first_name, phone, gender?, birth_date?, email?, address?, subscription_plan_id? }
     * Réponse 201 : { message, code, status: "pending" } - aucune donnée sensible renvoyée
     * 422 : online_registration_disabled | erreurs de validation
     *
     * Le champ "website" est un pot de miel : s'il est rempli, la requête est rejetée (anti-bot simple).
     */
    public function store(StorePublicRegistrationRequest $request): JsonResponse
    {
        if (! Setting::get('online_registration_enabled', false)) {
            throw new BusinessRuleException(
                'online_registration_disabled',
                'Les inscriptions en ligne ne sont pas ouvertes pour le moment, rendez-vous à l\'accueil du club.'
            );
        }

        $data = $request->safe()->except(['subscription_plan_id', 'website']);

        if ($request->filled('subscription_plan_id')) {
            $plan = SubscriptionPlan::find($request->subscription_plan_id);
            if ($plan !== null) {
                $data['notes'] = 'Formule souhaitée (site web) : '.$plan->name;
            }
        }

        $member = $this->members->create($data, null, RegistrationSource::Online);

        return response()->json([
            'message' => 'Votre demande d\'adhésion a bien été enregistrée. Elle sera validée à votre premier passage au club.',
            'code' => $member->code,
            'status' => $member->status->value,
        ], 201);
    }
}

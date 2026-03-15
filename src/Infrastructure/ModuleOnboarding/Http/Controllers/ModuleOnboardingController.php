<?php

namespace Src\Infrastructure\ModuleOnboarding\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Src\Application\ModuleOnboarding\ModuleOnboardingService;

class ModuleOnboardingController extends Controller
{
    public function __construct(
        private ModuleOnboardingService $service
    ) {
    }

    /**
     * État d'onboarding pour un module (étapes complétées, status).
     */
    public function status(Request $request, string $moduleName): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $this->ensureModuleExists($moduleName);

        $status = $this->service->getStatus((int) $user->id, $moduleName);

        return response()->json([
            'steps_completed' => $status->getStepsCompleted(),
            'status' => $status->getStatus(),
            'updated_at' => $status->getUpdatedAt()->format('c'),
        ]);
    }

    /**
     * Marquer une étape comme complétée.
     */
    public function completeStep(Request $request, string $moduleName): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $this->ensureModuleExists($moduleName);

        $stepId = $request->input('step_id');
        if (!is_string($stepId) || $stepId === '') {
            return response()->json(['error' => 'step_id required'], 422);
        }

        $status = $this->service->completeStep((int) $user->id, $moduleName, $stepId);

        return response()->json([
            'steps_completed' => $status->getStepsCompleted(),
            'status' => $status->getStatus(),
            'updated_at' => $status->getUpdatedAt()->format('c'),
        ]);
    }

    /**
     * Valider tout le module ("Je comprends").
     */
    public function completeModule(Request $request, string $moduleName): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $this->ensureModuleExists($moduleName);

        $status = $this->service->completeModule((int) $user->id, $moduleName);

        return response()->json([
            'steps_completed' => $status->getStepsCompleted(),
            'status' => $status->getStatus(),
            'updated_at' => $status->getUpdatedAt()->format('c'),
        ]);
    }

    /**
     * Configuration des étapes pour un module (pour le front).
     */
    public function steps(string $moduleName): JsonResponse
    {
        $this->ensureModuleExists($moduleName);

        $steps = config('module_onboarding.' . $moduleName . '.steps', []);
        usort($steps, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        return response()->json(['steps' => $steps]);
    }

    private function ensureModuleExists(string $moduleName): void
    {
        $modules = array_keys(config('module_onboarding', []));
        if (!in_array($moduleName, $modules, true)) {
            abort(404, 'Module onboarding not found');
        }
    }
}

<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Banking\BankPaymentReconciliationService;
use App\Services\Banking\Contracts\BankWebhookHandler;
//use App\Services\Banking\Handlers\CoopWebhookHandler;
use App\Services\Banking\Handlers\EquityWebhookHandler;
use App\Services\Banking\Handlers\KcbWebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BankWebhookController extends Controller
{
    public function __construct(protected BankPaymentReconciliationService $service) {}

    public function equity(Request $request, EquityWebhookHandler $handler)
    {
        return $this->handle($request, $handler);
    }

//    public function coop(Request $request, CoopWebhookHandler $handler)
//    {
//        return $this->handle($request, $handler);
//    }

    public function kcb(Request $request, KcbWebhookHandler $handler)
    {
        return $this->handle($request, $handler);
    }

    protected function handle(Request $request, BankWebhookHandler $handler)
    {
        if (!$handler->verify($request)) {
            Log::warning('Bank IPN verification failed', ['ip' => $request->ip()]);
            abort(401);
        }

        if (!$handler->shouldProcess($request)) {
            Log::info('Bank IPN skipped — not a successful transaction', [
                'handler' => get_class($handler),
                'payload' => $request->all(),
            ]);
            return response()->json($handler->acknowledgement($request));
        }

        $data = $handler->parse($request);
        $this->service->process($data);

        return response()->json($handler->acknowledgement($request));
    }

    public function fetch(Request $request)
    {
        $validated = $request->validate([
            'consumer_key'    => ['required', 'string'],
            'consumer_secret' => ['required', 'string'],
            'environment'     => ['required', 'in:sandbox,live'],
        ]);

        $baseUrl = $validated['environment'] === 'live'
            ? config('services.kcb_buni.live_base_url')   // e.g. https://api.buni.kcbgroup.com
            : config('services.kcb_buni.sandbox_base_url'); // e.g. https://uat.buni.kcbgroup.com

        try {
            // Step 1: OAuth client-credentials token — confirm exact path/params
            // against KCB's Buni API docs.
            $tokenResponse = Http::asForm()->post("{$baseUrl}/token", [
                'grant_type'    => 'client_credentials',
                'client_id'     => $validated['consumer_key'],
                'client_secret' => $validated['consumer_secret'],
            ]);

            if (! $tokenResponse->successful()) {
                Log::warning('KCB Buni token request failed', ['status' => $tokenResponse->status(), 'body' => $tokenResponse->body()]);
                return response()->json(['success' => false, 'message' => 'Could not authenticate with KCB Buni. Check the Consumer Key/Secret.']);
            }

            $accessToken = $tokenResponse->json('access_token');

            // Step 2: Fetch/generate the IPN signature secret — confirm the
            // actual endpoint name with KCB; this is a placeholder path.
            $signatureResponse = Http::withToken($accessToken)
                ->get("{$baseUrl}/ipn/signature-secret");

            if (! $signatureResponse->successful()) {
                Log::warning('KCB Buni IPN signature request failed', ['status' => $signatureResponse->status(), 'body' => $signatureResponse->body()]);
                return response()->json(['success' => false, 'message' => 'Authenticated, but could not retrieve the IPN signature secret from KCB.']);
            }

            return response()->json([
                'success'   => true,
                'signature' => $signatureResponse->json('signature'), // adjust key to match actual response shape
            ]);

        } catch (\Throwable $e) {
            Log::error('KCB Buni IPN signature fetch exception', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Unexpected error contacting KCB.'], 500);
        }
    }
}

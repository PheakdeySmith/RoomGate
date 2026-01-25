<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminIoTController extends Controller
{
    public function index()
    {
        return view('admin::dashboard.iot-control');
    }

    public function status(Request $request)
    {
        $ip = $this->validateIp($request->input('ip'));

        $response = Http::timeout(5)->get("http://{$ip}/api/status");

        return response()->json($response->json(), $response->status());
    }

    public function led(Request $request)
    {
        $ip = $this->validateIp($request->input('ip'));
        $state = $request->input('state');

        if (!in_array($state, ['0', '1'], true)) {
            return response()->json(['error' => 'state must be 0 or 1'], 422);
        }

        $response = Http::timeout(5)->post("http://{$ip}/api/led?state={$state}");

        return response()->json($response->json(), $response->status());
    }

    private function validateIp(?string $ip): string
    {
        $ip = trim((string) $ip);
        if ($ip === '') {
            abort(422, 'IP is required.');
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            abort(422, 'Invalid IP.');
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
            return $ip;
        }

        abort(422, 'IP must be a private network address.');
    }
}

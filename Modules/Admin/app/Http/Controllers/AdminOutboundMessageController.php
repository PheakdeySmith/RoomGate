<?php

namespace Modules\Admin\App\Http\Controllers;

use App\Models\OutboundMessage;
use Illuminate\Routing\Controller;

class AdminOutboundMessageController extends Controller
{
    public function index()
    {
        $messages = OutboundMessage::query()
            ->with(['tenant', 'user'])
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        return view('admin::dashboard.outbound-messages', compact('messages'));
    }
}

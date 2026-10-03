<?php

// app/Http/Controllers/CommunicationTemplateController.php
namespace App\Http\Controllers;

use App\Http\Requests\StoreCommunicationTemplateRequest;
use App\Models\CommunicationTemplate;

class CommunicationTemplateController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->hasPermission('communication.templates.manage'), 403);

        return view('communication.templates.index', [
            'templates' => CommunicationTemplate::latest()->get(),
        ]);
    }

    public function store(StoreCommunicationTemplateRequest $request)
    {
        CommunicationTemplate::create([...$request->validated(), 'created_by' => $request->user()->id]);

        return back()->with('success', 'Template created.');
    }

    public function update(StoreCommunicationTemplateRequest $request, CommunicationTemplate $template)
    {
        $template->update($request->validated());

        return back()->with('success', 'Template updated.');
    }

    public function destroy(CommunicationTemplate $template)
    {
        abort_unless(auth()->user()?->hasPermission('communication.templates.manage'), 403);
        $template->delete();

        return response()->json(['success' => true]);
    }
}

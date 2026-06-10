<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:25',
            'color' => 'required|string|max:25',
        ]);

        $request->user()->tags()->create([
            'name' => $validated['name'],
            'color' => $validated['color'],
        ]);

        return back();
    }

    public function destroy(Tag $tag, Request $request): RedirectResponse
    {
        if ($tag->user_id !== $request->user()->id) {
            abort(403);
        }

        $tag->delete();

        return back();
    }
}

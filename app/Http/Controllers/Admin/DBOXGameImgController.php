<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DBOXGame;
use App\Models\DBOXGameImg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DBOXGameImgController extends Controller
{
    public function store(Request $request, DBOXGame $game)
    {
        $validated = $request->validate([
            // You can either upload file OR provide existing path
            'image'      => ['nullable', 'image', 'max:5120'], // 5MB
            'path'       => ['nullable', 'string', 'max:255'],
            'label'      => ['nullable', 'string', 'max:100'],
            'is_primary' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (!$request->hasFile('image') && empty($validated['path'])) {
            return back()->withErrors(['image' => 'Upload image OR fill in existing path.']);
        }

        $isPrimary = (bool)($validated['is_primary'] ?? true);
        $sortOrder = (int)($validated['sort_order'] ?? 0);

        // Build path (relative to public/)
        $path = $validated['path'] ?? null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');

            $dir = public_path('images/games');
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $baseName = Str::slug($game->code ?: $game->name ?: 'game');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $filename = $baseName . '-' . Str::random(10) . '.' . $ext;

            $file->move($dir, $filename);

            $path = 'images/games/' . $filename;
        } else {
            // Normalize: remove leading slash if user typed "/images/games/xxx.png"
            $path = ltrim($path, '/');
        }

        DB::transaction(function () use ($game, $path, $validated, $isPrimary, $sortOrder) {
            if ($isPrimary) {
                DBOXGameImg::where('game_id', $game->id)->update(['is_primary' => false]);
            }

            DBOXGameImg::create([
                'game_id'    => $game->id,
                'path'       => $path,
                'label'      => $validated['label'] ?? null,
                'is_primary' => $isPrimary,
                'sort_order' => $sortOrder,
            ]);
        });

        return back()->with('success', 'Game image saved.');
    }

    public function destroy(DBOXGameImg $img)
    {
        $img->delete();
        return back()->with('success', 'Game image removed.');
    }

    public function setPrimary(DBOXGameImg $img)
    {
        DB::transaction(function () use ($img) {
            DBOXGameImg::where('game_id', $img->game_id)->update(['is_primary' => false]);
            $img->update(['is_primary' => true]);
        });

        return back()->with('success', 'Primary game image updated.');
    }
}

<?php
// /home/lmh/app/app/Http/Controllers/Admin/AdminDBOXImgUploadController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DBOXGame;
use App\Models\DBOXGameImg;
use App\Models\DBOXProvider;
use App\Models\DBOXProviderImg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminDBOXImgUploadController extends Controller
{
    public function form()
    {
        $providers = DBOXProvider::orderBy('name')->get(['id','code','name']);
        return view('admins.dbox.images.upload', compact('providers'));
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:game,provider'],
            'q'    => ['nullable', 'string', 'max:80'],
        ]);

        $type = $validated['type'];
        $q = trim((string)($validated['q'] ?? ''));

        if ($type === 'provider') {
            $query = DBOXProvider::query()
                ->select(['id','code','name'])
                ->orderBy('name');

            if ($q !== '') {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('code', 'like', "%{$q}%");
                });
            }

            return response()->json([
                'items' => $query->limit(30)->get(),
            ]);
        }

        $query = DBOXGame::query()
            ->select(['id','code','name'])
            ->orderBy('name');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('code', 'like', "%{$q}%");
            });
        }

        return response()->json([
            'items' => $query->limit(30)->get(),
        ]);
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:game,provider'],
            'target_id' => ['required', 'integer'],
        ]);

        $type = $validated['type'];
        $targetId = (int)$validated['target_id'];

        if ($type === 'game') {
            $game = DBOXGame::query()
                ->with(['images' => function ($q) {
                    $q->orderBy('is_primary', 'desc')
                      ->orderBy('sort_order', 'asc')
                      ->orderBy('id', 'desc');
                }])
                ->findOrFail($targetId);

            $html = view('admins.dbox.images.partials.preview', [
                'type' => 'game',
                'target' => $game,
                'images' => $game->images,
            ])->render();

            return response()->json(['ok' => true, 'html' => $html]);
        }

        $provider = DBOXProvider::query()
            ->with(['images' => function ($q) {
                $q->orderBy('is_primary', 'desc')
                  ->orderBy('sort_order', 'asc')
                  ->orderBy('id', 'desc');
            }])
            ->findOrFail($targetId);

        $html = view('admins.dbox.images.partials.preview', [
            'type' => 'provider',
            'target' => $provider,
            'images' => $provider->images,
        ])->render();

        return response()->json(['ok' => true, 'html' => $html]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'        => ['required', 'in:game,provider'],
            'target_id'   => ['required', 'integer'],
            'image'       => ['required', 'image', 'max:5120'],
            'label'       => ['nullable', 'string', 'max:100'],
            'is_primary'  => ['nullable', 'boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $type = $validated['type'];
        $targetId = (int)$validated['target_id'];
        $isPrimary = (bool)($validated['is_primary'] ?? true);
        $sortOrder = (int)($validated['sort_order'] ?? 0);

        if ($type === 'game') {
            $game = DBOXGame::findOrFail($targetId);

            $dir = public_path('images/games');
            if (!is_dir($dir)) @mkdir($dir, 0775, true);

            $baseName = Str::slug($game->code ?: $game->name ?: 'game');
            $file = $request->file('image');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $filename = $baseName . '-' . Str::random(10) . '.' . $ext;

            $file->move($dir, $filename);
            $path = 'images/games/' . $filename;

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
        } else {
            $provider = DBOXProvider::findOrFail($targetId);

            $dir = public_path('images/providers');
            if (!is_dir($dir)) @mkdir($dir, 0775, true);

            $baseName = Str::slug($provider->code ?: $provider->name ?: 'provider');
            $file = $request->file('image');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $filename = $baseName . '-' . Str::random(10) . '.' . $ext;

            $file->move($dir, $filename);
            $path = 'images/providers/' . $filename;

            DB::transaction(function () use ($provider, $path, $validated, $isPrimary, $sortOrder) {
                if ($isPrimary) {
                    DBOXProviderImg::where('provider_id', $provider->id)->update(['is_primary' => false]);
                }

                DBOXProviderImg::create([
                    'provider_id' => $provider->id,
                    'path'        => $path,
                    'label'       => $validated['label'] ?? null,
                    'is_primary'  => $isPrimary,
                    'sort_order'  => $sortOrder,
                ]);
            });
        }

        try { Cache::forget('home.providers'); } catch (\Throwable $e) {}

        return back()->with('success', 'Uploaded and saved.');
    }
}

<?php

namespace App\Http\Middleware;

use App\Services\GoogleTranslate;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class GoogleAutoTranslateHtml
{
    public function __construct(private GoogleTranslate $gt) {}

    public function handle(Request $request, Closure $next): Response
    {
        $lang = session('gt_lang', 'en');

        // ✅ PROOF middleware runs
        Log::info('GoogleAutoTranslateHtml hit', [
            'path' => $request->path(),
            'lang' => $lang,
        ]);

        /** @var Response $response */
        $response = $next($request);

        // header proof in browser devtools
        $response->headers->set('X-GT-Lang', $lang);

        // Only translate HTML
        $contentType = (string) $response->headers->get('Content-Type', '');
        if (stripos($contentType, 'text/html') === false) {
            return $response;
        }

        if ($lang === 'en') {
            return $response;
        }

        $html = (string) $response->getContent();
        if ($html === '') return $response;

        // Safety: avoid huge pages
        if (strlen($html) > 700000) {
            Log::warning('Skip translate: HTML too large', ['len' => strlen($html)]);
            return $response;
        }

        // Extract visible text chunks (simple)
        // This is a lightweight approach: collect text between tags, ignore script/style.
        $chunks = $this->extractTextChunks($html);

        if (!$chunks) {
            return $response;
        }

        // Translate unique strings (avoid duplicates)
        $unique = array_values(array_unique($chunks));

        // Call Google Translate service (this logs errors now)
        $translated = $this->gt->translateMany($unique, $lang);

        // Build map original => translated
        $map = [];
        foreach ($unique as $i => $orig) {
            $map[$orig] = $translated[$i] ?? $orig;
        }

        // Replace in HTML
        $out = $this->replaceChunks($html, $map);

        $response->setContent($out);
        return $response;
    }

    private function extractTextChunks(string $html): array
    {
        // Remove script/style blocks first
        $clean = preg_replace('~<script\b[^>]*>.*?</script>~is', '', $html) ?? $html;
        $clean = preg_replace('~<style\b[^>]*>.*?</style>~is', '', $clean) ?? $clean;

        // Match text nodes between tags
        preg_match_all('~>([^<]+)<~', $clean, $m);

        $texts = [];
        foreach (($m[1] ?? []) as $t) {
            $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $t2 = trim(preg_replace('/\s+/', ' ', $t));

            // skip empty / tiny / numbers only
            if ($t2 === '') continue;
            if (mb_strlen($t2) < 2) continue;
            if (preg_match('/^[\d\W]+$/u', $t2)) continue;

            $texts[] = $t2;
        }

        return $texts;
    }

    private function replaceChunks(string $html, array $map): string
    {
        // Replace only text between tags (keep HTML structure)
        // We'll do it by callback on each text node.
        $out = preg_replace_callback(
            '~>([^<]+)<~',
            function ($matches) use ($map) {
                $raw = $matches[1];
                $decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $norm = trim(preg_replace('/\s+/', ' ', $decoded));

                if ($norm === '') return $matches[0];

                $rep = $map[$norm] ?? $norm;

                // keep original spacing style roughly
                $repEsc = htmlspecialchars($rep, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                // preserve exact wrapper >
                return '>' . $repEsc . '<';
            },
            $html
        );

        return $out ?? $html;
    }
}

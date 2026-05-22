<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Application\Marketing\Services\ApplicationSeoService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MarketingLegalController extends Controller
{
    public function __construct(
        private ApplicationSeoService $seoService
    ) {}

    public function show(Request $request, string $page): Response
    {
        if ($request->attributes->get('storefront_shop')) {
            abort(404);
        }

        $pages = (array) config('marketing_legal.pages', []);
        if (!isset($pages[$page]) || !is_array($pages[$page])) {
            throw new NotFoundHttpException();
        }

        $def = $pages[$page];
        $slug = (string) ($def['slug'] ?? $page);
        $path = '/'.$slug;

        $company = (string) config('marketing_legal.company_name', 'OmniSolution');
        $email = (string) config('marketing_legal.contact_email', '');
        $country = (string) config('marketing_legal.country', '');
        $lastUpdated = (string) config('marketing_legal.last_updated', now()->toDateString());

        $sections = $def['sections'] ?? [];
        foreach ($sections as $i => $section) {
            if (!is_array($section)) {
                continue;
            }
            $paragraphs = $section['paragraphs'] ?? [];
            $replaced = [];
            foreach ($paragraphs as $p) {
                $replaced[] = str_replace(
                    [':company', ':email', ':country'],
                    [$company, $email, $country],
                    (string) $p
                );
            }
            $sections[$i]['paragraphs'] = $replaced;
        }

        if ($email !== '' && $page === 'legal') {
            foreach ($sections as $i => $section) {
                if (($section['heading'] ?? '') === 'Contact') {
                    $sections[$i]['paragraphs'][] = 'E-mail : '.$email;
                }
            }
        }

        return Inertia::render('Marketing/LegalDocument', [
            'pageKey' => $page,
            'document' => [
                'title' => (string) ($def['title'] ?? ''),
                'slug' => $slug,
                'lastUpdated' => $lastUpdated,
                'companyName' => $company,
                'contactEmail' => $email,
                'sections' => $sections,
            ],
            'pageSeo' => $this->seoService->buildPage($request, [
                'path' => $path,
                'title' => (string) ($def['title'] ?? '').' | '.config('marketing_legal.company_name', 'OmniSolution'),
                'description' => (string) ($def['meta_description'] ?? ''),
            ]),
        ]);
    }

}

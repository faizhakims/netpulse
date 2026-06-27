<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SemanticLinkHeader
{
    private const RDF_LINKS = [
        '/device'    => '/api/rdf/devices',
        '/dashboard' => '/api/rdf/devices',
        '/incidents' => '/api/rdf/incidents',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $contentType = $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'text/html')) {
            return $response;
        }

        $path   = $request->getPathInfo();
        $rdfUrl = $this->resolveRdfUrl($path);

        if ($rdfUrl === null) {
            return $response;
        }

        $baseUrl = $request->getSchemeAndHttpHost();

        $response->headers->set('Link', implode(', ', [
            "<{$baseUrl}{$rdfUrl}>; rel=\"alternate\"; type=\"application/ld+json\"",
            "<{$baseUrl}{$rdfUrl}?format=turtle>; rel=\"alternate\"; type=\"text/turtle\"",
            "<{$baseUrl}{$rdfUrl}?format=rdfxml>; rel=\"alternate\"; type=\"application/rdf+xml\"",
            "<{$baseUrl}/api/rdf/ontology>; rel=\"meta\"; type=\"text/turtle\"",
        ]));

        return $response;
    }

    private function resolveRdfUrl(string $path): ?string
    {
        foreach (self::RDF_LINKS as $prefix => $rdfUrl) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return $rdfUrl;
            }
        }

        return null;
    }
}

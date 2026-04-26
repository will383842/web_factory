<?php

declare(strict_types=1);

namespace App\Application\Marketing\Services;

use App\Application\Marketing\DTOs\SitemapEntry;

/**
 * Generates the XML sitemap (sitemap.xml) for a project.
 *
 * Includes hreflang alternates inline (xhtml:link) — required by Google
 * for proper multi-locale indexing of generated platforms (Spec 07 Multilingue).
 */
final class SitemapBuilder
{
    /**
     * @param list<SitemapEntry> $entries
     */
    public function buildXml(array $entries): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";

        foreach ($entries as $entry) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($entry->url, ENT_XML1).'</loc>'."\n";
            if ($entry->lastmod !== null) {
                $xml .= '    <lastmod>'.$entry->lastmod->format('c').'</lastmod>'."\n";
            }
            $xml .= '    <changefreq>'.$entry->changefreq.'</changefreq>'."\n";
            $xml .= '    <priority>'.number_format($entry->priority, 1).'</priority>'."\n";
            foreach ($entry->alternates as $locale => $altUrl) {
                $xml .= sprintf(
                    '    <xhtml:link rel="alternate" hreflang="%s" href="%s" />'."\n",
                    htmlspecialchars($locale, ENT_XML1),
                    htmlspecialchars($altUrl, ENT_XML1),
                );
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }
}

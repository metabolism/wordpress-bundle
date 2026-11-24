<?php

namespace Metabolism\WordpressBundle\Helper;

use StopWords\StopWords;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\DomCrawler\Crawler;

class TextHelper {

    public static function convertHtmlToCleanMarkdown(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        // 1. Chargement dans DOMDocument (natif PHP, encodage UTF-8 forcé)
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // 2. Supprimer les balises non éditoriales et les éléments masqués (accessibilité / modals / formulaires)
        $nodesToRemove = $xpath->query(
            '//script | //style | //svg | //form | //dialog | //nav | //picture | //iframe | //object | //embed | ' .
            '//*[@aria-hidden="true"] | //*[@hidden] | //*[@inert] | //*[@data-llm-ignore]  | //*[@data-nosnippet] | ' .
            '//*[contains(concat(" ", normalize-space(@class), " "), " sr-only ")] | ' .
            '//*[contains(concat(" ", normalize-space(@class), " "), " llm-ignore ")] | ' .
            '//*[contains(concat(" ", normalize-space(@class), " "), " no-index ")] | ' .
            '//*[contains(concat(" ", normalize-space(@class), " "), " screen-reader-text ")]'
        );
        foreach ($nodesToRemove as $node) {
            $node->parentNode?->removeChild($node);
        }

        // 3. Remplacer les <a href="#..."> (ancres locales de modales/menus) ou <a> sans href par leur texte brut
        foreach (iterator_to_array($xpath->query('//a[not(@href) or starts-with(@href, "#")]')) as $a) {
            $textNode = $dom->createTextNode($a->textContent);
            $a->parentNode?->replaceChild($textNode, $a);
        }

        // 4. S'assurer que les balises <time> ne se collent pas au texte suivant lors du strip_tags
        foreach ($xpath->query('//time') as $time) {
            $time->parentNode?->insertBefore($dom->createElement('br'), $time->nextSibling);
        }

        // 5. Gérer génériquement les cartes englobées dans un <a> et supprimer les liens vides (ex: ex-images)
        foreach (iterator_to_array($xpath->query('//a[@href]')) as $a) {
            if (trim($a->textContent) === '') {
                $a->parentNode?->removeChild($a);
                continue;
            }

            // Si le lien englobe un titre (h1 à h6), on ne lie que le titre et on déplie le reste du contenu
            $headings = $xpath->query('.//h1 | .//h2 | .//h3 | .//h4 | .//h5 | .//h6', $a);
            if ($headings->length > 0) {
                /** @var \DOMElement $heading */
                $heading = $headings->item(0);
                $newLink = $dom->createElement('a', htmlspecialchars(trim($heading->textContent)));
                $newLink->setAttribute('href', $a->getAttribute('href'));

                while ($heading->firstChild) {
                    $heading->removeChild($heading->firstChild);
                }
                $heading->appendChild($newLink);

                while ($a->firstChild) {
                    $a->parentNode?->insertBefore($a->firstChild, $a);
                }
                $a->parentNode?->removeChild($a);
            }
        }

        // 6. Conversion HTML -> Markdown
        $converter = new \League\HTMLToMarkdown\HtmlConverter([
            'header_style'    => 'atx',
            'strip_tags'      => true,
            'hard_break'      => false,
            'list_item_style' => '-',
        ]);

        $markdown = $converter->convert($dom->saveHTML());

        // 7. Nettoyage typographique générique
        $markdown = preg_replace('/\[\s*([^\]]+?)\s*\]\(/', '[$1](', $markdown); // Supprime les espaces dans [ texte ](url)
        $markdown = preg_replace('/[ \t]{2,}/', ' ', $markdown);                 // Réduit les espaces multiples sur une ligne
        $markdown = preg_replace('/^[ \t]+|[ \t]+$/m', '', $markdown);           // Supprime l'indentation résiduelle
        $markdown = preg_replace("/\n{3,}/", "\n\n", $markdown);                 // Max 1 ligne vide entre blocs
        $markdown = html_entity_decode($markdown, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return str_replace('<?xml encoding="utf-8" ?>', '', trim($markdown));
    }
}

<?php

namespace App\Libraries;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Limpia HTML enriquecido (TinyMCE) con una lista de etiquetas permitidas.
 *
 * - Solo se conservan las etiquetas de $allowedTags; las demás se quitan
 *   pero se conserva su texto (p. ej. <a>, <font>).
 * - <script>, <style>, <iframe>, <object>, etc. se eliminan con su contenido.
 * - Se eliminan todos los atributos excepto "class" (sin "javascript:" ni
 *   manejadores on*). No se permite "style" ni "href"/"src".
 * - Se eliminan los comentarios HTML.
 *
 * Usa solo DOMDocument (extensión dom), sin dependencias de Composer.
 */
class HtmlSanitizer
{
    /** Formato de texto y la estructura de columnas que usan las características. */
    public const RICH_TAGS = [
        'div', 'p', 'br', 'span', 'b', 'strong', 'i', 'em', 'u', 's', 'small',
        'ul', 'ol', 'li', 'h3', 'h4', 'h5', 'h6', 'hr', 'blockquote',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ];

    /** Contenido que se elimina por completo, incluido su texto. */
    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed',
        'applet', 'noscript', 'template', 'svg', 'math', 'form', 'textarea',
        'select', 'button', 'input', 'link', 'meta', 'base', 'title', 'head',
    ];

    private const ALLOWED_ATTRIBUTES = ['class'];

    public static function clean(?string $html, array $allowedTags = self::RICH_TAGS): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        $allowed = array_flip(array_map('strtolower', $allowedTags));

        $doc  = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        // El prefijo XML fuerza UTF-8; el contenedor permite recuperar el fragmento.
        $doc->loadHTML(
            '<?xml encoding="UTF-8"?><div id="__sanitize_root">' . $html . '</div>',
            LIBXML_NONET | LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = null;
        foreach ($doc->getElementsByTagName('div') as $div) {
            if ($div->getAttribute('id') === '__sanitize_root') {
                $root = $div;
                break;
            }
        }
        if ($root === null) {
            return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
        }

        self::cleanChildren($root, $allowed);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    private static function cleanChildren(DOMNode $node, array $allowed): void
    {
        // Copia: la lista cambia mientras se modifican los nodos
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                // Comentarios, instrucciones de procesamiento, etc.
                $node->removeChild($child);
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);
                continue;
            }

            self::cleanChildren($child, $allowed);

            if (! isset($allowed[$tag])) {
                // Quitar la etiqueta pero conservar su contenido ya limpio
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                if (! in_array($name, self::ALLOWED_ATTRIBUTES, true)) {
                    $child->removeAttribute($attr->nodeName);
                }
            }
        }
    }
}

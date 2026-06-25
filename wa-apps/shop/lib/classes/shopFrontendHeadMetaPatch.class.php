<?php

/**
 * Подставляет актуальные title / meta / og / canonical в HTML из full-page cache.
 */
class shopFrontendHeadMetaPatch
{
    /**
     * @param string $html
     * @param string|null $view_canonical canonical из Smarty {$canonical}
     * @return string
     */
    public static function apply($html, $view_canonical = null)
    {
        $response = wa()->getResponse();

        if ($view_canonical && !$response->getCanonical()) {
            $response->setMeta('canonical', (string) $view_canonical);
        }

        $title = (string) $response->getTitle();
        if ($title !== '') {
            $html = self::replaceOrInsertAfterTitle(
                $html,
                '/<title[^>]*>.*?<\/title>/is',
                '<title>'.self::escape($title).'</title>'
            );
        }

        // На cache-hit шаблон/SEO-плагин не отрабатывают, у response пустые meta.
        // Пустым значением НЕЛЬЗЯ затирать корректные meta, уже зашитые в кэше.
        $keywords = (string) $response->getMeta('keywords');
        if ($keywords !== '') {
            $html = self::patchMetaName($html, 'Keywords', $keywords);
        }
        $description = (string) $response->getMeta('description');
        if ($description !== '') {
            $html = self::patchMetaName($html, 'Description', $description);
        }

        $og = $response->getMeta('og');
        if (is_array($og)) {
            foreach ($og as $property => $content) {
                if ($content === '' || $content === null) {
                    continue;
                }
                $html = self::patchMetaProperty($html, (string) $property, (string) $content);
            }
        }

        $canonical = (string) $response->getCanonical();
        if ($canonical !== '') {
            $html = self::patchCanonicalLink($html, $canonical);
        }

        return $html;
    }

    protected static function patchMetaName($html, $name, $content)
    {
        $tag = '<meta name="'.$name.'" content="'.self::escape($content).'" />';
        $pattern = '/<meta name="'.preg_quote($name, '/').'" content="[^"]*"\s*\/?>/i';

        if (preg_match($pattern, $html)) {
            return preg_replace($pattern, $tag, $html, 1);
        }

        return self::insertAfterTitle($html, $tag);
    }

    protected static function patchMetaProperty($html, $property, $content)
    {
        $tag = '<meta property="'.self::escape($property).'" content="'.self::escape($content).'" />';
        $pattern = '/<meta property="'.preg_quote($property, '/').'" content="[^"]*"\s*\/?>/i';

        if (preg_match($pattern, $html)) {
            return preg_replace($pattern, $tag, $html, 1);
        }

        if (preg_match('/<meta name="Description" content="[^"]*"\s*\/?>/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            $pos = $matches[0][1] + strlen($matches[0][0]);

            return substr($html, 0, $pos)."\n    ".$tag.substr($html, $pos);
        }

        return self::insertAfterTitle($html, $tag);
    }

    protected static function patchCanonicalLink($html, $url)
    {
        $tag = '<link rel="canonical" href="'.self::escape($url).'" />';
        $pattern = '/<link rel="canonical" href="[^"]*"\s*\/?>/i';

        if (preg_match($pattern, $html)) {
            return preg_replace($pattern, $tag, $html, 1);
        }

        if (preg_match('/<meta name="Description" content="[^"]*"\s*\/?>/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            $pos = $matches[0][1] + strlen($matches[0][0]);

            return substr($html, 0, $pos)."\n    ".$tag.substr($html, $pos);
        }

        return self::insertAfterTitle($html, $tag);
    }

    protected static function insertAfterTitle($html, $tag)
    {
        return preg_replace('/(<title[^>]*>.*?<\/title>)/is', '$1'."\n    ".$tag, $html, 1);
    }

    protected static function replaceOrInsertAfterTitle($html, $pattern, $replacement)
    {
        if (preg_match($pattern, $html)) {
            return preg_replace($pattern, $replacement, $html, 1);
        }

        return self::insertAfterTitle($html, $replacement);
    }

    protected static function escape($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

<?php

/**
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Core\Tests\Unit\Html\Markdown;

use Ox\Core\Html\Markdown\ParsedownAdapter;
use Ox\Tests\UnitTestMediboard;

class ParsedownAdapterTest extends UnitTestMediboard
{
    public function testColoredText(): void
    {
        $colors = ['red', 'blue', 'green', 'orange'];
        shuffle($colors);
        $color = reset($colors);

        $text = "This is my {c:{$color}}colored{/c} text.";

        $expected = '<p>' . preg_replace('/{c:([#\w]\w+)}/', "<span style=\"color: {$color}\">", $text);
        $expected = preg_replace('/{\/c}/', "</span>", $expected) . '</p>';

        $md = new ParsedownAdapter();
        $md->enableColoredText();

        $html = $md->parse($text);

        $this->assertEquals($expected, $html);
    }

    /**
     * @param string $markdown
     * @param string $expected
     *
     * @dataProvider markdownProvider
     */
    public function testMarkdown(string $markdown, string $expected): void
    {
        $md = new ParsedownAdapter();
        $this->assertEquals($expected, $md->parse($markdown));
    }

    /**
     * Jeu de test pour les fonctionnalités de base Markdown
     *
     * @return array
     */
    public function markdownProvider(): array
    {
        return [
            'h1'         => ["#Titre de niveau 1#", "<h1>Titre de niveau 1</h1>"],
            'h2'         => ["##Titre de niveau 2##", "<h2>Titre de niveau 2</h2>"],
            'h3'         => ["###Titre de niveau 3###", "<h3>Titre de niveau 3</h3>"],
            'blockquote' => [
                "> Ceci est un bloc\n> sit amet\n\n> Second block",
                "<blockquote>\n<p>Ceci est un bloc\nsit amet</p>\n<p>Second block</p>\n</blockquote>",
            ],
            'ul1'        => ["* Rouge\n* Vert\n* Bleu", "<ul>\n<li>Rouge</li>\n<li>Vert</li>\n<li>Bleu</li>\n</ul>"],
            'ul2'        => ["+ Rouge\n+ Vert\n+ Bleu", "<ul>\n<li>Rouge</li>\n<li>Vert</li>\n<li>Bleu</li>\n</ul>"],
            'ul3'        => ["- Rouge\n- Vert\n- Bleu", "<ul>\n<li>Rouge</li>\n<li>Vert</li>\n<li>Bleu</li>\n</ul>"],
            'ol'         => ["1. Rouge\n2. Vert\n3. Bleu", "<ol>\n<li>Rouge</li>\n<li>Vert</li>\n<li>Bleu</li>\n</ol>"],
            'p'          => [
                "Ceci est un paragraphe normal :\n\tCeci est un bloc de code",
                "<p>Ceci est un paragraphe normal :\nCeci est un bloc de code</p>",
            ],
            'em1'        => ["*texte en italique*", "<p><em>texte en italique</em></p>"],
            'em2'        => ["_texte en italique_", "<p><em>texte en italique</em></p>"],
            'strong1'    => ["**texte en gras**", "<p><strong>texte en gras</strong></p>"],
            'strong2'    => ["__texte en gras__", "<p><strong>texte en gras</strong></p>"],
            'hr1'        => ["* * *", "<hr />"],
            'hr2'        => ["***", "<hr />"],
            'hr3'        => ["- - -", "<hr />"],
            'hr4'        => ["---------------", "<hr />"],
            'a1'         => [
                "Lien automatique http://example.com",
                "<p>Lien automatique <a href=\"http://example.com\">http://example.com</a></p>",
            ],
            'a2'         => [
                "crochets [http://example.com]",
                "<p>crochets [<a href=\"http://example.com\">http://example.com</a>]</p>",
            ],
            'a3'         => [
                "Entre accolades {http://example.com}",
                "<p>Entre accolades {<a href=\"http://example.com\">http://example.com</a>}</p>",
            ],
            'a4'         => [
                "Entre parentheses (http://example.com)",
                "<p>Entre parentheses (<a href=\"http://example.com\">http://example.com</a>)</p>",
            ],
            'img'        => [
                "![Texte alternatif](images/icons/edit.png)",
                "<p><img src=\"images/icons/edit.png\" alt=\"Texte alternatif\" /></p>",
            ],
            'code'       => [
                "Utilisez la fonction `printf()` pour afficher.",
                "<p>Utilisez la fonction <code>printf()</code> pour afficher.</p>",
            ],
        ];
    }
}

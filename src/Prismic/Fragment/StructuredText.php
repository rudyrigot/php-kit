<?php
/**
 * This file is part of the Prismic PHP SDK
 *
 * Copyright 2013 Zengularity (http://www.zengularity.com).
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prismic\Fragment;

use Prismic\Fragment\Block\EmbedBlock;
use Prismic\Fragment\Block\HeadingBlock;
use Prismic\Fragment\Block\ImageBlock;
use Prismic\Fragment\Block\ListItemBlock;
use Prismic\Fragment\Block\ParagraphBlock;
use Prismic\Fragment\Block\PreformattedBlock;
use Prismic\Fragment\Block\TextInterface;
use Prismic\Fragment\Link\DocumentLink;
use Prismic\Fragment\Link\FileLink;
use Prismic\Fragment\Link\ImageLink;
use Prismic\Fragment\Link\WebLink;
use Prismic\Fragment\Span\EmSpan;
use Prismic\Fragment\Span\HyperlinkSpan;
use Prismic\Fragment\Span\StrongSpan;

/**
 * This class embodies a StructuredText fragment.
 *
 * Technically, a StructuredText fragment is not much more than an array of blocks,
 * but there are many things to do with this fragment, including in the HTML serialization,
 * but not only. It is arguably the most powerful and manipulable way any CMS stores
 * structured text nowadays.
 */
class StructuredText implements FragmentInterface
{
    /**
     * @var array  the array of Prismic\Fragment\Block\BlockInterface objects
     */
    private $blocks;

    /**
     * Constructs a StructuredText object.
     *
     * @param array    $blocks   the array of \Prismic\Fragment\Block\BlockInterface objects
     */
    public function __construct($blocks)
    {
        $this->blocks = $blocks;
    }

    /**
     * Returns the array of blocks.
     *
     * @api
     *
     * @return array the array of \Prismic\Fragment\Block\BlockInterface objects
     */
    public function getBlocks()
    {
        return $this->blocks;
    }

    /**
     * Builds a text version of the StructuredText fragment.
     *
     * @api
     *
     * @return string the text version of the StructuredText fragment
     */
    public function asText()
    {
        $result = array_map(function ($block) {
            return $block instanceof TextInterface ? $block->getText() : '';
        }, $this->blocks);

        return join("\n\n", $result);
    }

    /**
     * Returns the first preformatted block in the StructuredText fragment.
     *
     * @api
     *
     * @return \Prismic\Fragment\Block\PreformattedBlock the first preformatted block in the StructuredText fragment
     */
    public function getFirstPreformatted()
    {
        $blocks = $this->getPreformatted();

        return reset($blocks);
    }

    /**
     * Returns an array of all preformatted blocks in the StructuredText fragment,
     * as \Prismic\Fragment\Block\PreformattedBlock objects.
     *
     * @api
     *
     * @return array all preformatted blocks in the StructuredText fragment
     */
    public function getPreformatted()
    {
        return array_filter($this->blocks, function ($block) {
            return ($block instanceof PreformattedBlock);
        });
    }

    /**
     * Returns the first paragraph block in the StructuredText fragment.
     *
     * @api
     *
     * @return \Prismic\Fragment\Block\ParagraphBlock the first paragraph block in the StructuredText fragment
     */
    public function getFirstParagraph()
    {
        $blocks = $this->getParagraphs();

        return reset($blocks);
    }

    /**
     * Returns an array of all paragraph blocks in the StructuredText fragment,
     * as \Prismic\Fragment\Block\ParagraphBlock objects.
     *
     * @api
     *
     * @return array all paragraph blocks in the StructuredText fragment
     */
    public function getParagraphs()
    {
        return array_filter($this->blocks, function ($block) {
            return ($block instanceof ParagraphBlock);
        });
    }

    /**
     * Returns the first image block in the StructuredText fragment.
     *
     * @api
     *
     * @return \Prismic\Fragment\Block\ImageBlock the first image block in the StructuredText fragment
     */
    public function getFirstImage()
    {
        $blocks = $this->getImages();

        return reset($blocks);
    }

    /**
     * Returns an array of all image blocks in the StructuredText fragment,
     * as \Prismic\Fragment\Block\ImageBlock objects.
     *
     * @api
     *
     * @return array all image blocks in the StructuredText fragment
     */
    public function getImages()
    {
        return array_filter($this->blocks, function ($block) {
            return ($block instanceof ImageBlock);
        });
    }

    /**
     * Returns the first heading block in the StructuredText fragment.
     *
     * @api
     *
     * @return \Prismic\Fragment\Block\HeadingBlock the first heading block in the StructuredText fragment
     */
    public function getFirstHeading()
    {
        $blocks = $this->getHeadings();

        return reset($blocks);
    }

    /**
     * Returns an array of all heading blocks in the StructuredText fragment,
     * as \Prismic\Fragment\Block\HeadingBlock objects.
     *
     * @api
     *
     * @return array all heading blocks in the StructuredText fragment
     */
    public function getHeadings()
    {
        return array_filter($this->blocks, function ($block) {
            return ($block instanceof HeadingBlock);
        });
    }

    /**
     * Builds a HTML version of the StructuredText fragment.
     *
     * @api
     *
     * @param \Prismic\LinkResolver $linkResolver the link resolver
     *
     * @return string the HTML version of the StructuredText fragment
     */
    public function asHtml($linkResolver = null)
    {
        $groups = array();
        foreach ($this->blocks as $block) {
            $count = count($groups);
            if ($count > 0) {
                $lastOne = $groups[$count - 1];
                if ('ul' == $lastOne->getTag() && ($block instanceof ListItemBlock) && !$block->isOrdered()) {
                    $lastOne->addBlock($block);
                } elseif ('ol' == $lastOne->getTag() && ($block instanceof ListItemBlock) && $block->isOrdered()) {
                    $lastOne->addBlock($block);
                } elseif (($block instanceof ListItemBlock) && !$block->isOrdered()) {
                    $newBlockGroup = new BlockGroup("ul", array());
                    $newBlockGroup->addBlock($block);
                    array_push($groups, $newBlockGroup);
                } else {
                    if (($block instanceof ListItemBlock) && $block->isOrdered()) {
                        $newBlockGroup = new BlockGroup("ol", array());
                        $newBlockGroup->addBlock($block);
                        array_push($groups, $newBlockGroup);
                    } else {
                        $newBlockGroup = new BlockGroup(null, array());
                        $newBlockGroup->addBlock($block);
                        array_push($groups, $newBlockGroup);
                    }
                }
            } else {
                $tag = (($block instanceof ListItemBlock) && !$block->isOrdered() ? "ul" : (($block instanceof ListItemBlock) && $block->isOrdered() ? "li" : null ));
                $newBlockGroup = new BlockGroup($tag, array());
                $newBlockGroup->addBlock($block);
                array_push($groups, $newBlockGroup);
            }
        }
        $html = "";
        foreach ($groups as $group) {
            $maybeTag = $group->getTag();
            if (isset($maybeTag)) {
                $html = $html . "<" . $group->getTag() . ">";
                foreach ($group->getBlocks() as $block) {
                    $html = $html . StructuredText::asHtmlBlock($block, $linkResolver);
                }
                $html = $html . "</" . $group->getTag() . ">";
            } else {
                foreach ($group->getBlocks() as $block) {
                    $html = $html . StructuredText::asHtmlBlock($block, $linkResolver);
                }
            }
        }

        return $html;
    }

    /**
     * Transforms a block into HTML (for internal use)
     *
     * @param \Prismic\Fragment\Block\BlockInterface  $block         a given block
     * @param \Prismic\LinkResolver                   $linkResolver  the link resolver
     *
     * @return string the HTML version of the block
     */
    public static function asHtmlBlock($block, $linkResolver = null)
    {
        if ($block instanceof HeadingBlock) {
            return nl2br('<h' . $block->getLevel() . '>' .
                    StructuredText::asHtmlText($block->getText(), $block->getSpans(), $linkResolver) .
                    '</h' . $block->getLevel() . '>');
        } elseif ($block instanceof ParagraphBlock) {
            return nl2br('<p>' .
                   StructuredText::asHtmlText($block->getText(), $block->getSpans(), $linkResolver) . '</p>');
        } elseif ($block instanceof ListItemBlock) {
            return nl2br('<li>' .
                   StructuredText::asHtmlText($block->getText(), $block->getSpans(), $linkResolver) . '</li>');
        } elseif ($block instanceof ImageBlock) {
            return nl2br('<p>' . $block->getView()->asHtml($linkResolver) . '</p>');
        } elseif ($block instanceof EmbedBlock) {
            return nl2br($block->getObj()->asHtml());
        } elseif ($block instanceof PreformattedBlock) {
            return '<pre>' .
                   StructuredText::asHtmlText($block->getText(), $block->getSpans(), $linkResolver) .
                   '</pre>';
        }

        return "";
    }

    /**
     * Transforms a text block into HTML (for internal use)
     *
     * @param string                 $text          the raw text of the block
     * @param array                  $spans         the spans of the block, as an array of \Prismic\Fragment\Span\SpanInterface objects
     * @param \Prismic\LinkResolver  $linkResolver  the link resolver
     *
     * @return string the HTML version of the block
     */
    public static function asHtmlText($text, $spans, $linkResolver = null)
    {
        if (empty($spans)) {
            return htmlentities($text);
        }

        $doc = new \DOMDocument;
        $doc->appendChild($doc->createTextNode($text));

        $iterateChildren = function ($node, $start, $span) use (&$iterateChildren, $linkResolver) {
            // Get length of node's text content
            $nodeLength = mb_strlen($node->textContent);

            // If this is a text node we have found the right node
            if ($node instanceof \DOMText) {
                if ($span->getEnd() - $span->getStart() > $nodeLength) {
                    // The span is too long for the node -- we have improperly
                    // nested spans
                    //throw new \Exception("Improperly nested span of type " . get_class($span) . " starting at offset {$span->getStart()}");
                    return;
                }

                // Split the text node into a head, meat and tail
                $meat = $node->splitText($span->getStart() - $start);
                $tail = $meat->splitText($span->getEnd() - $span->getStart());

                // Decide element type and attributes based on span class
                $attributes = array();
                if ($span instanceof StrongSpan) {
                    $nodeName = 'strong';
                } elseif ($span instanceof EmSpan) {
                    $nodeName = 'em';
                } elseif ($span instanceof HyperlinkSpan) {
                    $nodeName = 'a';
                    if ($span->getLink() instanceof DocumentLink) {
                        $attributes['href'] = $linkResolver ? $linkResolver($span->getLink()) : '';
                    } else {
                        $attributes['href'] = $span->getLink()->getUrl();
                    }
                    if ($attributes['href'] === null) {
                        // We have no link (LinkResolver said it is not valid,
                        // or something else went wrong). Abort this span.
                        return;
                    }
                } else {
                    //throw new \Exception("Unknown span type " . get_class($span));
                    $nodeName = 'span';
                }

                // Make the new span element, and put the text from the meat
                // inside
                $spanNode = $node->ownerDocument->createElement($nodeName, htmlspecialchars($meat->textContent));
                foreach ($attributes as $k => $v) {
                    $spanNode->setAttribute($k, $v);
                }

                // Replace the original meat text node with the span
                $meat->parentNode->replaceChild($spanNode, $meat);

                return;
            }

            // Skip this node if the span start is beyond it
            if ($span->getStart() >= $start + mb_strlen($node->textContent)) {
                return;
            }

            // Loop over child nodes to find the correct one
            if ($node->childNodes) {
                foreach ($node->childNodes as $child) {
                    $nodeLength = mb_strlen($child->textContent);
                    if ($span->getStart() < $start + $nodeLength) {
                        // This is the right node -- recurse
                        return $iterateChildren($child, $start, $span);
                    }
                    $start += $nodeLength;
                }
            }

            // Not found
            return;
        };

        foreach ($spans as $span) {
            if ($span->getEnd() < $span->getStart()) {
                //throw new \Exception("Span of type " . get_class($span) . " starting at {$span->getStart()} ends at {$span->getEnd()} (before it begins)");
                continue;
            }
            $iterateChildren($doc, 0, $span);
        }

        return trim($doc->saveHTML());

    }

    /**
     * Parses a given span (for internal use)
     *
     * @param  \stdClass  $json the json bit retrieved from the API that represents a span.
     *
     * @return \Prismic\Fragment\Span\SpanInterface  the manipulable object for that span.
     */
    public static function parseSpan($json)
    {
        $type = $json->type;
        $start = $json->start;
        $end = $json->end;

        if ("strong" == $type) {
            return new StrongSpan($start, $end);
        }

        if ("em" == $type) {
            return new EmSpan($start, $end);
        }

        if ("hyperlink" == $type && ($link = self::extractLink($json->data))) {
            return new HyperlinkSpan($start, $end, $link);
        }

        return null;
    }

    /**
     * Parses a given text block (for internal use)
     *
     * @param  \stdClass  $json the json bit retrieved from the API that represents a text block.
     *
     * @return \Prismic\Fragment\ParsedText  the parsed information for that text block.
     */
    public static function parseText($json)
    {
        $text = $json->text;
        $spans = array();
        foreach ($json->spans as $spanJson) {
            $span = StructuredText::parseSpan($spanJson);
            if (isset($span)) {
                array_push($spans, $span);
            }
        }

        return new ParsedText($text, $spans);
    }

    /**
     * Parses a given block (for internal use)
     *
     * @param  \stdClass  $json the json bit retrieved from the API that represents a block.
     *
     * @return \Prismic\Fragment\Block\BlockInterface  the manipulable object for that block.
     */
    public static function parseBlock($json)
    {
        if ($json->type == 'heading1') {
            $p = StructuredText::parseText($json);

            return new HeadingBlock($p->getText(), $p->getSpans(), 1);
        }

        if ($json->type == 'heading2') {
            $p = StructuredText::parseText($json);

            return new HeadingBlock($p->getText(), $p->getSpans(), 2);
        }

        if ($json->type == 'heading3') {
            $p = StructuredText::parseText($json);

            return new HeadingBlock($p->getText(), $p->getSpans(), 3);
        }

        if ($json->type == 'heading4') {
            $p = StructuredText::parseText($json);

            return new HeadingBlock($p->getText(), $p->getSpans(), 4);
        }

        if ($json->type == 'paragraph') {
            $p = StructuredText::parseText($json);

            return new ParagraphBlock($p->getText(), $p->getSpans());
        }

        if ($json->type == 'list-item') {
            $p = StructuredText::parseText($json);

            return new ListItemBlock($p->getText(), $p->getSpans(), false);
        }

        if ($json->type == 'o-list-item') {
            $p = StructuredText::parseText($json);

            return new ListItemBlock($p->getText(), $p->getSpans(), true);
        }

        if ($json->type == 'image') {
            $view = ImageView::parse($json);

            return new ImageBlock($view);
        }

        if ($json->type == 'embed') {
            return new EmbedBlock(Embed::parse($json));
        }

        if ($json->type == 'preformatted') {
            return new PreformattedBlock($json->text, $json->spans);
        }

        return null;
    }

    /**
     * Parses a given StructuredText fragment (for internal use)
     *
     * @param  \stdClass  $json the json bit retrieved from the API that represents a StructuredText fragment.
     *
     * @return \Prismic\Fragment\StructuredText  the manipulable object for that StructuredText fragment.
     */
    public static function parse($json)
    {
        $blocks = array();
        foreach ($json as $blockJson) {
            $maybeBlock = StructuredText::parseBlock($blockJson);
            if (isset($maybeBlock)) {
                array_push($blocks, $maybeBlock);
            }
        }

        return new StructuredText($blocks);
    }

    /**
     * Parses and extracts a link of absolutely any kind.
     *
     * @param  \stdClass $data the json bit retrieved from the API that represents a Link fragment.
     *
     * @return \Prismic\Fragment\Link\LinkInterface  the manipulable object for that Link fragment.
     */
    public static function extractLink($data)
    {
        switch ($data->type) {
            case 'Link.web':
                return WebLink::parse($data->value);
            case 'Link.document':
                return DocumentLink::parse($data->value);
            case 'Link.file';
                return FileLink::parse($data->value);
            case 'Link.image';
                return ImageLink::parse($data->value);
            default:
                return null;
        }
    }

}

<?php
/**
 * Modifier_striptags
 * Strip tags from a variable
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_striptags extends Modifier
{
    private $all_tags = array(
        "a",
        "abbr",
        "acronym",
        "address",
        "applet",
        "area",
        "article",
        "aside",
        "audio",
        "b",
        "base",
        "basefont",
        "bdi",
        "bdo",
        "big",
        "blockquote",
        "body",
        "br",
        "button",
        "canvas",
        "caption",
        "center",
        "cite",
        "code",
        "col",
        "colgroup",
        "command",
        "data",
        "datagrid",
        "datalist",
        "dd",
        "del",
        "details",
        "dfn",
        "dir",
        "div",
        "dl",
        "dt",
        "em",
        "embed",
        "eventsource",
        "fieldset",
        "figcaption",
        "figure",
        "font",
        "footer",
        "form",
        "frame",
        "frameset",
        "h1",
        "h2",
        "h3",
        "h4",
        "h5",
        "h6",
        "head",
        "header",
        "hgroup",
        "hr",
        "html",
        "i",
        "iframe",
        "img",
        "input",
        "isindex",
        "ins",
        "kbd",
        "keygen",
        "label",
        "legend",
        "li",
        "link",
        "main",
        "mark",
        "map",
        "menu",
        "meta",
        "meter",
        "nav",
        "noframes",
        "noscript",
        "object",
        "ol",
        "optgroup",
        "option",
        "output",
        "p",
        "param",
        "pre",
        "progress",
        "q",
        "ruby",
        "rp",
        "rt",
        "s",
        "samp",
        "script",
        "section",
        "select",
        "small",
        "source",
        "span",
        "strike",
        "strong",
        "style",
        "sub",
        "summary",
        "sup",
        "table",
        "tbody",
        "td",
        "textarea",
        "tfoot",
        "th",
        "thead",
        "time",
        "title",
        "tr",
        "track",
        "tt",
        "u",
        "ul",
        "var",
        "video",
        "wbr"
    );

    public function index($value, $parameters=array()) {
        $tags_to_strip = (isset($parameters[0])) ? $parameters[0] : "";

        if ($tags_to_strip) {
            // because PHP's strip_tags()'s second parameter is tags that should
            // *stay* rather than be stripped, we have to work backwards from a
            // master list of HTML 4 and 5 known tags
            $allowed_tags = $this->all_tags;
            $tags_array = explode(",", $tags_to_strip);

            // tags were passed, we need to strip those out
            if (count($tags_array)) {
                $allowed_tags = array_diff($this->all_tags, $tags_array);
            }

            // create string of allowed tags
            $allowed_tag_string = "<" . join("><", $allowed_tags) . ">";

            return strip_tags($value, $allowed_tag_string);
        }

        return strip_tags($value);
    }
}
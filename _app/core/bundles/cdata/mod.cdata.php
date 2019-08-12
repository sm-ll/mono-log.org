<?php
/**
 * Modifier_cdata
 * Wraps a value in CDATA tags (great for RSS feeds)
 *
 * @author  Jack McDade
 * @author  Fred LeBlanc
 * @author  Mubashar Iqbal
 */
class Modifier_cdata extends Modifier
{
    public function index($value, $parameters=array()) {
        return "<![CDATA[" . $value . "]]>";
    }
}
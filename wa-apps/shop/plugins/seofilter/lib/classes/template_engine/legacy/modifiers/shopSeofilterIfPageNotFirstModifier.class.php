<?php

class shopSeofilterIfPageNotFirstModifier extends shopSeofilterModifier
{
    public function modify($source)
    {
        if (waRequest::get('page', '1') != '1')
        {
            return $source;
        }
        else
        {
            return '';
        }
    }
}
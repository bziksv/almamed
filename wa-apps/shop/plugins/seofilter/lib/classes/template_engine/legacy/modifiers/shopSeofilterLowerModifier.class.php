<?php

class shopSeofilterLowerModifier extends shopSeofilterModifier
{
    public function modify($source)
    {
        return mb_strtolower($source);
    }
}
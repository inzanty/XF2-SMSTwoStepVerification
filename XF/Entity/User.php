<?php

namespace INZ\SMSTfa\XF\Entity;

use XF\Mvc\Entity\Structure;

class User extends XFCP_User
{
    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->columns['inztfa_phone_number'] = ['type' => self::STR, 'default' => ''];

        return $structure;
    }
}
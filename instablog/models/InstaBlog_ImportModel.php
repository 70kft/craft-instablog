<?php

namespace Craft;

class InstaBlog_ImportModel extends BaseModel
{
    /**
     * Filetypes.
     */
    const TypeXML       = 'text/xml';
    const TypeXMLApp    = 'application/xml';

    const AssetDestination = '1';
    const Backup = 'backup';

    /**
     * Use model validation to validate filetype.
     *
     * @return array
     */
    protected function defineAttributes()
    {
        return array(
            'filetype' => array(AttributeType::Enum,
                'required' => true,
                'label' => Craft::t('Filetype'),
                'values' => array(
                    self::TypeXML,
                    self::TypeXMLApp,
                ),
            ),
        );
    }
}
<?php
/**
 * @package plugins.varConsole
 * @subpackage model.filters
 *
 */
class KalturaVarConsolePartnerFilter extends KalturaPartnerFilter
{
    /**
     * @var KalturaPartnerGroupType
     */
    public $groupTypeEq;
    
    /**
     * @var string
     */
    public $groupTypeIn;
    
    private $map_between_objects = array
    (
    	"groupTypeEq" => "_eq_partner_group_type",
        "groupTypeIn" => "_in_partner_group_type",
    );
}
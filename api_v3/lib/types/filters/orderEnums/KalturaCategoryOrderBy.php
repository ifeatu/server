<?php
/**
 * @package api
 * @subpackage filters.enum
 */
class KalturaCategoryOrderBy extends KalturaStringEnum
{
	const DEPTH_ASC = "+depth";
	const DEPTH_DESC = "-depth";
	const NAME_ASC = "+name";
	const NAME_DESC = "-name";
	const ENTRIES_COUNT_ASC = "+entriesCount";
	const ENTRIES_COUNT_DESC = "-entriesCount";
	const CREATED_AT_ASC = "+createdAt";
	const CREATED_AT_DESC = "-createdAt";
	const UPDATED_AT_ASC = "+updatedAt";
	const UPDATED_AT_DESC = "-updatedAt";
	const DIRECT_ENTRIES_COUNT_ASC = "+directEntriesCount";
	const DIRECT_ENTRIES_COUNT_DESC = "-directEntriesCount";
	const PARTNER_SORT_VALUE_ASC = "+partnerSortValue";
	const PARTNER_SORT_VALUE_DESC = "-partnerSortValue";
}

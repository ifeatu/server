<?php
/**
 * @package plugins.tvinciDistribution
 * @subpackage lib
 */
class TvinciDistributionFeedHelper
{
	const DATE_FORMAT = 'd/m/Y H:i:s';

	const ACTION_SUBMIT = 'insert';
	const ACTION_UPDATE = 'update';
	const ACTION_DELETE = 'delete';

	/**
	 * var KalturaTvinciDistributionProfile
	 */
	protected $distributionProfile;

	/**
	 * @var array
	 */
	protected $fieldValues;

	/**
	 * var string
	 */
	protected $entryId;

	/**
	 * var string
	 */
	protected $createdAt;

	/**
	 * var string
	 */
	protected $broadcasterName;

	/**
	 * var array
	 */
	protected $picRatiosArray;

	/**
	 * var string
	 */
	protected $defaultThumbUrl;

	/**
	 * var string
	 */
	protected $mainPlayManifestUrl;
	
	/**
	 * var string
	 */
	protected $iPadPlayManifestUrl;
	
	/**
	 * var string
	 */
	protected $iPhonePlayManifestUrl;
	
	/**
	 * var string
	 */
	protected $language;

	/**
	 * @var DOMDocument
	 */
	protected $_doc;

	public function __construct(KalturaTvinciDistributionProfile $distributionProfile, $fieldValues)
	{
		$this->distributionProfile = $distributionProfile;
		$this->fieldValues = $fieldValues;
		$this->language = $fieldValues[TvinciDistributionField::LANGUAGE];
	}

	public function setEntryId( $entryId )						{ $this->entryId = $entryId; }
	public function getEntryId()								{ return $this->entryId; }

	public function setCreatedAt( $createdAt )					{ $this->createdAt = $createdAt; }
	public function getCreatedAt()								{ return $this->createdAt; }

	public function setBroadcasterName( $broadcasterName )		{ $this->broadcasterName = $broadcasterName; }
	public function getBroadcasterName()						{ return $this->broadcasterName; }

	public function setPicRatiosArray( $picRatiosArray )		{ $this->picRatiosArray = $picRatiosArray; }
	public function getPicRatiosArray()							{ return $this->picRatiosArray; }

	public function setDefaultThumbnailUrl( $defaultThumbUrl )	{ $this->defaultThumbUrl = $defaultThumbUrl; }
	public function getDefaultThumbnailUrl()					{ return $this->defaultThumbUrl; }

	public function setMainPlayManifestUrl( $url )				{ $this->mainPlayManifestUrl = $url; }
	public function getMainPlayManifestUrl()					{ return $this->mainPlayManifestUrl; }

	public function setiPadPlayManifestUrl( $url )				{ $this->iPadPlayManifestUrl = $url; }
	public function getiPadPlayManifestUrl()					{ return $this->iPadPlayManifestUrl; }

	public function setiPhonePlayManifestUrl( $url )			{ $this->iPhonePlayManifestUrl = $url; }
	public function getiPhonePlayManifestUrl()					{ return $this->iPhonePlayManifestUrl; }

	public function buildSubmitFeed()
	{
		return $this->createXml( self::ACTION_SUBMIT );
	}

	public function buildUpdateFeed()
	{
		return $this->createXml( self::ACTION_UPDATE );
	}

	public function buildDeleteFeed()
	{
		return $this->createXml( self::ACTION_DELETE );
	}

	private function createXml($action)
	{
		// Init the document
		$this->_doc = new DOMDocument();
		$this->_doc->formatOutput = true;
		$this->_doc->encoding = "UTF-8";

		// Build the feed
		$feed = $this->_doc->createElement('feed');
		$feed->setAttribute('broadcasterName', $this->broadcasterName);

		$export = $this->_doc->createElement('export');
		$feed->appendChild($export);

		$media = $this->_doc->createElement('media');
		$export->appendChild($media);

		$this->setAttribute($media, "co_guid", $this->entryId);
		$this->setAttribute($media, "action", $action );

 		if ( $action != self::ACTION_DELETE ) // No need for the following content in case of a delete scenario
		{
			$isActive = $this->fieldValues[TvinciDistributionField::ACTIVATE_PUBLISHING];
			$this->setAttribute($media, "is_active", $isActive);

			$media->appendChild( $this->createBasicElement() );
			$media->appendChild( $this->createStructureElement() );
			$media->appendChild( $this->createFilesElement() );
		}

		// Wrap as a CDATA section
		$feedAsXml = $this->_doc->saveXML($feed);
		$data = $this->_doc->createElement('data');
		$data->appendChild($this->_doc->createCDATASection($feedAsXml));

		// Create the document's root node
		$feederRootNode = $this->_doc->createElement('Feeder');
		$feederRootNode->appendChild($this->_doc->createElement('userName', $this->distributionProfile->username));
		$feederRootNode->appendChild($this->_doc->createElement('passWord', $this->distributionProfile->password));

		// Attach the CDATA section
		$feederRootNode->appendChild($data);

		// Attach the root node to the document
		$this->_doc->appendChild($feederRootNode);

		return $this->getXml();
	}

	/**
	 * Result XML:
	 * 	<$name>$value</$name>
	 */
	protected function createValueElement($name, $arr, $key)
	{
		$value = array_key_exists($key, $arr) ? $arr[$key] : "";
		$node = $this->_doc->createElement($name, $value);
		return $node;
	}

	/**
	 * Result XML:
	 * 	<$name>
	 * 		<value lang="$lang">$value</value>
	 * 	</$name>
	 */
	protected function createValueWithLangElement($name, $value, $lang)
	{
		$valueNode = $this->_doc->createElement('value', $value);
		$this->setAttribute($valueNode, "lang", $lang);

		$namedNode = $this->_doc->createElement($name);
		$namedNode->appendChild($valueNode);

		return $namedNode;
	}

	/**
	 * Result XML:
	 * 	<$name>
	 * 		<value lang="$lang">$value</value>
	 * 	</$name>
	 */
	protected function createValueWithLangElementFromAssocArray($name, $lang, array $arr, $key)
	{
		$value = array_key_exists($key, $arr) ? $arr[$key] : "";

		return $this->createValueWithLangElement($name, $value, $lang);
	}

	private function createDateElement($fieldName, $timestamp)
	{
		$formattedDate = date(self::DATE_FORMAT, $timestamp);
		$dateNode = $this->_doc->createElement($fieldName, $formattedDate);
		return $dateNode;
	}

	private function createDateElementFromAssocArray($fieldName, array $arr, $key)
	{
		$timestamp = $arr[$key];
		$dateNode = $this->createDateElement($fieldName, $timestamp);
		return $dateNode;
	}

	private function createMetadataElement($name, array $arr, $key)
	{
		$metaNode = $this->createValueElement("meta", $arr, $key);

		$this->setAttribute($metaNode, "name", $name);

		return $metaNode;
	}

	private function createMetadataWithLangElement($name, $lang, array $arr, $key)
	{
		$metaNode = $this->createValueWithLangElementFromAssocArray("meta", $lang, $arr, $key);

		$this->setAttribute($metaNode, "name", $name);
		$this->setAttribute($metaNode, "ml_handling", "unique");

		return $metaNode;
	}

	private function createMetadataContainerWithLangElement($name, $lang, array $arr, $key)
	{
		$multivalField = $arr[$key];
		$multivalArr = explode(',', $multivalField);

		$metaNode = $this->_doc->createElement('meta');
		$this->setAttribute($metaNode, "name", $name);
		$this->setAttribute($metaNode, "ml_handling", "unique");

		foreach ( $multivalArr as $val )
		{
			$metaNode->appendChild( $this->createValueWithLangElement('container', $val, $lang) );
		}

		return $metaNode;
	}

	private function createBasicElement()
	{
		$basicNode = $this->_doc->createElement("basic");

		$basicNode->appendChild( $this->createValueWithLangElementFromAssocArray('name', $this->language, $this->fieldValues, TvinciDistributionField::MEDIA_TITLE) );
		$basicNode->appendChild( $this->createValueWithLangElementFromAssocArray('description', $this->language, $this->fieldValues, TvinciDistributionField::MEDIA_DESCRIPTION) );
		$basicNode->appendChild( $this->createValueElement('media_type', $this->fieldValues, TvinciDistributionField::MEDIA_TYPE) );

		// Add default thumbnail
		if ( isset($this->defaultThumbUrl) )
		{
			$thumbnail = $this->_doc->createElement("thumb");
			$this->setAttribute($thumbnail, "url", $this->defaultThumbUrl);
			$basicNode->appendChild( $thumbnail );
		}

		$basicNode->appendChild( $this->createRulesElement() );
		$basicNode->appendChild( $this->createDatesElement() );
		$basicNode->appendChild( $this->createPicRatiosElement() );

		return $basicNode;
	}

	private function createRulesElement()
	{
		$rules = $this->_doc->createElement("rules");

		$rules->appendChild( $this->createValueElement('geo_block_rule', $this->fieldValues, TvinciDistributionField::GEO_BLOCK_RULE) );
		$rules->appendChild( $this->createValueElement('watch_per_rule', $this->fieldValues, TvinciDistributionField::WATCH_PERMISSIONS_RULE) );

		return $rules;
	}

	private function createDatesElement()
	{
		$dates = $this->_doc->createElement("dates");

		$dates->appendChild( $this->createDateElement('create', $this->createdAt) );
		$dates->appendChild( $this->createDateElementFromAssocArray('start', $this->fieldValues, TvinciDistributionField::START_DATE) );
		$dates->appendChild( $this->createDateElementFromAssocArray('catalog_start', $this->fieldValues, TvinciDistributionField::CATALOG_START_DATE) );
		$dates->appendChild( $this->createDateElementFromAssocArray('catalog_end', $this->fieldValues, TvinciDistributionField::CATALOG_END_DATE) );
		$dates->appendChild( $this->createDateElementFromAssocArray('final_end', $this->fieldValues, TvinciDistributionField::END_DATE) );

 		return $dates;
	}

	private function createPicRatiosElement()
	{
		$picRatiosNode = $this->_doc->createElement("pic_ratios");

		$picRatiosArray = $this->picRatiosArray;
		foreach ( $picRatiosArray as $picRatio )
		{
			$ratioNode = $this->_doc->createElement("ratio");
			$this->setAttribute($ratioNode, "thumb", $picRatio['url']);
			$this->setAttribute($ratioNode, "ratio", $picRatio['ratio']);
			$picRatiosNode->appendChild( $ratioNode );
		}

		return $picRatiosNode;
	}

	private function createStructureElement()
	{
		$structure = $this->_doc->createElement("structure");

 		$structure->appendChild( $this->createStringsElement() );
 		$structure->appendChild( $this->createBooleansElement() );
 		$structure->appendChild( $this->createDoublesElement() );
 		$structure->appendChild( $this->createMetasElement() );

 		return $structure;
	}

	private function createStringsElement()
	{
		$strings = $this->_doc->createElement("strings");

		$strings->appendChild( $this->createMetadataWithLangElement('Runtime', $this->language, $this->fieldValues, TvinciDistributionField::METADATA_RUNTIME) );
		$strings->appendChild( $this->createMetadataWithLangElement('Release date', $this->language, $this->fieldValues, TvinciDistributionField::METADATA_RELEASE_DATE) );

		return $strings;
	}

	private function createBooleansElement()
	{
		$booleans = $this->_doc->createElement("booleans");

		return $booleans;
	}

	private function createDoublesElement()
	{
		$doubles = $this->_doc->createElement("doubles");

		$doubles->appendChild( $this->createMetadataElement('Release year', $this->fieldValues, TvinciDistributionField::METADATA_RUNTIME) );

		return $doubles;
	}

	private function createMetasElement()
	{
		$metas = $this->_doc->createElement("metas");

		$metas->appendChild( $this->createMetadataContainerWithLangElement('Rating', $this->language, $this->fieldValues, TvinciDistributionField::METADATA_RATING) );
		$metas->appendChild( $this->createMetadataContainerWithLangElement('Country', $this->language, $this->fieldValues, TvinciDistributionField::METADATA_COUNTRY) );
		$metas->appendChild( $this->createMetadataContainerWithLangElement('Director', $this->language, $this->fieldValues, TvinciDistributionField::METADATA_DIRECTOR) );
		$metas->appendChild( $this->createMetadataContainerWithLangElement('Audio language', $this->language, $this->fieldValues, TvinciDistributionField::METADATA_AUDIO_LANGUAGE) );
		$metas->appendChild( $this->createMetadataContainerWithLangElement('Genre', $this->language, $this->fieldValues, TvinciDistributionField::METADATA_GENRE) );
		$metas->appendChild( $this->createMetadataContainerWithLangElement('Sub genre', $this->language, $this->fieldValues, TvinciDistributionField::METADATA_SUB_GENRE) );
		$metas->appendChild( $this->createMetadataContainerWithLangElement('Studio', $this->language, $this->fieldValues, TvinciDistributionField::METADATA_STUDIO) );
		$metas->appendChild( $this->createMetadataContainerWithLangElement('Cast', $this->language, $this->fieldValues, TvinciDistributionField::METADATA_CAST) );

		return $metas;
	}

	private function createFilesElement()
	{
		$files = $this->_doc->createElement("files");

		$files->appendChild( $this->createFileElement('Main', $this->getMainPlayManifestUrl()) );
		$files->appendChild( $this->createFileElement('Tablet Main', $this->getiPadPlayManifestUrl()) );
		$files->appendChild( $this->createFileElement('Smartphone Main', $this->getiPhonePlayManifestUrl()) );

 		return $files;
	}

	private function createFileElement($fileType, $url)
	{
		$fileNode = $this->_doc->createElement("file");

		$this->setAttribute($fileNode, "type", $fileType);
		$this->setAttribute($fileNode, "quality", "HIGH");
		$this->setAttribute($fileNode, "handling_type", "CLIP");
		$this->setAttribute($fileNode, "cdn_name", "Default CDN");
		$this->setAttribute($fileNode, "cdn_code", $url);
		$this->setAttribute($fileNode, "co_guid", $fileType);

		return $fileNode;
	}

	private function setAttribute($node, $attribName, $attribValue)
	{
		$node->setAttribute($attribName, htmlspecialchars($attribValue, ENT_COMPAT, 'UTF-8')); // ENT_COMPAT to leave single-quotes as is
	}

	public function __toString()
	{
		return $this->_doc->saveXML();
	}

	public function getXml()
	{
		return $this->_doc->saveXML();
	}
}
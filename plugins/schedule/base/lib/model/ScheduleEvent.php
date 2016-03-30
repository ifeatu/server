<?php

/**
 * Skeleton subclass for representing a row from the 'schedule_event' table.
 *
 * 
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package plugins.schedule
 * @subpackage model
 */
abstract class ScheduleEvent extends BaseScheduleEvent implements IRelatedObject, IIndexable
{
	const CUSTOM_DATA_FIELD_RECURRENCE = 'recurrence';
	const CUSTOM_DATA_FIELD_ORGANIZER_PUSER_ID = 'organizerPuserId';

	public function __construct() 
	{
		parent::__construct();
		$this->applyDefaultValues();
	}

	/**
	 * Applies default values to this object.
	 * This method should be called from the object's constructor (or equivalent initialization method).
	 * @see __construct()
	 */
	public function applyDefaultValues()
	{
		$this->setSequence(1);
	}
	
	/* (non-PHPdoc)
	 * @see BaseScheduleEvent::preInsert()
	 */
	public function preInsert(PropelPDO $con = null)
	{
		$this->setStatus(ScheduleEventStatus::ACTIVE);
		$this->setPartnerId(kCurrentContext::getCurrentPartnerId());
		
		if(!$this->getParentId())
		{
			$this->setOrganizerPuserId(kCurrentContext::$ks_uid);
			$this->incrementSequence();
		}
		
		if($this->getRecurrenceType() != ScheduleEventRecurrenceType::RECURRENCE)
		{
			if(is_null($this->getClassificationType()))
			{
				$this->setClassificationType(ScheduleEventClassificationType::PUBLIC_EVENT);
			}
		}
		$this->setCustomDataObj();
    	
		return parent::preInsert($con);
	}
	
	/* (non-PHPdoc)
	 * @see BaseScheduleEvent::preSave()
	 */
	public function preSave(PropelPDO $con = null)
	{
		if($this->getRecurrenceType() != ScheduleEventRecurrenceType::RECURRING && $this->getDuration() != $this->getEndDate(null) - $this->getStartDate(null))
		{
			$this->setDuration($this->getEndDate(null) - $this->getStartDate(null));
		}
    	
		return parent::preSave($con);
	}
	
	public function incrementSequence()
	{
		$this->setSequence(kDataCenterMgr::incrementVersion($this->getSequence()));
	}
	
	/**
	 * @param string $v
	 */
	protected function setOrganizerPuserId($puserId)
	{
		$kuser = kuserPeer::createKuserForPartner(kCurrentContext::getCurrentPartnerId(), $puserId, kCurrentContext::$is_admin_session);
		$this->setOrganizerKuserId($kuser->getId());
		$this->putInCustomData(self::CUSTOM_DATA_FIELD_ORGANIZER_PUSER_ID, $puserId);
	}
	
	/**
	 * @return string
	 */
	public function getOrganizerPuserId()
	{
		return $this->getFromCustomData(self::CUSTOM_DATA_FIELD_ORGANIZER_PUSER_ID);
	}
	
	/**
	 * @param array<kScheduleEventRecurrence> $v
	 */
	public function setRecurrences(array $v)
	{
		$this->putInCustomData(self::CUSTOM_DATA_FIELD_RECURRENCE, $v);
	}
	
	/**
	 * @return array<kScheduleEventRecurrence>
	 */
	public function getRecurrences()
	{
		return $this->getFromCustomData(self::CUSTOM_DATA_FIELD_RECURRENCE);
	}
	
	/**
	 * Returns a list of timestamps in the specified period.
	 * @param int $periodStart the starting timestamp of the period
	 * @param int $periodEnd the ending timestamp of the period
	 * @return array
	 */
	public function getDates($periodStart = null, $periodEnd = null, $limit = null)
	{
		if($this->getRecurrenceType() == ScheduleEventRecurrenceType::NONE)
		{
			return array($this->getStartDate(null));
		}
		
		if(!$periodStart)
		{
			$periodStart = time();
		}
		if(!$periodEnd)
		{
			$periodEnd = strtotime('+1 year', $periodStart);
		}
		if(!$limit)
		{
			$limit = SchedulePlugin::getScheduleEventmaxRecurrences();
		}
		
		$recurrences = $this->getRecurrences();
		$dates = array();
		foreach($recurrences as $recurrence)
		{
			/* @var $recurrence kScheduleEventRecurrence */
			$dates = array_merge($dates, $recurrence->getDates($periodStart, $periodEnd, $this->getStartDate(null), $limit));
		}
		
		sort($dates);
		if(count($dates) > $limit)
		{
			$dates = array_slice($dates, 0, $limit);
		}
		
		return $dates;
	}


	/**
	 * {@inheritDoc}
	 * @see IIndexable::getIntId()
	 */
	public function getIntId()
	{
		return $this->getId();
	}
	
	/**
	 * {@inheritDoc}
	 * @see IIndexable::getEntryId()
	 */
	public function getEntryId()
	{
		return null;
	}
	
	/**
	 * {@inheritDoc}
	 * @see IIndexable::getIndexObjectName()
	 */
	public function getIndexObjectName()
	{
		return 'ScheduleEventIndex';
	}
	
	/**
	 * {@inheritDoc}
	 * @see IIndexable::indexToSearchIndex()
	 */
	public function indexToSearchIndex()
	{
		kEventsManager::raiseEventDeferred(new kObjectReadyForIndexEvent($this));
	}
	
} // ScheduleEvent

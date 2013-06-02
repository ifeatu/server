<?php

require_once(dirname(__FILE__).'/../bootstrap.php');

if ($argc < 5)
	die("Usage: " . basename(__FILE__) . " <partner id> <source dc> <target dc> <max number of jobs to move>\n");

// input parameters
$partnerId = $argv[1];
$sourceDc = $argv[2];
$targetDc = $argv[3];
$maxMovedJobs = $argv[4];

// constants
$jobType = BatchJobType::CONVERT;
$jobSubType = conversionEngineType::PDF_CREATOR;
$jobStatus = BatchJob::BATCHJOB_STATUS_PENDING;
define('TEMP_JOB_STATUS', 5000);

function getAllReadyInternalFileSyncsForKey(FileSyncKey $key)
{
	$c = new Criteria();
	$c = FileSyncPeer::getCriteriaForFileSyncKey( $key );
	$c->addAnd ( FileSyncPeer::FILE_TYPE , FileSync::FILE_SYNC_FILE_TYPE_FILE);
	$c->addAnd ( FileSyncPeer::STATUS , FileSync::FILE_SYNC_STATUS_READY );
	$results = FileSyncPeer::doSelect( $c );
	
	$assocResults = array();
	foreach ($results as $curResult)
	{
		$assocResults[$curResult->getDc()] = $curResult; 
	}
	return $assocResults;
}

function lockJob($object)
{
	global $jobStatus;
	
	$con = Propel::getConnection();
	
	$lock_version = $object->getVersion() ;
	$criteria_for_exclusive_update = new Criteria();
	$criteria_for_exclusive_update->add(BatchJobLockPeer::ID, $object->getId());
	$criteria_for_exclusive_update->add(BatchJobLockPeer::VERSION, $lock_version);
	$criteria_for_exclusive_update->add(BatchJobLockPeer::STATUS, $jobStatus);
	
	$update = new Criteria();
	
	// increment the lock_version - this will make sure it's exclusive
	$update->add(BatchJobLockPeer::VERSION, $lock_version + 1);
	$update->add(BatchJobLockPeer::STATUS, TEMP_JOB_STATUS);
	
	$affectedRows = BasePeer::doUpdate( $criteria_for_exclusive_update, $update, $con);	
	if ( $affectedRows != 1 )
		return false;
	
	// update $object with what is in the database
	$object->setVersion($lock_version + 1);
	$object->setStatus(TEMP_JOB_STATUS);
	return true;
}

// get candidates for move
$c = new Criteria();
$c->add(BatchJobLockPeer::PARTNER_ID, $partnerId);
$c->add(BatchJobLockPeer::DC, $sourceDc);
$c->add(BatchJobLockPeer::JOB_TYPE, $jobType);
$c->add(BatchJobLockPeer::JOB_SUB_TYPE, $jobSubType);
$c->add(BatchJobLockPeer::STATUS, $jobStatus);
$c->add(BatchJobLockPeer::SCHEDULER_ID, null, Criteria::ISNULL);
$c->add(BatchJobLockPeer::WORKER_ID, null, Criteria::ISNULL);
$c->add(BatchJobLockPeer::BATCH_INDEX, null, Criteria::ISNULL);
$c->setLimit($maxMovedJobs);
$jobLocks = BatchJobLockPeer::doSelect($c, myDbHelper::getConnection(myDbHelper::DB_HELPER_CONN_PROPEL2));

foreach ($jobLocks as $jobLock)
{
	/* @var $jobLock BatchJobLock */
	/* @var $job BatchJob */
	$job = $jobLock->getBatchJob(myDbHelper::getConnection(myDbHelper::DB_HELPER_CONN_PROPEL2));
	
	// check whether the job can be moved
	$jobData = $job->getData();
	/* @var $jobData kConvartableJobData */
	$srcFileSyncs = $jobData->getSrcFileSyncs();
	if (count($srcFileSyncs) != 1)
		continue;		// unexpected - multiple sources for doc convert
	$srcFileSync = reset($srcFileSyncs);
	/* @var $srcFileSync kSourceFileSyncDescriptor */
	$sourceAsset = assetPeer::retrieveById($srcFileSync->getAssetId());
	if (!$sourceAsset)
		continue;		// unexpected - source flavor asset not found
	$sourceSyncKey = $sourceAsset->getSyncKey(asset::FILE_SYNC_FLAVOR_ASSET_SUB_TYPE_ASSET);
	$sourceFileSyncs = getAllReadyInternalFileSyncsForKey($sourceSyncKey);
	if (!isset($sourceFileSyncs[$sourceDc]) || 
		$sourceFileSyncs[$sourceDc]->getFullPath() != $srcFileSync->getFileSyncLocalPath())
		continue;		// unexpected - no file sync for source dc, or the path does not match the job data
	if (!isset($sourceFileSyncs[$targetDc]))
		continue;		// source file was not synced to target dc yet
	
	// lock the job to prevent any changes to it while it's being moved
	if (!lockJob($jobLock))
		continue;		// failed to lock the job
	
	// update batch job
	$srcFileSync->setFileSyncLocalPath($sourceFileSyncs[$targetDc]->getFullPath());
	$srcFileSync->setFileSyncRemoteUrl($sourceFileSyncs[$targetDc]->getExternalUrl($sourceAsset->getEntryId()));
	$jobData->setSrcFileSyncs(array($srcFileSync));
	$job->setData($jobData);
	$job->setDc($targetDc);
	
	// update batch job lock
	$jobLock->setStatus($jobStatus);
	$jobLock->setDc($targetDc);

	// save
	$job->save();
	$jobLock->save();
	
	echo 'Moved job '.$job->getId()."\n";
}

<?php
/**
 * Edge Server service
 *
 * @service edgeServer
 * @package api
 * @subpackage services
 */
class EdgeServerService extends KalturaBaseService
{
	public function initService($serviceId, $serviceName, $actionName)
	{
		parent::initService($serviceId, $serviceName, $actionName);
		
		$partnerId = $this->getPartnerId();
		if(!$this->getPartner()->getEnabledService(PermissionName::FEATURE_EDGE_SERVER))
			throw new KalturaAPIException(KalturaErrors::SERVICE_FORBIDDEN, $this->serviceName.'->'.$this->actionName);
			
		$this->applyPartnerFilterForClass('edgeServer');
	}
	
	/**
	 * Adds a edge server to the Kaltura DB.
	 *
	 * @action add
	 * @param KalturaEdgeServer $edgeServer sto
	 * @return KalturaEdgeServer
	 */
	function addAction(KalturaEdgeServer $edgeServer)
	{	
		if(!$edgeServer->status)
			$edgeServer->status = KalturaEdgeServerStatus::DISABLED; 
		
		$dbEdgeServer = $edgeServer->toInsertableObject();
		$dbEdgeServer->setPartnerId($this->getPartnerId());
		$dbEdgeServer->save();
		
		$edgeServer = new KalturaEdgeServer();
		$edgeServer->fromObject($dbEdgeServer, $this->getResponseProfile());
		return $edgeServer;
	}
	
	/**
	 * Get edge server by id
	 * 
	 * @action get
	 * @param int $edgeServerId
	 * @throws KalturaErrors::INVALID_OBJECT_ID
	 * @return KalturaEdgeServer
	 */
	function getAction($edgeServerId)
	{
		$dbEdgeServer = EdgeServerPeer::retrieveByPK($edgeServerId);
		if (!$dbEdgeServer)
			throw new KalturaAPIException(KalturaErrors::INVALID_OBJECT_ID, $edgeServerId);
		
		$edgeServer = new KalturaEdgeServer();
		
		$edgeServer->fromObject($dbEdgeServer, $this->getResponseProfile());
		return $edgeServer;
	}
	
	/**
	 * Update edge server by id 
	 * 
	 * @action update
	 * @param int $edgeServerId
	 * @param KalturaEdgeServer $edgeServer
	 * @return KalturaEdgeServer
	 */
	function updateAction($edgeServerId, KalturaEdgeServer $edgeServer)
	{
		$dbEdgeServer = EdgeServerPeer::retrieveByPK($edgeServerId);
		if (!$dbEdgeServer)
			throw new KalturaAPIException(KalturaErrors::INVALID_OBJECT_ID, $edgeServerId);
			
		$dbEdgeServer = $edgeServer->toUpdatableObject($dbEdgeServer);
		$dbEdgeServer->save();
		
		$edgeServer = new KalturaEdgeServer();
		$edgeServer->fromObject($dbEdgeServer, $this->getResponseProfile());
		return $edgeServer;
	}
	
	/**
	 * delete edge server by id
	 *
	 * @action delete
	 * @param string $edgeServerId
	 * @throws KalturaErrors::INVALID_OBJECT_ID
	 */
	function deleteAction($edgeServerId)
	{
		$dbEdgeServer = EdgeServerPeer::retrieveByPK($edgeServerId);
		if(!$dbEdgeServer)
			throw new KalturaAPIException(KalturaErrors::INVALID_OBJECT_ID, $edgeServerId);
	
		$dbEdgeServer->setStatus(EdgeServerStatus::DELETED);
		$dbEdgeServer->save();
	}
	
	/**	
	 * @action list
	 * @param KalturaEdgeServerFilter $filter
	 * @param KalturaFilterPager $pager
	 * @return KalturaEdgeServerListResponse
	 */
	public function listAction(KalturaEdgeServerFilter $filter = null, KalturaFilterPager $pager = null)
	{
		$c = new Criteria();
		
		if (!$filter)
			$filter = new KalturaEdgeServerFilter();
		
		$edgeSeverFilter = new EdgeServerFilter(); 
		$filter->toObject($edgeSeverFilter);
		$edgeSeverFilter->attachToCriteria($c);
		$list = EdgeServerPeer::doSelect($c);
			
		if (!$pager)
			$pager = new KalturaFilterPager();
			
		$pager->attachToCriteria($c);
		
		$response = new KalturaEdgeServerListResponse();
		$response->totalCount = EdgeServerPeer::doCount($c);
		$response->objects = KalturaEdgeServerArray::fromDbArray($list, $this->getResponseProfile());
		return $response;
	}
	
	/**
	 * Update Edge server status
	 *
	 * @action reportStatus
	 * @param string $hostName
	 * @param KalturaMediaServerStatus $mediaServerStatus
	 * @return KalturaEdgeServer
	 */
	function reportStatusAction($hostName)
	{
		$dbEdgeServer = EdgeServerPeer::retrieveByPartnerIdAndHostName($this->getPartnerId(), $hostName);
		if(!$dbEdgeServer)
			throw new KalturaAPIException(KalturaErrors::EDGE_SERVER_NOT_FOUND, $hostName);
		
		$dbEdgeServer->setHeartbeatTime(time());
		$dbEdgeServer->save();
		
		$edgeServer = new KalturaEdgeServer();
		$edgeServer->fromObject($dbEdgeServer, $this->getResponseProfile());
		return $edgeServer;
	}
}

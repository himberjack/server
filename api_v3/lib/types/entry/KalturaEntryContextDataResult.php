<?php
/**
 * @package api
 * @subpackage objects
 */
class KalturaEntryContextDataResult extends KalturaObject
{
	/**
	 * @var bool
	 */
	public $isSiteRestricted;
	
	/**
	 * @var bool
	 */
	public $isCountryRestricted;
	
	/**
	 * @var bool
	 */
	public $isSessionRestricted;
	
	/**
	 * @var bool
	 */
	public $isIpAddressRestricted;
	
	/**
	 * @var bool
	 */
	public $isUserAgentRestricted;
	
	/**
	 * @var int
	 */
	public $previewLength;
	
	/**
	 * @var bool
	 */
	public $isScheduledNow;
	
	/**
	 * @var bool
	 */
	public $isAdmin;
	
	/**
	 * @var string
	 */
	public $streamerType;
	
	/**
	 * @var string
	 */
	public $mediaProtocol;
}
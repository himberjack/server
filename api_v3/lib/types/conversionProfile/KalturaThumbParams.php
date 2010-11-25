<?php
class KalturaThumbParams extends KalturaAssetParams 
{
	/**
	 * @var KalturaThumbCropType
	 */
	public $cropType = 1;
	
	/**
	 * @var int
	 */
	public $quality = 100;
	
	/**
	 * @var int
	 */
	public $cropX = 0;
	
	/**
	 * @var int
	 */
	public $cropY = 0;
	
	/**
	 * @var int
	 */
	public $cropWidth = 0;
	
	/**
	 * @var int
	 */
	public $cropHeight = 0;
	
	/**
	 * @var int
	 */
	public $videoOffset = 3;
	
	/**
	 * @var int
	 */
	public $width = 0;
	
	/**
	 * @var int
	 */
	public $height = 0;
	
	/**
	 * @var int
	 */
	public $widthScale = 0;
	
	/**
	 * @var int
	 */
	public $heightScale = 0;
	
	/**
	 * Hexadecimal value
	 * @var string
	 */
	public $backgroundColor = 0;
	
//	Maybe support will be added in the future
//	
//	/**
//	 * @var KalturaCropProvider
//	 */
//	public $cropProvider;
//	
//	/**
//	 * @var KalturaCropProviderData
//	 */
//	public $cropProviderData;

	
	private static $map_between_objects = array
	(
		"cropType",
		"quality",
		"cropX",
		"cropY",
		"cropWidth",
		"cropHeight",
		"videoOffset",
		"width",
		"height",
		"widthScale",
		"heightScale",
		"backgroundColor",
	
//		Maybe support will be added in the future
//		"cropProvider",
//		"cropProviderData",
	);
	
	public function getMapBetweenObjects()
	{
		return array_merge(parent::getMapBetweenObjects(), self::$map_between_objects);
	}
	
	public function getExtraFilters()
	{
		return array();
	}
	
	public function getFilterDocs()
	{
		return array();
	}
}
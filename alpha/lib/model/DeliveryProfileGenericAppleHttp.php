<?php

class DeliveryProfileGenericAppleHttp extends DeliveryProfileAppleHttp {
	
	public function setPattern($v)
	{
		$this->putInCustomData("pattern", $v);
	}
	public function getPattern()
	{
		return $this->getFromCustomData("pattern");
	}
	
	public function setRendererClassParam($v)
	{
		$this->putInCustomData("rendererClass", $v);
	}
	
	public function getRendererClassParam()
	{
		return $this->getFromCustomData("rendererClass");
	}
	
	protected function getRendererClass() {
		$rendererClass = $this->getRendererClassParam();
		if($rendererClass)
			return $rendererClass;
		return $this->DEFAULT_RENDERER_CLASS;
	}
	
	protected function doGetFlavorAssetUrl(flavorAsset $flavorAsset) 
	{
		$url = $this->getBaseUrl($flavorAsset);
		if ($this->params->getFileExtension())
			$url .= "/name/a." . $this->params->getFileExtension();
		
		return kDeliveryUtils::formatGenericUrl($url, $this->getPattern(), $this->params);
	}
	
	protected function doGetFileSyncUrl(FileSync $fileSync)
	{
		$url = parent::doGetFileSyncUrl($fileSync);
		$pattern = $this->getPattern();
		if(is_null($pattern))
			$pattern = '/hls-vod/{url}.m3u8';
		return kDeliveryUtils::formatGenericUrl($url, $pattern, $this->params);
	}
}


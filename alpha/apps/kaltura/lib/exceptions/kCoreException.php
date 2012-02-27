<?php
/**
 * @FIXME - refactor the current error codes to another exception class which will inherit from kCoreException
 */
class kCoreException extends Exception
{
	public function __construct($message, $code)
	{
		KalturaLog::err('Message: '. $message .' \nCode: '.$code);
		$this->message = $message;
		$this->code = $code;
	}
	
	const INVALID_QUERY = "INVALID_QUERY";
	
	const INVALID_ENUM_FORMAT = "INVALID_ENUM_FORMAT";
	
	const DUPLICATE_CATEGORY = "DUPLICATE_CATEGORY";
	
	const PARENT_ID_IS_CHILD = "PARENT_ID_IS_CHILD";
	
	const MAX_NUMBER_OF_ACCESS_CONTROLS_REACHED = "MAX_NUMBER_OF_ACCESS_CONTROLS_REACHED";
	
	const MAX_NUMBER_OF_CATEGORIES_REACHED = "MAX_NUMBER_OF_CATEGORIES_REACHED";
	
	const MAX_CATEGORY_DEPTH_REACHED = "MAX_CATEGORY_DEPTH_REACHED";
	
	const MAX_CATEGORIES_PER_ENTRY = "MAX_CATEGORIES_PER_ENTRY";
	
	const INTERNAL_SERVER_ERROR = "INTERNAL_SERVER_ERROR";
	
	const OBJECT_TYPE_NOT_FOUND = "OBJECT_TYPE_NOT_FOUND";
	
	const SOURCE_FILE_NOT_FOUND = "SOURCE_FILE_NOT_FOUND";
	
	const FILE_NOT_FOUND = "FILE_NOT_FOUND";
	
	const ACCESS_CONTROL_CANNOT_DELETE_PARTNER_DEFAULT = "ACCESS_CONTROL_CANNOT_DELETE_PARTNER_DEFAULT";
	
	const ACCESS_CONTROL_CANNOT_DELETE_USED_PROFILE = "ACCESS_CONTROL_CANNOT_DELETE_USED_PROFILE";
}
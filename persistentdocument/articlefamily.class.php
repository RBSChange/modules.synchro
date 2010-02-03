<?php
/**
 * synchro_persistentdocument_articlefamily
 * @package modules.synchro
 */
class synchro_persistentdocument_articlefamily extends synchro_persistentdocument_articlefamilybase
{
	/**
	 * Returns the literal path of the article family in its parent families.
	 * @param String $separator
	 * @example "Top level family/Second level/The family"
	 * @return String
	 */
	public function getPath($separator = '/')
	{
		return synchro_ArticlefamilyService::getInstance()->getPath($this, $separator);
	}
}
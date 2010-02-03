<?php
/**
 * @package modules.synchro.lib.services
 */
class synchro_ModuleService extends ModuleBaseService
{
	/**
	 * Singleton
	 * @var synchro_ModuleService
	 */
	private static $instance = null;

	/**
	 * @return synchro_ModuleService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * @param Integer $documentId
	 * @return f_persistentdocument_PersistentTreeNode
	 */
//	public function getParentNodeForPermissions($documentId)
//	{
//		// Define this method to handle permissions on a virtual tree node. Example available in list module.
//	}
		
	/**
	 * @param f_persistentdocument_PersistentDocument $document
	 * @return f_persistentdocument_PersistentDocument or null
	 */
	public function getVirtualParentForBackoffice($document)
	{
		if ($document instanceof synchro_persistentdocument_article)
		{
			return $document->getFamily();
		}
		else if ($document instanceof synchro_persistentdocument_articlefamily)
		{
			$parent = $document->getDocumentService()->getParentFamily($document);
			if ($parent === null)
			{
				$parent = DocumentHelper::getDocumentInstance(ModuleService::getInstance()->getRootFolderId('synchro'));
			}
			return $parent;
		}
		else if ($document instanceof catalog_persistentdocument_price)
		{
			return $document->getArticle();
		}
		else if ($document instanceof catalog_persistentdocument_stock)
		{
			return $document->getArticle();
		}
		return null;
	}
}
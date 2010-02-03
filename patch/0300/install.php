<?php
/**
 * synchro_patch_0300
 * @package modules.synchro
 */
class synchro_patch_0300 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		parent::execute();
		
		$afs = synchro_ArticlefamilyService::getInstance();
		$ms = ModuleService::getInstance();
		$ts = TreeService::getInstance();
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$tm = f_persistentdocument_TransactionManager::getInstance();
		
		// -- Update database structure.
		$this->log('Update database structure...');
		
		// Rename tables.
		$this->executeSQLQuery("RENAME TABLE m_catalog_doc_article TO m_synchro_doc_article;");
		$this->executeSQLQuery("RENAME TABLE m_catalog_doc_article_i18n TO m_synchro_doc_article_i18n;");
		$this->executeSQLQuery("UPDATE m_synchro_doc_article SET document_model = 'modules_synchro/article' WHERE document_model = 'modules_catalog/article';");
		
		$this->executeSQLQuery("RENAME TABLE m_catalog_doc_articlefamily TO m_synchro_doc_articlefamily;");
		$this->executeSQLQuery("UPDATE m_synchro_doc_article SET document_model = 'modules_synchro/articlefamily' WHERE document_model = 'modules_catalog/articlefamily';");
		
		// Update f_document and f_relation.
		$this->executeSQLQuery("UPDATE f_document SET document_model = 'modules_synchro/article' WHERE document_model = 'modules_catalog/article';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id1 = 'modules_synchro/article' WHERE document_model_id1 = 'modules_catalog/article';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id2 = 'modules_synchro/article' WHERE document_model_id2 = 'modules_catalog/article';");
		
		$this->executeSQLQuery("UPDATE f_document SET document_model = 'modules_synchro/articlefamily' WHERE document_model = 'modules_catalog/articlefamily';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id1 = 'modules_synchro/articlefamily' WHERE document_model_id1 = 'modules_catalog/articlefamily';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id2 = 'modules_synchro/articlefamily' WHERE document_model_id2 = 'modules_catalog/articlefamily';");
		
		// Move root article families to synchro rootfolder.
		$this->log('Move root article families to synchro rootfolder...');
		$catalogRootId = $ms->getRootFolderId('catalog');
		$synchroRootNode = $ts->getInstanceByDocument(DocumentHelper::getDocumentInstance($ms->getRootFolderId('synchro')));
		foreach ($afs->createQuery()->add(Restrictions::childOf($catalogRootId))->find() as $articleFamily)
		{
			$ts->deleteNode($ts->getInstanceByDocument($articleFamily));
			$ts->newLastChildForNode($synchroRootNode, $articleFamily->getId());
		}
		
		// Import stock values from stock document to article one.
		$this->executeSQLQuery("ALTER TABLE `m_synchro_doc_article` ADD `stocklevel` varchar(255);");
		$this->executeSQLQuery("ALTER TABLE `m_synchro_doc_article` ADD `stockquantity` float;");
		$statement = $pp->executeSQLSelect('SELECT `article` , `level` , `quantity` FROM `m_catalog_doc_stock`');
		$statement->execute();
		$result = $statement->fetchAll();
		$this->log('Import stock values from stock document to article one... (' . count($result) . ' rows)');
		foreach ($result as $row)
		{
			$article = DocumentHelper::getDocumentInstance($row['article']);
			$article->setStockQuantity($row['quantity']);
			$article->setStockLevel($row['level']);
			$article->save();
		}
		$this->executeSQLQuery("DELETE FROM f_relation WHERE relation_name = 'stock' AND document_model_id1 = 'modules_catalog/stock';");
		$this->executeSQLQuery("DELETE FROM f_document WHERE document_model = 'modules_catalog/stock';");
		$this->executeSQLQuery("DROP TABLE m_catalog_doc_stock;");
	}

	/**
	 * Returns the name of the module the patch belongs to.
	 *
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'synchro';
	}

	/**
	 * Returns the number of the current patch.
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0300';
	}
}
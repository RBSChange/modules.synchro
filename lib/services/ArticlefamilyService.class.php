<?php
/**
 * synchro_ArticlefamilyService
 * @package modules.synchro
 */
class synchro_ArticlefamilyService extends f_persistentdocument_DocumentService
{
	/**
	 * Singleton
	 * @var synchro_ArticlefamilyService
	 */
	private static $instance = null;

	/**
	 * @return synchro_ArticlefamilyService
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
	 * @return synchro_persistentdocument_articlefamily
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_synchro/articlefamily');
	}

	/**
	 * Create a query based on 'modules_synchro/articlefamily' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_synchro/articlefamily');
	}

	/**
	 * Returns the literal path of the article family in its parent families.
	 *
	 * @example "Top level family/Second level/The family"
	 * @param synchro_persistentdocument_articlefamily $family
	 * @param String $pathSeparator
	 * @return String
	 */
	public function getPath($family, $pathSeparator = '/')
	{
		$path = array();
		if ( ! is_null($family) )
		{
			while ($family = synchro_ArticlefamilyService::getInstance()->createQuery()
				->add(Restrictions::eq('subFamily.id', $family->getId()))
				->findUnique())
			{
				$path[] = $family->getLabel();
			}
			return join($pathSeparator, array_reverse($path));
		}
		return '';
	}

	/**
	 * @param synchro_persistentdocument_articlefamily $family
	 * @return String
	 */
	public function getParentFamily($family)
	{
		return synchro_ArticlefamilyService::getInstance()->createQuery()
			->add(Restrictions::eq('subFamily.id', $family->getId()))
			->findUnique();
	}

	/**
	 * @param synchro_persistentdocument_articlefamily $document
	 * @return Array<String=>String>
	 */
	public function getPreviewAttributes($document)
	{
		$data = array();
		$data['location'] = $document->getPath(' / ');
		return $data;
	}

	/**
	 * @param synchro_persistentdocument_articlefamily $document
	 * @return void
	 */
	protected function preDelete($document)
	{
		$result = synchro_ArticleService::getInstance()->createQuery()
			->add(Restrictions::eq('family.id', $document->getId()))
			->setProjection(Projections::rowCount('count'))
			->findUnique();
		if ($result['count'] > 0 || $document->getSubFamilyCount() > 0)
		{
			throw new catalog_Exception("Could not delete a non-empty article family.");
		}
	}
}
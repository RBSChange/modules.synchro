<?php
/**
 * synchro_persistentdocument_article
 * @package modules.synchro
 */
class synchro_persistentdocument_article extends synchro_persistentdocument_articlebase
{
	/**
	 * Returns the literal path of the article in its parent families.
	 * @param String $separator
	 * @example "Top level family/Second level/Articles"
	 * @return String
	 */
	public function getFamilyPath($separator = '/')
	{
		return synchro_ArticleService::getInstance()->getArticleFamilyPath($this, $separator);
	}

	/**
	 * Returns the stock availability of the article.
	 * @param Double $quantity
	 * @return Boolean
	 */
	public function isAvailable($quantity = 1)
	{
		return synchro_ArticleService::getInstance()->isAvailable($this, $quantity);
	}

	/**
	 * @return Boolean
	 */
	public function isOrderable()
	{
		return synchro_ArticleService::getInstance()->isOrderable($this);
	}

	/**
	 * @return Boolean
	 */
	public function formatLabelForResourceTree()
	{
		return $this->getDocumentService()->formatLabelForResourceTree($this);
	}
	
	/**
	 * @return Boolean
	 */
	public function isNewTraduction()
	{
		$i18nDocument = $this->getI18nObject();
		return $i18nDocument->isNew();
	}
}
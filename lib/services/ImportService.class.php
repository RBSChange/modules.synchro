<?php
class catalog_ImportService extends BaseService
{
	/**
	 * Singleton
	 * @var ImportService
	 */
	private static $instance = null;

	/**
	 * Default tax code is '1'.
	 * @var String
	 */
	private $taxCode = 1;

	/**
	 * @var zone_persistentdocument_zone
	 */
	private $zone;

	/**
	 * @return catalog_ImportService
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
	 * Creates a new article or updates an existing article, uniquely identified
	 * by its referenceCode property.
	 *
	 * @param String $label
	 * @param String $referenceCode
	 * @param synchro_persistentdocument_articlefamilly $family
	 * @param String $genCode
	 * @param String $saleUnit
	 * @return synchro_persistentdocument_article
	 */
	public function createArticle($label, $referenceCode, $family, $master = null, $genCode = null, $saleUnit = null)
	{
		$as = synchro_ArticleService::getInstance();
		$article = $as->getByCodeReference($referenceCode);
		if ( is_null($article) )
		{
			$article = $as->getNewDocumentInstance();
			$article->setCodeReference($referenceCode);
		}
		$article->setLabel($label);
		$article->setFamily($family);
		$article->setGenCode($genCode);
		$article->setSaleUnit($saleUnit);
		$article->setMasterCode($master);
		$article->save();
		return $article;
	}

	/**
	 * @param String $code
	 * @return synchro_persistentdocument_article
	 */
	public function getArticleByCode($code)
	{
		return synchro_ArticleService::getInstance()->getByCodeReference($code);
	}

	/**
	 * @param synchro_persistentdocument_article $article
	 * @param catalog_persistentdocument_zone $zone
	 * @param String $value
	 * @param Integer $taxCode
	 * @param Boolean $erp return an erp price ?
	 * @return catalog_persistentdocument_price
	 */
	public function addArticlePrice($article, $zone, $value, $taxCode = 1, $erp = true)
	{
		$ps = $erp ? catalog_PriceerpService::getInstance() : catalog_PriceService::getInstance();
		$price = $ps->getNewDocumentInstance();
		$price->setValue($value);
		$price->setTaxCode($taxCode);
		$price->setArticle($article);
		$price->setZone($zone);
		$price->save();
		return $price;
	}

	/**
	 * @param synchro_persistentdocument_article $article
	 * @param Double $value
	 * @param Integer $taxCode
	 * @param Boolean $erp
	 * @param zone_persistentdocument_zone $zone
	 * @return void
	 */
	public function setArticlePrice($article, $value, $taxCode = 1, $erp = true, $zone = null, $customer = null)
	{
		$price = catalog_PriceService::getInstance()->createQuery()
			->add(Restrictions::eq('article.id', $article->getId()))
			->add(Restrictions::eq('model', $erp ? 'modules_catalog/priceerp' : 'modules_catalog/price'))
			->findUnique();
		if ( is_null($price) )
		{
			$ps = $erp ? catalog_PriceerpService::getInstance() : catalog_PriceService::getInstance();
			$price = $ps->getNewDocumentInstance();
			$price->setArticle($article);
			$price->setZone($zone ? $zone : $this->zone);
		}
		$price->setTaxCode($taxCode);
		$price->setValue($value);
		$price->setCustomer($customer);
		$price->save();
	}

	/**
	 * @param synchro_persistentdocument_article $article
	 * @param Double $value
	 * @param Integer $taxCode
	 * @param zone_persistentdocument_zone $zone
	 * @return void
	 */
	public function setArticleErpPrice($article, $value, $taxCode = 1, $zone = null, $customer = null)
	{
		$this->setArticlePrice($article, $value, $taxCode, true, $zone, $customer);
	}

	/**
	 * @param synchro_persistentdocument_article $article
	 * @param catalog_persistentdocument_zone $zone
	 * @param String $value
	 * @param Integer $taxCode
	 * @param Boolean $erp return an erp price ?
	 * @param String $type one of the catalog_DiscountHelper type constants.
	 * @return catalog_persistentdocument_pricediscount
	 */
	public function addArticlePricediscount($article, $zone, $value, $taxCode = 1, $erp = true, $type = catalog_DiscountHelper::TYPE_PRICE)
	{
		$ps = $erp ? catalog_PricediscounterpService::getInstance() : catalog_PricediscountService::getInstance();
		$price = $ps->getNewDocumentInstance();
		$price->setValue($value);
		$price->setDiscountType($type);
		$price->setTaxCode($taxCode);
		$price->setArticle($article);
		$price->setZone($zone);
		$price->save();
		return $price;
	}

	/**
	 * @param String $label
	 * @param String $value
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param String $type one of the catalog_DiscountHelper type constants.
	 * @return catalog_persistentdocument_shelfdiscount
	 */
	public function createShelfDiscount($label, $value, $shelf, $type = catalog_DiscountHelper::TYPE_PERCENTAGE)
	{
		$sds = catalog_ShelfdiscountService::getInstance();
		$shelfdiscount = $sds->getNewDocumentInstance();

		$shelfdiscount->setLabel($label);
		$shelfdiscount->setDiscountType($type);
		$shelfdiscount->setValue($value);
		$shelfdiscount->save($shelf->getTopic()->getId());

		return $shelfdiscount;
	}

	/**
	 * @param synchro_persistentdocument_article $article
	 * @param Double $quantity
	 * @deprecated
	 */
	public function setArticleStock($article, $quantity)
	{
		//catalog_StockService::getInstance()->setArticleStock($article, $quantity);
	}

	/**
	 * @param String $label
	 * @return brand_persistentdocument_brand
	 */
	public function createBrand($label)
	{
		// TODO : check for existing brand before creating new one.
		$bs = brand_BrandService::getInstance();
  		$brand = $bs->getNewDocumentInstance();
  		$brand->setLabel($label);
  		$brand->save();
  		return $brand;
	}

	/**
	 * @param f_persistentdocument_PersistentDocument $root catalog or shelf
	 * @param String $path
	 * @param String $separator
	 * @return catalog_persistentdocument_shelf
	 */
	public function mkShelf($root, $path, $separator = '/')
	{
		if (!($root instanceof catalog_persistentdocument_catalog) && !($root instanceof catalog_persistentdocument_shelf))
		{
			throw new catalog_Exception('Root must be a shelf or a catalog.');
		}

		$pathElements = explode($separator, $path);
		$parent = $root->getTopic();
		foreach ($pathElements as $element)
		{
			$query = f_persistentdocument_PersistentProvider::getInstance()->createQuery('modules_website/topic');
			$query->add(Restrictions::like('label', $element, MatchMode::EXACT()))
				  ->add(Restrictions::childOf($parent->getId()));
			$document = $query->findUnique();

			// If the document does not exist, create it.
			if (is_null($document))
			{
				$shelf = $this->createShelf($element, $parent->getId());
				$document = $shelf->getTopic();
			}

			$parent = $document;
		}

		return catalog_ShelfService::getInstance()->getByTopic($parent);
	}

	/**
	 * @param Array<Array<String,String>> $path
	 * @param String $module
	 * @return catalog_persistentdocument_shelf
	 */
	public function mkArticleFamily($path, $module = 'catalog')
	{
		$firstLevel = true;
		foreach ($path as $pathElement)
		{
			switch (count($pathElement) )
			{
				case 2 :
					$code = $pathElement[0];
					$label = $pathElement[1];
					break;
				case 1 :
					$code = $pathElement[0];
					$label = null;
					break;
				default :
					throw new Exception("Bad article family path.");
			}

			if ($firstLevel)
			{
				$parent = DocumentHelper::getDocumentInstance(ModuleService::getInstance()->getRootFolderId($module));
				$firstLevel = false;
				$query = f_persistentdocument_PersistentProvider::getInstance()->createQuery('modules_synchro/articlefamily')
					->add(Restrictions::ilike('code', $code, MatchMode::EXACT()))
					->add(Restrictions::childOf($parent->getId()));
				$document = $query->findUnique();
				if (is_null($document))
				{
					$ts = TreeService::getInstance();
					$document = $this->createArticlefamily($label, $code);
					f_persistentdocument_PersistentTreeNode::addNewChild(
						$ts->getInstanceByDocument($parent),
						$document
						);
				}
			}
			else
			{
				foreach ($parent->getSubFamilyArray() as $subFamily)
				{
					if ($subFamily->getCode() == $code)
					{
						$document = $subFamily;
					}
				}
				if ( is_null($document) )
				{
					$document = $this->createArticlefamily($label, $code);
					$parent->addSubFamily($document);
					$parent->save();
				}
			}

			$parent = $document;
			$document = null;
		}

		return $parent;
	}

	/**
	 * @param String $path
	 * @return synchro_persistentdocument_articlefamily
	 */
	public function getArticleFamily($path, $module = 'catalog')
	{
		$firstLevel = true;
		$pathElements = explode('/', $path);
		foreach ($pathElements as $element)
		{
			if ($element)
			{
				if ($firstLevel)
				{
					$parent = DocumentHelper::getDocumentInstance(ModuleService::getInstance()->getRootFolderId($module));
					$firstLevel = false;
					$query = f_persistentdocument_PersistentProvider::getInstance()->createQuery('modules_synchro/articlefamily')
						->add(Restrictions::like('code', $element, MatchMode::EXACT()))
						->add(Restrictions::childOf($parent->getId()));
					$document = $query->findUnique();
					if ( is_null($document) )
					{
						throw new Exception("Article family \"$path\" does not exist.");
					}
				}
				else
				{
					foreach ($parent->getSubFamilyArray() as $subFamily)
					{
						if ($subFamily->getCode() == $element)
						{
							$document = $subFamily;
						}
					}
					if ( is_null($document) )
					{
						throw new Exception("Article family \"$path\" does not exist.");
					}
				}
			}

			$parent = $document;
			$document = null;
		}

		return $parent;
	}

	/**
	 * Returns a published product built with articles.
	 *
	 * @param String $label
	 * @param String $referenceCode
	 * @param catalog_persistentdocument_shelf $shelf
	 * @param Array<synchro_persistentdocument_article> $articleArray
	 *
	 * @return catalog_persistentdocument_product
	 */
	public function createProductFromArticles($label, $referenceCode, $shelf, $articleArray)
	{
		$ps = catalog_ProductService::getInstance();
		$product = $ps->getNewDocumentInstance();
		$product->setLabel($label);
		foreach ($articleArray as $article)
		{
			$product->addArticle($article);
		}
		$product->save($shelf->getTopic()->getId());
		return $product;
	}

	/**
	 * Applies the prices of the given reference article to selected articles.
	 *
	 * @param synchro_persistentdocument_article $referenceArticle
	 * @param Array<synchro_persistentdocument_article> $articleArray
	 *
	 * @return void
	 */
	public function applyArticlePricesOnArticles($referenceArticle, $articleArray)
	{
		$prices = catalog_PriceService::getInstance()->getByArticle($referenceArticle);
		foreach ($articleArray as $article)
		{
			foreach ($prices as $price)
			{
				$newPrice = $price->getDocumentService()->getNewDocumentInstance();
				// TODO : find a better method.
				try
				{
					DocumentHelper::setPropertiesTo(DocumentHelper::getPropertiesOf($price), $newPrice);
					$newPrice->setZone($price->getZone()); // Document properties are not copied...
					$newPrice->setCustomer($price->getCustomer()); // Document properties are not copied...
					$newPrice->setArticle($article);
					$newPrice->save();
				}
				catch (Exception $e)
				{
					Framework::warn("Could not create new price on article ".$article->getId());
					Framework::exception($e);
				}
			}
		}
	}

	// --- Private stuff ---


	/**
	 * @param String $label
	 * @param String $code
	 * @return zone_persistentdocument_zone
	 */
	private function getZone($label, $code)
	{
		$zs = zone_ZoneService::getInstance();
		$zone = $zs->getByCode($code);
		if (is_null($zone))
		{
			$zone = $zs->getNewDocumentInstance();
			$zone->setLabel($label);
			$zone->setCode($code);
			$zone->save(ModuleService::getInstance()->getRootFolderId('zone'));
		}
		return $zone;
	}

	/**
	 * @param String $label
	 * @param String $code
	 * @return synchro_persistentdocument_articlefamily
	 */
	private function createArticleFamily($label, $code)
	{
		$afs = synchro_ArticlefamilyService::getInstance();
		$family = $afs->getNewDocumentInstance();
		$family->setLabel($label);
		$family->setCode($code);
		$family->save();
		return $family;
	}

	/**
	 * @param String $label
	 * @param Integer $parentId the parent must be a topic.
	 * @return catalog_persistentdocument_shelf
	 */
	private function createShelf($label, $parentId)
	{
		$shelf = catalog_ShelfService::getInstance()->getNewDocumentInstance();
		$shelf->setLabel($label);
		$shelf->save($parentId);
		return $shelf;
	}

	/**
	 */
	protected function __construct()
	{
		$this->zone = $this->getZone('France', 'FR');
	}

}
<?php
/**
 * synchro_ArticleService
 * @package modules.synchro
 */
class synchro_ArticleService extends f_persistentdocument_DocumentService
{
	/**
	 * Singleton
	 * @var synchro_ArticleService
	 */
	private static $instance = null;

	/**
	 * @return synchro_ArticleService
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
	 * Clear the service instance.
	 * Used for tests.
	 * @test
	 * @throws synchro_NotInTestException
	 */
	public static function clearInstance()
	{
		if (PROFILE != 'test')
		{
			throw new synchro_NotInTestException('This method is only usable in test mode.');
		}
		self::$instance = null;
	}

	/**
	 * @return synchro_persistentdocument_article
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_synchro/article');
	}

	/**
	 * Create a query based on 'modules_synchro/article' model and 'articleType' = 'Article'.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		// Filter the billing and shipping modes with a restiction on the 'articleType' field.
		return $this->pp->createQuery('modules_synchro/article')->add(Restrictions::eq('articleType', 'Article'));
	}

	/**
	 * Returns the stock availability of the given $article.
	 *
	 * @param synchro_persistentdocument_article $article
	 * @return Boolean
	 */
	public function isAvailable($article, $quantity = 1)
	{
		$stock = f_util_ArrayUtils::firstElement($article->getStockArrayInverse());
		if ( is_null($stock) )
		{
			return true;
		}
		return $stock->isAvailable($quantity);
	}

	/**
	 * Returns the literal path of the article in its parent families.
	 *
	 * @example "Top level family/Second level/Articles"
	 * @param synchro_persistentdocument_article $article
	 * @param String $pathSeparator
	 * @return String
	 */
	public function getArticleFamilyPath($article, $pathSeparator = '/')
	{
		$path = array();
		$family = $article->getFamily();
		if ( ! is_null($family) )
		{
			$path[] = $family->getLabel();
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
	 * @param synchro_persistentdocument_article $document
	 * @param String $oldPublicationStatus
	 * @return void
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		if (	$document->isPublished()
				// status transit from ACTIVE to PUBLICATED
			|| $oldPublicationStatus == 'PUBLICATED'
				// status transit from PUBLICATED to ACTIVE
			)
		{
			$this->publishIfPossibleProducts($document);
		}
	}

	/**
	 * Publish if possible the products associated to an article
	 *
	 * @param synchro_persistentdocument_article $article
	 * @return void
	 */
	protected function publishIfPossibleProducts($article)
	{
		$ps = catalog_ProductService::getInstance();
		$products = $ps->getByArticle($article);
		foreach ($products as $product)
		{
			$ps->publishIfPossible($product->getId());
		}
	}

	/**
	 * Check if there is a published price for the given article and zone.
	 * The price can be customer-specific or not.
	 *
	 * @param synchro_persistentdocument_article $article
	 * @param zone_persistentdocument_zone $zone
	 * @return true
	 */
	public function hasPriceForZone($article, $zone)
	{
		$query = catalog_PriceService::getInstance()->createQuery();
		$query->add(Restrictions::published());
		$query->add(Restrictions::eq('article', $article->getId()));
		$query->add(Restrictions::eq('zone', $zone->getId()));
		$query->setProjection(Projections::rowCount('count'));
		$result = $query->findUnique();
		return($result['count'] > 0);
	}

	/**
	 * @param catalog_persistentdocument_product $product
	 * @param synchro_persistentdocument_article $article
	 * @return catalog_ArticleInfo
	 */
	public function getArticleInfo($product, $article)
	{
		$articleInfo = new catalog_ArticleInfo();
		$articleInfo->setId($article->getId());
		$articleInfo->setLabel($article->getLabel());
		$articleInfo->setReference($article->getCodeReference());
/*
		$ps = catalog_PriceCalculatorService::getInstance();
		try
		{
			$priceInfo = $ps->getPriceInfo($product, $article);
		}
		catch (catalog_Exception $e)
		{
			$priceInfo = null;
		}
		$articleInfo->setPriceInfo($priceInfo);

		$articleInfo->setPriceQuantityInfoArray($ps->getPriceQuantityInfoArray($product, $article));

		$articleInfo->setDisplayQuantityPrices($this->displayQuantityPrices($articleInfo));
		
		// Stock handling.
		$stock = f_util_ArrayUtils::firstElement($article->getStockArrayInverse());
		if (!is_null($stock))
		{
			$stockService = $stock->getDocumentService();
			$articleInfo->setStockLevel($stock->getLevel());
			$articleInfo->setDisplayableQuantity($stockService->getDisplayableQuantity($stock));
		}
		else
		{
			$articleInfo->setStockLevel(null);
			$articleInfo->setDisplayableQuantity(null);
		}
		
		// Availability.
		$articleInfo->setIsAvailable($article->getIsAvailable());	
		
		// Limited sale handling.
		if (!is_null($stock) && $stock->getIsLimitedSale())
		{
			$articleInfo->setIsLimitedSale($stock->getIsLimitedSale());
			$articleInfo->setLimitedSaleInitialQuantity($stock->getLimitedSaleInitialQuantity());
			$articleInfo->setDisplayableLimitedSaleInitialQuantity($stockService->getDisplayableLimitedSaleInitialQuantity($stock));
		}
		else
		{
			$articleInfo->setIsLimitedSale(false);
			$articleInfo->setLimitedSaleInitialQuantity(null);
			$articleInfo->setDisplayableLimitedSaleInitialQuantity(null);
		}
		
		$this->setAdditionalAttributes($articleInfo, $product, $article);

		if (!is_null($article->getVisual()))
		{
			$articleInfo->setVisualId($article->getVisual()->getId());
		}
		else
		{
			$articleInfo->setVisualId(null);
		}
		*/
		return $articleInfo;
	}

	/**
	 * @param catalog_ArticleInfo $articleInfo
	 * @return Boolean
	 */
	private function displayQuantityPrices($articleInfo)
	{
		// If there is no quantity-based prices, there is nothing to display.
		if (is_null($articleInfo->getPriceQuantityInfoArray()) || f_util_ArrayUtils::isEmpty($articleInfo->getPriceQuantityInfoArray()))
		{
			return false;
		}
		// If the price is not a discount one, display quantity-based prices.
		else if (is_null($articleInfo->getPriceInfo()) || !$articleInfo->getPriceInfo()->getIsDiscount())
		{
			return true;
		}
		else
		{
			// If there is at least one discount quantity-based price, display quantity-based prices.
			foreach ($articleInfo->getPriceQuantityInfoArray() as $priceInfo)
			{
				if ($priceInfo->getIsDiscount())
				{
					return true;
				}
			}
			return false;
		}
	}
	
	/**
	 * @param String $code
	 * @return synchro_persistentdocument_article
	 */
	public function getByCodeReference($code)
	{
		return $this->createQuery()->add(Restrictions::eq('codeReference', $code))->findUnique();
	}

	/**
	 * @param catalog_ArticleInfo $articleInfo
	 * @param catalog_persistentdocument_product $product
	 * @param synchro_persistentdocument_article $article
	 */
	protected function setAdditionalAttributes($articleInfo, $product, $article)
	{
		// Nothing to do in generic version... This method has to be overloaded in specific implmentation.
	}

	/**
	 * The key is formated like that : '<articleId>_<zoneId>_<customerId>' or '<articleId>_<zoneId>'.
	 * @var Array<String, Array<String, Array<catalog_persistentdocument_price>>>
	 */
	private $pricesByArticleAndZone = array();

	/**
	 * @var Boolean
	 */
	private $isPricesByZoneCacheActivated = false;

	/**
	 * The result array is indexed by the prices models and contains arrays of published prices.
	 * @param synchro_persistentdocument_article $article
	 * @param catalog_persistentdocument_zone $zone
	 * @param customer_persistentdocument_customer $customer
	 * @return Array<String, Array<catalog_persistentdocument_price>>
	 */
	public function getPricesByZone($article, $zone, $customer = null)
	{
		// Check parameters.
		if (is_null($article))
		{
			throw new catalog_Exception(__METHOD__ . ' called with a NULL article');
		}
		if (is_null($zone))
		{
			throw new catalog_Exception(__METHOD__ . ' called with a NULL zone');
		}

		// Get prices.
		if (is_null($customer))
		{
			return $this->getGlobalPricesByZone($article, $zone);
		}
		else
		{
			return $this->getCustomerPricesByZone($article, $zone, $customer);
		}
	}

	/**
	 * The result array is indexed by the prices models.
	 * @param synchro_persistentdocument_article $article
	 * @param catalog_persistentdocument_zone $zone
	 * @return Array<String, Array<catalog_persistentdocument_price>>
	 */
	private function getGlobalPricesByZone($article, $zone)
	{
		if ($this->isPricesByZoneCacheActivated)
		{
			$pricesByModel = $this->getPricesByZoneInCache($article, $zone);
		}
		if (!isset($pricesByModel) || !is_array($pricesByModel))
		{
			$prices = $this->getPricesByArticleAndZoneAndCustomer($article, $zone);
			$pricesByModel = $this->sortPricesByModel($prices);
		}
		if ($this->isPricesByZoneCacheActivated)
		{
			$this->setPricesByZoneInCache($pricesByModel, $article, $zone);
		}
		return $pricesByModel;
	}

	/**
	 * The result array is indexed by the prices models.
	 * @param synchro_persistentdocument_article $article
	 * @param catalog_persistentdocument_zone $zone
	 * @param customer_persistentdocument_customer $customer
	 * @return Array<String, Array<catalog_persistentdocument_price>>
	 */
	private function getCustomerPricesByZone($article, $zone, $customer)
	{
		if ($this->isPricesByZoneCacheActivated)
		{
			$pricesByModel = $this->getPricesByZoneInCache($article, $zone, $customer);
		}
		if (!isset($pricesByModel) || !is_array($pricesByModel))
		{
			// Get customer specific prices.
			$prices = $this->getPricesByArticleAndZoneAndCustomer($article, $zone, $customer);
			$pricesByModel = $this->sortPricesByModel($prices);

			// Merge global prices.
			$globalPricesByModel = $this->getGlobalPricesByZone($article, $zone);
			foreach ($globalPricesByModel as $model => $prices)
			{
				if (!isset($pricesByModel[$model]))
				{
					$pricesByModel[$model] = $prices;
				}
			}
		}
		if ($this->isPricesByZoneCacheActivated)
		{
			$this->setPricesByZoneInCache($pricesByModel, $article, $zone, $customer);
		}
		return $pricesByModel;
	}

	/**
	 * Queries the published prices associated to the given documents.
	 * @param synchro_persistentdocument_article $article
	 * @param catalog_persistentdocument_zone $zone
	 * @param customer_persistentdocument_customer $customer
	 * @return Array<catalog_persistentdocument_price>
	 */
	private function getPricesByArticleAndZoneAndCustomer($article, $zone, $customer = null)
	{
		$query = catalog_PriceService::getInstance()->createQuery();
		$query->add(Restrictions::published());
		$query->add(Restrictions::eq('article', $article->getId()));
		$query->add(Restrictions::eq('zone', $zone->getId()));
		if (!is_null($customer))
		{
			$query->add(Restrictions::eq('customer', $customer->getId()));
		}
		else
		{
			$query->add(Restrictions::isEmpty('customer'));
		}
		return $query->find();
	}

	/**
	 * @param Array<catalog_persistentdocument_price> $prices
	 * @return Array<String, Array<catalog_persistentdocument_price>>
	 * @todo In Framework 2.0.3 there are some new methods to get injected model name, use it when possible - intessit 2008-05-28
	 */
	private function sortPricesByModel($prices)
	{
		$pricesByModel = array();
		foreach ($prices as $price)
		{
			$injectedModel = $price->getPersistentModel()->getSourceInjectionModel();
			if ($injectedModel !== null)
			{
				$modelName = $injectedModel->getName();
			}
			else
			{
				$modelName = $price->getDocumentModelName();
			}
			$pricesByModel[$modelName][] = $price;
		}
		return $pricesByModel;
	}

	/**
	 * The result array is indexed by the prices models.
	 * @param synchro_persistentdocument_article $article
	 * @param catalog_persistentdocument_zone $zone
	 * @param customer_persistentdocument_customer $customer
	 * @return Array<String, Array<catalog_persistentdocument_price>>
	 */
	private function getPricesByZoneInCache($article, $zone, $customer = null)
	{
		$key = $this->getPricesByZoneCacheKey($article, $zone, $customer);
		if (isset($this->pricesByArticleAndZone[$key]))
		{
			return $this->pricesByArticleAndZone[$key];
		}
		return null;
	}

	/**
	 * The result array is indexed by the prices models.
	 * @param Array<String, Array<catalog_persistentdocument_price>> $prices
	 * @param synchro_persistentdocument_article $article
	 * @param catalog_persistentdocument_zone $zone
	 * @param customer_persistentdocument_customer $customer
	 */
	private function setPricesByZoneInCache($prices, $article, $zone, $customer = null)
	{
		$key = $this->getPricesByZoneCacheKey($article, $zone, $customer);
		$this->pricesByArticleAndZone[$key] = $prices;
	}

	/**
	 * @param synchro_persistentdocument_article $article
	 * @param catalog_persistentdocument_zone $zone
	 * @param customer_persistentdocument_customer $customer
	 * @return String
	 */
	private function getPricesByZoneCacheKey($article, $zone, $customer = null)
	{
		$key = $article->getId() . '_' . $zone->getId();
		if (!is_null($customer))
		{
			$key .= '_' . $customer->getId();
		}
		return $key;
	}

	/**
	 * Enable the pricesByArticleAndZone cache.
	 * If the cache was already enabled, it has no effect
	 */
	public function enablePricesByZoneCache()
	{
		$this->isPricesByZoneCacheActivated = true;
	}

	/**
	 * Disable the pricesByArticleAndZone cache.
	 */
	public function disablePricesByZoneCache()
	{
		$this->pricesByArticleAndZone = array();
		$this->isPricesByZoneCacheActivated = false;
	}

	/**
	 * @param synchro_persistentdocument_article $document
	 * @return Array<String=>String>
	 */
	public function getPreviewAttributes($document)
	{
		$data = array();
		$data['location'] = $document->getFamilyPath(' / ');
		if ( ! is_null($visual = $document->getVisual()) )
		{
			$data['thumbnail'] = MediaHelper::getContent($visual, MediaHelper::PREVIEW_FORMAT, K::XUL);
		}
		return $data;
	}

	/**
	 * @param synchro_persistentdocument_article
	 * @return String
	 */
	public function formatLabelForResourceTree($article)
	{
		try
		{
			$format = Framework::getConfiguration('modules/catalog/resourceTreeLabelFormatForArticle');
		}
		catch (ConfigurationException $e)
		{
			$format = '$codeReference';
		}
		if (preg_match_all('/\$([\w]+)/', $format, $matches))
		{
			$count = count($matches[0]);
			for ($m = 0 ; $m < $count ; $m++)
			{
				$methodName = 'get' . ucfirst($matches[1][$m]);
				if (f_util_ClassUtils::methodExists($article, $methodName))
				{
					$format = str_replace($matches[0][$m], f_util_ClassUtils::callMethodOn($article, $methodName), $format);
				}
			}
			return $format;
		}
		return $document->getLabel();
	}

	/**
	 * @param synchro_persistentdocument_article $document
	 * @return void
	 */
	protected function preDelete($document)
	{
		foreach ($document->getPriceArrayInverse() as $price)
		{
			$price->delete();
		}
	}

	/**
	 * @param synchro_persistentdocument_article $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal).
	 * @return void
	 */
	protected function preUpdate($document, $parentNodeId)
	{
		// If it is a new traduction, refresh the isAvalable field.
		if ($document->isNewTraduction())
		{
			$this->refreshIsAvailableField($document);
		}
	}	
	
	/**
	 * @param synchro_persistentdocument_article $article
	 */
	public function refreshIsAvailableField($article)
	{
		$stock = f_util_ArrayUtils::firstElement($article->getStockArrayInverse());
		if (is_null($stock))
		{
			$article->setIsAvailable(true);
		}
		else 
		{
			$article->setIsAvailable($stock->getDocumentService()->isAvailableByLevel($stock->getLevel()));
		}
	}
	
	/**
	 * @param synchro_persistentdocument_article $article
	 * @return Boolean
	 */
	public function isOrderable($article)
	{
		return $this->isOrderableByAvailability($article->getIsAvailable());
	}
	
	/**
	 * @param Boolean $availability
	 * @return Boolean
	 * @deprecated
	 */
	public function isOrderableByAvailability($availability)
	{
		return $availability;
	}
	
	/**
	 * @param Integer $articleId
	 * @param Integer $shopId
	 * @return catalog_persistentdocument_product[]
	 */
	public function getProductsByArticleIdAndShop($articleId, $shop)
	{
		$query = catalog_ProductService::getInstance()->createQuery()->add(Restrictions::eq('articleId', $articleId));
		$criteria1 = $query->createCriteria('shelf');
		$criteria2 = $criteria1->createCriteria('topic')->add(Restrictions::descendentOf($shop->getTopic()->getId()));
		return $query->find();
	}
}
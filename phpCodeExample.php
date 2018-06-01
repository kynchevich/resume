<?php
namespace Ittown\Core\Ajax;
use Bitrix\Main\Application;
use Ittown\Core\Contracts\AAjaxHandler;
use Bitrix\Sale;
use Bitrix\Main\Localization\Loc;
\CModule::IncludeModule("sale");
\CModule::IncludeModule("catalog");
\CModule::IncludeModule("currency");


class BasketSale extends AAjaxHandler
{
	private $basket;
	private $xmlId;
	
	public function __construct(){
		parent::__construct();
		$this->basket = $this->getBasket();
		$this->request->id = (int)$this->getRequest()->id;
		$this->xmlId = $this->getXmlId();
		$this->request->quantity = ((int)$this->getRequest()->quantity)?(int)$this->getRequest()->quantity:1;
	}

	private function getXmlId()
	{
		$xmlId=\Bitrix\Iblock\ElementTable::getList(array(
			'select'  =>array('XML_ID'),
			'filter'  =>array('ID'=>$this->request->id),
		))->fetch();
		
		if($xmlId['XML_ID']) 
			return $xmlId['XML_ID'];
		else 
			return $this->request->id;
	}


	private function getBasket()
	{
		
		$basket = Sale\Basket::loadItemsForFUser(
		   Sale\Fuser::getId(),
		   \Bitrix\Main\Context::getCurrent()->getSite()
		);
		return $basket;
	}


	private function getBasketItem()
	{

		$item=$this->basket->getExistsItem(
			"catalog", 
			$this->request->id,
			array( 
				array("CODE" => "PRODUCT.XML_ID","VALUE" => $this->xmlId,)
			)
		);

		return $item;
	}

	private function createBasketItem()
	{

		$item = $this->basket->createItem('catalog', $this->request->id);
		$item->setFields(array(
		    'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
		    'LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
		    'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
		));

		$item->getPropertyCollection()->setProperty(array(
			array(
			   'CODE' => 'PRODUCT.XML_ID',
			   'VALUE' => $this->xmlId,
			),
		));

		return $item;
	}

	public function addQuantity()
	{

		$item=$this->getBasketItem();
		if(!$item)
			$item=$this->createBasketItem();

		$item->setField('QUANTITY', $item->getQuantity()+$this->request->quantity);

		// Проверка создался ли item в корзине
		if($item->getId())
			$this->responseSuccess(Loc::getMessage('success_add_basket'));
		else
			$this->responseError(Loc::getMessage('error_add_basket'));

		$this->sucsessBasket();
	}

	public function setQuantity()
	{

		$item=$this->getBasketItem();
		if(!$item)
			$item=$this->createBasketItem();

		$item->setField('QUANTITY', $this->request->quantity);

		if($item->getId())
			$this->responseSuccess('Quantity set');
		else
			$this->responseError(Loc::getMessage('error_add_basket'));
		$this->sucsessBasket();
	}

	public function deleteItem()
	{

		$item=$this->getBasketItem();
		if($item)
			$item->delete();
		$this->responseSuccess(Loc::getMessage('success_delite_product'));
		$this->sucsessBasket();
	}

	public function clearBasket()
	{
		$basketItems = $this->basket->getBasketItems();
		foreach ($basketItems as $item) {
			$item->delete();
		}
		$this->responseSuccess(Loc::getMessage('success_clear_basket'));
		$this->sucsessBasket();
	}
	// Получа текущюю корзину и возвращаем информацию о товарах 
	public function sucsessBasket()
	{
		$this->basket->save();
		$basketItems = $this->basket->getBasketItems();

		foreach ($basketItems as $item) {
			$basketData['PRODUCTS'][$item->getProductId()]['id']=$item->getProductId();
			$basketData['PRODUCTS'][$item->getProductId()]['qnt']=$item->getQuantity();
			$basketData['PRODUCTS'][$item->getProductId()]['canBuy']=$item->canBuy();
		}

		$basketData['totalQuantity']=array_sum($this->basket->getQuantityList());

		$this->responsePushData($basketData);
	}

	// Интерфейс класса AAjaxHandler должен быть реализоан
	public function ajaxStart()
	{
		$this->responseSuccess('Show basket');
	}
}
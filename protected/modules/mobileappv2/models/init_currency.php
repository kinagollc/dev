<?php
$mc_currency = isset($_GET['mc_currency'])?$_GET['mc_currency']:'';
if( Mobile_utility::fileExist("components/Item_utility.php") && Mobile_utility::fileExist("components/Price_Formatter.php") ){			
	Mobile_utility::$price_formater = true;
    Mobile_utility::InitMultiCurrency($mc_currency);
    Yii::app()->session['exchange_rate'] = Mobile_utility::$exchange_rates;
    Yii::app()->session['currency'] = $mc_currency;
} 
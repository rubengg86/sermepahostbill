<?php
//error_reporting(E_ALL);
/*
 * Sermepa HostBill gateway module
 * 
 * 
 * Under GPLv3 License - Ruben Garcia Garcia - 2013
 * 
 * see http://dev.hostbillapp.com/dev-kit/payment-gateway-modules/
 *
 */

class goguspay extends PaymentModule {

    /**
     * @var string Default module name to be displayed in adminarea
     */
    protected $modname = 'Gogus - Sermepa Gateway';


    /**
     * @var string Default module name to be displayed in adminarea
     */
    protected $description = 'Sermepa gateway module for HostBill';


    /**
     * List of currencies supported by gateway - if module supports all currencies - leave empty
     * @var array
     */
    protected $supportedCurrencies = array('USD', 'EUR', 'GBP');

    
    /**
     * Configuration array - types allowed: check, input, select
     */
    protected $configuration = array(
     
    );

    /**
     * Return HTML code that should be displayed in clientarea for client to pay (ie. in invoice details)
     * @return string
     */
    public function drawForm() {
    	
		$amount_spanish=number_format($this->amount, 2, '.', '')*100;
		$order=''.date('ymdHis');
		/* 
		 * Data sent by your bank
		 */
		//Commerce code
		$fuc="";
		//Currency code - EUR 978
		$moneda="978";
		//Transfer type - 0 = Authorization
		$transtype="0";
		// URL where sermepa will post the ok or ko data
		$urlnotif=$this->callback_url."&invoice_id=".$this->invoice_id;
		//Commerce secret key
		$clave="";
		//Sermepa url
		$url_serpema="";
		//Url when user will return when transaction was ok
		$return_url_ok='';
		//Url when user will return when transaction was KO
		$return_url_ko='';

        $string = "</form><form action='".$url_serpema."' method='POST' target='tpv' name='servired_form'>";
            //draw submit button
       $string.="<input name='submitsermepa' id='submitsermepa' type='submit' value='Pagar ahora' />";

            //draw hidden fields with payment details
            
        $string.="<input type='hidden' name='invoice_id' value='{$this->invoice_id}'/>";
        $string.="<input type='hidden' name='currency_code' value='" . $this->getCurrency() . "'/>";
		$string.="<input type='hidden' name='amount' value='$amount_spanish'/><br>";
		$string.="<input type='hidden' name='txn_id ' value='".$order."'/><br>";
		$string.="<input type='hidden' name='amount' value='fee'/><br>";
           
            //draw hidden fields with client details
        $string.="<input type='hidden' name='firstname' value='{$this->client['firstname']}'/>";
        $string.="<input type='hidden' name='lastname' value='{$this->client['lastname']}'/>";
        $string.="<input type='hidden' name='country' value='{$this->client['country']}'/>";
        $string.="<input type='hidden' name='address' value='{$this->client['address1']}'/>";
		
			//Generated hash
           	$message=$amount_spanish.$order.$fuc.$moneda.$transtype.$urlnotif.$clave;
		 	$signature = strtoupper(sha1($message)); 
		    $string.='<input type="hidden" name="Ds_Merchant_Amount" value="'.$amount_spanish.'" />';
            $string.='<input type="hidden" name="Ds_Merchant_Currency" value="'.$moneda.'" />';
            $string.='<input type="hidden" name="Ds_Merchant_Order" value="'.$order.'" />';
            $string.='<input type="hidden" name="Ds_Merchant_MerchantCode" value="'.$fuc.'" />';
            $string.='<input type="hidden" name="Ds_Merchant_Terminal" value="001" />';
          	$string.=' <input type="hidden" name="Ds_Merchant_TransactionType" value="'.$transtype.'" />';
            $string.='<input type="hidden" name="Ds_Merchant_Titular" value="Titular" />';
            $string.=' <input type="hidden" name="Ds_Merchant_MerchantName" value="MERCHAN_NAME_HERE" />';
            $string.='<input type="hidden" name="Ds_Merchant_MerchantURL" value="'.$urlnotif.'" />';
            $string.='<input type="hidden" name="Ds_Merchant_ProductDescription" value="'.$this->invoice_id.'" />';
            $string.='<input type="hidden" name="Ds_Merchant_ConsumerLanguage " value="001" />';
            $string.=' <input type="hidden" name="Ds_Merchant_UrlOK" value="'.$return_url_ok.'" />';
            $string.='<input type="hidden" name="Ds_Merchant_UrlKO" value="'.$return_url_ko.'" />';
            $string.='<input type="hidden" name="Ds_Merchant_PayMethods" value="T" />';
            $string.='<input type="hidden" name="Ds_Merchant_MerchantSignature" value="'.$signature.'" />  ';     
        	$string.="</form>";

        return $string;
    }

    //data coming in from payment gateway
    function callback() {
    	
		//$message=$_POST['Ds_Amount'].$_POST['Ds_Order'].$_POST['Ds_MerchantCode'].$_POST['Ds_Currency'].'0'.$this->callback_url."&invoice_id=".$this->invoice_id.$clave;
		//$signature_local = strtoupper(sha1($message));
		//TODO Create a double hash check in return post
		if($_POST['Ds_AuthorisationCode']){
			$verified = true;
		}else{
			$verified=false;
		}
        
        //1. verify data
        
        if ($verified) {
            //2. log incoming payment
            $this->logActivity(array(
                'result' => 'Successfull',
                'output' => $_POST
            ));

            //3. add transaction to invoice
            $invoice_id = $_GET['invoice_id'];
            $amount = $_POST['Ds_Amount']/100;
            $fee = $_POST['fee'];
            $transaction_id = $_POST['Ds_Order'];
            
            $this->addTransaction(array(
                'in' => $amount,
                'invoice_id' => $invoice_id,
                'fee' => $fee,
                'transaction_id' => $transaction_id
            ));
        } else {
             $this->logActivity(array(
                'result' => 'Failed',
                'output' => $_POST
            ));
        }
    }

}

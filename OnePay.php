<?php 

class OnePay{

	protected $gateway;
	public function __construct($gateway) {
		$this->gateway = $gateway;
	}

	public function getUrlPayment($amount){
		$params = array(
			'vpc_Version' => '2',
			'vpc_Command' => 'pay',
			'vpc_Locale' => 'en',
			'vpc_ReturnURL' => 'http://localhost/resultPayment.php',
			'vpc_MerchTxnRef' => date ( 'YmdHis' ).rand(),
			'vpc_OrderInfo' => date ( 'YmdHis' ),
			'vpc_Amount' => $amount.'00',
			'AgainLink' => 'http://localhost/resultPayment.php',
			'Title' => 'VPC 3-Party',
			);

		$gateway = $_GET['gateway'];

		if($this->gateway === 'Inland')
		{

			$url='https://mtf.onepay.vn/onecomm-pay/vpc.op?';
			$SECURE_SECRET = "A3EFDFABA8653DF2342E8DAC29B51AF0";
			$params['vpc_AccessCode'] = 'D67342C2';
			$params['vpc_Merchant'] = 'ONEPAY';
			$params['vpc_Currency'] = 'VND';

		}else
		{

			$url='https://mtf.onepay.vn/vpcpay/vpcpay.op?';
			$SECURE_SECRET = "6D0870CDE5F24F34F3915FB0045120DB";
			$params['vpc_AccessCode'] = '6BEB2546';
			$params['vpc_Merchant'] = 'TESTONEPAY';
		}

		$md5HashData = "";
		ksort ($params);

		foreach ($params as $key => $value) {

			$url.= urlencode($key).'='.urlencode($value).'&';

			if ((strlen($value) > 0) && ((substr($key, 0,4)=="vpc_") || (substr($key,0,5) =="user_"))) {
				$md5HashData .= $key . "=" . $value . "&";
			}
		}

		$md5HashData = rtrim($md5HashData, "&");

		$url .= "vpc_SecureHash=" . strtoupper(hash_hmac('SHA256', $md5HashData, pack('H*',$SECURE_SECRET)));
		return $url;
	}

	public function getResponsePayment($vpc_MerchTxnRef){
		$params = array(
			'vpc_Version' => '1',
			'vpc_Command' => 'queryDR',
			'vpc_MerchTxnRef' => $vpc_MerchTxnRef,
			'vpc_User' => 'op01',
			'vpc_Password' => 'op123456',
			);

		if($this->gateway === 'Inland'){
			$vpcURL = 'https://mtf.onepay.vn/onecomm-pay/Vpcdps.op';
			$params['vpc_AccessCode'] = 'D67342C2';
			$params['vpc_Merchant'] = 'ONEPAY';
		}else
		{
			$vpcURL = 'https://mtf.onepay.vn/vpcpay/Vpcdps.op';
			$params['vpc_AccessCode'] = '6BEB2546';
			$params['vpc_Merchant'] = 'TESTONEPAY';
		}

		$postData = "";
		$ampersand = "";

		foreach ($params as $key => $value) {
			if (strlen($value) > 0) {
				$postData .= $ampersand . urlencode($key) . '=' . urlencode($value);
				$ampersand = "&";
			}
		}

		ob_start();

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $vpcURL);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);


		curl_exec($ch);

		$response = ob_get_contents();


		ob_end_clean();

		$message = "";

		if (strchr($response, "<html>") || strchr($response, "<html>")) {
			$message = $response;
		} else {

			if (curl_error($ch))
				$message = "%s: s" . curl_errno($ch) . "<br/>" . curl_error($ch);
		}


		curl_close($ch);

		$map = array();

		if (strlen($message) == 0) {
			$pairArray = split("&", $response);
			foreach ($pairArray as $pair) {
				$param = split("=", $pair);
				$map[urldecode($param[0])] = urldecode($param[1]);
			}
			$message = $this->null2unknown($map, "vpc_Message");
		}

		$amount = $this->null2unknown($map, "vpc_Amount");
		$locale = $this->null2unknown($map, "vpc_Locale");
		$batchNo = $this->null2unknown($map, "vpc_BatchNo");
		$command = $this->null2unknown($map, "vpc_Command");
		$version = $this->null2unknown($map, "vpc_Version");
		$cardType = $this->null2unknown($map, "vpc_Card");
		$orderInfo = $this->null2unknown($map, "vpc_OrderInfo");
		$receiptNo = $this->null2unknown($map, "vpc_ReceiptNo");
		$merchantID = $this->null2unknown($map, "vpc_Merchant");
		$authorizeID = $this->null2unknown($map, "vpc_AuthorizeId");
		$transactionNo = $this->null2unknown($map, "vpc_TransactionNo");
		$acqResponseCode = $this->null2unknown($map, "vpc_AcqResponseCode");
		$txnResponseCode = $this->null2unknown($map, "vpc_TxnResponseCode");

		$drExists = $this->null2unknown($map, "vpc_DRExists");
		$multipleDRs = $this->null2unknown($map, "vpc_FoundMultipleDRs");

		$verType = $this->null2unknown($map, "vpc_VerType");
		$verStatus = $this->null2unknown($map, "vpc_VerStatus");
		$token = $this->null2unknown($map, "vpc_VerToken");
		$verSecurLevel = $this->null2unknown($map, "vpc_VerSecurityLevel");
		$enrolled = $this->null2unknown($map, "vpc_3DSenrolled");
		$xid = $this->null2unknown($map, "vpc_3DSXID");
		$acqECI = $this->null2unknown($map, "vpc_3DSECI");
		$authStatus = $this->null2unknown($map, "vpc_3DSstatus");

		$shopTransNo = $this->null2unknown($map, "vpc_ShopTransactionNo");
		$authorisedAmount = $this->null2unknown($map, "vpc_AuthorisedAmount");
		$capturedAmount = $this->null2unknown($map, "vpc_CapturedAmount");
		$refundedAmount = $this->null2unknown($map, "vpc_RefundedAmount");
		$ticketNumber = $this->null2unknown($map, "vpc_TicketNo");

		$errorTxt = "";
		if ($txnResponseCode == "7" || $txnResponseCode == "No Value Returned") {
			$errorTxt = "Error";
		}

		$transStatus = "";
		if ($txnResponseCode == "0") {
			$transStatus = "Giao dịch thành công";
		} elseif ($txnResponseCode != "0") {
			$transStatus = "Giao dịch thất bại";
		}


		$result = array(
			'OnePay'	=> array(
				'errorTxt'          => $errorTxt,
				'resCode'			=> $txnResponseCode,
				'resDescription'	=> $this->getResponseDescription($txnResponseCode)	
				)
			);

		return json_encode($result);
	}

	private function getResponseDescription($responseCode)
	{
		switch ($responseCode) {
			case "0" :
			$result = "Transaction Successful";
			break;
			case "?" :
			$result = "Transaction status is unknown";
			break;
			case "1" :
			$result = "Bank system reject";
			break;
			case "2" :
			$result = "Bank Declined Transaction";
			break;
			case "3" :
			$result = "No Reply from Bank";
			break;
			case "4" :
			$result = "Expired Card";
			break;
			case "5" :
			$result = "Insufficient funds";
			break;
			case "6" :
			$result = "Error Communicating with Bank";
			break;
			case "7" :
			$result = "Payment Server System Error";
			break;
			case "8" :
			$result = "Transaction Type Not Supported";
			break;
			case "9" :
			$result = "Bank declined transaction (Do not contact Bank)";
			break;
			case "A" :
			$result = "Transaction Aborted";
			break;
			case "C" :
			$result = "Transaction Cancelled";
			break;
			case "D" :
			$result = "Deferred transaction has been received and is awaiting processing";
			break;
			case "F" :
			$result = "3D Secure Authentication failed";
			break;
			case "I" :
			$result = "Card Security Code verification failed";
			break;
			case "L" :
			$result = "Shopping Transaction Locked (Please try the transaction again later)";
			break;
			case "N" :
			$result = "Cardholder is not enrolled in Authentication scheme";
			break;
			case "P" :
			$result = "Transaction has been received by the Payment Adaptor and is being processed";
			break;
			case "R" :
			$result = "Transaction was not processed - Reached limit of retry attempts allowed";
			break;
			case "S" :
			$result = "Duplicate SessionID (OrderInfo)";
			break;
			case "T" :
			$result = "Address Verification Failed";
			break;
			case "U" :
			$result = "Card Security Code Failed";
			break;
			case "V" :
			$result = "Address Verification and Card Security Code Failed";
			break;
			default  :
			$result = "Unable to be determined";
		}
		return $result;
	}

	private function null2unknown($map, $key)
	{
		if (array_key_exists($key, $map)) {
			if (!is_null($map[$key])) {
				return $map[$key];
			}
		}
		return "No Value Returned";
	}

}

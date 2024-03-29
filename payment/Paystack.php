<?php 

    class PaystackPayment {

        var $secretKey;

        function __construct($secretKey){
            $this->secretKey = $secretKey;
        }

        function generateReference (int $length = 8) {
            $id = "";
            $prefix = str_split("ABCDEFGH");
            $id .= $prefix[rand(0, count($prefix)-1)];
            $id .= $prefix[rand(0, count($prefix)-1)];

            for ($i = 0; $i < $length; $i++) { 
                $id .= rand(0, 9);
            }

            return $id;
        }

        function initializePayment ($email, $amount, $callback_url = "") {
            $url = "https://api.paystack.co/transaction/initialize";
            $fields = [
                'email' => filter_var($email, FILTER_SANITIZE_EMAIL),
                'amount' => intval($amount) * 100,
                'callback_url' => $callback_url,
                'reference' => $this->generateReference()
            ];
            $fields_string = http_build_query($fields);

            //open connection
            $ch = curl_init();

            //set the url, number of POST vars, POST data
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_POST, true);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer {$this->secretKey}",
                "Cache-Control: no-cache",
            ));

            //So that curl_exec returns the contents of the cURL; rather than echoing it
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 

            
            //execute post
            $result = curl_exec($ch);
            return  json_decode($result, true);
        }

        function verifyTransaction($ref)
        {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.paystack.co/transaction/verify/{$ref}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Authorization: Bearer {$this->secretKey}",
                    "Cache-Control: no-cache",
                ),
            ));
            
            $response = json_decode(curl_exec($curl), true);
            $err = curl_error($curl);
            curl_close($curl);

            
            if ($err) {
                return [
                    'error' => $err
                ];
            } else {
                return $response;
            }
        }
    }
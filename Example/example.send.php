<?php

/**
 * This file is part of simple-web3-php package.
 * 
 * (c) Alex Cabrera  
 * 
 * @author Alex Cabrera
 * @license MIT 
 */
namespace SWeb3;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

include_once("../vendor/autoload.php");
include_once("example.config.php");
 
use stdClass; 
use SWeb3\SWeb3;
use SWeb3\Utils;
use SWeb3\SWeb3_Contract;
use phpseclib\Math\BigInteger as BigNumber;


//IMPORTANT
//Remember that this is an example showing how to execute the common features of sending signed transactions through the ethereum rpc api
//This code does not represent a clean / efficient / performant aproach to implement them in a production environment


$extra_curl_params = [];
//INFURA ONLY: Prepare extra curl params, to add infura private key to the request
$extra_curl_params[CURLOPT_USERPWD] = ':'.INFURA_PROJECT_SECRET;

//initialize SWeb3 main object
$sweb3 = new SWeb3(ETHEREUM_NET_ENDPOINT, $extra_curl_params);
//send chain id, important for transaction signing 0x1 = main net, 0x3 ropsten... full list = https://chainlist.org/
$sweb3->chainId = '0x3';//ropsten
   

//refresh gas price 
//if you don't provide explicit gas price, the system will update current gas price from the net (call)
$gasPrice = $sweb3->refreshGasPrice();

//GENERAL OPERATIONS
//uncomment all functions you want to execute. mind that every call will make a state changing transaction to the selected net.

SendETH();

//CONTRACT
//uncomment all functions you want to execute. mind that every call will make a state changing transaction to the selected net.

//initialize contract from address and ABI string
$contract = new SWeb3_contract($sweb3, SWP_Contract_Address, SWP_Contract_ABI);
//Contract_Set_public_uint();
//Contract_AddTupleA();
//Contract_AddTupleA_Params();
//AddTuple_B();


exit(0);



function SendETH()
{
    global $sweb3;
    //send 0.001 eth to 0x3Fc47d792BD1B0f423B0e850F4E2AD172d408447

    //estimate gas cost
    $sendParams = [ 
        'from' => SWP_ADDRESS,
        'to' => '0x3Fc47d792BD1B0f423B0e850F4E2AD172d408447', 
        'gasPrice' => $sweb3->gasPrice,
        'value' => $sweb3->utils->toWei('0.001', 'ether')
    ]; 

    //get function estimateGas
    $gasEstimateResult = $sweb3->call('eth_estimateGas', [$sendParams]);
    $gasEstimate = $sweb3->utils->hexToDec($gasEstimateResult->result);
 
    //prepare sending
    $sendParams['nonce'] = $sweb3->getNonce(SWP_ADDRESS); 
    $sendParams['gasLimit'] = $gasEstimate;

    $result = $sweb3->send($sendParams); 
    PrintCallResult('SendETH', $result);
}


function Contract_Set_public_uint()
{
    global $sweb3, $contract;
 
    //nonce depends on the sender/signing address. it's the number of transactions made by this address, and can be used to override older transactions
    //it's used as a counter/queue
    //get nonce gives you the "desired next number" (makes a query to the provider), but you can setup more complex & efficient nonce handling ... at your own risk ;)
    $extra_data = ['nonce' => $sweb3->getNonce(SWP_ADDRESS)];

    //$contract->send always populates: gasPrice, gasLimit, IF AND ONLY IF they are not already defined in $extra_data 
    //$contract->send always populates: to (contract address), data (ABI encoded $sendData), these can NOT be defined from outside
    $result = $contract->send('Set_public_uint', time(),  $extra_data);
    
    PrintCallResult('Contract_Set_public_uint: ' . time(), $result);
}


function Contract_AddTupleA()
{
    global $sweb3, $contract;

    $send_data = new stdClass();
    $send_data->uint_a = time();
    $send_data->boolean_a = true;
    $send_data->address_a = SWP_ADDRESS;
    $send_data->bytes_a = 'Dynamic inserted tuple with SWP with tuple'; 

    $extra_data = ['nonce' => $sweb3->getNonce(SWP_ADDRESS)];
    $result = $contract->send('AddTupleA', $send_data,  $extra_data);
     
    PrintCallResult('Contract_AddTupleA: ' . time(), $result);
}

function Contract_AddTupleA_Params()
{
    global $sweb3, $contract;

    $send_data = [];
    $send_data['uint_a'] = time();
    $send_data['boolean_a'] = true;
    $send_data['address_a'] = SWP_ADDRESS;
    $send_data['bytes_a'] = 'Dynamic inserted tuple with SWP by params'; 

    $extra_data = ['nonce' => $sweb3->getNonce(SWP_ADDRESS)];
    $result = $contract->send('AddTupleA_Params', $send_data,  $extra_data);
     
    PrintCallResult('Contract_AddTupleA_Params: ' . time(), $result);
}


function AddTuple_B()
{
    global $sweb3, $contract;

    $send_data = new stdClass();
    $send_data->uint_b = time();
    $send_data->string_b = 'Dynamic inserted tuple with SWP with tuple'; 
    $send_data->string_array_b = ['Dynamic', 'inserted', 'tuple', 'with', 'SWP', 'with', 'tuple']; 

    $extra_data = ['nonce' => $sweb3->getNonce(SWP_ADDRESS)];
    $result = $contract->send('AddTuple_B', $send_data,  $extra_data);
     
    PrintCallResult('AddTuple_B: ' . time(), $result);
}
  


function PrintCallResult($callName, $result)
{
    echo "<br/> Call -> <b>". $callName . "</b><br/>";
    echo "Result -> ". json_encode($result) . "<br/>";
}
<?php

include_once("../payment/Paystack.php");
include_once("../db/config.php");

$secret = "sk_test_5957cfbc50bd3ef35da73787e5be405ff09c46d0";
$payment = new PaystackPayment($secret);

if (isset($_POST['pay'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $amount = filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_INT);

    $callbackUrl = "http://localhost/payment/handler/payment_handler.php";

    $result = $payment->initializePayment($email, $amount, $callbackUrl);

    print_r($result);
    $reference_id = $result['data']['reference'];
    $url = $result['data']['authorization_url'];

    $query = "INSERT INTO payment_table (reference_id, amount, status) VALUES (:ref, :amount, :status)";
    $result = $connect->prepare($query);
    $result->execute([
        'ref' => $reference_id,
        'amount' => $amount,
        'status' => "pending"
    ]);

    if ($result) {
        header("location: {$url}");
    }
}


if (isset($_GET['reference'])) {
    $ref = $_GET['reference'];

    $data = $payment->verifyTransaction($ref);

    if ($data['data']['status'] === 'success') {
        $query = "UPDATE payment_table SET status = 'success' WHERE reference_id = ?";
        $result = $connect->prepare($query);
        $result->execute([$ref]);

        if ($result) {
            header('location: ../index.php?alert=payment_success');
        }
    } else {
        $query = "UPDATE payment_table SET status = 'failed' WHERE reference_id = ?";
        $result = $connect->prepare($query);
        $result->execute([$ref]);

        if ($result) {
            header('location: ../index.php?alert=payment_failed');
        }
    }
}

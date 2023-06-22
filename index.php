<?php
    require_once 'AzureCustomMailer.php';

    $endPoint = 'https://<my-resource>.communication.azure.com';
    $from = '<sender@example.com>';
    $recipient = '<recipient@example.com>';
    $recipientName = '<Recipient Name>';
    $subject = 'Test AzureCustomMailer';
    $body = 'email-templates/email.html';
    $attachment = 'attachments/blue-black-muscle-car-with-license-plate-that-says-trans-front.jpg';

    $azureCustomMailer = AzureCustomMailer::create($endPoint, AZURE_EMAIL_ACCESS_KEY);

    $azureCustomMailer->azureSetFrom($from);
    $azureCustomMailer->azureAddAddress($recipient, $recipientName);
    $azureCustomMailer->azureSubject = $subject;
    $azureCustomMailer->azureIsHTML(true);
    $azureCustomMailer->azureBody = file_get_contents($body);

    if($azureCustomMailer->azureSend())
        echo "Email sent successfully, operation Id is: " . $azureCustomMailer->getOperationId() . "<br>";
    else
        var_dump($azureCustomMailer->azureGetError());


    // $azureCustomMailer = new AzureCustomMailer();
    // $azureCustomMailer->azureEndpoint = $endPoint;
    // $azureCustomMailer->azureAccessKey = AZURE_EMAIL_ACCESS_KEY;

    // $status = $azureCustomMailer->azureGetSendStatus('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
    // var_dump($status);

?>
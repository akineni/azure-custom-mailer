<h2>AzureCustomMailer</h2>

This PHP class, **AzureCustomMailer**, provides functionality to send emails using Azure services. It allows you to set the sender, recipients, subject, body, attachments, and custom headers of the email. The class handles the authentication and HTTP request to the Azure email service.

To use this class, you can follow these steps:

 1. Create an instance of the AzureCustomMailer class using the create()
    method, passing the Azure endpoint and access key as arguments.
    
    $mailer = AzureCustomMailer::create($$azureEndpoint, $azureAccessKey);

 2. Set the email properties such as sender, recipients, subject, body,
    attachments, and custom headers using the provided methods like
    **azureSetFrom()**, **azureAddAddress()**, **azureAddAttachment()**, etc.

 3. Optionally, you can set the email content to be HTML or plain text using the **azureIsHTML()** method.

 4. Call the **azureSend()** method to send the email. It will return true if the email was sent successfully, or false otherwise.

 5. If the email was sent successfully, you can retrieve the operation ID of the email using the **azureGetOperationId()** method.

 6. If there was an error during the email sending process, you can retrieve the error information using the **azureGetError()** method.

 7. Remember to destroy the AzureCustomMailer instance by calling the **__destruct()** method or letting it go out of scope.

**Note:** This code is just the definition of the **AzureCustomMailer** class. You need to instantiate and use it in your own code to send emails using Azure.